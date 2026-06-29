<?php

use App\Http\Controllers\Auth\EcosystemAuthController;
use App\Models\Community;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use Illuminate\Support\Facades\Route;

Route::get('/auth/ecosystem', [EcosystemAuthController::class, 'handle'])
    ->name('ecosystem.auth');

Route::get('/', fn () => view('welcome'));

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $user    = auth()->user();
        $profile = PulseProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['role' => 'customer'],
        );

        return view('dashboard', [
            'totalPosts'       => PulsePost::published()->count(),
            'totalCommunities' => Community::count(),
            'myPoints'         => $profile->community_points,
        ]);
    })->name('dashboard');
});
