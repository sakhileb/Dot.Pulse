<?php

namespace App\Actions\Pulse;

use App\Models\PulseReport;
use Illuminate\Support\Facades\RateLimiter;

class ReportContent
{
    public function handle(
        int    $reporterId,
        string $morphType,
        int    $morphId,
        string $reason,
        string $details = '',
    ): PulseReport {
        abort_unless(
            RateLimiter::attempt('pulse-report:' . $reporterId, 5, fn () => true),
            429,
            'Too many reports submitted. Please wait before submitting more.',
        );

        // Prevent duplicate open reports from the same user on the same content
        $existing = PulseReport::where('reporter_id', $reporterId)
            ->where('reportable_type', $morphType)
            ->where('reportable_id', $morphId)
            ->whereIn('status', ['open', 'under_review'])
            ->first();

        if ($existing) {
            return $existing; // idempotent
        }

        return PulseReport::create([
            'reporter_id'     => $reporterId,
            'reportable_type' => $morphType,
            'reportable_id'   => $morphId,
            'reason'          => $reason,
            'details'         => $details ?: null,
            'status'          => 'open',
        ]);
    }
}
