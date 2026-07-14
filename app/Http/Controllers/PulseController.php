<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\PulseEvent;
use App\Models\PulseMarketplaceItem;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PulseController extends Controller
{
    public function communities(): View
    {
        return view('pulse.communities.index');
    }

    public function createCommunity(): View
    {
        return view('pulse.communities.create');
    }

    public function community(string $slug): View
    {
        $community = Community::where('slug', $slug)->firstOrFail();

        return view('pulse.communities.show', compact('community'));
    }

    public function post(int $id): View
    {
        $post = PulsePost::with(['author', 'community', 'enrichment', 'hashtags'])
            ->findOrFail($id);

        $post->increment('views_count');

        return view('pulse.posts.show', compact('post'));
    }

    public function profile(string $username): View
    {
        $user    = User::where('name', $username)->firstOrFail();
        $profile = PulseProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['role' => 'customer'],
        );

        return view('pulse.users.show', compact('user', 'profile'));
    }

    public function search(Request $request): View
    {
        $query = $request->query('q', '');

        return view('pulse.search', compact('query'));
    }

    public function marketplace(): View
    {
        return view('pulse.marketplace.index');
    }

    public function createMarketplaceItem(): View
    {
        return view('pulse.marketplace.create');
    }

    public function notifications(): View
    {
        return view('pulse.notifications');
    }

    public function events(): View
    {
        return view('pulse.events.index');
    }

    public function createEvent(): View
    {
        return view('pulse.events.create');
    }

    public function moderation(): View
    {
        /** @var \App\Models\User $user */
        $user    = auth()->user();
        $profile = $user->profile ?? PulseProfile::where('user_id', $user->id)->first();

        abort_unless(
            $profile && in_array($profile->role, ['moderator', 'admin']),
            403,
        );

        return view('pulse.moderation.index');
    }

    public function editPulseProfile(): View
    {
        return view('pulse.profile.edit');
    }

    public function messages(?int $id = null): View
    {
        return view('pulse.messages.index', ['activeId' => $id]);
    }
}
