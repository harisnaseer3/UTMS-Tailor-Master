<?php
$file = 'views/measurement_inputs.php';
$content = file_get_contents($file);

$init = "<?php\n// views/measurement_inputs.php\n\n\$idPrefix = isset(\$idPrefix) ? \$idPrefix : 'm_';\n";
$content = preg_replace('/<\?php\n\/\/ views\/measurement_inputs\.php\n/', $init, $content);

$content = str_replace('id="m_', 'id="<?php echo $idPrefix; ?>', $content);
$content = str_replace('for="m_', 'for="<?php echo $idPrefix; ?>', $content);
$content = str_replace('id="womens-specific-fields"', 'id="<?php echo $idPrefix; ?>womens-specific-fields"', $content);

file_put_contents($file, $content);
echo "Refactored measurement_inputs.php";
