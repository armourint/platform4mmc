<?php

namespace App\Livewire\Admin;

use App\Jobs\ProcessDataImport;
use App\Models\DataImport;
use App\Models\DatasetVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('admin')]
class Imports extends Component
{
    use WithFileUploads, WithPagination;

    public array $modules = [];

    public ?string $module = null;
    public $file;
    public ?string $versionLabel = null;
    public ?string $notes = null;
    public bool $resetBefore = false;
    public bool $verbose = false;
    public ?string $sheet = null; // optional override for environmental

    #[Url] public string $status = '';
    #[Url] public string $search = '';

    public function mount(): void
    {
        $cfg = config('mmc_imports', []);
        $this->modules = collect($cfg)->mapWithKeys(fn($v, $k) => [$k => $v['label'] ?? $k])->all();
    }

    protected function rules(): array
    {
        return [
            'module'       => ['required', Rule::in(array_keys($this->modules))],
            'file'         => ['required', 'file', 'mimes:xlsx,csv,txt,json', 'max:51200'],
            'versionLabel' => ['nullable', 'string', 'max:50'],
            'notes'        => ['nullable', 'string', 'max:255'],
            'resetBefore'  => ['boolean'],
            'verbose'      => ['boolean'],
            'sheet'        => ['nullable','string','max:50'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $disk = 'public';
        $dir  = 'imports/'.($this->module ?? 'misc');
        $storedPath = $this->file->store($dir, $disk);

        $label = $this->versionLabel ?: 'v'.now()->format('Y.m.d-His');
        $dataset = DatasetVersion::create([
            'module'        => $this->module,
            'version_label' => $label,
            'status'        => 'draft',
            'notes'         => $this->notes,
        ]);

        $metaExtra = [];
        if ($this->sheet && $this->module === 'environmental') {
            $metaExtra['--sheet'] = $this->sheet;
        }

        $import = DataImport::create([
            'module' => $this->module,
            'dataset_version_id' => $dataset->id,
            'user_id' => Auth::id(),
            'original_name' => $this->file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $storedPath,
            'status' => 'queued',
            'meta' => [
                'reset'      => $this->resetBefore,
                'verbose'    => $this->verbose,
                'extra_args' => $metaExtra,
                'mime'       => $this->file->getMimeType(),
                'size'       => $this->file->getSize(),
            ],
        ]);

        dispatch(new ProcessDataImport($import));

        $this->reset(['file', 'versionLabel', 'notes', 'resetBefore', 'verbose', 'sheet']);
        session()->flash('ok', "Import queued for {$this->modules[$this->module]} — dataset draft {$label} created.");

        $this->dispatch('$refresh');
    }

    public function retry(int $id): void
    {
        $import = DataImport::findOrFail($id);
        $import->update(['status' => 'queued', 'error' => null]);
        dispatch(new ProcessDataImport($import));
        session()->flash('ok', 'Import re-queued.');
        $this->dispatch('$refresh');
    }

    public function makeCurrent(int $datasetVersionId): void
    {
        DatasetVersion::makeCurrent($datasetVersionId);
        session()->flash('ok', "Dataset #{$datasetVersionId} is now the current version for its module.");
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $q = DataImport::query()
            ->when($this->status, fn($q)=>$q->where('status',$this->status))
            ->when($this->search, fn($q)=>$q->where(function($q){
                $q->where('original_name','like',"%{$this->search}%")
                  ->orWhere('module','like',"%{$this->search}%");
            }))
            ->latest();

        $imports = $q->paginate(15);

        // Poll while jobs are active or just-finished
        $active = DataImport::whereIn('status', ['queued','processing'])->exists();
        $recentlyCompleted = DataImport::where('status','completed')
            ->where('updated_at','>=',now()->subSeconds(6))->exists();
        $shouldPoll = $active || $recentlyCompleted;

        // Current id per module (authoritative)
        $modules = array_keys(config('mmc_imports', []));
        $moduleCurrents = [];
        foreach ($modules as $m) {
            $moduleCurrents[$m] = DatasetVersion::currentId($m);
        }

        return view('livewire.admin.imports', [
            'imports'        => $imports,
            'supportsReset'  => (bool) data_get(config("mmc_imports.{$this->module}"), 'supports_reset', false),
            'shouldPoll'     => $shouldPoll,
            'moduleCurrents' => $moduleCurrents,
        ]);
    }
}
