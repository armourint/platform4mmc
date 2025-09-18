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

    public $a1_a3, $a4_a5, $c1_c4;
    public $reuse_pct, $recycle_pct, $area_m2;

    public $u_value, $ber_rating; // new, optional

    protected function rules(): array
    {
        return [
            'a1_a3' => ['required','numeric','min:0'],
            'a4_a5' => ['required','numeric','min:0'],
            'c1_c4' => ['required','numeric','min:0'],
            'reuse_pct'   => ['nullable','numeric','between:0,100'],
            'recycle_pct' => ['nullable','numeric','between:0,100'],
            'area_m2'     => ['nullable','numeric','min:0.01'],
            'u_value'     => ['nullable','numeric','min:0'],
            'ber_rating'  => ['nullable','string','max:4'],
        ];
    }

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    public function calculate()
    {
        $this->validate();

        $dv = DatasetVersion::where('module','environmental')
            ->where('status','published')
            ->latest('id')
            ->first();

        if (!$dv) {
            $this->addError('a1_a3', 'No published dataset for Environmental. Ask an admin to publish one.');
            return;
        }

        $total = (float)$this->a1_a3 + (float)$this->a4_a5 + (float)$this->c1_c4;
        $per_m2 = $this->area_m2 ? round($total / (float)$this->area_m2, 3) : null;

        $inputs = [
            'a1_a3' => (float)$this->a1_a3,
            'a4_a5' => (float)$this->a4_a5,
            'c1_c4' => (float)$this->c1_c4,
            'reuse_pct'   => $this->reuse_pct !== null ? (float)$this->reuse_pct : null,
            'recycle_pct' => $this->recycle_pct !== null ? (float)$this->recycle_pct : null,
            'area_m2'     => $this->area_m2 !== null ? (float)$this->area_m2 : null,
            'u_value'     => $this->u_value !== null ? (float)$this->u_value : null,
            'ber_rating'  => $this->ber_rating,
        ];

        $outputs = [
            'total'     => $total,
            'per_m2'    => $per_m2,
            'breakdown' => [
                'A1-A3' => (float)$this->a1_a3,
                'A4-A5' => (float)$this->a4_a5,
                'C1-C4' => (float)$this->c1_c4,
            ],
            'dataset_label' => $dv->version_label,
        ];

        $assessment = Assessment::create([
            'project_id'         => $this->project->id,
            'type'               => 'environmental',
            'dataset_version_id' => $dv->id,
            'inputs'             => $inputs,
            'outputs'            => $outputs,
            'status'             => 'completed',
        ]);

        return $this->redirectRoute('assess.environmental.result', $assessment);
    }

    public function render()
    {
        return view('livewire.assessments.environmental-form', ['project'=>$this->project]);
    }
}
