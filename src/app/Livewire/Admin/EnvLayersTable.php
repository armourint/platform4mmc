<?php

namespace App\Livewire\Admin;

use App\Models\EnvironmentalLayer;
use App\Models\DatasetVersion;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EnvLayersTable extends Component
{
    use WithPagination;

    #[Url] public ?int $datasetVersionId = null;
    #[Url] public ?string $systemCategory = null;
    #[Url] public ?string $systemCode = null;
    #[Url] public string $search = '';
    #[Url] public string $sort = 'system_code';
    #[Url] public string $dir = 'asc';

    public function sortBy(string $col): void
    {
        $this->dir  = ($this->sort === $col && $this->dir === 'asc') ? 'desc' : 'asc';
        $this->sort = $col;
        $this->resetPage();
    }

    protected function latestPublishedId(string $module = 'environmental'): ?int
    {
        return \App\Models\DatasetVersion::currentId($module);
    }


    public function render()
    {
        $versionId = $this->datasetVersionId ?: $this->latestPublishedId();

        $q = EnvironmentalLayer::query()
            ->when($versionId, fn($q) => $q->where('dataset_version_id', $versionId))
            ->when($this->systemCategory, fn($q) => $q->where('system_category', $this->systemCategory))
            ->when($this->systemCode, fn($q) => $q->where('system_code', $this->systemCode))
            ->when($this->search, fn($q) => $q->where(function($q){
                $q->where('assembly_id','like',"%{$this->search}%")
                  ->orWhere('system_name','like',"%{$this->search}%");
            }))
            ->orderBy($this->sort, $this->dir);

        return view('livewire.admin.env-layers-table', [
            'layers'   => $q->paginate(25),
            'versions' => DatasetVersion::query()->where('module','environmental')->latest()->get(),
        ])->layout('admin');
    }
}
