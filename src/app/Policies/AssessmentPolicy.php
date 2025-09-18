<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    public function view(User $user, Assessment $assessment): bool
    {
        return $user->id === $assessment->project->owner_id;
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->id === $assessment->project->owner_id;
    }
}
