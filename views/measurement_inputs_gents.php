<?php
// views/measurement_inputs_gents.php

$upper = isset($measurements['upper']) ? $measurements['upper'] : [];
$lower = isset($measurements['lower']) ? $measurements['lower'] : [];
?>

<!-- Gents Upper Body Fields -->
<div style="margin-bottom: 25px;">
    <h4 style="color: var(--neon-cyan); font-size: 15px; border-bottom: 1px solid rgba(0, 240, 255, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>📐 Gents Upper Body Measurements</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">جسم کے اوپری حصے کا ناپ (مردانہ)</span>
    </h4>
    
    <div class="measurement-grid">
        <!-- 1. Kameez Length & Daman Style -->
        <div class="measurement-input-wrapper" style="grid-column: span 2;">
            <label for="<?php echo $idPrefix; ?>g_up_length" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Kameez Length & Daman Style</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">قمیض کی لمبائی اور دامن</span>
            </label>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <input type="number" step="0.1" name="gents_upper[length]" id="<?php echo $idPrefix; ?>g_up_length" class="gents-input" value="<?php echo floatval($upper['length'] ?? 0); ?>" style="width: 45%;">
                <select name="gents_upper[daman_style]" id="<?php echo $idPrefix; ?>g_up_daman_style" class="form-control gents-input" style="width: 55%; padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                    <option value="" <?php echo (empty($upper['daman_style'])) ? 'selected' : ''; ?>>- Daman / دامن -</option>
                    <option value="Goal Daman" <?php echo (isset($upper['daman_style']) && $upper['daman_style'] === 'Goal Daman') ? 'selected' : ''; ?>>Goal Daman / گول دامن</option>
                    <option value="Choras Daman" <?php echo (isset($upper['daman_style']) && $upper['daman_style'] === 'Choras Daman') ? 'selected' : ''; ?>>Choras Daman / چورس دامن</option>
                </select>
            </div>
        </div>

        <!-- 2. Kameez Width (Kameez Chorai) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_up_kameez_width" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Kameez Width (Kameez Chorai)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">قمیض کی چوڑائی</span>
            </label>
            <input type="number" step="0.1" name="gents_upper[kameez_width]" id="<?php echo $idPrefix; ?>g_up_kameez_width" class="gents-input" value="<?php echo floatval($upper['kameez_width'] ?? 0); ?>">
        </div>

        <!-- 3. Daman Width (Daman Chorai) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_up_hem_width" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Daman Width (Daman Chorai)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">دامن کی چوڑائی</span>
            </label>
            <input type="number" step="0.1" name="gents_upper[hem_width]" id="<?php echo $idPrefix; ?>g_up_hem_width" class="gents-input" value="<?php echo floatval($upper['hem_width'] ?? 0); ?>">
        </div>

        <!-- 4. Gala Dropdown & Size -->
        <div class="measurement-input-wrapper" style="grid-column: span 2;">
            <label for="<?php echo $idPrefix; ?>g_up_neck" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Gala (Neck / Collar Style & Size)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">گلا / بین / کالر کی قسم اور سائز</span>
            </label>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <input type="number" step="0.1" name="gents_upper[neck]" id="<?php echo $idPrefix; ?>g_up_neck" class="gents-input" value="<?php echo floatval($upper['neck'] ?? 0); ?>" style="width: 45%;" placeholder="Size">
                <select name="gents_upper[gala_style]" id="<?php echo $idPrefix; ?>g_up_gala_style" class="form-control gents-input" style="width: 55%; padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                    <option value="" <?php echo (empty($upper['gala_style'])) ? 'selected' : ''; ?>>- Gala Style / گلا -</option>
                    <option value="Ban" <?php echo (isset($upper['gala_style']) && $upper['gala_style'] === 'Ban') ? 'selected' : ''; ?>>Ban / بین</option>
                    <option value="Collar" <?php echo (isset($upper['gala_style']) && $upper['gala_style'] === 'Collar') ? 'selected' : ''; ?>>Color / کالر</option>
                    <option value="Maghfi" <?php echo (isset($upper['gala_style']) && $upper['gala_style'] === 'Maghfi') ? 'selected' : ''; ?>>Maghfi / مغفی</option>
                    <option value="Teera Ban" <?php echo (isset($upper['gala_style']) && $upper['gala_style'] === 'Teera Ban') ? 'selected' : ''; ?>>Teera Ban / تیرا بین</option>
                    <option value="Fix Collar" <?php echo (isset($upper['gala_style']) && $upper['gala_style'] === 'Fix Collar') ? 'selected' : ''; ?>>Fix Color / فکس کالر</option>
                </select>
            </div>
        </div>

        <!-- 5. Sleeve (Bazu) Length -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_up_sleeve" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Sleeve (Bazu) Length</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">بازو لمبائی</span>
            </label>
            <input type="number" step="0.1" name="gents_upper[sleeve]" id="<?php echo $idPrefix; ?>g_up_sleeve" class="gents-input" value="<?php echo floatval($upper['sleeve'] ?? 0); ?>">
        </div>

        <!-- 6. Shoulder (Teera) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_up_shoulder" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Shoulder (Teera)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">تیرا</span>
            </label>
            <input type="number" step="0.1" name="gents_upper[shoulder]" id="<?php echo $idPrefix; ?>g_up_shoulder" class="gents-input" value="<?php echo floatval($upper['shoulder'] ?? 0); ?>">
        </div>

        <!-- 7. Chest (Chati) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_up_chest" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Chest (Chaati)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">چھاتی</span>
            </label>
            <input type="number" step="0.1" name="gents_upper[chest]" id="<?php echo $idPrefix; ?>g_up_chest" class="gents-input" value="<?php echo floatval($upper['chest'] ?? 0); ?>">
        </div>

        <!-- 8. Waist / Fitting (Kamar) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_up_fitting" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Waist / Fitting (Kamar)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">کمر / فٹنگ</span>
            </label>
            <input type="number" step="0.1" name="gents_upper[fitting]" id="<?php echo $idPrefix; ?>g_up_fitting" class="gents-input" value="<?php echo floatval($upper['fitting'] ?? 0); ?>">
        </div>

        <!-- 9. Kuff (Cuff Style) Dropdown & Size -->
        <div class="measurement-input-wrapper" style="grid-column: span 2;">
            <label for="<?php echo $idPrefix; ?>g_up_cuff_size" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Kuff Style & Size</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">کف کا ڈیزائن اور سائز</span>
            </label>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <input type="number" step="0.1" name="gents_upper[cuff_size]" id="<?php echo $idPrefix; ?>g_up_cuff_size" class="gents-input" value="<?php echo floatval($upper['cuff_size'] ?? 0); ?>" style="width: 45%;" placeholder="Size">
                <select name="gents_upper[sleeve_style]" id="<?php echo $idPrefix; ?>g_up_sleeve_style" class="form-control gents-input" style="width: 55%; padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                    <option value="" <?php echo (empty($upper['sleeve_style'])) ? 'selected' : ''; ?>>- Kuff Style / کف -</option>
                    <option value="Stud Cuff" <?php echo (isset($upper['sleeve_style']) && $upper['sleeve_style'] === 'Stud Cuff') ? 'selected' : ''; ?>>Stud Cuff / اسٹڈ کف</option>
                    <option value="Khula Bazu" <?php echo (isset($upper['sleeve_style']) && $upper['sleeve_style'] === 'Khula Bazu') ? 'selected' : ''; ?>>Khula Bazu / کھلا بازو</option>
                    <option value="No Stud" <?php echo (isset($upper['sleeve_style']) && $upper['sleeve_style'] === 'No Stud') ? 'selected' : ''; ?>>No Stud / بغیر اسٹڈ</option>
                    <option value="Kaj" <?php echo (isset($upper['sleeve_style']) && $upper['sleeve_style'] === 'Kaj') ? 'selected' : ''; ?>>Kaj / کاج</option>
                </select>
            </div>
        </div>

        <!-- 10. Front Patti Lambai -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_up_patti_length" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Front Patti Length</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">سامنے پٹی لمبائی</span>
            </label>
            <input type="number" step="0.1" name="gents_upper[patti_length]" id="<?php echo $idPrefix; ?>g_up_patti_length" class="gents-input" value="<?php echo floatval($upper['patti_length'] ?? 0); ?>">
        </div>

        <!-- 11. Pocket Selection -->
        <div class="measurement-input-wrapper" style="grid-column: span 3;">
            <label style="min-height: 24px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px; margin-bottom: 5px;">
                <span>Pocket Options (جیبیں - Multiple Selection)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">جیب کے مختلف آپشنز منتخب کریں</span>
            </label>
            <?php 
                $selectedPockets = isset($upper['pockets']) ? (is_array($upper['pockets']) ? $upper['pockets'] : explode(',', $upper['pockets'])) : [];
            ?>
            <div style="display: flex; gap: 15px; justify-content: center; align-items: center; background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); flex-wrap: wrap;">
                <label style="font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px; margin: 0; font-weight: normal !important;">
                    <input type="checkbox" name="gents_upper[pockets][]" class="gents-input" value="Front Pocket" <?php echo in_array('Front Pocket', $selectedPockets) ? 'checked' : ''; ?>> Front Pocket / سامنے جیب
                </label>
                <label style="font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px; margin: 0; font-weight: normal !important;">
                    <input type="checkbox" name="gents_upper[pockets][]" class="gents-input side-pocket-cb" value="1 Side Pocket" <?php echo in_array('1 Side Pocket', $selectedPockets) ? 'checked' : ''; ?> onclick="if(this.checked){ document.querySelectorAll('.side-pocket-cb').forEach(c => { if(c !== this) c.checked = false; }); }"> 1 Side Pocket / ۱ سائیڈ جیب
                </label>
                <label style="font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px; margin: 0; font-weight: normal !important;">
                    <input type="checkbox" name="gents_upper[pockets][]" class="gents-input side-pocket-cb" value="2 Side Pockets" <?php echo in_array('2 Side Pockets', $selectedPockets) ? 'checked' : ''; ?> onclick="if(this.checked){ document.querySelectorAll('.side-pocket-cb').forEach(c => { if(c !== this) c.checked = false; }); }"> 2 Side Pockets / ۲ سائیڈ جیبیں
                </label>
                <label style="font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px; margin: 0; font-weight: normal !important;">
                    <input type="checkbox" name="gents_upper[pockets][]" class="gents-input" value="Shalwar Pocket" <?php echo in_array('Shalwar Pocket', $selectedPockets) ? 'checked' : ''; ?>> Shalwar Pocket / شلوار جیب
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Gents Lower Body Fields -->
<div style="margin-bottom: 25px;">
    <h4 style="color: var(--neon-cyan); font-size: 15px; border-bottom: 1px solid rgba(0, 240, 255, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>👖 Gents Lower Body Measurements</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">جسم کے نچلے حصے کا ناپ (مردانہ)</span>
    </h4>
    
    <div class="measurement-grid">
        <!-- 1. Length & Shalwar Type -->
        <div class="measurement-input-wrapper" style="grid-column: span 2;">
            <label for="<?php echo $idPrefix; ?>g_lo_length" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Shalwar Length & Type</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">شلوار کی لمبائی اور قسم</span>
            </label>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <input type="number" step="0.1" name="gents_lower[length]" id="<?php echo $idPrefix; ?>g_lo_length" class="gents-input" value="<?php echo floatval($lower['length'] ?? 0); ?>" style="width: 45%;">
                <select name="gents_lower[length_type]" id="<?php echo $idPrefix; ?>g_lo_length_type" class="form-control gents-input" style="width: 55%; padding: 2px; font-size: 13px; text-align: center; border:none; height:28px; margin: 0; background: rgba(0, 0, 0, 0.4); color: white;">
                    <option value="" <?php echo (empty($lower['length_type'])) ? 'selected' : ''; ?>>- Shalwar Type / قسم -</option>
                    <option value="Normal Shalwar" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Normal Shalwar') ? 'selected' : ''; ?>>Normal Shalwar / نارمل شلوار</option>
                    <option value="Patyala" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Patyala') ? 'selected' : ''; ?>>Patyala / پٹیالہ</option>
                    <option value="Choti Shalwar" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Choti Shalwar') ? 'selected' : ''; ?>>Choti Shalwar / چھوٹی شلوار</option>
                    <option value="Trouser" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Trouser') ? 'selected' : ''; ?>>Trouser / ٹراؤزر</option>
                    <option value="Pajama Shalwar" <?php echo (isset($lower['length_type']) && $lower['length_type'] === 'Pajama Shalwar') ? 'selected' : ''; ?>>Pajama Shalwar / پاجامہ شلوار</option>
                </select>
            </div>
        </div>

        <!-- 2. Inseam -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_lo_inseam" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Inseam</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">اندرونی لمبائی</span>
            </label>
            <input type="number" step="0.1" name="gents_lower[inseam]" id="<?php echo $idPrefix; ?>g_lo_inseam" class="gents-input" value="<?php echo floatval($lower['inseam'] ?? 0); ?>">
        </div>

        <!-- 3. Pancha (Bottom Opening) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_lo_bottom_opening" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Paincha (Pancha)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">پائنچہ</span>
            </label>
            <input type="number" step="0.1" name="gents_lower[bottom_opening]" id="<?php echo $idPrefix; ?>g_lo_bottom_opening" class="gents-input" value="<?php echo floatval($lower['bottom_opening'] ?? 0); ?>">
        </div>

        <!-- 4. Rise (Asan) -->
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>g_lo_rise" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Rise (Asan)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">آسن</span>
            </label>
            <input type="number" step="0.1" name="gents_lower[rise]" id="<?php echo $idPrefix; ?>g_lo_rise" class="gents-input" value="<?php echo floatval($lower['rise'] ?? 0); ?>">
        </div>
    </div>
</div>

<!-- Measurement Notes -->
<div style="margin-bottom: 10px;">
    <h4 style="color: var(--neon-gold); font-size: 15px; border-bottom: 1px solid rgba(255, 184, 0, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>📝 Gents Measurement Notes</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">پیمائش کے نوٹس / خاص ہدایات</span>
    </h4>
    <div class="form-group">
        <textarea name="gents_measurement_notes" id="<?php echo $idPrefix; ?>g_measurement_notes" class="form-control gents-input" rows="3" placeholder="Any specific requirements or things to keep in mind..."><?php echo htmlspecialchars($measurements['notes'] ?? ''); ?></textarea>
    </div>
</div>
