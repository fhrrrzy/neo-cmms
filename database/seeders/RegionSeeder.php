<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'no' => 1, 'name' => 'Regional I Eks N3, DSMTU, N8', 'category' => 'palmco'],
            ['id' => 2, 'no' => 2, 'name' => 'Regional II  Eks N2,N4, N14', 'category' => 'palmco'],
            ['id' => 3, 'no' => 3, 'name' => 'Regional III Eks N5', 'category' => 'palmco'],
            ['id' => 4, 'no' => 4, 'name' => 'Regional IV Eks N6', 'category' => 'palmco'],
            ['id' => 5, 'no' => 5, 'name' => 'Regional V Eks N13', 'category' => 'palmco'],
            ['id' => 6, 'no' => 6, 'name' => 'Regional VI Eks N1', 'category' => 'supporting_co'],
            ['id' => 7, 'no' => 7, 'name' => 'Regional VII Eks N7', 'category' => 'supporting_co'],
        ];

        foreach ($rows as $row) {
            Region::updateOrCreate(['id' => $row['id']], [
                'no' => $row['no'],
                'name' => $row['name'],
                'category' => $row['category'],
            ]);
        }
    }
}
