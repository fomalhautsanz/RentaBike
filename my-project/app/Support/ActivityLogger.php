<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Http\Request;

class ActivityLogger
{
    public static function record(Request $request, string $action, string $details): void
    {
        $user = $request->attributes->get('authenticatedStaff') ?? $request->user();

        if ($user instanceof Staff) {
            $userId = (int) $user->staff_id;
            $userType = 'staff';
        } elseif ($user instanceof Admin) {
            $userId = (int) $user->admin_id;
            $userType = 'admin';
        } else {
            $userId = 0;
            $userType = 'system';
        }

        ActivityLog::create([
            'user_id' => $userId,
            'user_type' => $userType,
            'action' => $action,
            'details' => $details,
        ]);
    }

    public static function formatChanges(array $changes): string
    {
        $parts = [];

        foreach ($changes as $label => $change) {
            $before = self::displayValue($change['before']);
            $after = self::displayValue($change['after']);
            $parts[] = "{$label}: {$before} -> {$after}";
        }

        return $parts === [] ? 'No field values changed' : implode('; ', $parts);
    }

    public static function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '(empty)';
        }

        if (is_array($value)) {
            $value = array_values($value);
            return $value === [] ? '(none)' : implode(', ', array_map(
                fn ($item) => (string) $item,
                $value
            ));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }
}
