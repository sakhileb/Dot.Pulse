<?php

namespace App\Actions\Pulse;

use App\Models\PulsePoll;
use App\Models\PulsePollVote;
use Illuminate\Support\Facades\DB;

class VotePoll
{
    /** Returns the updated poll. Returns null if poll is closed. */
    public function handle(int $pollId, int $userId, int $optionIndex): ?PulsePoll
    {
        $poll = PulsePoll::findOrFail($pollId);

        if ($poll->closes_at && $poll->closes_at->isPast()) {
            return null; // poll closed
        }

        $options = $poll->options;
        abort_if(! isset($options[$optionIndex]), 422, 'Invalid option index.');

        DB::transaction(function () use ($poll, $userId, $optionIndex) {
            $existing = PulsePollVote::where('pulse_poll_id', $poll->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                // Change vote
                $existing->update(['option_index' => $optionIndex]);
            } else {
                PulsePollVote::create([
                    'pulse_poll_id' => $poll->id,
                    'user_id' => $userId,
                    'option_index' => $optionIndex,
                ]);
                $poll->increment('votes_count');
            }
        });

        return $poll->refresh();
    }

    /** Get vote counts per option index for a poll. */
    public function getResults(PulsePoll $poll): array
    {
        $counts = PulsePollVote::where('pulse_poll_id', $poll->id)
            ->selectRaw('option_index, count(*) as count')
            ->groupBy('option_index')
            ->pluck('count', 'option_index')
            ->toArray();

        $results = [];
        foreach ($poll->options as $i => $option) {
            $results[$i] = [
                'label' => $option,
                'votes' => (int) ($counts[$i] ?? 0),
            ];
        }

        return $results;
    }

    public function userVote(int $pollId, int $userId): ?int
    {
        return PulsePollVote::where('pulse_poll_id', $pollId)
            ->where('user_id', $userId)
            ->value('option_index');
    }
}
