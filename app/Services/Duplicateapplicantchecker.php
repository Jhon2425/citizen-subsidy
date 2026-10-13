<?php

namespace App\Services;

use App\Models\Application;
use App\Models\SeniorCitizen;

/**
 * Finds people who are already in the system (applications or the
 * senior citizens master list) so they can't be registered twice.
 *
 * Applications store first/middle/last separately, but senior_citizens only
 * has `list_name` ("Dela Cruz, Juan ..."), so names are compared as a set of
 * normalized words. That makes "Dela Cruz, Juan" and "Juan Dela Cruz" equal.
 */
class DuplicateApplicantChecker
{
    /**
     * Check a name entered through the application form.
     * Middle name is only compared when one was typed.
     */
    public function checkApplicant(array $name, ?int $ignoreApplicationId = null): ?string
    {
        $first  = $this->tokens($name['first_name'] ?? '');
        $last   = $this->tokens($name['last_name'] ?? '');
        $middle = $this->tokens($name['middle_name'] ?? '');

        if (! $first || ! $last) {
            return null;
        }

        $required = array_values(array_unique(array_merge($first, $middle, $last)));

        $application = Application::query()
            ->when($ignoreApplicationId, fn ($q) => $q->whereKeyNot($ignoreApplicationId))
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower(trim((string) $name['first_name']))])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower(trim((string) $name['last_name']))])
            ->when($middle, fn ($q) => $q->whereRaw(
                "LOWER(TRIM(COALESCE(middle_name, ''))) = ?",
                [mb_strtolower(trim((string) $name['middle_name']))]
            ))
            ->first();

        if ($application) {
            return $this->applicationMessage($application);
        }

        return $this->seniorMessage($required, $ignoreApplicationId);
    }

    /**
     * Check a single full-name string (used by the manual "add senior citizen" form).
     */
    public function checkFullName(string $fullName): ?string
    {
        $required = $this->tokens($fullName);

        if (count($required) < 2) {
            return null;
        }

        $applications = Application::query()
            ->where(function ($q) use ($required) {
                foreach ($required as $word) {
                    $like = '%' . $this->escapeLike($word) . '%';
                    $q->where(fn ($w) => $w
                        ->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like));
                }
            })
            ->limit(25)
            ->get();

        foreach ($applications as $application) {
            $existing = $this->tokens($application->first_name . ' ' . $application->middle_name . ' ' . $application->last_name);
            $core     = $this->tokens($application->first_name . ' ' . $application->last_name);

            // typed name is inside the record, or the record's first + last are inside the typed name
            if (! array_diff($required, $existing) || ! array_diff($core, $required)) {
                return $this->applicationMessage($application);
            }
        }

        return $this->seniorMessage($required);
    }

    private function seniorMessage(array $required, ?int $ignoreApplicationId = null): ?string
    {
        $candidates = SeniorCitizen::query()
            ->when($ignoreApplicationId, fn ($q) => $q->where(
                fn ($w) => $w->whereNull('application_id')->orWhere('application_id', '!=', $ignoreApplicationId)
            ))
            ->where(function ($q) use ($required) {
                foreach ($required as $word) {
                    $q->where('list_name', 'like', '%' . $this->escapeLike($word) . '%');
                }
            })
            ->limit(25)
            ->get(['id', 'list_name']);

        foreach ($candidates as $senior) {
            // every typed word must be a whole word in list_name ("ann" must not match "joanna")
            if (! array_diff($required, $this->tokens($senior->list_name))) {
                return 'This person is already registered in the Senior Citizens master list (' . $senior->list_name . ').';
            }
        }

        return null;
    }

    private function applicationMessage(Application $application): string
    {
        return 'An application for this person already exists (' . $application->full_name
            . ', status: ' . ucfirst((string) $application->status) . ').';
    }

    /** lowercase words, punctuation removed: "Dela Cruz, Juan" => [dela, cruz, juan] */
    private function tokens(?string $value): array
    {
        $value = mb_strtolower((string) $value);
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value);

        return array_values(array_unique(preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY)));
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}