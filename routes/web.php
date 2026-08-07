<?php

use App\Http\Controllers\Auth\EcosystemAuthController;
use App\Http\Controllers\PulseController;
use App\Models\Community;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;

Route::get('/auth/ecosystem', [EcosystemAuthController::class, 'handle'])
    ->name('ecosystem.auth');

Route::get('/', fn () => view('welcome'));

// Cookie Policy — Jetstream's termsAndPrivacyPolicy feature covers terms.show/policy.show
// natively (registered at /terms-of-service and /privacy-policy, reading resources/markdown/
// terms.md and policy.md). There's no Jetstream equivalent for a Cookie Policy, so this one is
// wired by hand, following the exact same Markdown-source convention.
Route::get('/cookies', function () {
    return view('cookies', [
        'cookies' => Str::markdown(file_get_contents(Jetstream::localizedMarkdownPath('cookies.md'))),
    ]);
})->name('cookies');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        $user = Auth::user();
        $profile = PulseProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['role' => 'customer'],
        );

        return view('dashboard', [
            'totalPosts' => PulsePost::published()->count(),
            'totalCommunities' => Community::count(),
            'myPoints' => $profile->community_points,
        ]);
    })->name('dashboard');

    // Communities
    Route::get('/communities', [PulseController::class, 'communities'])->name('communities.index');
    Route::get('/communities/create', [PulseController::class, 'createCommunity'])->name('communities.create');
    Route::get('/communities/{slug}', [PulseController::class, 'community'])->name('communities.show');

    // Posts
    Route::get('/posts/{id}', [PulseController::class, 'post'])->name('posts.show');

    // User profiles
    Route::get('/u/{username}', [PulseController::class, 'profile'])->name('profile.public');

    // Search
    Route::get('/search', [PulseController::class, 'search'])->name('search');

    // Marketplace
    Route::get('/marketplace', [PulseController::class, 'marketplace'])->name('marketplace.index');
    Route::get('/marketplace/submit', [PulseController::class, 'createMarketplaceItem'])->name('marketplace.create');

    // Notifications
    Route::get('/notifications', [PulseController::class, 'notifications'])->name('notifications.index');

    // Events
    Route::get('/events', [PulseController::class, 'events'])->name('events.index');
    Route::get('/events/create', [PulseController::class, 'createEvent'])->name('events.create');

    // Moderation (moderator / admin only)
    Route::get('/moderation', [PulseController::class, 'moderation'])->name('moderation.index');

    // Pulse profile editor
    Route::get('/profile/pulse-settings', [PulseController::class, 'editPulseProfile'])->name('pulse-profile.edit');

    // Messaging
    Route::get('/messages', [PulseController::class, 'messages'])->name('messages.index');
    Route::get('/messages/{id}', [PulseController::class, 'messages'])->name('messages.show');
});
