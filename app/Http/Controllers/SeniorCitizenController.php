<?php

namespace App\Http\Controllers;

use App\Imports\SeniorCitizensImport;
use App\Models\SeniorCitizen;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SeniorCitizenController extends Controller
{
    /**
     * Master list of approved senior citizens (senior-citizens/index.blade.php).
     */
    public function index(Request $request)
    {
        $seniors = SeniorCitizen::query()
            ->active()
            ->barangay($request->barangay)
            ->search($request->search)
            ->orderBy('list_name')
            ->paginate(15)
            ->withQueryString();

        // Populates the barangay filter dropdown.
        $barangays = SeniorCitizen::query()
            ->active()
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay');

        $stats = [
            'total'         => SeniorCitizen::active()->count(),
            'approved'      => SeniorCitizen::active()->count(),
            'newThisMonth'  => SeniorCitizen::active()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return view('senior-citizens.index', compact('seniors', 'barangays', 'stats'));
    }

    /**
     * Manually add a senior citizen directly (not via an Application),
     * e.g. for walk-in registrations already vetted offline.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'list_name'       => ['required', 'string', 'max:255'],
            'birth_date'      => ['required', 'date', 'before:today'],
            'gender'          => ['required', Rule::in(['male', 'female'])],
            'barangay'        => ['required', 'string', 'max:255'],
            'municipality'    => ['required', 'string', 'max:255'],
            'address'    => ['nullable', 'string', 'max:255'],
            'contact_number'  => ['nullable', 'string', 'max:20'],
        ]);

        $data['is_active'] = true;

        SeniorCitizen::create($data);

        return back()->with('success', 'Senior citizen added successfully.');
    }

    /**
     * Bulk-import senior citizens from an Excel / CSV file
     * (Import modal on the master list).
     */
    public function import(Request $request)
    {
        // Named "import" bag so errors only reopen the import modal,
        // not the edit modal.
        $request->validateWithBag('import', [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'Please choose a file to import.',
            'file.mimes'    => 'The file must be an .xlsx, .xls or .csv file.',
            'file.max'      => 'The file may not be larger than 5 MB.',
        ]);

        $import = new SeniorCitizensImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'The file could not be read. Please use the template and try again.');
        }

        if ($import->imported === 0 && $import->skipped === 0) {
            return back()->with('error', 'No rows were found in the file. Make sure the first row contains the column headings.');
        }

        return back()
            ->with('success', "Import finished: {$import->imported} added, {$import->skipped} skipped.")
            ->with('import_errors', array_slice($import->errors, 0, 20));
    }

    /**
     * Downloadable CSV template that matches the import columns.
     */
    public function importTemplate()
    {
        $headers = [
            'list_name', 'gender', 'birth_date', 'contact_number',
            'address', 'barangay', 'municipality', 'province', 'region',
        ];

        $sample = [
            'Dela Cruz, Juan', 'male', '1950-03-25', '09171234567',
            '123 Rizal St.', 'Poblacion', 'Lipa City', 'Batangas', 'Region IV-A',
        ];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'senior-citizens-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Update a senior citizen's information (edit modal on the master list).
     */
    public function update(Request $request, SeniorCitizen $seniorCitizen)
    {
        $validated = $request->validate([
            'list_name'         => ['required', 'string', 'max:255'],
            'birth_date'        => ['required', 'date', 'before:today'],
            'gender'            => ['required', Rule::in(['male', 'female'])],
            'contact_number'    => ['nullable', 'string', 'max:20'],
            'is_active'         => ['required', 'boolean'],
            'address'      => ['nullable', 'string', 'max:255'],

            // Only sent when "Change address" was used in the modal.
            'region_code'       => ['nullable', 'string'],
            'region'            => ['nullable', 'string', 'max:255'],
            'province_code'     => ['nullable', 'string'],
            'province'          => ['nullable', 'string', 'max:255'],
            'municipality_code' => ['nullable', 'string'],
            'municipality'      => ['nullable', 'string', 'max:255'],
            'barangay_code'     => ['nullable', 'string'],
            'barangay'          => ['nullable', 'string', 'max:255'],
        ]);

        // Keep the existing address unless a new barangay was chosen.
        if (empty($validated['barangay_code'])) {
            $validated = Arr::except($validated, [
                'region_code', 'region', 'province_code', 'province',
                'municipality_code', 'municipality', 'barangay_code', 'barangay',
            ]);
        }

        // Only write columns that actually exist on the table.
        $columns = Schema::getColumnListing($seniorCitizen->getTable());

        $seniorCitizen->forceFill(Arr::only($validated, $columns))->save();

        return redirect()
            ->route('senior-citizens.index')
            ->with('success', "{$seniorCitizen->list_name} was updated.");
    }

    /**
     * Mark a senior citizen as deceased. Sets is_active = false, which drops
     * them off the master list via the `active` scope used in index().
     */
    public function deceased(SeniorCitizen $seniorCitizen)
    {
        $seniorCitizen->update([
            'is_active'   => false,
            'deceased_at' => now(),
        ]);

        return back()->with('success', "{$seniorCitizen->list_name} has been marked as deceased and removed from the list.");
    }
}