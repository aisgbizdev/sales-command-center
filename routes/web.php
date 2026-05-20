<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatReviewController;
use App\Http\Controllers\KnowledgeUpdateQueueController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\ReactApiController;
use App\Http\Controllers\ReactAppController;
use App\Http\Controllers\UserMasterDataController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WhatsAppWebJsController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('webhooks.whatsapp.receive');

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
    Route::get('/prospects/create', ReactAppController::class)->name('prospects.create');
    Route::get('/prospects/{prospect}', ReactAppController::class)->name('prospects.show');
    Route::get('/prospects/{prospect}/edit', ReactAppController::class)->name('prospects.edit');
    Route::get('/pipeline', ReactAppController::class)->name('prospects.pipeline');
    Route::get('/queue', ReactAppController::class)->name('queue.index');
    Route::get('/manager-insights', ReactAppController::class)->name('manager-insights');
    Route::get('/kinerja-penjualan', ReactAppController::class)->name('prospects.performance');
    Route::get('/chat-reviews', ReactAppController::class)->name('chat-reviews.index');
    Route::get('/chat-reviews/create', ReactAppController::class)->name('chat-reviews.create');
    Route::get('/chat-reviews/{chatReview}', ReactAppController::class)->name('chat-reviews.show');
    Route::get('/chat-reviews/{chatReview}/edit', ReactAppController::class)->name('chat-reviews.edit');
    Route::get('/knowledge-queue', ReactAppController::class)->name('knowledge-queue.index');
    Route::get('/users', ReactAppController::class)->name('users.index');
    Route::get('/wa-webview', [WhatsAppWebJsController::class, 'index'])->name('wa-webview.index');
    Route::match(['GET', 'POST'], '/wa-webjs-api/{path?}', [WhatsAppWebJsController::class, 'proxy'])
        ->where('path', '.*')
        ->name('wa-webview.proxy');
    Route::get('/react/{path?}', function () {
        return redirect()->route('dashboard');
    })->where('path', '.*');

    Route::prefix('/react-api')->group(function () {
        Route::get('/meta', [ReactApiController::class, 'meta'])->name('react-api.meta');
        Route::get('/dashboard', [ReactApiController::class, 'dashboard'])->name('react-api.dashboard');
        Route::get('/action-center', [ReactApiController::class, 'actionCenter'])->name('react-api.action-center');
        Route::get('/queue', [ReactApiController::class, 'queue'])->name('react-api.queue');
        Route::post('/queue/{prospect}/done', [ReactApiController::class, 'queueMarkDone'])->name('react-api.queue.done');
        Route::post('/queue/{prospect}/snooze', [ReactApiController::class, 'queueSnooze'])->name('react-api.queue.snooze');
        Route::post('/queue/{prospect}/dismiss', [ReactApiController::class, 'queueDismiss'])->name('react-api.queue.dismiss');
        Route::get('/queue/{prospect}/history', [ReactApiController::class, 'queueHistory'])->name('react-api.queue.history');
        Route::get('/prospects', [ReactApiController::class, 'prospects'])->name('react-api.prospects');
        Route::get('/prospects/form', [ReactApiController::class, 'prospectForm'])->name('react-api.prospects.form');
        Route::get('/prospects/{prospect}', [ReactApiController::class, 'prospectDetail'])->name('react-api.prospects.show');
        Route::get('/prospects/{prospect}/timeline', [ReactApiController::class, 'prospectTimeline'])->name('react-api.prospects.timeline');
        Route::post('/prospects/{prospect}/logs', [ReactApiController::class, 'storeProspectLog'])->name('react-api.prospects.logs.store');
        Route::get('/pipeline', [ReactApiController::class, 'pipeline'])->name('react-api.pipeline');
        Route::get('/performance', [ReactApiController::class, 'performance'])->name('react-api.performance');
        Route::get('/objection-insights', [ReactApiController::class, 'objectionInsights'])->name('react-api.objection-insights');
        Route::get('/manager-insights', [ReactApiController::class, 'managerInsights'])->name('react-api.manager-insights');
        Route::get('/chat-reviews', [ReactApiController::class, 'chatReviews'])->name('react-api.chat-reviews');
        Route::get('/chat-reviews/form', [ReactApiController::class, 'chatReviewForm'])->name('react-api.chat-reviews.form');
        Route::get('/chat-reviews/{chatReview}', [ReactApiController::class, 'chatReviewDetail'])->name('react-api.chat-reviews.show');
        Route::get('/knowledge-queue', [ReactApiController::class, 'knowledgeQueue'])->name('react-api.knowledge-queue');
        Route::get('/users', [UserManagementController::class, 'index'])->name('react-api.users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('react-api.users.store');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('react-api.users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('react-api.users.destroy');
        Route::get('/user-master-data', [UserMasterDataController::class, 'index'])->name('react-api.user-master-data.index');
        Route::post('/user-master-data/roles', [UserMasterDataController::class, 'storeRole'])->name('react-api.user-master-data.roles.store');
        Route::patch('/user-master-data/roles/{role}', [UserMasterDataController::class, 'updateRole'])->name('react-api.user-master-data.roles.update');
        Route::delete('/user-master-data/roles/{role}', [UserMasterDataController::class, 'destroyRole'])->name('react-api.user-master-data.roles.destroy');
        Route::post('/user-master-data/units', [UserMasterDataController::class, 'storeUnit'])->name('react-api.user-master-data.units.store');
        Route::patch('/user-master-data/units/{unit}', [UserMasterDataController::class, 'updateUnit'])->name('react-api.user-master-data.units.update');
        Route::delete('/user-master-data/units/{unit}', [UserMasterDataController::class, 'destroyUnit'])->name('react-api.user-master-data.units.destroy');
        Route::post('/user-master-data/teams', [UserMasterDataController::class, 'storeTeam'])->name('react-api.user-master-data.teams.store');
        Route::patch('/user-master-data/teams/{team}', [UserMasterDataController::class, 'updateTeam'])->name('react-api.user-master-data.teams.update');
        Route::delete('/user-master-data/teams/{team}', [UserMasterDataController::class, 'destroyTeam'])->name('react-api.user-master-data.teams.destroy');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::patch('/prospects/{prospect}/quick-update', [ProspectController::class, 'quickUpdate'])->name('prospects.quick-update');
    Route::post('/chat-reviews/{chatReview}/manager-notes', [ChatReviewController::class, 'addManagerNote'])->name('chat-reviews.manager-note');
    Route::post('/chat-reviews/{chatReview}/knowledge-queue', [KnowledgeUpdateQueueController::class, 'store'])->name('knowledge-queue.store');
    Route::patch('/knowledge-queue/{knowledgeQueue}/review', [KnowledgeUpdateQueueController::class, 'setReview'])->name('knowledge-queue.set-review');
    Route::patch('/knowledge-queue/{knowledgeQueue}/approve', [KnowledgeUpdateQueueController::class, 'approve'])->name('knowledge-queue.approve');
    Route::patch('/knowledge-queue/{knowledgeQueue}/reject', [KnowledgeUpdateQueueController::class, 'reject'])->name('knowledge-queue.reject');

    Route::resource('prospects', ProspectController::class)
        ->except(['index', 'create', 'show', 'edit'])
        ->parameters(['prospects' => 'prospect']);
    Route::resource('chat-reviews', ChatReviewController::class)
        ->except(['index', 'create', 'show', 'edit'])
        ->parameters(['chat-reviews' => 'chatReview']);
});
