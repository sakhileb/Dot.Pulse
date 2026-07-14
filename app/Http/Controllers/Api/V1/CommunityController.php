<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pulse\CreateCommunity;
use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $communities = Community::where('visibility', 'public')
            ->when($request->industry, fn ($q) => $q->where('industry', $request->industry))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderByDesc('members_count')
            ->paginate(20);

        return response()->json($communities);
    }

    public function show(string $slug): JsonResponse
    {
        $community = Community::with(['creator:id,name', 'rules'])
            ->where('slug', $slug)->firstOrFail();

        $this->authorize('view', $community);

        return response()->json($community);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|min:3|max:80|unique:communities,name',
            'description' => 'nullable|max:500',
            'industry'    => 'nullable|max:60',
            'visibility'  => 'required|in:public,private,enterprise',
        ]);

        $community = app(CreateCommunity::class)->handle(
            userId:      $request->user()->id,
            name:        $request->name,
            description: $request->description ?? '',
            industry:    $request->industry ?? '',
            visibility:  $request->visibility,
            teamId:      $request->user()->currentTeam?->id,
        );

        return response()->json($community, 201);
    }

    public function join(Request $request, string $slug): JsonResponse
    {
        $community = Community::where('slug', $slug)->firstOrFail();

        if ($community->hasMember($request->user())) {
            return response()->json(['message' => 'Already a member.'], 409);
        }

        CommunityMembership::create([
            'community_id' => $community->id,
            'user_id'      => $request->user()->id,
            'role'         => 'member',
        ]);
        $community->increment('members_count');

        return response()->json(['message' => 'Joined successfully.']);
    }

    public function leave(Request $request, string $slug): JsonResponse
    {
        $community = Community::where('slug', $slug)->firstOrFail();

        CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $request->user()->id)
            ->delete();
        $community->decrement('members_count');

        return response()->json(['message' => 'Left community.']);
    }
}
