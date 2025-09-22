<?php

namespace Database\Seeders;

use App\Models\Plant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plants = $this->getPlantData();

        foreach ($plants as $plant) {
            Plant::updateOrCreate(
                ['plant_code' => $plant['plant_code']],
                $plant
            );
        }
    }

    private function getPlantData(): array
    {
        return [
            // Regional 1 - Kebun
            ['plant_code' => '1E02', 'name' => 'KEBUN SEI DAUN', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E03', 'name' => 'KEBUN TORGAMBA', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E05', 'name' => 'KEBUN SEI BARUHUR', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E06', 'name' => 'KEBUN SEI KEBARA', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E07', 'name' => 'KEBUN AEK TOROP', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E08', 'name' => 'KEBUN PIR AEK RASO', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E09', 'name' => 'KEBUN SISUMUT', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E10', 'name' => 'KEBUN AEK NABARA UTARA', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E11', 'name' => 'KEBUN AEK NABARA SELATAN', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E12', 'name' => 'KEBUN RANTAU PRAPAT', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E13', 'name' => 'KEBUN MEMBANG MUDA', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E14', 'name' => 'KEBUN LABUHAN HAJI', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E15', 'name' => 'KEBUN MERBAU SELATAN', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E16', 'name' => 'KEBUN SEI DADAP', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E17', 'name' => 'KEBUN PULAU MANDI', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E18', 'name' => 'KEBUN AMBALUTU', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E19', 'name' => 'KEBUN SEI SILAU', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E20', 'name' => 'KEBUN HUTA PADANG', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E21', 'name' => 'KEBUN BANDAR SELAMAT', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E22', 'name' => 'KEBUN DUSUN HULU', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E23', 'name' => 'KEBUN BANDAR BETSY', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E24', 'name' => 'KEBUN BANGUN', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E25', 'name' => 'KEBUN GUNUNG PAMELA', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E26', 'name' => 'KEBUN GUNUNG MONACO', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E27', 'name' => 'KEBUN SILAU DUNIA', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E28', 'name' => 'KEBUN GUNUNG PARA', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E29', 'name' => 'KEBUN SEI PUTIH', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E30', 'name' => 'KEBUN SARANG GITING', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E31', 'name' => 'KEBUN TANAH RAJA', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E32', 'name' => 'KEBUN RAMBUTAN', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E33', 'name' => 'KEBUN HAPESONG', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '1E34', 'name' => 'KEBUN BATANG TORU', 'description' => 'Kebun', 'is_active' => true],

            // Regional 1 - Pabrik Sawit
            ['plant_code' => '1F02', 'name' => 'PABRIK SEI DAUN', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F03', 'name' => 'PABRIK TORGAMBA', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F04', 'name' => 'PABRIK SEI BARUHUR', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F05', 'name' => 'PABRIK AEK TOROP', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F06', 'name' => 'PABRIK AEK RASO', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F07', 'name' => 'PABRIK SISUMUT', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F08', 'name' => 'PABRIK AEK NABARA SELATAN', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F09', 'name' => 'PABRIK SEI SILAU', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F10', 'name' => 'PABRIK SEI MANGKEI', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F11', 'name' => 'PABRIK RAMBUTAN', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F12', 'name' => 'PABRIK HAPESONG', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F13', 'name' => 'PPIS SEI MANGKEI', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F14', 'name' => 'PLTBS SEI MANGKEI', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '1F15', 'name' => 'KAWASAN INDUSTRI SEI MANGKEI', 'description' => 'PB Sawit', 'is_active' => true],

            // Regional 1 - Pabrik Karet
            ['plant_code' => '1F16', 'name' => 'PABRIK KARET RANTAU PRAPAT', 'description' => 'PB Karet', 'is_active' => true],
            ['plant_code' => '1F17', 'name' => 'PABRIK KARET MEMBANG MUDA', 'description' => 'PB Karet', 'is_active' => true],
            ['plant_code' => '1F18', 'name' => 'PABRIK KARET SEI SILAU', 'description' => 'PB Karet', 'is_active' => true],
            ['plant_code' => '1F19', 'name' => 'PABRIK KARET BANDAR BETSY', 'description' => 'PB Karet', 'is_active' => true],
            ['plant_code' => '1F20', 'name' => 'PABRIK KARET GUNUNG PARA', 'description' => 'PB Karet', 'is_active' => true],
            ['plant_code' => '1F21', 'name' => 'PABRIK KARET SARANG GITING', 'description' => 'PB Karet', 'is_active' => true],
            ['plant_code' => '1F22', 'name' => 'PABRIK KARET HAPESONG', 'description' => 'PB Karet', 'is_active' => true],
            ['plant_code' => '1F23', 'name' => 'PABRIK KARET RAMBUTAN', 'description' => 'PB Karet', 'is_active' => true],

            // Regional 1 - Pelabuhan
            ['plant_code' => '1R00', 'name' => 'REGIONAL 1', 'description' => 'Pelabuhan', 'is_active' => true],

            // Sample from other regions (you can expand this)
            ['plant_code' => '2E01', 'name' => 'KEBUN BAH JAMBI', 'description' => 'Kebun', 'is_active' => true],
            ['plant_code' => '2F01', 'name' => 'PABRIK BAH JAMBI', 'description' => 'PB Sawit', 'is_active' => true],
            ['plant_code' => '2R00', 'name' => 'REGIONAL 2', 'description' => 'Pelabuhan', 'is_active' => true],

            // Add more regions as needed...
        ];
    }
}
