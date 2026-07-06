<?php
// views/measurement_inputs.php

// Ensure measurements variables exist
$upper = isset($measurements['upper']) ? $measurements['upper'] : [];
$lower = isset($measurements['lower']) ? $measurements['lower'] : [];
?>

<style>
.measurement-input-wrapper label {
    font-weight: 700 !important;
}
.measurement-input-wrapper label span:first-child {
    font-size: 13px !important;
    font-weight: 800 !important;
    color: var(--text-primary) !important;
}
.measurement-input-wrapper label span:last-child {
    font-size: 11px !important;
    font-weight: 700 !important;
    color: var(--neon-cyan) !important;
}
body.light-theme .measurement-input-wrapper label span:last-child {
    color: var(--neon-orchid) !important;
}
</style>

<!-- Upper Body Fields -->
<div style="margin-bottom: 25px;">
    <h4 style="color: var(--neon-cyan); font-size: 15px; border-bottom: 1px solid rgba(0, 240, 255, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>📐 Upper Body Measurements</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">جسم کے اوپری حصے کا ناپ</span>
    </h4>
    
    <div class="measurement-grid">
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_length" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Length (Lambai)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">لمبائی</span>
            </label>
            <input type="number" step="0.1" name="upper[length]" id="<?php echo $idPrefix; ?>up_length" value="<?php echo floatval($upper['length'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_shoulder" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Shoulder (Teera)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">تیرا</span>
            </label>
            <input type="number" step="0.1" name="upper[shoulder]" id="<?php echo $idPrefix; ?>up_shoulder" value="<?php echo floatval($upper['shoulder'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_chest" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Chest/Bust (Cheeti)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">چھاتی</span>
            </label>
            <input type="number" step="0.1" name="upper[chest]" id="<?php echo $idPrefix; ?>up_chest" value="<?php echo floatval($upper['chest'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_armhole" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Armhole (Monda)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">مونڈھا</span>
            </label>
            <input type="number" step="0.1" name="upper[armhole]" id="<?php echo $idPrefix; ?>up_armhole" value="<?php echo floatval($upper['armhole'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_sleeve" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Sleeve (Baazu)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">بازو</span>
            </label>
            <input type="number" step="0.1" name="upper[sleeve]" id="<?php echo $idPrefix; ?>up_sleeve" value="<?php echo floatval($upper['sleeve'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_neck" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Neck F/B (Gala)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">اگلا/پچھلا گلا</span>
            </label>
            <input type="number" step="0.1" name="upper[neck]" id="<?php echo $idPrefix; ?>up_neck" value="<?php echo floatval($upper['neck'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_hem_width" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Hem Width (Daman)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">دامن/گھیرا</span>
            </label>
            <input type="number" step="0.1" name="upper[hem_width]" id="<?php echo $idPrefix; ?>up_hem_width" value="<?php echo floatval($upper['hem_width'] ?? 0); ?>">
        </div>
    </div>
</div>

<!-- Lower Body Fields -->
<div style="margin-bottom: 25px;">
    <h4 style="color: var(--neon-cyan); font-size: 15px; border-bottom: 1px solid rgba(0, 240, 255, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>👖 Lower Body Measurements</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">جسم کے نچلے حصے کا ناپ</span>
    </h4>
    
    <div class="measurement-grid">
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>lo_length" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Length (Lambai)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">لمبائی</span>
            </label>
            <input type="number" step="0.1" name="lower[length]" id="<?php echo $idPrefix; ?>lo_length" value="<?php echo floatval($lower['length'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>lo_waist" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Waist (Kamar)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">کمر</span>
            </label>
            <input type="number" step="0.1" name="lower[waist]" id="<?php echo $idPrefix; ?>lo_waist" value="<?php echo floatval($lower['waist'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>lo_hips" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Hips (Hips)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">ہپس</span>
            </label>
            <input type="number" step="0.1" name="lower[hips]" id="<?php echo $idPrefix; ?>lo_hips" value="<?php echo floatval($lower['hips'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>lo_rise" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Rise (Asan)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">آسن</span>
            </label>
            <input type="number" step="0.1" name="lower[rise]" id="<?php echo $idPrefix; ?>lo_rise" value="<?php echo floatval($lower['rise'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>lo_bottom_opening" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Bottom (Paincha)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">پائنچہ</span>
            </label>
            <input type="number" step="0.1" name="lower[bottom_opening]" id="<?php echo $idPrefix; ?>lo_bottom_opening" value="<?php echo floatval($lower['bottom_opening'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>lo_inseam" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Inseam</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">اندرونی لمبائی</span>
            </label>
            <input type="number" step="0.1" name="lower[inseam]" id="<?php echo $idPrefix; ?>lo_inseam" value="<?php echo floatval($lower['inseam'] ?? 0); ?>">
        </div>
    </div>
</div>

<!-- Women's Specific Complexity Fields -->
<div style="margin-bottom: 10px;">
    <h4 style="color: var(--neon-orchid); font-size: 15px; border-bottom: 1px solid rgba(184, 41, 242, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em; display: flex; justify-content: space-between; align-items: center;">
        <span>🎀 Women's Specific Fields</span>
        <span style="font-size: 13px; font-weight: normal; text-transform: none; font-family: var(--font-urdu);">خواتین کے لباس کی مخصوص تفصیلات</span>
    </h4>
    
    <div class="measurement-grid">
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_darts" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Darts</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">ڈارٹس (پلیٹیں)</span>
            </label>
            <select name="upper[darts]" id="<?php echo $idPrefix; ?>up_darts" class="form-control" style="padding: 2px; font-size: 13px; text-align: center; border:none; height:28px;">
                <option value="No" <?php echo (isset($upper['darts']) && $upper['darts'] === 'No') ? 'selected' : ''; ?>>No</option>
                <option value="Yes" <?php echo (isset($upper['darts']) && $upper['darts'] === 'Yes') ? 'selected' : ''; ?>>Yes</option>
            </select>
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_cut" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Cut (A-Line/Frock)</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">کٹائی کا ڈیزائن</span>
            </label>
            <input type="text" name="upper[cut]" id="<?php echo $idPrefix; ?>up_cut" value="<?php echo htmlspecialchars($upper['cut'] ?? 'Straight'); ?>" style="font-size:13px;">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_flare" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Flare / Ghera</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">گھیراؤ / فلیر</span>
            </label>
            <input type="number" step="0.1" name="upper[flare]" id="<?php echo $idPrefix; ?>up_flare" value="<?php echo floatval($upper['flare'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_upper_chest" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Upper Chest</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">اوپری چھاتی</span>
            </label>
            <input type="number" step="0.1" name="upper[upper_chest]" id="<?php echo $idPrefix; ?>up_upper_chest" value="<?php echo floatval($upper['upper_chest'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="<?php echo $idPrefix; ?>up_lower_chest" style="min-height: 34px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px;">
                <span>Lower Chest</span>
                <span style="color: var(--text-muted); font-size: 10px; font-family: var(--font-urdu);">نچلی چھاتی</span>
            </label>
            <input type="number" step="0.1" name="upper[lower_chest]" id="<?php echo $idPrefix; ?>up_lower_chest" value="<?php echo floatval($upper['lower_chest'] ?? 0); ?>">
        </div>
    </div>
</div>
