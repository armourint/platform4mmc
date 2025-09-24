<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use App\Models\DatasetVersion;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EnvironmentalForm extends Component
{
    use AuthorizesRequests;

    public Project $project;

    // Carbon footprint (kgCO₂e)
    public ?float $a1_a3 = null; // Product Stage
    public ?float $a4_a5 = null; // Construction Stage
    public ?float $c1_c4 = null; // End-of-life Stage

    // Energy efficiency
    public ?float $u_value = null;       // W/m²K
    public ?string $ber_rating = null;   // A1..G

    // End-of-life recyclability (%)
    public ?float $reuse_potential = null;
    public ?float $material_recyclability = null;

    public string $saved_at = '';

    protected function rules(): array
    {
        return [
            'a1_a3'                  => ['nullable','numeric','min:0'],
            'a4_a5'                  => ['nullable','numeric','min:0'],
            'c1_c4'                  => ['nullable','numeric','min:0'],
            'u_value'                => ['nullable','numeric','min:0'],
            'ber_rating'             => ['nullable','in:A1,A2,A3,B1,B2,B3,C1,C2,C3,D1,D2,E1,E2,F,G'],
            'reuse_potential'        => ['nullable','numeric','min:0','max:100'],
            'material_recyclability' => ['nullable','numeric','min:0','max:100'],
        ];
    }

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;

        // Prefill from most recent environmental assessment, if any
        $latest = Assessment::query()
            ->where('project_id', $project->id)
            ->where('type', 'environmental')
            ->latest('id')
            ->first();

        if ($latest) {
            $in = (array) $latest->inputs;
            $this->a1_a3                  = data_get($in, 'a1_a3');
            $this->a4_a5                  = data_get($in, 'a4_a5');
            $this->c1_c4                  = data_get($in, 'c1_c4');
            $this->u_value                = data_get($in, 'u_value');
            $this->ber_rating             = data_get($in, 'ber_rating');
            $this->reuse_potential        = data_get($in, 'reuse_potential');
            $this->material_recyclability = data_get($in, 'material_recyclability');
        }
    }

    public function save(): void
    {
        $this->validate();

        // Ensure we have a published dataset for 'environmental'.
        $dv = $this->ensureEnvDataset();

        $inputs = [
            'a1_a3'                  => $this->a1_a3,
            'a4_a5'                  => $this->a4_a5,
            'c1_c4'                  => $this->c1_c4,
            'u_value'                => $this->u_value,
            'ber_rating'             => $this->ber_rating,
            'reuse_potential'        => $this->reuse_potential,
            'material_recyclability' => $this->material_recyclability,
        ];

        $total_co2 = (float) ($this->a1_a3 ?? 0)
                   + (float) ($this->a4_a5 ?? 0)
                   + (float) ($this->c1_c4 ?? 0);

        $outputs = [
            'total_co2' => round($total_co2, 3), // kgCO2e
        ];

        Assessment::create([
            'project_id'         => $this->project->id,
            'type'               => 'environmental',
            'dataset_version_id' => $dv->id,          // guaranteed here
            'inputs'             => $inputs,
            'outputs'            => $outputs,
            'status'             => 'completed',
        ]);

        $this->saved_at = now()->toDateTimeString();
        $this->dispatch('env-saved');
    }

    private function ensureEnvDataset(): DatasetVersion
    {
        // 1) Prefer a published one
        $dv = DatasetVersion::query()
            ->where('module', 'environmental')
            ->where('status', 'published')
            ->latest('id')
            ->first();

        // 2) Otherwise use any latest one
        if (!$dv) {
            $dv = DatasetVersion::query()
                ->where('module', 'environmental')
                ->latest('id')
                ->first();
        }

        // 3) If none exist, create a published placeholder for demo use
        if (!$dv) {
            $dv = DatasetVersion::create([
                'module'        => 'environmental',
                'version_label' => 'env-' . now()->format('Y.m.d-His'),
                'status'        => 'published',
                'notes'         => 'Auto-created by EnvironmentalForm (placeholder for demo).',
            ]);
        }

        // If we found a non-published one, promote it so results link to a published dataset
        if ($dv->status !== 'published') {
            $dv->update(['status' => 'published']);
        }

        return $dv->fresh();
    }

    public function getBerOptions(): array
    {
        return [
            'A1','A2','A3',
            'B1','B2','B3',
            'C1','C2','C3',
            'D1','D2',
            'E1','E2',
            'F','G',
        ];
    }

    public function render()
    {
        return view('livewire.assessments.environmental-form', [
            'project'    => $this->project,
            'berOptions' => $this->getBerOptions(),
        ]);
    }
}
