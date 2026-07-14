<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pulse\FollowUser;
use App\Http\Controllers\Controller;
use App\Models\PulseFollower;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggle(Request $request, int $userId): JsonResponse
    {
        $target = User::findOrFail($userId);
        $nowFollowing = app(FollowUser::class)->handle($request->user(), $target);

        return response()->json([
            'following' => $nowFollowing,
            'followers_count' => PulseFollower::where('following_id', $userId)->count(),
        ]);
    }
}
