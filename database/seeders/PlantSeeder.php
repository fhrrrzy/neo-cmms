<?php

namespace Database\Seeders;

use App\Models\Plant;
use Illuminate\Database\Seeder;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = $this->readCsv(base_path('database/seeders/plant.csv'));

        foreach ($rows as $plant) {
            Plant::updateOrCreate(
                ['plant_code' => $plant['plant_code']],
                $plant
            );
        }
    }

    private function readCsv(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines) {
            return [];
        }

        // Remove header
        array_shift($lines);

        $rows = [];
        foreach ($lines as $line) {
            $cols = array_map('trim', explode(';', $line));
            if (count($cols) < 15) {
                continue;
            }

            [$plantId, $_ignoredId, $namaPks, $regionalId, $kaps, $fkKaps, $fkUtil, $unit, $bunch, $pln, $cofiring, $hiddenCost, $hiddenCpo, $jenis, $kapsSf] = $cols;

            $rows[] = [
                'plant_code' => $plantId,
                'regional_id' => (int) $regionalId,
                'name' => $namaPks,
                'kaps_terpasang' => (int) $kaps,
                'faktor_koreksi_kaps' => (int) $fkKaps,
                'faktor_koreksi_utilitas' => (int) $fkUtil,
                'unit' => (int) $unit,
                'instalasi_bunch_press' => (bool) $bunch,
                'pln_isasi' => (bool) $pln,
                'cofiring' => (bool) $cofiring,
                'hidden_pica_cost' => (bool) $hiddenCost,
                'hidden_pica_cpo' => (bool) $hiddenCpo,
                'jenis' => (int) $jenis,
                'kaps_terpasang_sf' => (int) $kapsSf,
                'is_active' => true,
            ];
        }

        return $rows;
    }
}
