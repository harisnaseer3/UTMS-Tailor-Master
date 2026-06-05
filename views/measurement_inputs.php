<?php
// views/measurement_inputs.php

// Ensure measurements variables exist
$upper = isset($measurements['upper']) ? $measurements['upper'] : [];
$lower = isset($measurements['lower']) ? $measurements['lower'] : [];
?>

<!-- Upper Body Fields -->
<div style="margin-bottom: 25px;">
    <h4 style="color: var(--neon-cyan); font-size: 15px; border-bottom: 1px solid rgba(0, 240, 255, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em;">
        📐 <?php echo __('upper_body'); ?>
    </h4>
    
    <div class="measurement-grid">
        <div class="measurement-input-wrapper">
            <label for="m_up_length"><?php echo __('length'); ?></label>
            <input type="number" step="0.1" name="upper[length]" id="m_up_length" value="<?php echo floatval($upper['length'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_shoulder"><?php echo __('shoulder'); ?></label>
            <input type="number" step="0.1" name="upper[shoulder]" id="m_up_shoulder" value="<?php echo floatval($upper['shoulder'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_chest"><?php echo __('chest'); ?></label>
            <input type="number" step="0.1" name="upper[chest]" id="m_up_chest" value="<?php echo floatval($upper['chest'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_armhole"><?php echo __('armhole'); ?></label>
            <input type="number" step="0.1" name="upper[armhole]" id="m_up_armhole" value="<?php echo floatval($upper['armhole'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_sleeve"><?php echo __('sleeve'); ?></label>
            <input type="number" step="0.1" name="upper[sleeve]" id="m_up_sleeve" value="<?php echo floatval($upper['sleeve'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_neck"><?php echo __('neck'); ?></label>
            <input type="number" step="0.1" name="upper[neck]" id="m_up_neck" value="<?php echo floatval($upper['neck'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_hem_width"><?php echo __('hem_width'); ?></label>
            <input type="number" step="0.1" name="upper[hem_width]" id="m_up_hem_width" value="<?php echo floatval($upper['hem_width'] ?? 0); ?>">
        </div>
    </div>
</div>

<!-- Lower Body Fields -->
<div style="margin-bottom: 25px;">
    <h4 style="color: var(--neon-cyan); font-size: 15px; border-bottom: 1px solid rgba(0, 240, 255, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em;">
        👖 <?php echo __('lower_body'); ?>
    </h4>
    
    <div class="measurement-grid">
        <div class="measurement-input-wrapper">
            <label for="m_lo_length"><?php echo __('length'); ?></label>
            <input type="number" step="0.1" name="lower[length]" id="m_lo_length" value="<?php echo floatval($lower['length'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_lo_waist"><?php echo __('waist'); ?></label>
            <input type="number" step="0.1" name="lower[waist]" id="m_lo_waist" value="<?php echo floatval($lower['waist'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_lo_hips"><?php echo __('hips'); ?></label>
            <input type="number" step="0.1" name="lower[hips]" id="m_lo_hips" value="<?php echo floatval($lower['hips'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_lo_rise"><?php echo __('rise'); ?></label>
            <input type="number" step="0.1" name="lower[rise]" id="m_lo_rise" value="<?php echo floatval($lower['rise'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_lo_bottom_opening"><?php echo __('bottom_opening'); ?></label>
            <input type="number" step="0.1" name="lower[bottom_opening]" id="m_lo_bottom_opening" value="<?php echo floatval($lower['bottom_opening'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_lo_inseam"><?php echo __('inseam'); ?></label>
            <input type="number" step="0.1" name="lower[inseam]" id="m_lo_inseam" value="<?php echo floatval($lower['inseam'] ?? 0); ?>">
        </div>
    </div>
</div>

<!-- Women's Specific Complexity Fields -->
<div style="margin-bottom: 10px;">
    <h4 style="color: var(--neon-orchid); font-size: 15px; border-bottom: 1px solid rgba(184, 41, 242, 0.15); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.05em;">
        🎀 <?php echo __('women_complexity'); ?>
    </h4>
    
    <div class="measurement-grid">
        <div class="measurement-input-wrapper">
            <label for="m_up_darts"><?php echo __('darts'); ?></label>
            <select name="upper[darts]" id="m_up_darts" class="form-control" style="padding: 2px; font-size: 13px; text-align: center; border:none; height:28px;">
                <option value="No" <?php echo (isset($upper['darts']) && $upper['darts'] === 'No') ? 'selected' : ''; ?>>No</option>
                <option value="Yes" <?php echo (isset($upper['darts']) && $upper['darts'] === 'Yes') ? 'selected' : ''; ?>>Yes</option>
            </select>
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_cut"><?php echo __('cut'); ?></label>
            <input type="text" name="upper[cut]" id="m_up_cut" value="<?php echo htmlspecialchars($upper['cut'] ?? 'Straight'); ?>" style="font-size:13px;">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_flare"><?php echo __('flare'); ?></label>
            <input type="number" step="0.1" name="upper[flare]" id="m_up_flare" value="<?php echo floatval($upper['flare'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_upper_chest"><?php echo __('upper_chest'); ?></label>
            <input type="number" step="0.1" name="upper[upper_chest]" id="m_up_upper_chest" value="<?php echo floatval($upper['upper_chest'] ?? 0); ?>">
        </div>
        <div class="measurement-input-wrapper">
            <label for="m_up_lower_chest"><?php echo __('lower_chest'); ?></label>
            <input type="number" step="0.1" name="upper[lower_chest]" id="m_up_lower_chest" value="<?php echo floatval($upper['lower_chest'] ?? 0); ?>">
        </div>
    </div>
</div>
