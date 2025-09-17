<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Create as ProjectsCreate;
use App\Livewire\Assessments\Hub as AssessmentsHub;
use App\Livewire\Assessments\Index as AssessmentsIndex;
use App\Livewire\Knowledge\Index as KnowledgeIndex;
use App\Livewire\Assessments\ViabilityWizard;
use App\Livewire\Assessments\EnvironmentalForm;
use App\Livewire\Assessments\Results;

// Home → login/dashboard depending on auth
Route::get('/', fn () => redirect()->route(auth()->check() ? 'projects.index' : 'login'));

if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}

Route::middleware(['auth'])->group(function () {
    Route::get('/projects', ProjectsIndex::class)->name('projects.index');
    Route::get('/projects/create', ProjectsCreate::class)->name('projects.create');

    Route::get('/projects/{project}/assess', AssessmentsHub::class)->name('assessments.hub');
    Route::get('/projects/{project}/viability', ViabilityWizard::class)->name('assessments.viability');
    Route::get('/projects/{project}/environmental', EnvironmentalForm::class)->name('assessments.environmental');

    Route::get('/assessments/{assessment}/results', Results::class)->name('assessments.results');
    Route::get('/assessments', AssessmentsIndex::class)->name('assessments.index');
    Route::get('/knowledge', KnowledgeIndex::class)->name('knowledge.index');
});
