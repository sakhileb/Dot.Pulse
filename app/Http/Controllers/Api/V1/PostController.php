<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pulse\ReportContent;
use App\Http\Controllers\Controller;
use App\Jobs\EnrichPost;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\PulseReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = PulsePost::with(['author:id,name', 'community:id,name,slug', 'enrichment'])
            ->published()
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->community_id, fn ($q) => $q->where('community_id', $request->community_id))
            ->orderByDesc('ai_relevance_score')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($posts);
    }

    public function show(int $id): JsonResponse
    {
        $post = PulsePost::with(['author:id,name', 'community:id,name,slug', 'enrichment', 'hashtags'])
            ->published()
            ->findOrFail($id);

        return response()->json($post);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type'         => 'required|in:' . implode(',', PulsePost::$types),
            'title'        => 'nullable|string|max:200',
            'body'         => 'required|string|min:10|max:10000',
            'community_id' => 'nullable|exists:communities,id',
        ]);

        $post = PulsePost::create([
            'user_id'      => $request->user()->id,
            'community_id' => $request->community_id,
            'team_id'      => $request->user()->currentTeam?->id,
            'type'         => $request->type,
            'title'        => $request->title,
            'body'         => $request->body,
            'status'       => 'pending',
        ]);

        $profile = PulseProfile::where('user_id', $request->user()->id)->first();
        $profile?->addPoints(5);

        EnrichPost::dispatch($post);

        return response()->json($post, 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = PulsePost::findOrFail($id);
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    public function react(Request $request, int $id): JsonResponse
    {
        $request->validate(['emoji' => 'nullable|string|max:10']);
        $emoji = $request->emoji ?? '👍';

        $post = PulsePost::findOrFail($id);
        $user = $request->user();

        $existing = $post->reactions()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('reactions_count');
            return response()->json(['reacted' => false, 'count' => $post->reactions_count]);
        }

        $post->reactions()->create(['user_id' => $user->id, 'emoji' => $emoji]);
        $post->increment('reactions_count');

        return response()->json(['reacted' => true, 'count' => $post->reactions_count]);
    }

    public function report(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason'  => 'required|string|min:3|max:100',
            'details' => 'nullable|string|max:500',
        ]);

        $report = app(ReportContent::class)->handle(
            reporterId: $request->user()->id,
            morphType:  PulsePost::class,
            morphId:    $id,
            reason:     $request->reason,
            details:    $request->details ?? '',
        );

        return response()->json($report, 201);
    }
}
