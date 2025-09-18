<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\Project;
use App\Policies\AssessmentPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Project::class    => ProjectPolicy::class,
        Assessment::class => AssessmentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
