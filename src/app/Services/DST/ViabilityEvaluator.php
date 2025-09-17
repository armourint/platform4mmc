<?php

namespace App\Services\DST;

use App\Models\DatasetVersion;
use App\Models\Rule;
use App\Models\System;

class ViabilityEvaluator
{
    public function evaluate(array $inputs, DatasetVersion $dv): array
    {
        $ctx = $this->canon($inputs);
        $systems = System::all()->keyBy('id');
        $rules = Rule::where('dataset_version_id',$dv->id)
            ->where('module','viability')
            ->orderByDesc('priority')->get();

        $per = $systems->map(fn()=> ['ok'=>true,'reason'=>null])->toArray();

        foreach ($rules as $r) {
            if ($this->matches($ctx, $r->conditions_json)) {
                if ($r->rule_type === 'exclude') {
                    $per[$r->system_id] = ['ok'=>false,'reason'=>$r->reason];
                }
            }
        }

        $ok = collect($per)->where('ok',true)->count();
        return ['score'=>['viability_score'=>$ok,'per_system'=>$per]];
    }

    private function matches(array $ctx, array $cond): bool
    {
        foreach ($cond as $key=>$rule) {
            $val = $ctx[$key] ?? null;
            if (!$this->matchOne($val,$rule)) return false;
        }
        return true;
    }

    private function matchOne($val, array $r): bool
    {
        if (isset($r['in'])) return in_array($val,$r['in'],true);
        if (isset($r['eq'])) return $val === $r['eq'];
        if (isset($r['lte'])) return is_numeric($val) && $val <= $r['lte'];
        if (isset($r['gte'])) return is_numeric($val) && $val >= $r['gte'];
        if (isset($r['contains'])) return is_array($val) && in_array($r['contains'],$val,true);
        return false;
    }

    private function canon(array $in): array
    {
        $norm = fn($v)=>is_string($v)? strtolower(trim($v)) : $v;
        return array_map(fn($v)=>is_array($v)? array_map($norm,$v) : $norm($v), $in);
    }
}
