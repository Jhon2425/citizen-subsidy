<?php

namespace App\Http\Controllers;

// ASSUMPTION: change this to your real applications model.
// It needs `status` (pending / approved / rejected), `created_at`, and the
// columns/relations used in rows() below.
use App\Models\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'rejected'];

    private const GREEN      = '166534';
    private const GREEN_SOFT = 'DCFCE7';
    private const GRAY_SOFT  = 'F3F4F6';
    private const BORDER     = 'D1D5DB';

    private const STATUS_STYLE = [
        'pending'  => ['fill' => 'FEF9C3', 'font' => '854D0E'],
        'approved' => ['fill' => 'DCFCE7', 'font' => '166534'],
        'rejected' => ['fill' => 'FEE2E2', 'font' => '991B1B'],
    ];

    /* ------------------------------------------------------------------ */
    /*  Actions                                                            */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $year    = $this->year($request);
        $monthly = $this->monthly($year);

        return view('reports.index', [
            'year'    => $year,
            'years'   => $this->years(),
            'counts'  => $this->counts($monthly),
            'monthly' => $monthly,
        ]);
    }

    /**
     * Streams a formatted .xlsx (Summary, Monthly and Applications sheets).
     * Needs:  composer require phpoffice/phpspreadsheet
     * Until that package is installed, it falls back to a CSV so the button never 500s.
     */
    public function export(Request $request): StreamedResponse
    {
        $year    = $this->year($request);
        $monthly = $this->monthly($year);
        $counts  = $this->counts($monthly);
        $rows    = $this->rows($year);

        if (! class_exists(Spreadsheet::class)) {
            return $this->exportCsv($year, $counts, $monthly, $rows);
        }

        $book = $this->buildWorkbook($year, $counts, $monthly, $rows);

        return response()->streamDownload(function () use ($book) {
            IOFactory::createWriter($book, 'Xlsx')->save('php://output');
            $book->disconnectWorksheets();
        }, "applications-report-{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Fallback: UTF-8 CSV (with BOM) that Excel opens directly. */
    private function exportCsv(int $year, array $counts, Collection $monthly, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($year, $counts, $monthly, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ["Applications Report - {$year}"]);
            fputcsv($out, []);
            fputcsv($out, ['Status', 'Headcount']);
            foreach (self::STATUSES as $status) {
                fputcsv($out, [ucfirst($status), $counts[$status]]);
            }
            fputcsv($out, ['Total', $counts['total']]);

            fputcsv($out, []);
            fputcsv($out, ['Month', 'Pending', 'Approved', 'Rejected', 'Total added']);
            foreach ($monthly as $m) {
                fputcsv($out, [$m['label'], $m['pending'], $m['approved'], $m['rejected'], $m['total']]);
            }

            fputcsv($out, []);
            fputcsv($out, ['No.', 'Full Name', 'Barangay', 'Subsidy', 'Status', 'Date Added']);
            foreach ($rows as $i => $row) {
                fputcsv($out, [$i + 1, $row['name'], $row['barangay'], $row['subsidy'], ucfirst($row['status']), $row['date']->format('M d, Y')]);
            }

            fclose($out);
        }, "applications-report-{$year}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ------------------------------------------------------------------ */
    /*  Data                                                               */
    /* ------------------------------------------------------------------ */

    /** Always 12 rows (Jan–Dec) so empty months still show as zero. */
    private function monthly(int $year): Collection
    {
        $applications = Application::query()
            ->whereYear('created_at', $year)
            ->get(['status', 'created_at']);

        return collect(range(1, 12))->map(function (int $month) use ($applications, $year) {
            $inMonth = $applications->filter(fn ($a) => $a->created_at->month === $month);

            $row = ['label' => Carbon::create($year, $month, 1)->format('M Y')];
            foreach (self::STATUSES as $status) {
                $row[$status] = $inMonth->where('status', $status)->count();
            }
            $row['total'] = $inMonth->count();

            return $row;
        });
    }

    /** @return array{total:int,pending:int,approved:int,rejected:int} */
    private function counts(Collection $monthly): array
    {
        $counts = ['total' => (int) $monthly->sum('total')];
        foreach (self::STATUSES as $status) {
            $counts[$status] = (int) $monthly->sum($status);
        }

        return $counts;
    }

    /**
     * One row per application for the "Applications" sheet.
     *
     * Schema-tolerant: it looks for a relation to the applicant / subsidy under
     * common names, and falls back to plain columns on `applications`.
     * If a column still shows "—", add your real names to the lists below.
     */
    private const APPLICANT_RELATIONS = ['senior', 'seniorCitizen', 'applicant', 'beneficiary', 'citizen', 'user'];
    private const SUBSIDY_RELATIONS   = ['subsidy', 'subsidyProgram', 'program', 'subsidyType'];
    private const NAME_FIELDS         = ['full_name', 'name'];
    private const BARANGAY_FIELDS     = ['barangay', 'barangay_name'];
    private const SUBSIDY_FIELDS      = ['subsidy', 'subsidy_name', 'subsidy_type', 'program', 'name', 'title'];

    private function rows(int $year): Collection
    {
        $probe     = new Application();
        $applicant = $this->firstRelation($probe, self::APPLICANT_RELATIONS);
        $subsidy   = $this->firstRelation($probe, self::SUBSIDY_RELATIONS);

        $query = Application::query()->whereYear('created_at', $year)->orderBy('created_at');
        if ($with = array_filter([$applicant, $subsidy])) {
            $query->with($with);
        }

        return $query->get()->map(function ($a) use ($applicant, $subsidy) {
            $person = $applicant ? $a->getRelation($applicant) : null;

            return [
                'name'     => $this->personName($person) ?: $this->personName($a) ?: '—',
                'barangay' => $this->pick($person, self::BARANGAY_FIELDS) ?: $this->pick($a, self::BARANGAY_FIELDS) ?: '—',
                'subsidy'  => $this->pick($subsidy ? $a->getRelation($subsidy) : null, self::SUBSIDY_FIELDS)
                              ?: $this->pick($a, array_slice(self::SUBSIDY_FIELDS, 0, 4)) ?: '—',
                'status'   => $a->status,
                'date'     => $a->created_at,
            ];
        })->values();
    }

    /** First candidate that is a real Eloquent relationship on the model. */
    private function firstRelation(Model $model, array $candidates): ?string
    {
        foreach ($candidates as $name) {
            if (method_exists($model, $name) && $model->{$name}() instanceof Relation) {
                return $name;
            }
        }

        return null;
    }

    /** First non-empty value among the given attributes (handles a related model, e.g. barangay). */
    private function pick(?Model $model, array $fields): string
    {
        if (! $model) {
            return '';
        }

        foreach ($fields as $field) {
            $value = $model->getAttribute($field);
            if ($value instanceof Model) {
                $value = $value->getAttribute('name');
            }
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function personName(?Model $model): string
    {
        if (! $model) {
            return '';
        }

        if ($name = $this->pick($model, self::NAME_FIELDS)) {
            return $name;
        }

        return trim(implode(' ', array_filter([
            $model->getAttribute('first_name'),
            $model->getAttribute('middle_name'),
            $model->getAttribute('last_name'),
        ])));
    }

    /** Years from the first application up to the current year, newest first. */
    private function years(): Collection
    {
        $first = Application::query()->oldest()->value('created_at');
        $start = $first ? Carbon::parse($first)->year : now()->year;

        return collect(range(now()->year, min($start, now()->year)));
    }

    private function year(Request $request): int
    {
        $year = (int) $request->input('year', now()->year);

        return ($year >= 2000 && $year <= now()->year) ? $year : now()->year;
    }

    /* ------------------------------------------------------------------ */
    /*  Workbook                                                           */
    /* ------------------------------------------------------------------ */

    private function buildWorkbook(int $year, array $counts, Collection $monthly, Collection $rows): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setTitle("Applications Report {$year}")
            ->setCreator(config('app.name', 'Senior Citizen Subsidy Management'));

        $this->summarySheet($book->getActiveSheet(), $year, $counts);
        $this->monthlySheet($book->createSheet(), $year, $monthly);
        $this->applicationsSheet($book->createSheet(), $year, $rows);

        $book->setActiveSheetIndex(0);

        return $book;
    }

    /** Sheet 1 — headcount by status. */
    private function summarySheet(Worksheet $s, int $year, array $counts): void
    {
        $s->setTitle('Summary');
        $this->banner($s, 'A', 'C', "Applications Report — {$year}");

        $s->fromArray(['Status', 'Headcount', 'Share'], null, 'A5');
        $this->headerRow($s, 'A5:C5');

        $r = 6;
        foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label) {
            $s->setCellValue("A{$r}", $label);
            $s->setCellValue("B{$r}", $counts[$key]);
            $s->setCellValue("C{$r}", "=IF(\$B\$9=0,0,B{$r}/\$B\$9)");

            $style = self::STATUS_STYLE[$key];
            $s->getStyle("A{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $style['font']]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $style['fill']]],
            ]);
            $r++;
        }

        $s->setCellValue('A9', 'Total');
        $s->setCellValue('B9', '=SUM(B6:B8)');
        $s->setCellValue('C9', '=SUM(C6:C8)');
        $this->totalRow($s, 'A9:C9');
        $this->box($s, 'A5:C9');
        $s->getStyle('B6:B9')->getNumberFormat()->setFormatCode('#,##0');
        $s->getStyle('C6:C9')->getNumberFormat()->setFormatCode('0%');
        $s->getStyle('B6:C9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $s->getColumnDimension('A')->setWidth(24);
        $s->getColumnDimension('B')->setWidth(16);
        $s->getColumnDimension('C')->setWidth(16);

        $this->printSetup($s, PageSetup::ORIENTATION_PORTRAIT);
    }

    /** Sheet 2 — applications added per month (Jan–Dec). */
    private function monthlySheet(Worksheet $s, int $year, Collection $monthly): void
    {
        $s->setTitle('Monthly');
        $this->banner($s, 'A', 'E', "Applications Added per Month — {$year}");

        $s->fromArray(['Month', 'Pending', 'Approved', 'Rejected', 'Total added'], null, 'A5');
        $this->headerRow($s, 'A5:E5');

        $r = 6;
        foreach ($monthly as $row) {
            $s->setCellValue("A{$r}", $row['label']);
            $s->setCellValue("B{$r}", $row['pending']);
            $s->setCellValue("C{$r}", $row['approved']);
            $s->setCellValue("D{$r}", $row['rejected']);
            $s->setCellValue("E{$r}", "=SUM(B{$r}:D{$r})");

            if ($r % 2 === 1) { // zebra striping
                $s->getStyle("A{$r}:E{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::GRAY_SOFT);
            }
            $r++;
        }

        $last  = $r - 1;   // last month row (17)
        $total = $r;       // total row (18)

        $s->setCellValue("A{$total}", "Total {$year}");
        foreach (['B', 'C', 'D', 'E'] as $col) {
            $s->setCellValue("{$col}{$total}", "=SUM({$col}6:{$col}{$last})");
        }
        $this->totalRow($s, "A{$total}:E{$total}");
        $this->box($s, "A5:E{$total}");
        $s->getStyle("B6:E{$total}")->getNumberFormat()->setFormatCode('#,##0');
        $s->getStyle("B6:E{$total}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("A6:A{$last}")->getFont()->setBold(true);

        $s->freezePane('A6');

        $s->getColumnDimension('A')->setWidth(18);
        foreach (['B', 'C', 'D', 'E'] as $col) {
            $s->getColumnDimension($col)->setWidth(15);
        }

        $this->printSetup($s, PageSetup::ORIENTATION_PORTRAIT);
    }

    /** Sheet 3 — every application with the applicant's name, status and date. */
    private function applicationsSheet(Worksheet $s, int $year, Collection $rows): void
    {
        $s->setTitle('Applications');
        $this->banner($s, 'A', 'F', "Applications List — {$year}");

        $s->fromArray(['No.', 'Full Name', 'Barangay', 'Subsidy', 'Status', 'Date Added'], null, 'A5');
        $this->headerRow($s, 'A5:F5');

        $r = 6;
        foreach ($rows as $i => $row) {
            $s->setCellValue("A{$r}", $i + 1);
            $s->setCellValue("B{$r}", $row['name']);
            $s->setCellValue("C{$r}", $row['barangay']);
            $s->setCellValue("D{$r}", $row['subsidy']);
            $s->setCellValue("E{$r}", ucfirst($row['status']));
            $s->setCellValue("F{$r}", Date::PHPToExcel($row['date']));

            if ($style = self::STATUS_STYLE[$row['status']] ?? null) {
                $s->getStyle("E{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $style['font']]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $style['fill']]],
                ]);
            }
            $r++;
        }

        $last = max($r - 1, 5);

        if ($rows->isEmpty()) {
            $s->mergeCells('A6:F6');
            $s->setCellValue('A6', 'No applications for this year.');
            $s->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $s->getStyle('A6')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');
            $last = 6;
        }

        $this->box($s, "A5:F{$last}");
        $s->getStyle("A6:A{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("E6:F{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("F6:F{$last}")->getNumberFormat()->setFormatCode('mmm dd, yyyy');

        // Excel filter dropdowns + frozen header so the list stays readable.
        $s->setAutoFilter("A5:F{$last}");
        $s->freezePane('A6');

        foreach (['A' => 7, 'B' => 36, 'C' => 22, 'D' => 24, 'E' => 14, 'F' => 16] as $col => $width) {
            $s->getColumnDimension($col)->setWidth($width);
        }

        $s->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(5, 5);
        $this->printSetup($s, PageSetup::ORIENTATION_LANDSCAPE);
    }

    /* ------------------------------------------------------------------ */
    /*  Styling helpers                                                    */
    /* ------------------------------------------------------------------ */

    /** Green title block used at the top of each sheet (rows 1-3). */
    private function banner(Worksheet $s, string $from, string $to, string $title): void
    {
        $s->mergeCells("{$from}1:{$to}1");
        $s->setCellValue("{$from}1", 'Senior Citizen Subsidy Management');
        $s->mergeCells("{$from}2:{$to}2");
        $s->setCellValue("{$from}2", $title);
        $s->mergeCells("{$from}3:{$to}3");
        $s->setCellValue("{$from}3", 'Generated ' . now()->format('F d, Y h:i A'));

        $s->getStyle("{$from}1:{$to}3")->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN]],
            'font'      => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $s->getStyle("{$from}1")->getFont()->setBold(true)->setSize(16);
        $s->getStyle("{$from}2")->getFont()->setBold(true)->setSize(12);
        $s->getStyle("{$from}3")->getFont()->setSize(9)->getColor()->setRGB('BBF7D0');

        $s->getRowDimension(1)->setRowHeight(28);
        $s->getRowDimension(2)->setRowHeight(20);
    }

    private function headerRow(Worksheet $s, string $range): void
    {
        $s->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '15803D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $s->getRowDimension((int) preg_replace('/\D/', '', explode(':', $range)[0]))->setRowHeight(22);
    }

    private function totalRow(Worksheet $s, string $range): void
    {
        $s->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN_SOFT]],
        ]);
    }

    private function box(Worksheet $s, string $range): void
    {
        $s->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER);
        $s->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function printSetup(Worksheet $s, string $orientation): void
    {
        $s->getPageSetup()
            ->setOrientation($orientation)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $s->setShowGridlines(false);
    }
}