<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

// Public Knowledge (must be above auth group)
use App\Livewire\Knowledge\Index as KnowledgeIndex;
use App\Livewire\Knowledge\Show as KnowledgeShow;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('projects.index')
        : redirect()->route('login');
})->name('home');

Route::get('/knowledge', KnowledgeIndex::class)->name('knowledge.index');
Route::get('/knowledge/{article:slug}', KnowledgeShow::class)->name('knowledge.show');

// Auth scaffolding
if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}

/*
|--------------------------------------------------------------------------
| Authenticated app routes
|--------------------------------------------------------------------------
*/

use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Create as ProjectsCreate;
use App\Livewire\Assessments\Hub as AssessmentsHub;
use App\Livewire\Assessments\Index as AssessmentsIndex;
use App\Livewire\Assessments\ViabilityWizard;
use App\Livewire\Assessments\EnvironmentalForm;
use App\Livewire\Assessments\Results;
use App\Livewire\Environmental\SystemBrowser;
use App\Livewire\Environmental\SystemCompare;

Route::middleware(['auth'])->group(function () {
    // Optional alias for Breeze “Dashboard”
    Route::get('/dashboard', ProjectsIndex::class)->name('dashboard');

    // Projects
    Route::get('/projects', ProjectsIndex::class)->name('projects.index');
    Route::get('/projects/create', ProjectsCreate::class)->name('projects.create');

    // Assessments per project (DST)
    Route::get('/projects/{project}/assess', AssessmentsHub::class)->name('assessments.hub');
    Route::get('/projects/{project}/viability', ViabilityWizard::class)->name('assessments.viability');
    Route::get('/projects/{project}/environmental', EnvironmentalForm::class)->name('assessments.environmental');

    // Results
    Route::get('/assessments/{assessment}/results', Results::class)->name('assessments.results');

    // Index page for nav
    Route::get('/assessments', AssessmentsIndex::class)->name('assessments.index');

    // Environmental System Browser (comparison UI)
    //Route::get('/environmental/browser', SystemBrowser::class)->name('environmental.browser');
    Route::get('/environmental/browser', SystemCompare::class)->name('environmental.browser');
});

/*
|--------------------------------------------------------------------------
| Admin routes (landing + data + knowledge CMS)
|--------------------------------------------------------------------------
*/

use App\Livewire\Admin\Imports;
use App\Http\Controllers\Admin\DataImportController;
use App\Livewire\Admin\EnvLayersTable;
use App\Livewire\Admin\RulesTable;
use App\Livewire\Admin\ManufacturersTable;
use App\Livewire\Admin\ManufacturersMap;

// Knowledge CMS (admin)
use App\Livewire\Admin\Knowledge\ArticleList   as AdminArticleList;
use App\Livewire\Admin\Knowledge\CreateArticle as AdminCreateArticle;
use App\Livewire\Admin\Knowledge\EditArticle   as AdminEditArticle;

use App\Livewire\Admin\Knowledge\CreateCategory as AdminCreateCategory;
use App\Livewire\Admin\Knowledge\EditCategory   as AdminEditCategory;
use App\Livewire\Admin\Knowledge\CategoryList   as AdminCategoryList;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin landing (Overview)
        Route::view('/', 'admin.index')->name('index');

        // Data management
        Route::get('/layers', EnvLayersTable::class)->name('layers');
        Route::get('/rules', RulesTable::class)->name('rules');
        Route::get('/manufacturers', ManufacturersTable::class)->name('manufacturers');
        Route::get('/manufacturers/map', ManufacturersMap::class)->name('manufacturers.map');

        // Imports
        Route::get('/imports', Imports::class)->name('imports');
        Route::get('/imports/{import}/download', [DataImportController::class, 'download'])
            ->name('imports.download')
            ->whereNumber('import');

        // Knowledge CMS
        Route::get('/knowledge/ping', fn () => 'admin-knowledge-ok')->name('knowledge.ping');

        Route::prefix('knowledge')->name('knowledge.')->group(function () {
            // Landing alias → Articles index
            Route::get('/', function () {
                return redirect()->route('admin.knowledge.articles.index');
            })->name('index');

            // Articles
            Route::prefix('articles')->name('articles.')->group(function () {
                Route::get('/', AdminArticleList::class)->name('index');                // /admin/knowledge/articles
                Route::get('/create', AdminCreateArticle::class)->name('create');       // /admin/knowledge/articles/create
                Route::get('/{article:id}/edit', AdminEditArticle::class)               // /admin/knowledge/articles/123/edit
                    ->whereNumber('article')
                    ->name('edit');
            });

            // Categories
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', AdminCategoryList::class)->name('index');               // /admin/knowledge/categories
                Route::get('/create', AdminCreateCategory::class)->name('create');      // /admin/knowledge/categories/create
                Route::get('/{category:id}/edit', AdminEditCategory::class)             // /admin/knowledge/categories/123/edit
                    ->whereNumber('category')
                    ->name('edit');
            });
        });

        // (Optional legacy) redirect old datasets UI to landing
        Route::redirect('/datasets', '/admin')->name('datasets.legacy');
    });
