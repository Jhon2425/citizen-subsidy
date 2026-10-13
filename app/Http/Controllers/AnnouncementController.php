<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\SeniorCitizen;
use App\Models\Subsidy;
use App\Services\SemaphoreSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    public function __construct(protected SemaphoreSmsService $sms)
    {
    }

    public function index()
    {
        $announcements = Announcement::with(['barangays', 'subsidy'])
            ->latest()
            ->paginate(10);

        $barangays = $this->availableBarangays();

        // Created subsidies for the "Subsidy Type" dropdown
        $subsidies = Subsidy::orderBy('name')->get();

        $recipientsCount = $this->recipientsQuery([])->count();

        return view('announcements.index', compact(
            'announcements',
            'barangays',
            'subsidies',
            'recipientsCount'
        ));
    }

    public function create()
    {
        $barangays = $this->availableBarangays();

        $subsidies = Subsidy::orderBy('name')->get();

        $recipientsCount = $this->recipientsQuery([])->count();

        return view('announcements.create', compact(
            'barangays',
            'subsidies',
            'recipientsCount'
        ));
    }

    public function recipientsCount(Request $request)
    {
        $codes = array_filter(
            (array) $request->input('barangay_codes', [])
        );

        return response()->json([
            'count' => $this->recipientsQuery($codes)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subsidy_id' => ['required', 'exists:subsidies,id'],
            'message' => ['required', 'string', 'max:300'],
            'distribution_date' => ['nullable', 'date'],
            'distribution_location' => ['nullable', 'string', 'max:255'],
            'barangay_codes' => ['nullable', 'array'],
            'barangay_codes.*' => ['string'],
        ]);

        $codes = array_values(array_filter($validated['barangay_codes'] ?? []));

        $announcement = DB::transaction(function () use ($validated, $codes) {

            $announcement = Announcement::create([
                'title' => $validated['title'],
                'subsidy_id' => $validated['subsidy_id'],
                'message' => $validated['message'],
                'distribution_date' => $validated['distribution_date'] ?? null,
                'distribution_location' => $validated['distribution_location'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->syncBarangays($announcement, $codes);

            // Auto-enlist eligible senior citizens into the subsidy.
            // Only those in the selected barangays (all when none selected).
            // syncWithoutDetaching + the unique index prevent duplicates.
            Subsidy::findOrFail($validated['subsidy_id'])
                ->seniorCitizens()
                ->syncWithoutDetaching($this->eligibleQuery($codes)->pluck('id'));

            $announcement->update([
                'recipients_count' => $this->recipientsQuery($codes)->count(),
            ]);

            return $announcement;
        });

        if ($request->boolean('send_now')) {
            return $this->deliverAndRedirect($announcement, 'Announcement created');
        }

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Announcement saved as draft.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        // Sent or in-progress messages can't be edited.
        abort_unless(
            in_array($announcement->status, ['draft', 'failed']),
            403,
            'This announcement can no longer be edited.'
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subsidy_id' => ['required', 'exists:subsidies,id'],
            'message' => ['required', 'string', 'max:300'],
            'distribution_date' => ['nullable', 'date'],
            'distribution_location' => ['nullable', 'string', 'max:255'],
            'barangay_codes' => ['nullable', 'array'],
            'barangay_codes.*' => ['string'],
        ]);

        $codes = array_values(array_filter($validated['barangay_codes'] ?? []));

        DB::transaction(function () use ($announcement, $validated, $codes) {

            $announcement->update([
                'title' => $validated['title'],
                'subsidy_id' => $validated['subsidy_id'],
                'message' => $validated['message'],
                'distribution_date' => $validated['distribution_date'] ?? null,
                'distribution_location' => $validated['distribution_location'] ?? null,
                'recipients_count' => $this->recipientsQuery($codes)->count(),
                'status' => 'draft', // a failed announcement returns to draft once edited
            ]);

            // Replace the selected barangays.
            $announcement->barangays()->delete();
            $this->syncBarangays($announcement, $codes);

            // Enlist seniors for the (possibly new) subsidy, same as store().
            Subsidy::findOrFail($validated['subsidy_id'])
                ->seniorCitizens()
                ->syncWithoutDetaching($this->eligibleQuery($codes)->pluck('id'));
        });

        if ($request->boolean('send_now')) {
            return $this->deliverAndRedirect($announcement->fresh(), 'Announcement updated');
        }

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        abort_if($announcement->status === 'sending', 409, 'This announcement is being sent right now.');

        DB::transaction(function () use ($announcement) {
            $announcement->barangays()->delete();
            $announcement->delete();
        });

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Announcement deleted.');
    }

    public function send(Announcement $announcement)
    {
        abort_if($announcement->status === 'sent', 400, 'Already sent.');
        abort_if($announcement->status === 'sending', 409, 'This announcement is being sent right now.');

        return $this->deliverAndRedirect($announcement, 'Announcement');
    }

    /* =====================================================
        SMS DELIVERY (direct, no queue)
    ====================================================== */

    /**
     * Send the announcement and redirect back with a success or error flash.
     */
    protected function deliverAndRedirect(Announcement $announcement, string $prefix)
    {
        $result = $this->deliver($announcement);

        if ($result['sent'] > 0) {
            $message = "{$prefix} and SMS sent to {$result['sent']} senior citizen(s).";

            if ($result['failed'] > 0) {
                $message .= " {$result['failed']} could not be reached.";
            }

            return redirect()
                ->route('announcements.index')
                ->with('success', $message);
        }

        // Nothing was attempted: no recipients matched.
        if ($result['sent'] === 0 && $result['failed'] === 0 && empty($result['errors'])) {
            return redirect()
                ->route('announcements.index')
                ->with('error', "{$prefix}, but no senior citizen with a valid contact number was found for the selected barangay(s).");
        }

        // Show the real reason (e.g. missing API key, no credits) instead of a generic message.
        $reason = !empty($result['errors'])
            ? ' Reason: ' . \Illuminate\Support\Str::limit((string) $result['errors'][0], 200)
            : ' Check the logs and try again.';

        return redirect()
            ->route('announcements.index')
            ->with('error', "{$prefix}, but the SMS could not be sent.{$reason}");
    }

    /**
     * Send the announcement's message to its recipients through Semaphore
     * and update status / sent_count / recipients_count.
     *
     * @return array{sent: int, failed: int, errors: array<int, string>}
     */
    protected function deliver(Announcement $announcement): array
    {
        // Sending many messages can take a while.
        @set_time_limit(0);

        $announcement->update(['status' => 'sending', 'sent_count' => 0]);

        try {
            $codes = $announcement->barangays()->pluck('barangay_code')->all();

            // Numbers come from the senior citizen records, limited to the
            // barangays selected on this announcement (all if none selected).
            $rawNumbers = $this->recipientsQuery($codes)->pluck('contact_number');

            $numbers = $rawNumbers
                ->map(fn ($n) => $this->normalizeNumber($n))
                ->filter()
                ->unique()
                ->values()
                ->all();

            Log::info('Announcement SMS recipients', [
                'announcement_id' => $announcement->id,
                'barangay_codes' => $codes,
                'records_found' => $rawNumbers->count(),
                'valid_numbers' => count($numbers),
                'dropped_invalid' => $rawNumbers->count() - count($numbers),
            ]);

            if (empty($numbers)) {
                $announcement->update([
                    'status' => 'failed',
                    'recipients_count' => 0,
                ]);

                return ['sent' => 0, 'failed' => 0, 'errors' => []];
            }

            $result = $this->sms->sendBulk($numbers, $announcement->message);

            if (!empty($result['errors'])) {
                Log::error('Announcement SMS errors', [
                    'announcement_id' => $announcement->id,
                    'errors' => $result['errors'],
                ]);
            }

            $announcement->update([
                'sent_count' => $result['sent'],
                'recipients_count' => count($numbers),
                // "sent" if at least one message went through.
                // Use `$result['failed'] === 0` to be stricter.
                'status' => $result['sent'] > 0 ? 'sent' : 'failed',
            ]);

            return [
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'errors' => $result['errors'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Announcement SMS delivery crashed', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
            ]);

            $announcement->update(['status' => 'failed']);

            return ['sent' => 0, 'failed' => 0, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Convert to Semaphore-friendly PH format (09XXXXXXXXX).
     * Accepts 09XXXXXXXXX, 9XXXXXXXXX, 639XXXXXXXXX, +639XXXXXXXXX.
     */
    protected function normalizeNumber(?string $number): ?string
    {
        $n = preg_replace('/\D+/', '', (string) $number);

        if (str_starts_with($n, '63') && strlen($n) === 12) {
            $n = '0' . substr($n, 2);
        } elseif (strlen($n) === 10 && str_starts_with($n, '9')) {
            $n = '0' . $n;
        }

        return preg_match('/^09\d{9}$/', $n) ? $n : null;
    }

    /* =====================================================
        HELPERS
    ====================================================== */

    /**
     * Save the selected barangays for an announcement.
     * senior_citizens uses "barangay"; announcement_barangays uses "barangay_name".
     */
    protected function syncBarangays(Announcement $announcement, array $codes): void
    {
        if (empty($codes)) {
            return;
        }

        $allBarangays = $this->availableBarangays()->keyBy('barangay_code');

        foreach ($codes as $code) {
            $announcement->barangays()->create([
                'barangay_code' => $code,
                'barangay_name' => $allBarangays->get($code)?->barangay ?? $code,
            ]);
        }
    }

    /**
     * Eligible seniors (active, not deceased), limited to the given
     * barangay codes. No codes = every eligible senior citizen.
     */
    protected function eligibleQuery(array $codes)
    {
        return SeniorCitizen::eligible()
            ->when(
                count($codes) > 0,
                fn ($q) => $q->whereIn('barangay_code', $codes)
            );
    }

    /**
     * SMS recipients = eligible seniors with a contact number on file.
     */
    protected function recipientsQuery(array $codes)
    {
        return $this->eligibleQuery($codes)->whereNotNull('contact_number');
    }

    /**
     * Distinct barangays from senior_citizens.
     */
    protected function availableBarangays()
    {
        return SeniorCitizen::whereNotNull('barangay_code')
            ->select('barangay_code', 'barangay')
            ->distinct()
            ->orderBy('barangay')
            ->get();
    }
}