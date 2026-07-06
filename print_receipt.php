<?php
// print_receipt.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/dictionary.php';

$pdo = getDBConnection();
// We don't enforce strict login here because sometimes the master might want to print it directly from a link,
// but it's better to secure it. If you want it secure, uncomment requireLogin();
// requireLogin(); 

$tagId = $_GET['id'] ?? '';
$error = '';
$order = null;

if (empty($tagId)) {
    $error = 'Order Tag ID is missing.';
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, c.name as customer_name, c.phone as customer_phone, s.name as shop_name, s.phone as shop_phone 
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN shops s ON o.shop_id = s.id
            WHERE o.tag_id = ?
        ");
        $stmt->execute([$tagId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $error = 'Order not found for the provided Tag ID.';
        }
    } catch (PDOException $e) {
        $error = 'Database connection error: ' . $e->getMessage();
    }
}

// Decode measurements
$measurements = [];
if ($order && !empty($order['measurements_snapshot'])) {
    $measurements = json_decode($order['measurements_snapshot'], true);
}
$upper = $measurements['upper'] ?? [];
$lower = $measurements['lower'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($tagId); ?></title>
    <style>
        body {
            font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #000;
            background: #fff;
        }
        .receipt-container {
            max-width: 400px; /* Suitable for thermal or standard narrow receipt */
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 14px;
            color: #333;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }
        .measurements-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 13px;
        }
        .measurement-item {
            display: flex;
            justify-content: space-between;
        }
        .financials {
            border-top: 2px dashed #000;
            padding-top: 15px;
            margin-top: 20px;
        }
        .financials .row {
            font-size: 16px;
        }
        .financials .total {
            font-weight: bold;
            font-size: 18px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #555;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .error {
            color: red;
            text-align: center;
            font-weight: bold;
        }

        .btn-print {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            padding: 12px;
            background: #000;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            border: none;
        }

        /* Print Specific Styles */
        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .receipt-container {
                border: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<?php if (!empty($error)): ?>
    <div class="receipt-container">
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php elseif ($order): ?>
    
    <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>

    <div class="receipt-container" id="printable-area">
        <div class="header">
            <h1><?php echo htmlspecialchars($order['shop_name']); ?></h1>
            <?php if (!empty($order['shop_phone'])): ?>
                <p>📞 <?php echo htmlspecialchars($order['shop_phone']); ?></p>
            <?php endif; ?>
            <p style="margin-top: 5px;">Date: <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
        </div>

        <div class="row">
            <span><strong>Order ID:</strong></span>
            <span><?php echo htmlspecialchars($order['tag_id']); ?></span>
        </div>
        <div class="row">
            <span><strong>Customer:</strong></span>
            <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
        </div>
        <div class="row">
            <span><strong>Phone:</strong></span>
            <span><?php echo htmlspecialchars($order['customer_phone']); ?></span>
        </div>
        <div class="row">
            <span><strong>Status:</strong></span>
            <span style="text-transform: uppercase;"><?php echo htmlspecialchars($order['status']); ?></span>
        </div>

        <?php if (!empty($upper) || !empty($lower)): ?>
        <div class="section-title">Measurements</div>
        <div class="measurements-grid">
            <?php 
            $fields = [
                ['label' => 'Up. Length', 'val' => $upper['length'] ?? ''],
                ['label' => 'Shoulder', 'val' => $upper['shoulder'] ?? ''],
                ['label' => 'Chest', 'val' => $upper['chest'] ?? ''],
                ['label' => 'Armhole', 'val' => $upper['armhole'] ?? ''],
                ['label' => 'Sleeve', 'val' => $upper['sleeve'] ?? ''],
                ['label' => 'Neck', 'val' => $upper['neck'] ?? ''],
                ['label' => 'Hem Width', 'val' => $upper['hem_width'] ?? ''],
                ['label' => 'Darts', 'val' => $upper['darts'] ?? '', 'is_string' => true],
                ['label' => 'Cut', 'val' => $upper['cut'] ?? '', 'is_string' => true],
                ['label' => 'Flare', 'val' => $upper['flare'] ?? ''],
                ['label' => 'Upper Chest', 'val' => $upper['upper_chest'] ?? ''],
                ['label' => 'Lower Chest', 'val' => $upper['lower_chest'] ?? ''],
                
                ['label' => 'Low. Length', 'val' => $lower['length'] ?? ''],
                ['label' => 'Waist', 'val' => $lower['waist'] ?? ''],
                ['label' => 'Hips', 'val' => $lower['hips'] ?? ''],
                ['label' => 'Rise', 'val' => $lower['rise'] ?? ''],
                ['label' => 'Bottom', 'val' => $lower['bottom_opening'] ?? ''],
                ['label' => 'Inseam', 'val' => $lower['inseam'] ?? '']
            ];
            
            foreach ($fields as $field) {
                $val = $field['val'];
                $isString = $field['is_string'] ?? false;
                
                // Print if string is not empty, or if number is greater than 0
                if ((!$isString && floatval($val) > 0) || ($isString && !empty(trim($val)) && trim($val) !== 'No')) {
                    $displayVal = htmlspecialchars($val) . ($isString ? '' : '"');
                    echo '<div class="measurement-item"><span>' . $field['label'] . ':</span> <strong>' . $displayVal . '</strong></div>';
                }
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($order['notes'])): ?>
        <div class="section-title">Notes</div>
        <p style="font-size: 13px; font-style: italic; margin-top: 0;">"<?php echo nl2br(htmlspecialchars($order['notes'])); ?>"</p>
        <?php endif; ?>

        <div class="financials">
            <div class="row">
                <span>Total Amount:</span>
                <span>Rs. <?php echo number_format($order['price'], 0); ?></span>
            </div>
            <div class="row">
                <span>Advance Paid:</span>
                <span>Rs. <?php echo number_format($order['advance_paid'], 0); ?></span>
            </div>
            <div class="row total">
                <span>Balance Due:</span>
                <span>Rs. <?php echo number_format($order['price'] - $order['advance_paid'], 0); ?></span>
            </div>
        </div>

        <div class="footer">
            <p style="margin-bottom: 5px;">Thank you for your business!</p>
            <p style="margin: 0;"><strong><?php echo htmlspecialchars($order['shop_name']); ?></strong> - Tailor Master</p>
            <!-- Optional disclaimer text -->
            <p style="font-size: 10px; margin-top: 10px; color: #888;">
                Items not collected within 30 days are not our responsibility. Please bring this receipt for collection.
            </p>
        </div>
    </div>

    <!-- Auto-trigger print dialog -->
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
<?php endif; ?>

</body>
</html>
