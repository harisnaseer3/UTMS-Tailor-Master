<?php
// includes/CuttingFormulaEngine.php

class CuttingFormulaEngine {
    
    /**
     * Applies cutting formulas to the measurements based on gender.
     * Original measurements are not modified in the database.
     * 
     * @param string $gender The customer's gender ('female', 'ladies', 'woman', etc.)
     * @param array $measurements The decoded measurements array (e.g. ['upper' => [...], 'lower' => [...]])
     * @return array The modified measurements array
     */
    public static function applyFormulas($gender, $measurements) {
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['female', 'ladies', 'woman'])) {
            return self::applyLadiesFormulas($measurements);
        } else if (in_array($gender, ['male', 'gents', 'man'])) {
            return self::applyGentsFormulas($measurements);
        }
        
        return $measurements;
    }

    /**
     * Apply Ladies Cutting Formulas
     */
    private static function applyLadiesFormulas($measurements) {
        $upper = $measurements['upper'] ?? [];
        $lower = $measurements['lower'] ?? [];

        // 1. Kameez Length: +1
        if (isset($upper['length']) && floatval($upper['length']) > 0) {
            $upper['length'] = floatval($upper['length']) + 1;
        }

        // 2. Sleeve Length (Bazu)
        if (isset($upper['sleeve']) && floatval($upper['sleeve']) > 0) {
            $sleeveStyle = $upper['sleeve_style'] ?? '';
            if (strcasecmp($sleeveStyle, 'Cuff') === 0) {
                // Cuff Sleeve: Bazu - 1
                $upper['sleeve'] = floatval($upper['sleeve']) - 1;
            } else {
                // Other sleeve types: Bazu + 1
                $upper['sleeve'] = floatval($upper['sleeve']) + 1;
            }
        }

        // 3. Teera: (Teera + 1) / 2
        if (isset($upper['shoulder']) && floatval($upper['shoulder']) > 0) {
            $upper['shoulder'] = (floatval($upper['shoulder']) + 1) / 2;
        }

        // 4. Chest (Chati): (Chest + 4) / 2
        if (isset($upper['chest']) && floatval($upper['chest']) > 0) {
            $upper['chest'] = (floatval($upper['chest']) + 4) / 2;
        }

        // 5. Fitting: (Fitting + 4) / 2
        if (isset($upper['fitting']) && floatval($upper['fitting']) > 0) {
            $upper['fitting'] = (floatval($upper['fitting']) + 4) / 2;
        }

        // 6. Hip Width: (Hip Width + 1.5) / 2
        if (isset($lower['hips']) && floatval($lower['hips']) > 0) {
            $lower['hips'] = (floatval($lower['hips']) + 1.5) / 2;
        }

        // 7. Shalwar Length: Shalwar Length + 3
        if (isset($lower['length']) && floatval($lower['length']) > 0) {
            $lower['length'] = floatval($lower['length']) + 3;
        }

        // 8. Pancha: Pancha + 1
        if (isset($lower['bottom_opening']) && floatval($lower['bottom_opening']) > 0) {
            $lower['bottom_opening'] = floatval($lower['bottom_opening']) + 1;
        }

        // 9. Inseam:
        if (isset($lower['inseam']) && floatval($lower['inseam']) > 0) {
            $shalwarType = $lower['length_type'] ?? '';
            if (strcasecmp($shalwarType, 'Trouser') === 0) {
                $lower['inseam'] = floatval($lower['inseam']) + 1;
            } else {
                $lower['inseam'] = floatval($lower['inseam']) + 0.5;
            }
        }

        // 10. Rise (Asan): Rise + 3
        if (isset($lower['rise']) && floatval($lower['rise']) > 0) {
            $lower['rise'] = floatval($lower['rise']) + 3;
        }

        return [
            'upper' => $upper,
            'lower' => $lower,
            'notes' => $measurements['notes'] ?? ''
        ];
    }

    /**
     * Apply Gents Cutting Formulas (To be implemented later)
     */
    private static function applyGentsFormulas($measurements) {
        return $measurements;
    }
}
