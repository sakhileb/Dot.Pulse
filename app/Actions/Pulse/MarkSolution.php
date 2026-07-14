<?php

namespace App\Actions\Pulse;

use App\Models\PulseComment;
use App\Models\PulseProfile;
use App\Models\User;
use App\Notifications\SolutionAccepted;

class MarkSolution
{
    /**
     * Mark a comment as the accepted solution for its post.
     * Only the post author may call this.
     */
    public function handle(int $commentId, int $requestingUserId): PulseComment
    {
        $comment = PulseComment::with(['post', 'author'])->findOrFail($commentId);

        abort_unless(
            $comment->post->user_id === $requestingUserId,
            403,
            'Only the post author can accept a solution.',
        );

        // Clear any previous solution on this post
        PulseComment::where('pulse_post_id', $comment->pulse_post_id)
            ->where('is_solution', true)
            ->update(['is_solution' => false]);

        $comment->update(['is_solution' => true]);

        // Award 10 points to the solution author
        $profile = PulseProfile::where('user_id', $comment->user_id)->first();
        if ($profile) {
            $profile->addPoints(10);
            $profile->increment('solutions_accepted');
        }

        // Award solution-giver badge
        app(\App\Services\BadgeAwarder::class)->checkAll($comment->author);

        // Notify the comment author (not self)
        if ($comment->user_id !== $requestingUserId) {
            $comment->author->notify(new SolutionAccepted($comment, $comment->post));
        }

        return $comment->refresh();
    }
}
