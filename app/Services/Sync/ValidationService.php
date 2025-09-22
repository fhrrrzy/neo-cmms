<?php

namespace App\Services\Sync;

use App\Models\ApiSyncLog;
use Illuminate\Support\Facades\Log;
use Exception;

class ValidationService
{
    /**
     * Validate equipment data integrity.
     *
     * @param array $equipment
     * @return array Validation results
     */
    public function validateEquipmentData(array $equipment): array
    {
        $errors = [];
        $warnings = [];

        // Required field validation
        $requiredFields = ['EQUNR', 'SWERK', 'BUKRS'];
        foreach ($requiredFields as $field) {
            if (!isset($equipment[$field]) || empty($equipment[$field])) {
                $errors[] = "Missing required field: $field";
            }
        }

        // Data type validation
        if (isset($equipment['EQUNR']) && strlen($equipment['EQUNR']) > 50) {
            $errors[] = "Equipment number exceeds maximum length (50 characters)";
        }

        if (isset($equipment['SWERK']) && strlen($equipment['SWERK']) > 50) {
            $errors[] = "Plant code exceeds maximum length (50 characters)";
        }

        if (isset($equipment['BUKRS']) && strlen($equipment['BUKRS']) > 50) {
            $errors[] = "Company code exceeds maximum length (50 characters)";
        }

        // Business logic validation
        if (isset($equipment['CREATED_AT'])) {
            try {
                \Carbon\Carbon::parse($equipment['CREATED_AT']);
            } catch (Exception $e) {
                $warnings[] = "Invalid created_at date format: " . $equipment['CREATED_AT'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate running time data integrity.
     *
     * @param array $runningTime
     * @return array Validation results
     */
    public function validateRunningTimeData(array $runningTime): array
    {
        $errors = [];
        $warnings = [];

        // Required field validation
        $requiredFields = ['EQUNR', 'SWERK', 'DATE', 'RECDV', 'CNTRR'];
        foreach ($requiredFields as $field) {
            if (!isset($runningTime[$field]) || $runningTime[$field] === null || $runningTime[$field] === '') {
                $errors[] = "Missing required field: $field";
            }
        }

        // Data type validation
        if (isset($runningTime['RECDV'])) {
            if (!is_numeric($runningTime['RECDV'])) {
                $errors[] = "Running hours must be numeric";
            } elseif ((float) $runningTime['RECDV'] < 0) {
                $errors[] = "Running hours cannot be negative";
            } elseif ((float) $runningTime['RECDV'] > 24) {
                $warnings[] = "Running hours exceeds 24 hours in a day";
            }
        }

        if (isset($runningTime['CNTRR'])) {
            if (!is_numeric($runningTime['CNTRR'])) {
                $errors[] = "Cumulative hours must be numeric";
            } elseif ((float) $runningTime['CNTRR'] < 0) {
                $errors[] = "Cumulative hours cannot be negative";
            }
        }

        // Date validation
        if (isset($runningTime['DATE'])) {
            try {
                $date = \Carbon\Carbon::parse($runningTime['DATE']);
                if ($date->isFuture()) {
                    $warnings[] = "Running time date is in the future";
                }
                if ($date->lt(\Carbon\Carbon::now()->subYears(5))) {
                    $warnings[] = "Running time date is more than 5 years old";
                }
            } catch (Exception $e) {
                $errors[] = "Invalid date format: " . $runningTime['DATE'];
            }
        }

        // Business logic validation
        if (isset($runningTime['RECDV']) && isset($runningTime['CNTRR'])) {
            if (is_numeric($runningTime['RECDV']) && is_numeric($runningTime['CNTRR'])) {
                if ((float) $runningTime['CNTRR'] < (float) $runningTime['RECDV']) {
                    $errors[] = "Cumulative hours cannot be less than daily running hours";
                }
            }
        }

        // Date time validation
        if (isset($runningTime['DATE_TIME'])) {
            try {
                \Carbon\Carbon::parse($runningTime['DATE_TIME']);
            } catch (Exception $e) {
                $warnings[] = "Invalid date_time format: " . $runningTime['DATE_TIME'];
            }
        }

        // API created at validation
        if (isset($runningTime['CREATED_AT'])) {
            try {
                \Carbon\Carbon::parse($runningTime['CREATED_AT']);
            } catch (Exception $e) {
                $warnings[] = "Invalid created_at date format: " . $runningTime['CREATED_AT'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate cumulative hours progression for equipment.
     *
     * @param string $equipmentNumber
     * @param string $date
     * @param float $cumulativeHours
     * @return array Validation results
     */
    public function validateCumulativeHoursProgression(string $equipmentNumber, string $date, float $cumulativeHours): array
    {
        $errors = [];
        $warnings = [];

        try {
            // Find equipment
            $equipment = \App\Models\Equipment::where('equipment_number', $equipmentNumber)->first();
            
            if (!$equipment) {
                $errors[] = "Equipment not found: $equipmentNumber";
                return [
                    'valid' => false,
                    'errors' => $errors,
                    'warnings' => $warnings,
                ];
            }

            // Get the previous day's record
            $previousRecord = \App\Models\EquipmentRunningTime::where('equipment_id', $equipment->id)
                ->where('date', '<', $date)
                ->orderBy('date', 'desc')
                ->first();

            if ($previousRecord) {
                if ($cumulativeHours < $previousRecord->cumulative_hours) {
                    // Check if this is a significant regression
                    $difference = $previousRecord->cumulative_hours - $cumulativeHours;
                    if ($difference > 100) { // Significant regression threshold
                        $errors[] = "Significant cumulative hours regression detected: $difference hours";
                    } else {
                        $warnings[] = "Minor cumulative hours regression detected: $difference hours";
                    }
                }

                // Check for unrealistic daily increase
                $dailyIncrease = $cumulativeHours - $previousRecord->cumulative_hours;
                if ($dailyIncrease > 24) {
                    $warnings[] = "Daily cumulative hours increase exceeds 24 hours: $dailyIncrease hours";
                }
            }

            // Get the next day's record (if updating historical data)
            $nextRecord = \App\Models\EquipmentRunningTime::where('equipment_id', $equipment->id)
                ->where('date', '>', $date)
                ->orderBy('date', 'asc')
                ->first();

            if ($nextRecord && $cumulativeHours > $nextRecord->cumulative_hours) {
                $errors[] = "Cumulative hours cannot exceed future records";
            }

        } catch (Exception $e) {
            $errors[] = "Error validating cumulative hours progression: " . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate API response structure.
     *
     * @param array $response
     * @param string $expectedType 'equipment' or 'running_time'
     * @return array Validation results
     */
    public function validateApiResponse(array $response, string $expectedType): array
    {
        $errors = [];
        $warnings = [];

        // Check for data key
        if (!isset($response['data'])) {
            $errors[] = "Missing 'data' key in API response";
            return [
                'valid' => false,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        if (!is_array($response['data'])) {
            $errors[] = "API response 'data' must be an array";
            return [
                'valid' => false,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        // Check if data is empty
        if (empty($response['data'])) {
            $warnings[] = "API response contains no data";
        }

        // Validate structure based on type
        if (!empty($response['data'])) {
            $sampleRecord = $response['data'][0];
            
            if ($expectedType === 'equipment') {
                $requiredFields = ['EQUNR', 'SWERK', 'BUKRS'];
            } else {
                $requiredFields = ['EQUNR', 'SWERK', 'DATE', 'RECDV', 'CNTRR'];
            }

            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $sampleRecord)) {
                    $errors[] = "Missing required field in sample record: $field";
                }
            }
        }

        // Check for metadata
        if (isset($response['meta'])) {
            if (!isset($response['meta']['total'])) {
                $warnings[] = "Missing 'total' in response metadata";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Log validation results.
     *
     * @param array $validation
     * @param string $context
     * @param array $data
     */
    public function logValidationResults(array $validation, string $context, array $data = []): void
    {
        if (!$validation['valid']) {
            Log::error("Validation failed for $context", [
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
                'data' => $data,
            ]);
        } elseif (!empty($validation['warnings'])) {
            Log::warning("Validation warnings for $context", [
                'warnings' => $validation['warnings'],
                'data' => $data,
            ]);
        }
    }
}