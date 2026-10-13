<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\SeniorCitizen;
use App\Models\Subsidy;
use App\Services\DuplicateApplicantChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = Application::query()
            ->with('subsidy')
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->status),
                fn ($q) => $q->where('status', '!=', 'approved')
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                // Every word must match first, middle, or last name,
                // so "juan cruz" and "cruz, juan" both work.
                $words = preg_split('/\s+/', trim(str_replace(',', ' ', $request->search)));

                foreach ($words as $word) {
                    $q->where(fn ($w) => $w
                        ->where('last_name', 'like', "%{$word}%")
                        ->orWhere('first_name', 'like', "%{$word}%")
                        ->orWhere('middle_name', 'like', "%{$word}%"));
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $subsidies = Subsidy::active()->orderBy('name')->get();

        return view('applications.index', compact('applications', 'subsidies'));
    }

    public function create()
    {
        return view('applications.create', [
            'subsidies' => Subsidy::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Live duplicate check used by the Register New modal while typing.
     */
    public function checkDuplicate(Request $request, DuplicateApplicantChecker $checker)
    {
        $message = $checker->checkApplicant(
            $request->only(['first_name', 'last_name', 'middle_name']),
            $request->integer('ignore_id') ?: null
        );

        return response()->json([
            'duplicate' => $message !== null,
            'message'   => $message,
        ]);
    }

    public function store(Request $request, DuplicateApplicantChecker $checker)
    {
        $data = $this->validated($request);

        if ($message = $checker->checkApplicant($data)) {
            return back()->withInput()->withErrors(['duplicate' => $message]);
        }

        $application = Application::create($data);

        if ($application->status === 'approved') {
            $this->promoteToSeniorCitizen($application);
        }

        return redirect()
            ->route('applications.index')
            ->with('success', 'Application saved successfully.');
    }

    public function edit(Application $application)
    {
        return view('applications.edit', [
            'application' => $application,
            'subsidies'   => Subsidy::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Application $application, DuplicateApplicantChecker $checker)
    {
        $wasApproved = $application->status === 'approved';

        $data = $this->validated($request);

        // Ignore this application (and the senior citizen created from it)
        if ($message = $checker->checkApplicant($data, $application->id)) {
            return back()->withInput()->withErrors(['duplicate' => $message]);
        }

        $application->update($data);

        if ($application->status === 'approved' && ! $wasApproved) {
            $this->promoteToSeniorCitizen($application);
        }

        return redirect()
            ->route('applications.index')
            ->with('success', 'Application updated successfully.');
    }

    /**
     * Inline status change from the index table. A reason is required
     * for pending / rejected.
     */
    public function updateStatus(Request $request, Application $application)
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'reason' => ['required_if:status,pending,rejected', 'nullable', 'string', 'max:500'],
        ]);

        $wasApproved = $application->status === 'approved';

        $application->update([
            'status' => $request->status,
            'reason' => $request->status === 'approved' ? '' : (string) $request->reason,
        ]);

        if ($application->status === 'approved' && ! $wasApproved) {
            $this->promoteToSeniorCitizen($application);

            return back()->with('success', "{$application->full_name} approved and added to Senior Citizens.");
        }

        return back()->with('success', 'Status updated.');
    }

    public function destroy(Application $application)
    {
        $application->delete();

        $this->resetAutoIncrement();

        return back()->with('success', 'Application removed.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'subsidy_id'        => ['required', 'integer', Rule::exists('subsidies', 'id')],

            'last_name'         => ['required', 'string', 'max:100'],
            'first_name'        => ['required', 'string', 'max:100'],
            'middle_name'       => ['nullable', 'string', 'max:100'],
            'name_extension'    => ['nullable', 'string', 'max:10'],

            'birth_date'        => ['required', 'date', 'before:today'],
            'gender'            => ['required', Rule::in(['male', 'female'])],

            'address'           => ['nullable', 'string', 'max:255'],

            'region'            => ['required', 'string', 'max:255'],
            'region_code'       => ['required', 'string', 'max:12'],
            'province'          => ['nullable', 'string', 'max:255'],
            'province_code'     => ['nullable', 'string', 'max:12'],
            'municipality'      => ['required', 'string', 'max:255'],
            'municipality_code' => ['required', 'string', 'max:12'],
            'barangay'          => ['required', 'string', 'max:255'],
            'barangay_code'     => ['required', 'string', 'max:12'],

            'contact_number'    => ['nullable', 'string', 'max:20'],
            'status'            => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'reason'            => ['required_if:status,pending,rejected', 'nullable', 'string', 'max:500'],
        ]);

        // Laravel turns '' into null; these columns are NOT NULL, so restore ''.
        foreach (['middle_name', 'name_extension', 'address', 'province', 'province_code', 'contact_number', 'reason'] as $key) {
            $data[$key] = $data[$key] ?? '';
        }

        if ($data['status'] === 'approved') {
            $data['reason'] = '';
        }

        return $data;
    }

    protected function promoteToSeniorCitizen(Application $application): SeniorCitizen
    {
        return SeniorCitizen::updateOrCreate(
            ['application_id' => $application->id],
            [
                'list_name'         => $application->full_name,
                'birth_date'        => $application->birth_date,
                'gender'            => $application->gender,
                'address'           => $application->address,
                'region'            => $application->region,
                'region_code'       => $application->region_code,
                'province'          => $application->province,
                'province_code'     => $application->province_code,
                'municipality'      => $application->municipality,
                'municipality_code' => $application->municipality_code,
                'barangay'          => $application->barangay,
                'barangay_code'     => $application->barangay_code,
                'contact_number'    => $application->contact_number,
                'is_active'         => true,
            ]
        );
    }

    /**
     * Moves the auto-increment counter back to MAX(id) + 1, so deleting the
     * newest record(s) lets the next insert reuse that number.
     */
    protected function resetAutoIncrement(): void
    {
        $table = (new Application)->getTable();

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1"),
            'sqlite' => DB::statement("UPDATE sqlite_sequence SET seq = (SELECT COALESCE(MAX(id), 0) FROM {$table}) WHERE name = '{$table}'"),
            'pgsql'  => DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 0) + 1, false)"),
            default  => null,
        };
    }
}