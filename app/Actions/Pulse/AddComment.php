<?php

namespace App\Actions\Pulse;

use App\Events\CommentPosted;
use App\Models\PulseComment;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Notifications\NewCommentOnPost;
use App\Services\BadgeAwarder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class AddComment
{
    public function handle(
        int $postId,
        int $userId,
        string $body,
        ?int $parentId = null,
    ): PulseComment {
        // Rate limiting
        abort_unless(
            RateLimiter::attempt('pulse-comment:'.$userId, 40, fn () => true),
            429,
            'Too many comments. Please wait before posting again.',
        );

        $post = PulsePost::with('author')->findOrFail($postId);

        // A user must be able to view the post (published + community membership
        // for private/enterprise communities) before they can comment on it.
        abort_unless(
            Gate::forUser(User::find($userId))->allows('view', $post),
            403,
        );

        $comment = PulseComment::create([
            'pulse_post_id' => $post->id,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        $post->increment('comments_count');

        // Award 2 points for contributing a comment
        $profile = PulseProfile::where('user_id', $userId)->first();
        $profile?->addPoints(2);

        // Check badges
        app(BadgeAwarder::class)->checkAll($comment->author);

        // Notify post author (not self)
        if ($post->user_id !== $userId) {
            $post->author->notify(new NewCommentOnPost($comment->load('author'), $post));
        }

        // Broadcast to post channel
        CommentPosted::dispatch($comment->load('author'));

        // Parse @mentions and notify mentioned users
        $this->notifyMentions($comment, $userId);

        return $comment;
    }

    private function notifyMentions(PulseComment $comment, int $authorId): void
    {
        preg_match_all('/@([\w\-.]+)/', $comment->body, $matches);
        if (empty($matches[1])) {
            return;
        }

        collect($matches[1])
            ->unique()
            ->take(5)
            ->each(function (string $name) use ($comment, $authorId) {
                $mentioned = User::where('name', $name)->first();
                if ($mentioned && $mentioned->id !== $authorId) {
                    $mentioned->notify(new MentionNotification($comment));
                }
            });
    }
}
