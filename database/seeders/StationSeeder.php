<?php

namespace Database\Seeders;

use App\Models\Plant;
use App\Models\Station;
use Illuminate\Database\Seeder;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stations = [
            ['code' => 'STAS01', 'description' => 'Jembatan Timbang'],
            ['code' => 'STAS02', 'description' => 'Loading Ramp'],
            ['code' => 'STAS03', 'description' => 'Sterilizer'],
            ['code' => 'STAS04', 'description' => 'Rail Track'],
            ['code' => 'STAS05', 'description' => 'Thresser & Hoisting'],
            ['code' => 'STAS06', 'description' => 'Pressan'],
            ['code' => 'STAS07', 'description' => 'Klarifikasi'],
            ['code' => 'STAS08', 'description' => 'Pengolahan Inti Sawi'],
            ['code' => 'STAS09', 'description' => 'Boiler'],
            ['code' => 'STAS10', 'description' => 'Pengolahan Air'],
            ['code' => 'STAS11', 'description' => 'Kamar Mesin'],
            ['code' => 'STAS12', 'description' => 'Tangki Timbun dan Ke'],
            ['code' => 'STAS13', 'description' => 'Limbah'],
            ['code' => 'STAS14', 'description' => 'Empty Bunch Hopper'],
            ['code' => 'STAS19', 'description' => 'Laboratorium'],
        ];

        foreach (Plant::all() as $plant) {
            foreach ($stations as $s) {
                Station::updateOrCreate(
                    [
                        'plant_id' => $plant->id,
                        'cost_center' => $plant->plant_code . $s['code'],
                    ],
                    [
                        'description' => $s['description'],
                        'keterangan' => 'OBJEK',
                    ]
                );
            }
        }
    }
}
