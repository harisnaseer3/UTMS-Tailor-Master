<?php
// includes/dictionary.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default to English
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Toggle language if parameter present
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'ur' ? 'ur' : 'en';
    $_SESSION['lang'] = $lang;
    
    // Redirect back to clean URL if possible
    $redirect = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $redirect);
    exit();
}

$dictionary = [
    'en' => [
        'title' => 'Universal Tailoring Management System (UTMS)',
        'app_name' => 'UTMS',
        'tagline' => 'Next-Gen Multi-Tenant Tailoring SaaS',
        'dashboard' => 'Dashboard',
        'customers' => 'Customers',
        'orders' => 'Orders',
        'measurements' => 'Measurements',
        'my_vault' => 'My Vault',
        'ledger' => 'Ledger',
        'login' => 'Sign In',
        'register' => 'Register Customer',
        'logout' => 'Sign Out',
        'username' => 'Username',
        'password' => 'Password',
        'role' => 'Access Role',
        'shop' => 'Workshop / Shop',
        'shop_name' => 'Tailoring Shop Name',
        'phone' => 'Contact Number',
        'role_master' => 'Tailor Master',
        'role_karigar' => 'Karigar (Sub-Tailor)',
        'role_customer' => 'Customer',
        'nav_lang' => 'اردو (Urdu)',
        'search' => 'Search customers, orders...',
        'add_customer' => 'Add Customer',
        'new_order' => 'Create New Order',
        'tag_id' => 'Unique Tag ID',
        'status' => 'Status',
        'status_received' => 'Fabric Received',
        'status_cutting' => 'In-Cutting',
        'status_stitching' => 'In-Stitching',
        'status_ready' => 'Ready for Delivery',
        'status_dispatched' => 'Dispatched',
        'actions' => 'Actions',
        'date' => 'Date',
        'notes' => 'Special Instructions / Notes',
        'price' => 'Total Price (PKR)',
        'advance' => 'Advance Paid (PKR)',
        'balance' => 'Balance Due (PKR)',
        'save' => 'Save Changes',
        'cancel' => 'Cancel',
        'upload_fabric' => 'Upload Fabric',
        'scan_qr' => 'Scan QR Code',
        'scientific_term' => 'Scientific Term',
        'native_term' => 'Native Term',
        'upper_body' => 'Upper Body Measurements',
        'lower_body' => 'Lower Body Measurements',
        
        // Scientific & Native terms
        'length' => 'Length (Lambai)',
        'shoulder' => 'Shoulder (Teera)',
        'chest' => 'Chest/Bust (Cheeti)',
        'armhole' => 'Armhole (Monda)',
        'sleeve' => 'Sleeve (Baazu)',
        'neck' => 'Neck F/B (Gala Agla/Pichla)',
        'hem_width' => 'Hem Width (Daman/Gera)',
        'waist' => 'Waist (Kamar)',
        'hips' => 'Hips (Hips)',
        'rise' => 'Rise (Asan)',
        'bottom_opening' => 'Bottom Opening (Paincha)',
        
        // Female Specific
        'darts' => 'Darts',
        'cut' => 'Cut (A-Line/Frock)',
        'flare' => 'Flare / Ghera',
        'inseam' => 'Inseam',
        'upper_chest' => 'Upper Chest',
        'lower_chest' => 'Lower Chest',
        'women_complexity' => 'Women\'s Specific Fields',
        
        'session_expired' => 'Session expired or invalid',
        'upload_success' => 'Image uploaded successfully!',
        'kanban_board' => 'Kanban Production Board',
        'total_revenue' => 'Total Revenue',
        'total_orders' => 'Active Orders',
        'outstanding' => 'Outstanding Balance',
        'add_new_customer' => 'Add New Customer Profile',
        'quick_view' => 'Quick View',
        'select_customer' => 'Select Customer',
        'customer_name' => 'Customer Name',
        'quick_actions' => 'Quick Actions',
        'upload_bridge_msg' => 'Scan this QR code from your mobile device to snap and upload a fabric photo directly into this order session.'
    ],
    
    'ur' => [
        'title' => 'یونیورسل ٹیلرنگ مینجمنٹ سسٹم (UTMS)',
        'app_name' => 'یو ٹی ایم ایس',
        'tagline' => 'جدید کثیر کرایہ دار ٹیلرنگ ساس',
        'dashboard' => 'ڈیش بورڈ',
        'customers' => 'گاہکوں کی فہرست',
        'orders' => 'آرڈرز کی فہرست',
        'measurements' => 'پیمائش / ناپ',
        'my_vault' => 'میرا والٹ',
        'ledger' => 'کھاتہ رجسٹر',
        'login' => 'لاگ ان کریں',
        'register' => 'گاہک رجسٹر کریں',
        'logout' => 'لاگ آؤٹ',
        'username' => 'صارف کا نام',
        'password' => 'پاس ورڈ',
        'role' => 'کردار کا انتخاب',
        'shop' => 'ٹیلرنگ دکان',
        'shop_name' => 'ٹیلرنگ دکان کا نام',
        'phone' => 'فون نمبر',
        'role_master' => 'ٹیلر ماسٹر',
        'role_karigar' => 'کاریگر',
        'role_customer' => 'گاہک',
        'nav_lang' => 'English (انگریزی)',
        'search' => 'گاہک یا آرڈر تلاش کریں...',
        'add_customer' => 'نیا گاہک شامل کریں',
        'new_order' => 'نیا آرڈر درج کریں',
        'tag_id' => 'آرڈر نمبر (ٹیگ آئی ڈی)',
        'status' => 'حالت',
        'status_received' => 'کپڑا موصول ہوا',
        'status_cutting' => 'کٹائی جاری ہے',
        'status_stitching' => 'سلائی جاری ہے',
        'status_ready' => 'تیار (ترسیل کے لیے)',
        'status_dispatched' => 'روانہ کر دیا گیا (Dispatched)',
        'actions' => 'کارروائی',
        'date' => 'تاریخ',
        'notes' => 'خصوصی ہدایات / نوٹ',
        'price' => 'کل رقم (روپے)',
        'advance' => 'پیشگی رقم (روپے)',
        'balance' => 'بقایا رقم (روپے)',
        'save' => 'تبدیلیاں محفوظ کریں',
        'cancel' => 'منسوخ کریں',
        'upload_fabric' => 'کپڑے کی تصویر',
        'scan_qr' => 'کیو آر کوڈ اسکین کریں',
        'scientific_term' => 'پیمائش کی قسم',
        'native_term' => 'مادری زبان',
        'upper_body' => 'جسم کے اوپری حصے کا ناپ',
        'lower_body' => 'جسم کے نچلے حصے کا ناپ',
        
        // Scientific & Native terms
        'length' => 'لمبائی (Lambai)',
        'shoulder' => 'تیرا (Teera)',
        'chest' => 'چھاتی (Cheeti)',
        'armhole' => 'مونڈھا (Monda)',
        'sleeve' => 'بازو (Baazu)',
        'neck' => 'اگلا/پچھلا گلا (Gala)',
        'hem_width' => 'دامن/گھیرا (Daman)',
        'waist' => 'کمر (Kamar)',
        'hips' => 'ہپس (Hips)',
        'rise' => 'آسن (Asan)',
        'bottom_opening' => 'پائنچہ (Paincha)',
        
        // Female Specific
        'darts' => 'ڈارٹس (پلیٹیں)',
        'cut' => 'کٹائی کا ڈیزائن (اے لائن/فراک)',
        'flare' => 'گھیراؤ / فلیر',
        'inseam' => 'اندرونی لمبائی (انسیام)',
        'upper_chest' => 'اوپری چھاتی',
        'lower_chest' => 'نچلی چھاتی',
        'women_complexity' => 'خواتین کے لباس کی مخصوص تفصیلات',
        
        'session_expired' => 'سیشن ختم ہو چکا ہے یا لنک غلط ہے',
        'upload_success' => 'تصویر کامیابی سے اپ لوڈ ہو گئی ہے!',
        'kanban_board' => 'پروڈکشن بورڈ (کنبان)',
        'total_revenue' => 'کل آمدنی',
        'total_orders' => 'فعال آرڈرز',
        'outstanding' => 'بقایا واجبات',
        'add_new_customer' => 'نیا گاہک رجسٹر کریں',
        'quick_view' => 'فوری جائزہ',
        'select_customer' => 'گاہک منتخب کریں',
        'customer_name' => 'گاہک کا نام',
        'quick_actions' => 'فوری کارروائیاں',
        'upload_bridge_msg' => 'موبائل سے کپڑے کی تصویر کھینچ کر فوراً اپ لوڈ کرنے کے لیے یہ کیو آر کوڈ اسکین کریں۔'
    ]
];

// Translation helper function
function __($key) {
    global $dictionary;
    $lang = $_SESSION['lang'];
    return isset($dictionary[$lang][$key]) ? $dictionary[$lang][$key] : $key;
}

// Get HTML direction (RTL or LTR)
function getHTMLDirection() {
    return $_SESSION['lang'] === 'ur' ? 'rtl' : 'ltr';
}

// Language toggle URL
function getLangToggleUrl() {
    $currentLang = $_SESSION['lang'];
    $targetLang = $currentLang === 'ur' ? 'en' : 'ur';
    return "?lang=" . $targetLang;
}
