<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use App\Models\DatasetVersion;
use App\Models\System;
use App\Services\DST\ViabilityEvaluator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Results extends Component
{
    public Assessment $assessment;

    /** Parsed evaluation for rendering */
    public array $summary = [];
    /** @var array<string, array{status:string,reasons:array}> */
    public array $systems = [];
    /** @var array<string, array<int, array{id:int,type:string,reason:?string}>> */
    public array $matchedRules = [];

    /** name lookup cache */
    protected array $systemNamesByCode = [];

    public function mount(Assessment $assessment): void
    {
        $this->assessment = $assessment;

        if ($assessment->type !== 'viability') {
            // For now this page is focused on viability outputs
            $this->summary = ['status' => 'N/A', 'include_count' => 0, 'exclude_count' => 0];
            $this->systems = [];
            $this->matchedRules = [];
            return;
        }

        // Use the dataset version that was used when the assessment was created
        $dv = DatasetVersion::findOrFail($assessment->dataset_version_id);

        // Re-evaluate with current evaluator using the stored inputs
        /** @var ViabilityEvaluator $evaluator */
        $evaluator = app(ViabilityEvaluator::class);
        $result = $evaluator->evaluate((array) ($assessment->inputs ?? []), $dv);

        $this->summary      = (array) ($result['summary'] ?? []);
        $this->systems      = (array) ($result['systems'] ?? []);
        $this->matchedRules = (array) ($result['matched_rules'] ?? []);

        // Preload system names
        $this->systemNamesByCode = System::query()
            ->get(['code', 'name'])
            ->mapWithKeys(fn ($s) => [$s->code => $s->name])
            ->all();
    }

    public function render()
    {
        // Split systems into included vs excluded, preserve order by name
        $included = collect($this->systems)
            ->map(fn ($v, $code) => ['code' => $code, 'name' => $this->nameFor($code)] + $v)
            ->filter(fn ($row) => ($row['status'] ?? '') === 'included')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $excluded = collect($this->systems)
            ->map(fn ($v, $code) => [
                'code' => $code,
                'name' => $this->nameFor($code),
                'reasons' => (array) ($v['reasons'] ?? []),
            ])
            ->filter(function ($row) {
                // Exclude “included” from this list; also skip if no reasons
                $status = $this->systems[$row['code']]['status'] ?? '';
                return $status === 'excluded';
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('livewire.assessments.results', [
            'summary'  => $this->summary,
            'included' => $included,   // Collection of ['code','name','status','reasons']
            'excluded' => $excluded,   // Collection of ['code','name','reasons']
        ]);
    }

    private function nameFor(string $systemCode): string
    {
        return $this->systemNamesByCode[$systemCode] ?? $this->pretty($systemCode);
    }

    private function pretty(string $code): string
    {
        // e.g. "CONCRETE_BLOCK" | "concrete-block" -> "Concrete Block"
        $code = str_replace(['_', '-'], ' ', $code);
        return mb_convert_case($code, MB_CASE_TITLE, 'UTF-8');
    }
}
