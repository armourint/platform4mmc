<?php

namespace App\Livewire\Admin;

use App\Models\Rule;
use App\Models\DatasetVersion;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RulesTable extends Component
{
    use WithPagination;

    #[Url] public ?int $datasetVersionId = null;
    #[Url] public ?string $systemCode = null;
    #[Url] public ?string $ruleType = null; // include|exclude
    #[Url] public string $search = '';

    protected function latestPublishedId(): ?int
    {
        return \App\Models\DatasetVersion::currentId('viability');
    }


    public function render()
    {
        $versionId = $this->datasetVersionId ?: $this->latestPublishedId();

        $q = Rule::query()
            ->when($versionId, fn($q)=>$q->where('dataset_version_id',$versionId))
            ->when($this->systemCode, fn($q)=>$q->where('system_code',$this->systemCode))
            ->when($this->ruleType, fn($q)=>$q->where('rule_type',$this->ruleType))
            ->when($this->search, fn($q)=>$q->where(function($q){
                $q->where('reason','like',"%{$this->search}%")
                  ->orWhere('module','like',"%{$this->search}%");
            }));

        return view('livewire.admin.rules-table', [
            'rules'    => $q->paginate(25),
            'versions' => DatasetVersion::query()->where('module','viability')->latest()->get(),
        ])->layout('admin');
    }
}
