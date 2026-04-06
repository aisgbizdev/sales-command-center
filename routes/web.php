<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatReviewController;
use App\Http\Controllers\KnowledgeUpdateQueueController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\ReactApiController;
use App\Http\Controllers\ReactAppController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login', [AuthController::class, 'showLogin']);
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::prefix('/legacy')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('legacy.dashboard');
        Route::get('/prospects', [ProspectController::class, 'index'])->name('legacy.prospects.index');
        Route::get('/pipeline', [ProspectController::class, 'pipeline'])->name('legacy.prospects.pipeline');
        Route::get('/kinerja-penjualan', [ProspectController::class, 'performance'])->name('legacy.prospects.performance');
        Route::get('/chat-reviews', [ChatReviewController::class, 'index'])->name('legacy.chat-reviews.index');
        Route::get('/knowledge-queue', [KnowledgeUpdateQueueController::class, 'index'])->name('legacy.knowledge-queue.index');
    });

    Route::get('/dashboard', ReactAppController::class)->name('dashboard');
    Route::get('/prospects', ReactAppController::class)->name('prospects.index');
    Route::get('/pipeline', ReactAppController::class)->name('prospects.pipeline');
    Route::get('/kinerja-penjualan', ReactAppController::class)->name('prospects.performance');
    Route::get('/chat-reviews', ReactAppController::class)->name('chat-reviews.index');
    Route::get('/knowledge-queue', ReactAppController::class)->name('knowledge-queue.index');
    Route::get('/react/{path?}', function () {
        return redirect()->route('dashboard');
    })->where('path', '.*');

    Route::prefix('/react-api')->group(function () {
        Route::get('/meta', [ReactApiController::class, 'meta'])->name('react-api.meta');
        Route::get('/dashboard', [ReactApiController::class, 'dashboard'])->name('react-api.dashboard');
        Route::get('/prospects', [ReactApiController::class, 'prospects'])->name('react-api.prospects');
        Route::get('/pipeline', [ReactApiController::class, 'pipeline'])->name('react-api.pipeline');
        Route::get('/performance', [ReactApiController::class, 'performance'])->name('react-api.performance');
        Route::get('/chat-reviews', [ReactApiController::class, 'chatReviews'])->name('react-api.chat-reviews');
        Route::get('/knowledge-queue', [ReactApiController::class, 'knowledgeQueue'])->name('react-api.knowledge-queue');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::patch('/prospects/{prospect}/quick-update', [ProspectController::class, 'quickUpdate'])->name('prospects.quick-update');
    Route::post('/chat-reviews/{chatReview}/manager-notes', [ChatReviewController::class, 'addManagerNote'])->name('chat-reviews.manager-note');
    Route::post('/chat-reviews/{chatReview}/knowledge-queue', [KnowledgeUpdateQueueController::class, 'store'])->name('knowledge-queue.store');
    Route::patch('/knowledge-queue/{knowledgeQueue}/review', [KnowledgeUpdateQueueController::class, 'setReview'])->name('knowledge-queue.set-review');
    Route::patch('/knowledge-queue/{knowledgeQueue}/approve', [KnowledgeUpdateQueueController::class, 'approve'])->name('knowledge-queue.approve');
    Route::patch('/knowledge-queue/{knowledgeQueue}/reject', [KnowledgeUpdateQueueController::class, 'reject'])->name('knowledge-queue.reject');

    Route::resource('prospects', ProspectController::class)
        ->except(['index'])
        ->parameters(['prospects' => 'prospect']);
    Route::resource('chat-reviews', ChatReviewController::class)
        ->except(['index'])
        ->parameters(['chat-reviews' => 'chatReview']);
});
