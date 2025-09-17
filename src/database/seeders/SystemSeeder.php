<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('systems')->insert([
            ['code'=>'BLOCK','name'=>'Concrete Block'],
            ['code'=>'ICF','name'=>'Insulated Concrete Form'],
            ['code'=>'LGS','name'=>'Light Gauge Steel'],
            ['code'=>'TIMBER','name'=>'Timber Frame'],
        ]);

    }
}
