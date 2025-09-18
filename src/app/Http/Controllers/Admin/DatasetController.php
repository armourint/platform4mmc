<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatasetVersion;
use App\Models\Rule;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatasetController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->get('module', 'viability');

        $datasets = DatasetVersion::where('module', $module)
            ->orderByDesc('status')
            ->orderByDesc('id')
            ->get();

        return view('admin.datasets.index', compact('datasets', 'module'));
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'module'        => 'required|in:viability,environmental',
            'version_label' => 'required|string|max:100',
            'notes'         => 'nullable|string',
        ]);

        DatasetVersion::create([
            'module'        => $data['module'],
            'version_label' => $data['version_label'],
            'status'        => 'draft',
            'payload'       => ['notes' => $data['notes'] ?? null],
        ]);

        return back()->with('ok', 'Draft dataset created.');
    }

    public function publish(DatasetVersion $dataset)
    {
        DB::transaction(function () use ($dataset) {
            DatasetVersion::where('module', $dataset->module)
                ->where('status', 'published')
                ->update(['status' => 'archived']);

            $dataset->update(['status' => 'published']);
        });

        return back()->with('ok', 'Dataset published.');
    }

    public function archive(DatasetVersion $dataset)
    {
        $dataset->update(['status' => 'archived']);
        return back()->with('ok', 'Dataset archived.');
    }

    public function importRules(DatasetVersion $dataset, Request $request)
    {
        // Rules are only for Viability datasets
        if ($dataset->module !== 'viability') {
            return back()->withErrors(['rules_json' => 'Rules can only be imported for Viability datasets.']);
        }

        $data = $request->validate([
            'rules_json' => 'required|string',
        ]);

        $items = json_decode($data['rules_json'], true);
        if (!is_array($items)) {
            return back()->withErrors(['rules_json' => 'Invalid JSON. Expecting an array of rule objects.']);
        }

        DB::transaction(function () use ($dataset, $items) {
            foreach ($items as $i => $row) {
                // Minimal validation per row
                $code      = $row['system_code'] ?? null;
                $ruleType  = $row['rule_type']   ?? 'exclude';
                $priority  = (int)($row['priority'] ?? 100);
                $reason    = $row['reason']      ?? null;
                $conds     = $row['conditions']  ?? [];   // incoming key in JSON
                                                        // will be saved into conditions_json column

                if (!$code) {
                    throw new \RuntimeException("Row {$i}: missing system_code.");
                }

                $systemId = System::where('code', $code)->value('id');
                if (!$systemId) {
                    throw new \RuntimeException("Row {$i}: unknown system_code '{$code}'.");
                }

                Rule::create([
                    'module'             => $dataset->module,     // required, NOT NULL in your schema
                    'dataset_version_id' => $dataset->id,
                    'system_id'          => $systemId,
                    'priority'           => $priority,
                    'rule_type'          => $ruleType,
                    'conditions_json'    => $conds,               // <-- matches your model/DB column
                    'reason'             => $reason,
                ]);
            }
        });

        return back()->with('ok', 'Rules imported.');
    }

}
