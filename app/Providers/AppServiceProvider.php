<?php

namespace App\Providers;

use App\Models\Opportunity;
use App\Models\Organization;
use App\Policies\OpportunityPolicy;
use App\Policies\OrganizationPolicy;
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
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
    }
}
