<?php
// views/measurement_inputs_ladies.php

$upper = isset($measurements['upper']) ? $measurements['upper'] : [];
$lower = isset($measurements['lower']) ? $measurements['lower'] : [];
?>

<!-- Ladies Upper Body Fields -->
<div style="margin-bottom: 25px;">
    <h4 style="color: var(--neon-pink); font-size: 15px; border-bottom: 1px solid rgba(255, 0, 127, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>👗 Ladies Upper Body Measurements</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">جسم کے اوپری حصے کا ناپ (زنانہ)</span>
    </h4>
    
    <div class="measurement-grid">
        <!-- 1. Length (Kameez / Shirt Length) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_length" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Length (Lambai)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">قمیض / قمیص کی لمبائی</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[length]" id="<?php echo $idPrefix; ?>l_up_length" class="ladies-input" value="<?php echo floatval($upper['length'] ?? 0); ?>">
        </div>

        <!-- 2. Shoulder (Teera) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_shoulder" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Shoulder (Teera)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">تیرا</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[shoulder]" id="<?php echo $idPrefix; ?>l_up_shoulder" class="ladies-input" value="<?php echo floatval($upper['shoulder'] ?? 0); ?>">
        </div>

        <!-- 3. Chest (Chaati) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_chest" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Chest (Chaati)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">چھاتی</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[chest]" id="<?php echo $idPrefix; ?>l_up_chest" class="ladies-input" value="<?php echo floatval($upper['chest'] ?? 0); ?>">
        </div>

        <!-- 4. Upper Chest -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_upper_chest" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Upper Chest</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">اوپری چھاتی</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[upper_chest]" id="<?php echo $idPrefix; ?>l_up_upper_chest" class="ladies-input" value="<?php echo floatval($upper['upper_chest'] ?? 0); ?>">
        </div>

        <!-- 5. Lower Chest / Bust -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_lower_chest" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Lower Chest / Bust</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">نچلی چھاتی</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[lower_chest]" id="<?php echo $idPrefix; ?>l_up_lower_chest" class="ladies-input" value="<?php echo floatval($upper['lower_chest'] ?? 0); ?>">
        </div>

        <!-- 6. Fitting (Waist) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_fitting" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Fitting (Waist)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">کمر کی فٹنگ</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[fitting]" id="<?php echo $idPrefix; ?>l_up_fitting" class="ladies-input" value="<?php echo floatval($upper['fitting'] ?? 0); ?>">
        </div>

        <!-- 7. Chowk (Side Slit) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_chowk" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Chowk (Side Slit)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">چاک</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[chowk]" id="<?php echo $idPrefix; ?>l_up_chowk" class="ladies-input" value="<?php echo floatval($upper['chowk'] ?? 0); ?>">
        </div>

        <!-- 8. Armhole (Mondha) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_armhole" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Armhole (Mondha)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">منڈھا</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[armhole]" id="<?php echo $idPrefix; ?>l_up_armhole" class="ladies-input" value="<?php echo floatval($upper['armhole'] ?? 0); ?>">
        </div>

        <!-- 9. Sleeve & Style -->
        <div class="measurement-input-wrapper" style="grid-column: span 2;">
            <label for="<?php echo $idPrefix; ?>l_up_sleeve" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Sleeve & Style (Bazu)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">بازو کی لمبائی اور اسٹائل</span>
            </label>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <input type="number" step="0.1" name="ladies_upper[sleeve]" id="<?php echo $idPrefix; ?>l_up_sleeve" class="ladies-input" value="<?php echo floatval($upper['sleeve'] ?? 0); ?>" style="width: 45%;">
                <select name="ladies_upper[sleeve_style]" id="<?php echo $idPrefix; ?>l_up_sleeve_style" class="form-control ladies-input" style="width: 55%; padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                    <option value="" <?php echo (empty($upper['sleeve_style'])) ? 'selected' : ''; ?>>- Style / اسٹائل -</option>
                    <option value="Straight" <?php echo (isset($upper['sleeve_style']) && $upper['sleeve_style'] === 'Straight') ? 'selected' : ''; ?>>Straight / سیدھا</option>
                    <option value="Cuff" <?php echo (isset($upper['sleeve_style']) && $upper['sleeve_style'] === 'Cuff') ? 'selected' : ''; ?>>Cuff / کف</option>
                    <option value="Bell Bottom" <?php echo (isset($upper['sleeve_style']) && $upper['sleeve_style'] === 'Bell Bottom') ? 'selected' : ''; ?>>Bell Bottom / بیل باٹم</option>
                    <option value="Elastic" <?php echo (isset($upper['sleeve_style']) && $upper['sleeve_style'] === 'Elastic') ? 'selected' : ''; ?>>Elastic / الیکٹک</option>
                </select>
            </div>
        </div>

        <!-- 10. Neck (Gala) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_neck" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Neck Size (Gala)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">گلا</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[neck]" id="<?php echo $idPrefix; ?>l_up_neck" class="ladies-input" value="<?php echo floatval($upper['neck'] ?? 0); ?>">
        </div>

        <!-- 11. Hem / Daman Width -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_hem_width" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Daman Width</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">دامن</span>
            </label>
            <input type="number" step="0.1" name="ladies_upper[hem_width]" id="<?php echo $idPrefix; ?>l_up_hem_width" class="ladies-input" value="<?php echo floatval($upper['hem_width'] ?? 0); ?>">
        </div>

        <!-- 12. Darts (Chunnat/Plates) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_darts" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Darts (Plates)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">ڈارٹس / پلیٹیں</span>
            </label>
            <select name="ladies_upper[darts]" id="<?php echo $idPrefix; ?>l_up_darts" class="form-control ladies-input" style="padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                <option value="No" <?php echo (!isset($upper['darts']) || $upper['darts'] === 'No') ? 'selected' : ''; ?>>No / نہیں</option>
                <option value="Yes" <?php echo (isset($upper['darts']) && $upper['darts'] === 'Yes') ? 'selected' : ''; ?>>Yes / ہاں</option>
                <option value="Front Only" <?php echo (isset($upper['darts']) && $upper['darts'] === 'Front Only') ? 'selected' : ''; ?>>Front Only / صرف سامنے</option>
                <option value="Back Only" <?php echo (isset($upper['darts']) && $upper['darts'] === 'Back Only') ? 'selected' : ''; ?>>Back Only / صرف پیچھے</option>
            </select>
        </div>

        <!-- 13. Shirt Cut -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_up_cut" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Shirt Cut</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">قمیض کا کٹ</span>
            </label>
            <select name="ladies_upper[cut]" id="<?php echo $idPrefix; ?>l_up_cut" class="form-control ladies-input" style="padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                <option value="Straight" <?php echo (!isset($upper['cut']) || $upper['cut'] === 'Straight') ? 'selected' : ''; ?>>Straight / سیدھا</option>
                <option value="A-Line" <?php echo (isset($upper['cut']) && $upper['cut'] === 'A-Line') ? 'selected' : ''; ?>>A-Line / اے لائن</option>
                <option value="Frock" <?php echo (isset($upper['cut']) && $upper['cut'] === 'Frock') ? 'selected' : ''; ?>>Frock / فراک</option>
                <option value="Kurti" <?php echo (isset($upper['cut']) && $upper['cut'] === 'Kurti') ? 'selected' : ''; ?>>Kurti / کرتی</option>
            </select>
        </div>
    </div>
