<?php

namespace App\Livewire\Environmental;

use App\Services\Assessments\EnvironmentalCalculator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SystemCompare extends Component
{
    public int $datasetVersionId;
    public array $categories = [];        // UI labels
    public string $categoryKey = 'wall';  // wall|cladding|slab

    // Option A
    public ?string $mmcA = null;
    public ?string $systemCodeA = null;
    public array $snapA = [];

    // Option B
    public ?string $mmcB = null;
    public ?string $systemCodeB = null;
    public array $snapB = [];

    // Lists
    public array $mmcOptions = [];        // per category
    public array $systemsA = [];
    public array $systemsB = [];

    public function mount(EnvironmentalCalculator $calc): void
    {
        $this->datasetVersionId = $calc->latestPublishedDatasetId()
            ?? throw new \RuntimeException('No published environmental dataset found.');

        $this->categories = array_map(fn($c) => $c['label'], $calc->categories());

        // initialise lists
        $this->refreshLists($calc);

        // Preselect first MMC + first system for A/B (if present)
        if (!$this->mmcA && !empty($this->mmcOptions)) $this->mmcA = $this->mmcOptions[0];
        if (!$this->mmcB && !empty($this->mmcOptions)) $this->mmcB = $this->mmcOptions[0];

        $this->systemsA = $calc->systemsFor($this->datasetVersionId, $this->categoryKey, $this->mmcA);
        $this->systemsB = $calc->systemsFor($this->datasetVersionId, $this->categoryKey, $this->mmcB);

        if (!$this->systemCodeA && !empty($this->systemsA)) $this->systemCodeA = $this->systemsA[0]['code'];
        if (!$this->systemCodeB && !empty($this->systemsB)) $this->systemCodeB = $this->systemsB[0]['code'];

        $this->recompute();
    }

    public function updatedCategoryKey(EnvironmentalCalculator $calc): void
    {
        $this->refreshLists($calc);

        // reset selections per category
        $this->mmcA = $this->mmcOptions[0] ?? null;
        $this->mmcB = $this->mmcOptions[0] ?? null;

        $this->systemsA = $calc->systemsFor($this->datasetVersionId, $this->categoryKey, $this->mmcA);
        $this->systemsB = $calc->systemsFor($this->datasetVersionId, $this->categoryKey, $this->mmcB);

        $this->systemCodeA = $this->systemsA[0]['code'] ?? null;
        $this->systemCodeB = $this->systemsB[0]['code'] ?? null;

        $this->recompute();
    }

    public function updatedMmcA(EnvironmentalCalculator $calc): void
    {
        $this->systemsA = $calc->systemsFor($this->datasetVersionId, $this->categoryKey, $this->mmcA);
        $this->systemCodeA = $this->systemsA[0]['code'] ?? null;
        $this->recompute();
    }

    public function updatedMmcB(EnvironmentalCalculator $calc): void
    {
        $this->systemsB = $calc->systemsFor($this->datasetVersionId, $this->categoryKey, $this->mmcB);
        $this->systemCodeB = $this->systemsB[0]['code'] ?? null;
        $this->recompute();
    }

    public function updatedSystemCodeA(): void
    {
        $this->recompute();
    }

    public function updatedSystemCodeB(): void
    {
        $this->recompute();
    }

    protected function refreshLists(EnvironmentalCalculator $calc): void
    {
        $this->mmcOptions = $calc->mmcList($this->datasetVersionId, $this->categoryKey);
    }

    protected function recompute(): void
    {
        $calc = app(EnvironmentalCalculator::class);

        $this->snapA = ($this->systemCodeA)
            ? $calc->snapshotForSystem($this->datasetVersionId, $this->systemCodeA)
            : [];

        $this->snapB = ($this->systemCodeB)
            ? $calc->snapshotForSystem($this->datasetVersionId, $this->systemCodeB)
            : [];
    }

    /** Comparison derived metrics for table and charts. */
    public function getCompareData(): array
    {
        $ka = $this->snapA['kpi'] ?? [];
        $kb = $this->snapB['kpi'] ?? [];

        $ma = (float)($ka['mass_total_kg_m2'] ?? 0);
        $mb = (float)($kb['mass_total_kg_m2'] ?? 0);

        $a1a3a = (float)($ka['a1a3_total_kgco2e_m2'] ?? 0);
        $a1a3b = (float)($kb['a1a3_total_kgco2e_m2'] ?? 0);

        $cfa = $ma > 0 ? $a1a3a / $ma : 0;
        $cfb = $mb > 0 ? $a1a3b / $mb : 0;

        $delta = fn($b, $a) => $b - $a;
        $pct   = fn($b, $a) => $a != 0 ? ($b - $a) / $a * 100.0 : null;

        return [
            'table' => [
                'mass'     => ['a' => $ma, 'b' => $mb, 'd' => $delta($mb, $ma)],
                'a1a3'     => ['a' => $a1a3a, 'b' => $a1a3b, 'd' => $delta($a1a3b, $a1a3a)],
                'a4'       => ['a' => null, 'b' => null, 'd' => null], // placeholder
                'a1a4'     => ['a' => $a1a3a, 'b' => $a1a3b, 'd' => $delta($a1a3b, $a1a3a)],
                'cf_avg'   => ['a' => $cfa, 'b' => $cfb, 'd' => $delta($cfb, $cfa)],
            ],
            'scatter' => [
                'a' => ['x' => $ma, 'y' => $a1a3a],
                'b' => ['x' => $mb, 'y' => $a1a3b],
            ],
            'improve' => [
                'mass_pct' => $pct($mb, $ma),
                'a1a3_pct' => $pct($a1a3b, $a1a3a),
            ],
        ];
    }

    public function render()
    {
        $calc = app(EnvironmentalCalculator::class);
        $cats = $calc->categories();

        // expose category labels for view
        $categoryLabels = collect($cats)->map(fn($v, $k) => ['key' => $k, 'label' => $v['label']])->values()->all();

        return view('livewire.environmental.system-compare', [
            'categoryLabels' => $categoryLabels,
            'systemsA'       => $this->systemsA,
            'systemsB'       => $this->systemsB,
            'compare'        => $this->getCompareData(),
        ]);
    }
}
