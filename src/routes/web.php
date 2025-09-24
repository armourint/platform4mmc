<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Create as ProjectsCreate;
use App\Livewire\Assessments\Hub as AssessmentsHub;
use App\Livewire\Assessments\Index as AssessmentsIndex;
use App\Livewire\Assessments\ViabilityWizard;
use App\Livewire\Assessments\EnvironmentalForm;
use App\Livewire\Assessments\Results;
use App\Livewire\Knowledge\Index as KnowledgeIndex;

use App\Livewire\Admin\Imports;
use App\Http\Controllers\Admin\DataImportController;

// Admin Livewire pages
use App\Livewire\Admin\EnvLayersTable;
use App\Livewire\Admin\RulesTable;
use App\Livewire\Admin\ManufacturersTable;
use App\Livewire\Admin\ManufacturersMap;
// use App\Livewire\Admin\ProductsTable;
// use App\Livewire\Admin\Imports;

// Home → login or projects
Route::get('/', fn () => redirect()->route(auth()->check() ? 'projects.index' : 'login'));

if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}

Route::middleware(['auth'])->group(function () {
    // Optional alias for Breeze “Dashboard”
    Route::get('/dashboard', ProjectsIndex::class)->name('dashboard');

    // Projects
    Route::get('/projects', ProjectsIndex::class)->name('projects.index');
    Route::get('/projects/create', ProjectsCreate::class)->name('projects.create');

    // Assessments per project
    Route::get('/projects/{project}/assess', AssessmentsHub::class)->name('assessments.hub');
    Route::get('/projects/{project}/viability', ViabilityWizard::class)->name('assessments.viability');
    Route::get('/projects/{project}/environmental', EnvironmentalForm::class)->name('assessments.environmental');

    // Results
    Route::get('/assessments/{assessment}/results', Results::class)->name('assessments.results');

    // Index pages for nav
    Route::get('/assessments', AssessmentsIndex::class)->name('assessments.index');
    Route::get('/knowledge',   KnowledgeIndex::class)->name('knowledge.index');
});

/*
|--------------------------------------------------------------------------
| Admin routes (landing + new admin screens)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin landing (Overview)
        Route::view('/', 'admin.index')->name('index');

        // New Admin screens
        Route::get('/layers', EnvLayersTable::class)->name('layers');
        Route::get('/rules', RulesTable::class)->name('rules');
        Route::get('/manufacturers', ManufacturersTable::class)->name('manufacturers');
        Route::get('/manufacturers/map', ManufacturersMap::class)->name('manufacturers.map');

        Route::get('/imports', Imports::class)->name('imports');
        Route::get('/imports/{import}/download', [DataImportController::class, 'download'])
            ->name('imports.download')
            ->whereNumber('import');

        // (Optional legacy) redirect old datasets UI to landing
        Route::redirect('/datasets', '/admin')->name('datasets.legacy');
    });
