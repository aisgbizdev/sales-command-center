<?php

namespace App\Providers;

use App\Models\ChatReview;
use App\Models\KnowledgeUpdateQueue;
use App\Models\Prospect;
use App\Policies\ChatReviewPolicy;
use App\Policies\KnowledgeUpdateQueuePolicy;
use App\Policies\ProspectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Prospect::class, ProspectPolicy::class);
        Gate::policy(ChatReview::class, ChatReviewPolicy::class);
        Gate::policy(KnowledgeUpdateQueue::class, KnowledgeUpdateQueuePolicy::class);

        Gate::define('access-performance', fn ($user) => $user->isSuperAdmin() || $user->isKepala() || $user->isManager());
        Gate::define('access-chat-reviews', fn ($user) => $user->isSuperAdmin() || $user->isKepala() || $user->isManager());
        Gate::define('access-knowledge-queue', fn ($user) => $user->isSuperAdmin() || $user->isKepala());
        Gate::define('manage-users', fn ($user) => $user->isSuperAdmin());
        Gate::define('manage-system-settings', fn ($user) => $user->isSuperAdmin());
    }
}