</div>

<!-- Ladies Lower Body Fields -->
<div style="margin-bottom: 25px;">
    <h4 style="color: var(--neon-pink); font-size: 15px; border-bottom: 1px solid rgba(255, 0, 127, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>👖 Ladies Lower Body Measurements</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">جسم کے نچلے حصے کا ناپ (زنانہ)</span>
    </h4>
    
    <div class="measurement-grid">
        <!-- 1. Length & Type (Lambai) -->
        <div class="measurement-input-wrapper" style="grid-column: span 2;">
            <label for="<?php echo $idPrefix; ?>l_lo_length" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Length & Type (Lambai)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">شلوار کی لمبائی اور قسم</span>
            </label>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <input type="number" step="0.1" name="ladies_lower[length]" id="<?php echo $idPrefix; ?>l_lo_length" class="ladies-input" value="<?php echo floatval($lower['length'] ?? 0); ?>" style="width: 45%;">
                <select name="ladies_lower[length_type]" id="<?php echo $idPrefix; ?>l_lo_length_type" class="form-control ladies-input" style="width: 55%; padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                    <option value="" <?php echo (empty($lower['length_type'])) ? 'selected' : ''; ?>>- Type / قسم -</option>
                    <option value="Plain Shalwar" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Plain Shalwar') ? 'selected' : ''; ?>>Plain Shalwar / سادہ شلوار</option>
                    <option value="Belt Shalwar" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Belt Shalwar') ? 'selected' : ''; ?>>Belt Shalwar / بیلٹ شلوار</option>
                    <option value="Trouser" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Trouser') ? 'selected' : ''; ?>>Trouser / ٹراؤزر</option>
                    <option value="Capri" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Capri') ? 'selected' : ''; ?>>Capri / کیپری</option>
                    <option value="Other" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Other') ? 'selected' : ''; ?>>Other / دوسرا</option>
                </select>
            </div>
        </div>

        <!-- 2. Hips / Seat -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_lo_hips" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Hips / Seat</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">ہپ / نشست</span>
            </label>
            <input type="number" step="0.1" name="ladies_lower[hips]" id="<?php echo $idPrefix; ?>l_lo_hips" class="ladies-input" value="<?php echo floatval($lower['hips'] ?? 0); ?>">
        </div>

        <!-- 3. Rise (Asan) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_lo_rise" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Rise (Asan)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">آسن</span>
            </label>
            <input type="number" step="0.1" name="ladies_lower[rise]" id="<?php echo $idPrefix; ?>l_lo_rise" class="ladies-input" value="<?php echo floatval($lower['rise'] ?? 0); ?>">
        </div>

        <!-- 4. Bottom & Type (Paincha) -->
        <div class="measurement-input-wrapper" style="grid-column: span 2;">
            <label for="<?php echo $idPrefix; ?>l_lo_bottom_opening" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Bottom & Type (Paincha)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">پائنچہ کا سائز اور قسم</span>
            </label>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <input type="number" step="0.1" name="ladies_lower[bottom_opening]" id="<?php echo $idPrefix; ?>l_lo_bottom_opening" class="ladies-input" value="<?php echo floatval($lower['bottom_opening'] ?? 0); ?>" style="width: 45%;">
                <select name="ladies_lower[bottom_opening_type]" id="<?php echo $idPrefix; ?>l_lo_bottom_opening_type" class="form-control ladies-input" style="width: 55%; padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                    <option value="" <?php echo (empty($lower['bottom_opening_type'])) ? 'selected' : ''; ?>>- Type / قسم -</option>
                    <option value="Cut Work" <?php echo (isset($lower['bottom_opening_type']) && $lower['bottom_opening_type'] === 'Cut Work') ? 'selected' : ''; ?>>Cut Work / کٹ ورک</option>
                    <option value="Aged" <?php echo (isset($lower['bottom_opening_type']) && $lower['bottom_opening_type'] === 'Aged') ? 'selected' : ''; ?>>Aged / ایجڈ</option>
                    <option value="Other" <?php echo (isset($lower['bottom_opening_type']) && $lower['bottom_opening_type'] === 'Other') ? 'selected' : ''; ?>>Other / دوسرا</option>
                </select>
            </div>
        </div>

        <!-- 5. Inseam -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>l_lo_inseam" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Inseam</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">اندرونی لمبائی</span>
            </label>
            <input type="number" step="0.1" name="ladies_lower[inseam]" id="<?php echo $idPrefix; ?>l_lo_inseam" class="ladies-input" value="<?php echo floatval($lower['inseam'] ?? 0); ?>">
        </div>
    </div>
</div>

<!-- Measurement Notes -->
<div style="margin-bottom: 10px;">
    <h4 style="color: var(--neon-gold); font-size: 15px; border-bottom: 1px solid rgba(255, 184, 0, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>📝 Ladies Measurement Notes</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">پیمائش کے نوٹس / خاص ہدایات</span>
    </h4>
    <div class="form-group">
        <textarea name="ladies_measurement_notes" id="<?php echo $idPrefix; ?>l_measurement_notes" class="form-control ladies-input" rows="3" placeholder="Any specific requirements or things to keep in mind..."><?php echo htmlspecialchars($measurements['notes'] ?? ''); ?></textarea>
    </div>
</div>
