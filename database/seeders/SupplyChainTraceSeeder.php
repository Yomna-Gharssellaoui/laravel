<?php

namespace Database\Seeders;

use App\Models\SupplyChainTrace;
use Illuminate\Database\Seeder;

class SupplyChainTraceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SupplyChainTrace::factory()->count(40)->create();
    }
}
