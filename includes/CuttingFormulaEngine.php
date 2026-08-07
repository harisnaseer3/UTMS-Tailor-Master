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
     * Apply Gents Cutting Formulas
     */
    private static function applyGentsFormulas($measurements) {
        $upper = $measurements['upper'] ?? [];
        $lower = $measurements['lower'] ?? [];

        // 1. Kameez Length Formula:
        // When Goal Daman is selected: Kameez Length + 1.25
        // When Choras Daman is selected: Kameez Length + 2
        if (isset($upper['length']) && floatval($upper['length']) > 0) {
            $damanStyle = $upper['daman_style'] ?? '';
            if (strcasecmp($damanStyle, 'Goal Daman') === 0) {
                $upper['length'] = floatval($upper['length']) + 1.25;
            } else if (strcasecmp($damanStyle, 'Choras Daman') === 0) {
                $upper['length'] = floatval($upper['length']) + 2;
            } else {
                // Default fallback if daman style not specified
                $upper['length'] = floatval($upper['length']) + 2;
            }
        }

        // 2. Gala (Neck / Collar) Formula: ((Gala + 1.25) / 3.14) / 2
        if (isset($upper['neck']) && floatval($upper['neck']) > 0) {
            $upper['neck'] = ((floatval($upper['neck']) + 1.25) / 3.14) / 2;
        }

        // 3. Teera Formula: (Teera / 2) + 0.5
        if (isset($upper['shoulder']) && floatval($upper['shoulder']) > 0) {
            $upper['shoulder'] = (floatval($upper['shoulder']) / 2) + 0.5;
        }

        // 3. Chest (Chaati) Formula: Chest / 4
        if (isset($upper['chest']) && floatval($upper['chest']) > 0) {
            $upper['chest'] = floatval($upper['chest']) / 4;
        }

        // 4. Kamar (Waist / Fitting) Formula: (Kamar / 2) + 0.5
        if (isset($upper['fitting']) && floatval($upper['fitting']) > 0) {
            $upper['fitting'] = (floatval($upper['fitting']) / 2) + 0.5;
        }

        // 5. Kameez Width Formula: (Kameez Width / 2) + 0.5
        if (isset($upper['kameez_width']) && floatval($upper['kameez_width']) > 0) {
            $upper['kameez_width'] = (floatval($upper['kameez_width']) / 2) + 0.5;
        }

        // 6. Daman Width Formula: (Daman Width / 2) + 0.25
        if (isset($upper['hem_width']) && floatval($upper['hem_width']) > 0) {
            $upper['hem_width'] = (floatval($upper['hem_width']) / 2) + 0.25;
        }

        // 7. Pancha (Bottom Opening) Formula: Pancha + 0.5
        if (isset($lower['bottom_opening']) && floatval($lower['bottom_opening']) > 0) {
            $lower['bottom_opening'] = floatval($lower['bottom_opening']) + 0.5;
        }

        // 8. Sleeve (Bazu) Formula:
        // - When Khula Bazu selected: Sleeve + 1.5
        // - Otherwise (all cuff types except Khula Bazu):
        //     - If cuff size is less than 8 (minimum of 8 condition): Sleeve - 1
        //     - If cuff size is 8 or more (8 or more condition): Sleeve - 1.25
        if (isset($upper['sleeve']) && floatval($upper['sleeve']) > 0) {
            $sleeveVal = floatval($upper['sleeve']);
            $sleeveStyle = trim($upper['sleeve_style'] ?? '');
            $cuffSize = floatval($upper['cuff_size'] ?? 0);

            if (strcasecmp($sleeveStyle, 'Khula Bazu') === 0) {
                $upper['sleeve'] = $sleeveVal + 1.5;
            } else {
                if ($cuffSize >= 8) {
                    $upper['sleeve'] = $sleeveVal - 1.25;
                } else {
                    $upper['sleeve'] = $sleeveVal - 1;
                }
            }
        }

        // 9. Armhole Formulas for Gents:
        // Bazu Armhole = Chest (Chaati) / 4
        // Kameez Armhole:
        // - Part 1: (Kameez Width / 2) + 0.5   (which is Kameez Width Formula Ans)
        // - Part 2: Teera initial value / 2
        // - Part 3: Part 1 - Part 2
        // - Kameez Armhole: Bazu Armhole Ans - Part 3
        $rawChest = floatval($measurements['upper']['chest'] ?? 0);
        $rawTeera = floatval($measurements['upper']['shoulder'] ?? 0);
        $rawKameezWidth = floatval($measurements['upper']['kameez_width'] ?? 0);

        $bazuArmholeAns = ($rawChest > 0) ? ($rawChest / 4) : ($upper['chest'] ?? 0);
        $upper['bazu_armhole'] = $bazuArmholeAns;

        if ($rawKameezWidth > 0 && $rawTeera > 0 && $bazuArmholeAns > 0) {
            $part1 = ($rawKameezWidth / 2) + 0.5;
            $part2 = $rawTeera / 2;
            $part3 = $part1 - $part2;
            $upper['kameez_armhole'] = $bazuArmholeAns - $part3;
        } else {
            $upper['kameez_armhole'] = 0;
        }

        return [
            'upper' => $upper,
            'lower' => $lower,
            'notes' => $measurements['notes'] ?? ''
        ];
    }
}
