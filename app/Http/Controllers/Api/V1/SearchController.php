<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\PulseHashtag;
use App\Models\PulsePost;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);
        $q = '%' . $request->q . '%';

        return response()->json([
            'posts' => PulsePost::with('author:id,name')
                ->published()
                ->where(fn ($b) => $b->where('title', 'like', $q)->orWhere('body', 'like', $q))
                ->limit(10)->get(['id', 'type', 'title', 'body', 'user_id', 'created_at']),

            'communities' => Community::where('visibility', 'public')
                ->where(fn ($b) => $b->where('name', 'like', $q)->orWhere('description', 'like', $q))
                ->limit(5)->get(['id', 'name', 'slug', 'industry', 'members_count']),

            'users' => User::where('name', 'like', $q)
                ->limit(5)->get(['id', 'name', 'email']),
        ]);
    }
}
