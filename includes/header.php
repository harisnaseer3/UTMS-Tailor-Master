<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/dictionary.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo getHTMLDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('title'); ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#07050f">
    <!-- QR Code Generator Library via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js" integrity="sha512-pUhApVQtLbnpLtJnGDuzD0So2xtmLJnJ7oBoMsBnZOkVkpqOfGLGPaBJGayD2zQe3lCgCibhJB14cj5wAxwVKA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <!-- Tom Select (For Searchable Dropdowns) -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
</head>
<body>
<script>
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-theme');
    }
</script>

<header class="aether-header">
    <div class="app-container nav-content">
        <a href="index.php" class="logo-section">
            <div class="logo-icon">
                <!-- Needle & Thread SVG icon -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.247 5.247a3.5 3.5 0 114.95 4.95l-7.778 7.778a5.5 5.5 0 01-7.778 0 5.5 5.5 0 010-7.778l7.778-7.778m4.95 4.95L12 12m3.247-6.753L12 12" />
                </svg>
            </div>
            <span class="logo-text"><?php echo __('app_name'); ?></span>
        </a>
        
        <nav class="nav-links">
            <?php if (isLoggedIn()): ?>
                <a href="dashboard.php" class="btn-glass"><?php echo __('dashboard'); ?></a>
                
                <?php if (in_array($_SESSION['role'], ['master', 'karigar'])): ?>
                    <!-- Dropdown or simple link to orders -->
                <?php endif; ?>
                
                <span class="text-muted" style="font-size: 14px;">
                    [<?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo __('role_' . $_SESSION['role']); ?>)]
                </span>
                
                <a href="login.php?action=logout" class="btn-glass"><?php echo __('logout'); ?></a>
            <?php else: ?>
                <a href="login.php" class="btn-glass btn-neon-cyan"><?php echo __('login'); ?></a>
                <a href="register.php" class="btn-glass"><?php echo __('register'); ?></a>
            <?php endif; ?>
            
            <!-- Bilingual Toggle Link -->
            <a href="<?php echo getLangToggleUrl(); ?>" class="btn-glass" style="border-color: var(--neon-gold); color: var(--neon-gold);">
                <?php echo __('nav_lang'); ?>
            </a>

            <!-- Theme Switcher Button -->
            <button onclick="toggleTheme()" class="btn-glass" id="theme-toggle-btn" style="border-color: var(--neon-cyan); color: var(--neon-cyan); padding: 8px 12px;" title="Toggle Light/Dark Mode">
                🌓
            </button>
        </nav>
    </div>
</header>

<main class="app-container">
