<?php

use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\CommunityController;
use App\Http\Controllers\Api\V1\FollowController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Legacy
Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () {

    // Feed & Posts
    Route::get('feed',                [PostController::class, 'index'])->name('feed');
    Route::get('posts/{id}',          [PostController::class, 'show'])->name('posts.show');
    Route::post('posts',              [PostController::class, 'store'])->name('posts.store');
    Route::delete('posts/{id}',       [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('posts/{id}/react',   [PostController::class, 'react'])->name('posts.react');
    Route::post('posts/{id}/report',  [PostController::class, 'report'])->name('posts.report');

    // Comments
    Route::get('posts/{postId}/comments',  [CommentController::class, 'index'])->name('comments.index');
    Route::post('posts/{postId}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{id}',         [CommentController::class, 'destroy'])->name('comments.destroy');

    // Communities
    Route::get('communities',                    [CommunityController::class, 'index'])->name('communities.index');
    Route::post('communities',                   [CommunityController::class, 'store'])->name('communities.store');
    Route::get('communities/{slug}',             [CommunityController::class, 'show'])->name('communities.show');
    Route::post('communities/{slug}/join',       [CommunityController::class, 'join'])->name('communities.join');
    Route::delete('communities/{slug}/leave',    [CommunityController::class, 'leave'])->name('communities.leave');

    // Follow
    Route::post('users/{id}/follow', [FollowController::class, 'toggle'])->name('users.follow');

    // Notifications
    Route::get('notifications',             [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-read',  [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('notifications/{id}/read',  [NotificationController::class, 'markOne'])->name('notifications.mark-one');
    Route::get('notifications/unread-count',[NotificationController::class, 'unreadCount'])->name('notifications.unread');

    // Search
    Route::get('search', SearchController::class)->name('search');
});

