<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pulse\AddComment;
use App\Http\Controllers\Controller;
use App\Models\PulseComment;
use App\Models\PulsePost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, int $postId): JsonResponse
    {
        $post = PulsePost::findOrFail($postId);
        $this->authorize('view', $post);

        $comments = PulseComment::with(['author:id,name', 'replies.author:id,name'])
            ->where('pulse_post_id', $postId)
            ->whereNull('parent_id')
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    public function store(Request $request, int $postId): JsonResponse
    {
        $request->validate([
            'body'      => 'required|string|min:2|max:5000',
            'parent_id' => 'nullable|exists:pulse_comments,id',
        ]);

        $comment = app(AddComment::class)->handle(
            postId:   $postId,
            userId:   $request->user()->id,
            body:     $request->body,
            parentId: $request->parent_id,
        );

        return response()->json($comment->load('author:id,name'), 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = PulseComment::findOrFail($id);
        $this->authorize('delete', $comment);

        $comment->delete();
        $comment->post->decrement('comments_count');

        return response()->json(['message' => 'Comment deleted.']);
    }
}
