<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /* =========================================================
     * Uses your real tables:
     *   - applications / subsidy_applications  -> application counts + chart
     *   - subsidy_releases (+ subsidies)       -> money distributed + charts
     * Columns are auto-detected. Force one by setting it below.
     * ========================================================= */

    private ?string $amountColumn  = null; // column on subsidy_releases (or subsidies) with the money
    private ?string $releasedDate  = null; // date column on subsidy_releases
    private ?string $programName   = null; // name column on subsidies

    /** Release rows with these statuses are NOT counted as distributed. */
    private array $excludedReleaseStatuses = ['cancelled', 'canceled', 'failed', 'rejected', 'pending', 'draft'];

    private array $amountCandidates = ['amount', 'subsidy_amount', 'release_amount', 'amount_released', 'total_amount', 'monthly_amount', 'benefit_amount'];
    private array $dateCandidates   = ['released_at', 'release_date', 'date_released', 'distribution_date', 'distributed_at', 'paid_at', 'created_at'];
    private array $nameCandidates   = ['name', 'title', 'subsidy_name', 'program_name', 'type'];

    private function detect(?string $table, ?string $forced, array $candidates): ?string
    {
        if ($forced !== null) {
            return $forced;
        }
        if (! $table || ! Schema::hasTable($table)) {
            return null;
        }
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    /** First existing table that has rows (or the first existing one). */
    private function pickTable(array $candidates): ?string
    {
        $fallback = null;
        foreach ($candidates as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $fallback ??= $table;
            if (DB::table($table)->exists()) {
                return $table;
            }
        }
        return $fallback;
    }

    private function monthExpr(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', {$column}) AS INTEGER)",
            'pgsql'  => "EXTRACT(MONTH FROM {$column})",
            default  => "MONTH({$column})",
        };
    }

    public function index()
    {
        /* ---------------- SENIOR CITIZENS ---------------- */
        $totalSeniorCitizens   = DB::table('senior_citizens')->count();
        $approvedBeneficiaries = $totalSeniorCitizens;

        $activeBeneficiaries = Schema::hasColumn('senior_citizens', 'is_active')
            ? DB::table('senior_citizens')
                ->where(fn ($q) => $q->where('is_active', 1)->orWhereNull('is_active'))
                ->count()
            : $totalSeniorCitizens;

        /* ---------------- APPLICATIONS ---------------- */
        $apps = $this->pickTable(['subsidy_applications', 'applications']);

        $countStatus = function (array $statuses) use ($apps) {
            if (! $apps || ! Schema::hasColumn($apps, 'status')) {
                return 0;
            }
            return DB::table($apps)->whereIn('status', $statuses)->count();
        };

        $pendingApplications      = $countStatus(['pending']);
        $verificationApplications = $countStatus(['for_verification', 'verification', 'under_review']);
        $approvedApplications     = $countStatus(['approved']);
        $rejectedApplications     = $countStatus(['rejected', 'denied']);

        $monthlyApplications = collect();
        if ($apps && Schema::hasColumn($apps, 'created_at')) {
            $monthlyApplications = DB::table($apps)
                ->selectRaw($this->monthExpr("$apps.created_at") . ' as month, COUNT(*) as total')
                ->whereYear("$apps.created_at", now()->year)
                ->groupBy('month')
                ->get();
        }

        /* ---------------- SUBSIDY RELEASES (money out) ---------------- */
        $rel = Schema::hasTable('subsidy_releases') ? 'subsidy_releases' : null;

        $totalSubsidyDistributed = 0.0;
        $thisMonthSubsidy        = 0.0;
        $thisMonthReleases       = 0;
        $monthlySubsidies        = collect();
        $subsidyBreakdown        = collect();
        $debug                   = ['applications_table' => $apps, 'releases_table' => $rel];

        if ($rel) {
            $hasSubsidies = Schema::hasTable('subsidies') && Schema::hasColumn($rel, 'subsidy_id');

            // Money column: on releases first, otherwise on the subsidies program table.
            $amountExpr = null;
            $relAmount = $this->detect($rel, $this->amountColumn, $this->amountCandidates);
            if ($relAmount) {
                $amountExpr = "$rel.$relAmount";
            } elseif ($hasSubsidies) {
                $subAmount = $this->detect('subsidies', $this->amountColumn, $this->amountCandidates);
                if ($subAmount) {
                    $amountExpr = "subsidies.$subAmount";
                }
            }

            $dateCol  = $this->detect($rel, $this->releasedDate, $this->dateCandidates);
            $nameCol  = $hasSubsidies ? $this->detect('subsidies', $this->programName, $this->nameCandidates) : null;

            $base = function () use ($rel, $hasSubsidies) {
                $q = DB::table($rel);
                if ($hasSubsidies) {
                    $q->leftJoin('subsidies', 'subsidies.id', '=', "$rel.subsidy_id");
                }
                if (Schema::hasColumn($rel, 'status')) {
                    $q->where(function ($w) use ($rel) {
                        $w->whereNull("$rel.status")->orWhereNotIn("$rel.status", $this->excludedReleaseStatuses);
                    });
                }
                return $q;
            };

            if ($amountExpr) {
                $totalSubsidyDistributed = (float) $base()->sum(DB::raw($amountExpr));
            }

            if ($dateCol) {
                $thisMonthReleases = $base()
                    ->whereYear("$rel.$dateCol", now()->year)
                    ->whereMonth("$rel.$dateCol", now()->month)
                    ->count();

                if ($amountExpr) {
                    $thisMonthSubsidy = (float) $base()
                        ->whereYear("$rel.$dateCol", now()->year)
                        ->whereMonth("$rel.$dateCol", now()->month)
                        ->sum(DB::raw($amountExpr));

                    $monthlySubsidies = $base()
                        ->selectRaw($this->monthExpr("$rel.$dateCol") . " as month, SUM($amountExpr) as total")
                        ->whereYear("$rel.$dateCol", now()->year)
                        ->groupBy('month')
                        ->get();
                }
            }

            if ($amountExpr) {
                if ($nameCol) {
                    $subsidyBreakdown = $base()
                        ->selectRaw("COALESCE(subsidies.$nameCol, 'Unspecified') as name, SUM($amountExpr) as total_amount")
                        ->groupBy("subsidies.$nameCol")
                        ->get();
                } elseif ($totalSubsidyDistributed > 0) {
                    $subsidyBreakdown = collect([
                        (object) ['name' => 'All subsidies', 'total_amount' => $totalSubsidyDistributed],
                    ]);
                }
            }

            $debug += [
                'releases_rows'      => DB::table($rel)->count(),
                'releases_columns'   => Schema::getColumnListing($rel),
                'subsidies_columns'  => Schema::hasTable('subsidies') ? Schema::getColumnListing('subsidies') : null,
                'amount_expr'        => $amountExpr,
                'date_col'           => $dateCol,
                'name_col'           => $nameCol,
                'release_statuses'   => Schema::hasColumn($rel, 'status') ? DB::table($rel)->distinct()->pluck('status') : null,
            ];
        }

        // Visit /dashboard?debug=1 to see what was detected. Remove this block when done.
        if (request()->boolean('debug')) {
            dd($debug, compact('totalSubsidyDistributed', 'monthlySubsidies', 'subsidyBreakdown'));
        }

        $recentAnnouncements = $this->recentAnnouncements();

        return view('dashboard', compact(
            'totalSeniorCitizens',
            'approvedBeneficiaries',
            'activeBeneficiaries',
            'pendingApplications',
            'verificationApplications',
            'approvedApplications',
            'rejectedApplications',
            'totalSubsidyDistributed',
            'thisMonthSubsidy',
            'thisMonthReleases',
            'monthlyApplications',
            'monthlySubsidies',
            'subsidyBreakdown',
            'recentAnnouncements'
        ));
    }

    private function recentAnnouncements()
    {
        if (class_exists(\App\Models\Announcement::class)) {
            return \App\Models\Announcement::latest()->limit(5)->get();
        }

        if (! Schema::hasTable('announcements')) {
            return collect();
        }

        return DB::table('announcements')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $row->distribution_date = ! empty($row->distribution_date)
                    ? Carbon::parse($row->distribution_date)
                    : null;
                $row->distribution_location ??= null;
                $row->recipients_count ??= 0;
                $row->sent_count ??= 0;
                $row->status ??= 'draft';

                return $row;
            });
    }
}