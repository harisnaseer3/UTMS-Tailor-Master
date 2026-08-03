<?php
// views/measurement_inputs.php
// Wrapper template that includes BOTH Gents and Ladies forms with wrapper containers
// JavaScript in app.js dynamically toggles visibility based on customer gender!

$idPrefix = isset($idPrefix) ? $idPrefix : 'm_';
$measurements = isset($measurements) && is_array($measurements) ? $measurements : [];
?>

<!-- Gents Measurement Form Container -->
<div id="<?php echo $idPrefix; ?>gents_form_wrapper">
    <?php include __DIR__ . '/measurement_inputs_gents.php'; ?>
</div>

<!-- Ladies Measurement Form Container -->
<div id="<?php echo $idPrefix; ?>ladies_form_wrapper" style="display: none;">
    <?php include __DIR__ . '/measurement_inputs_ladies.php'; ?>
</div>
