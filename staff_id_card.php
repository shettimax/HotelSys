<?php
// staff_id_card.php?pnumber=ST001 - Printable ID Card
require_once __DIR__.'/database.php';
$pnumber = isset($_GET['pnumber']) ? strtoupper(trim($_GET['pnumber'])) : '';
$batch_mode = isset($_GET['batch']) ? true : false;
if (empty($pnumber)) die('Invalid PNumber');
// Use prepared statement to prevent SQL injection
$stmt = mysqli_prepare($conn, "SELECT * FROM staff WHERE pnumber = ?");
mysqli_stmt_bind_param($stmt, "s", $pnumber);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) == 0) die('Staff not found: ' . htmlspecialchars($pnumber));
$staff = mysqli_fetch_assoc($result);
// Hotel/Company settings - now configurable from Admin > Settings instead of hardcoded,
// so this file (and this whole app) can be redeployed for a different hotel without editing code.
$app_config = include __DIR__.'/config.php';
$hotel_settings = isset($app_config['hotel']) ? $app_config['hotel'] : array();
$hotel_name = !empty($hotel_settings['name']) ? $hotel_settings['name'] : 'HOTEL NAME';
$hotel_tagline = !empty($hotel_settings['tagline']) ? $hotel_settings['tagline'] : '';
$hotel_logo_url = !empty($hotel_settings['logo']) ? $hotel_settings['logo'] : 'img/logox.png';
$address_line1 = isset($hotel_settings['address_line1']) ? $hotel_settings['address_line1'] : '';
$address_line2 = isset($hotel_settings['address_line2']) ? $hotel_settings['address_line2'] : '';
$phone = isset($hotel_settings['phone']) ? $hotel_settings['phone'] : '';
$website = isset($hotel_settings['website']) ? $hotel_settings['website'] : '';
// Default photo if none exists
$default_photo_url = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='55' fill='%23e0e0e0'/%3E%3Ctext x='60' y='65' font-family='Arial' font-size='30' text-anchor='middle' fill='%23999'%3EID%3C/text%3E%3C/svg%3E";
// Get staff email or use default
$staff_email = !empty($staff['email']) ? $staff['email'] : (!empty($hotel_settings['email']) ? $hotel_settings['email'] : 'staff@example.com');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff ID Card - <?php echo htmlspecialchars($staff['pnumber']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
       
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
       
        .id-card-container {
            max-width: 1000px;
            width: 100%;
        }
       
        .id-card {
            width: 3.5in;
            height: 2.25in;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
            margin: 0 auto;
            color: white;
            border: 1px solid #444;
        }
       
        .card-front {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
       
        /* === PERFECT HOTEL NAME HEADER === */
        .card-header {
            background: linear-gradient(135deg, #001f3f 0%, #003366 50%, #004488 100%);
            padding: 14px 10px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-bottom: 4px solid #00d173;
            box-shadow: 0 4px 15px rgba(0, 212, 115, 0.3);
            min-height: 58px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
       
        .card-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            animation: shine 4s infinite;
            pointer-events: none;
        }
       
        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
       
        .hotel-name {
            font-size: 19px;
            font-weight: 900;
            letter-spacing: 2.5px;
            color: #ffffff;
            text-shadow: 
                0 0 15px #00d173,
                2px 2px 8px rgba(0,0,0,0.9);
            text-transform: uppercase;
            margin: 0;
            line-height: 1.1;
        }
       
        .hotel-tagline {
            font-size: 9px;
            color: #ccffcc;
            letter-spacing: 1.5px;
            margin-top: 3px;
            font-weight: 500;
            opacity: 0.9;
        }
       
        .card-body {
            display: flex;
            flex: 1;
            padding: 10px;
        }
       
        .photo-section {
            flex: 0 0 95px;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-right: 10px;
        }
       
        .staff-photo {
            width: 95px;
            height: 110px;
            background: #333;
            border: 2px solid #00d173;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 6px;
        }
       
        .staff-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
       
        .staff-id {
            font-size: 9px;
            background: #00a859;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-align: center;
            width: 100%;
        }
       
        .info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
       
        .staff-name {
            font-size: 16px;
            font-weight: 700;
            color: white;
            margin-bottom: 3px;
            line-height: 1.2;
        }
       
        .staff-title {
            font-size: 11px;
            color: #00d173;
            margin-bottom: 6px;
            font-weight: 600;
        }
       
        .staff-dept, .staff-phone, .staff-email {
            font-size: 10px;
            color: #aaa;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
       
        .staff-email {
            font-size: 9px;
            word-break: break-all;
        }
       
        .card-footer {
            background: rgba(0, 0, 0, 0.3);
            padding: 5px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #444;
            min-height: 32px;
        }
       
        .valid-date {
            font-size: 8px;
            color: #bbb;
            display: flex;
            align-items: center;
            gap: 4px;
        }
       
        .card-logo {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            padding: 4px;
        }
       
        .card-logo i {
            font-size: 18px;
            color: #00d173;
        }
       
        /* Card Back */
        .card-back {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 12px;
            transform: rotateY(180deg);
            backface-visibility: hidden;
            transition: transform 0.6s;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
       
        .id-card:hover .card-front {
            transform: rotateY(180deg);
        }
       
        .id-card:hover .card-back {
            transform: rotateY(0deg);
        }
       
        .back-header {
            text-align: center;
            margin-bottom: 10px;
        }
       
        .back-title {
            color: #00d173;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
        }
       
        .hotel-info {
            text-align: center;
            font-size: 8px;
            color: #bbb;
            line-height: 1.3;
            margin-bottom: 10px;
        }
       
        .hotel-info strong {
            color: #fff;
            font-size: 9px;
        }
       
        .emergency-contact {
            font-size: 8px;
            color: #ff6b6b;
            text-align: center;
            margin-top: 8px;
            padding: 4px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 3px;
        }
       
        .magnetic-strip {
            height: 18px;
            background: linear-gradient(90deg, #222 0%, #444 50%, #222 100%);
            margin-top: 8px;
            border-radius: 2px;
        }
       
        .signature-area {
            margin-top: 8px;
            border-top: 1px dashed #555;
            padding-top: 6px;
            text-align: center;
        }
       
        .signature {
            height: 20px;
            border-bottom: 1px solid #666;
            margin-bottom: 3px;
        }
       
        .signature-label {
            font-size: 7px;
            color: #999;
        }
       
        /* Controls and Instructions */
        .controls {
            margin-top: 30px;
            text-align: center;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
       
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
       
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
       
        .btn-primary {
            background: #00a859;
            color: white;
        }
       
        .btn-primary:hover {
            background: #008e4c;
            transform: translateY(-2px);
        }
       
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
       
        .btn-secondary:hover {
            background: #5a6268;
        }
       
        .instructions {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #00a859;
        }
       
        .instructions h4 {
            color: #00a859;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
       
        .instructions ul {
            list-style-position: inside;
            color: #666;
            line-height: 1.6;
            padding-left: 5px;
        }
       
        .instructions li {
            margin-bottom: 5px;
        }
       
        /* === PRINT FIX: show FRONT then BACK, stacked as two cards on print === */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
           
            .id-card-container {
                max-width: 100% !important;
                width: 100% !important;
            }
           
            .id-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                width: 3.5in !important;
                height: 2.25in !important;
                margin: 0.15in auto !important;
                overflow: visible !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }
           
            .controls, .instructions, .btn-group {
                display: none !important;
            }
           
            @page {
                size: auto;
                margin: 0.5cm;
            }
           
            .card-header::before {
                animation: none !important;
            }
           
            /* FRONT: print as a normal static block (no flip transform) */
            .card-front {
                position: relative !important;
                transform: none !important;
                height: 2.25in !important;
            }
            .id-card:hover .card-front {
                transform: none !important;
            }

            /* BACK: instead of the hover-flip overlay, print it as its own
               card directly below the front, on its own page so front/back
               line up for duplex printing. */
            .card-back {
                display: flex !important;
                position: static !important;
                transform: none !important;
                width: 3.5in !important;
                height: 2.25in !important;
                margin: 0.15in auto !important;
                border: 1px solid #000 !important;
                border-radius: 12px;
                page-break-before: always;
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .id-card:hover .card-back {
                transform: none !important;
            }
           
            .multiple-cards {
                display: grid !important;
                grid-template-columns: repeat(2, 3.5in) !important;
                gap: 0.3in !important;
                justify-content: center !important;
            }
        }
       
        .multiple-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(3.5in, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            justify-items: center;
        }
       
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
           
            .multiple-cards {
                grid-template-columns: 1fr;
            }
           
            .id-card {
                width: 100%;
                max-width: 3.5in;
                height: auto;
                aspect-ratio: 3.5 / 2.25;
            }
           
            .btn-group {
                flex-direction: column;
                align-items: center;
            }
           
            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
           
            .controls {
                padding: 15px;
            }
        }
       
        .id-card::after {
            content: "PREVIEW";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 30px;
            color: rgba(255,255,255,0.07);
            font-weight: bold;
            pointer-events: none;
            z-index: 1;
        }
       
        @media print {
            .id-card::after {
                content: none;
            }
        }
       
        <?php if ($batch_mode): ?>
        body {
            padding: 20px;
            display: block;
        }
       
        .id-card-container {
            max-width: none;
        }
       
        .multiple-cards {
            grid-template-columns: repeat(3, 3.5in);
            justify-content: center;
        }
       
        .controls {
            position: sticky;
            top: 0;
            z-index: 100;
            margin-top: 0;
            margin-bottom: 20px;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="id-card-container">
        <?php if (!$batch_mode): ?>
        <!-- Single ID Card Display -->
        <div class="id-card">
            <!-- Front Side -->
            <div class="card-front">
                <div class="card-header">
                    <div class="hotel-name"><?php echo htmlspecialchars($hotel_name); ?></div>
                    <div class="hotel-tagline"><?php echo htmlspecialchars($hotel_tagline); ?></div>
                </div>
               
                <div class="card-body">
                    <div class="photo-section">
                        <div class="staff-photo">
                            <?php if ($staff['photo'] && file_exists($staff['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($staff['photo']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">
                            <?php else: ?>
                                <img src="<?php echo h($default_photo_url); ?>" alt="No Photo">
                            <?php endif; ?>
                        </div>
                        <div class="staff-id"><?php echo htmlspecialchars($staff['pnumber']); ?></div>
                    </div>
                   
                    <div class="info-section">
                        <div>
                            <div class="staff-name"><?php echo htmlspecialchars($staff['name']); ?></div>
                            <div class="staff-title">STAFF MEMBER</div>
                            <div class="staff-dept"><i class="fas fa-building"></i> <?php echo htmlspecialchars($staff['department']); ?> Department</div>
                            <?php if (!empty($staff['phone'])): ?>
                                <div class="staff-phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($staff['phone']); ?></div>
                            <?php endif; ?>
                            <div class="staff-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($staff_email); ?></div>
                        </div>
                    </div>
                </div>
               
                <div class="card-footer">
                    <div class="valid-date">
                        <i class="far fa-calendar-alt"></i> Valid until: <?php echo date('M Y', strtotime('+1 year')); ?>
                    </div>
                    <div class="card-logo">
                        <i class="fas fa-hotel"></i>
                    </div>
                </div>
            </div>
           
            <!-- Back Side -->
            <div class="card-back">
                <div>
                    <div class="back-header">
                        <div class="back-title">STAFF IDENTIFICATION CARD</div>
                        <div class="hotel-info">
                            <strong><?php echo htmlspecialchars($hotel_name); ?></strong><br>
                            <?php echo htmlspecialchars($address_line1); ?><br>
                            <?php echo htmlspecialchars($address_line2); ?><br>
                            <?php echo htmlspecialchars($phone); ?><br>
                            <?php echo htmlspecialchars($website); ?>
                        </div>
                    </div>
                   
                    <div class="emergency-contact">
                        <i class="fas fa-exclamation-triangle"></i> In case of emergency, contact Front Desk
                    </div>
                </div>
               
                <div>
                    <div class="magnetic-strip"></div>
                   
                    <div class="signature-area">
                        <div class="signature"></div>
                        <div class="signature-label">Authorized Signature</div>
                    </div>
                </div>
            </div>
        </div>
       
        <!-- Controls for single card view -->
        <div class="controls">
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print ID Card
                </button>
                <button onclick="printMultiple()" class="btn btn-secondary">
                    <i class="fas fa-copy"></i> Print Multiple Copies
                </button>
                <button onclick="window.location.href='admin_staff.php'" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Staff List
                </button>
            </div>
           
            <div class="instructions">
                <h4><i class="fas fa-info-circle"></i> Printing Instructions</h4>
                <ul>
                    <li>Use high-quality photo paper for best results</li>
                    <li>Print at 100% scale (do not scale to fit)</li>
                    <li>Standard ID card size is 3.5" x 2.25" (CR-80)</li>
                    <li>Laminate the card for durability</li>
                    <li>For multiple copies, use the "Print Multiple Copies" button above</li>
                    <li>Make sure to enable background graphics in print settings</li>
                </ul>
            </div>
        </div>
       
        <?php else: ?>
        <!-- Batch Mode Display -->
        <div class="controls">
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print All Cards (6 copies)
                </button>
                <button onclick="window.location.href='?pnumber=<?php echo urlencode($pnumber); ?>'" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Single View
                </button>
            </div>
            <p style="margin-top: 10px; color: #666; font-size: 14px;">
                Showing 6 copies of <?php echo htmlspecialchars($staff['name']); ?>'s ID card for batch printing.
            </p>
        </div>
       
        <div class="multiple-cards">
            <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="id-card">
                <div class="card-front">
                    <div class="card-header">
                        <div class="hotel-name"><?php echo htmlspecialchars($hotel_name); ?></div>
                        <div class="hotel-tagline"><?php echo htmlspecialchars($hotel_tagline); ?></div>
                    </div>
                   
                    <div class="card-body">
                        <div class="photo-section">
                            <div class="staff-photo">
                                <?php if ($staff['photo'] && file_exists($staff['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($staff['photo']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">
                                <?php else: ?>
                                    <img src="<?php echo h($default_photo_url); ?>" alt="No Photo">
                                <?php endif; ?>
                            </div>
                            <div class="staff-id"><?php echo htmlspecialchars($staff['pnumber']); ?></div>
                        </div>
                       
                        <div class="info-section">
                            <div>
                                <div class="staff-name"><?php echo htmlspecialchars($staff['name']); ?></div>
                                <div class="staff-title">STAFF MEMBER</div>
                                <div class="staff-dept"><i class="fas fa-building"></i> <?php echo htmlspecialchars($staff['department']); ?></div>
                                <div class="staff-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($staff_email); ?></div>
                            </div>
                        </div>
                    </div>
                   
                    <div class="card-footer">
                        <div class="valid-date">
                            <i class="far fa-calendar-alt"></i> Valid until: <?php echo date('M Y', strtotime('+1 year')); ?>
                        </div>
                        <div class="card-logo">
                            <i class="fas fa-hotel"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    <script>
    function printMultiple() {
        window.location.href = '?pnumber=<?php echo urlencode($pnumber); ?>&batch=1';
    }
   
    <?php if (isset($_GET['autoprint'])): ?>
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 1000);
    });
    <?php endif; ?>
   
    window.addEventListener('afterprint', function() {
        <?php if (isset($_GET['autoprint'])): ?>
        setTimeout(function() {
            window.location.href = '?pnumber=<?php echo urlencode($pnumber); ?>';
        }, 1000);
        <?php endif; ?>
    });
   
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
        if (e.key === 'Escape') {
            <?php if ($batch_mode): ?>
            window.location.href = '?pnumber=<?php echo urlencode($pnumber); ?>';
            <?php else: ?>
            window.location.href = 'admin_staff.php';
            <?php endif; ?>
        }
    });
    </script>
</body>
</html>