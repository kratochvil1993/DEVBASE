<?php
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check - only accessible if logged in
if (isAppLocked()) {
    header('Location: lock.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="cs" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Info - DevBase</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #e0e0e0;
            font-family: 'Inter', sans-serif;
        }
        .info-container {
            margin-top: 30px;
            margin-bottom: 50px;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            overflow: hidden;
        }
        /* Styling for phpinfo() output */
        .phpinfo-wrapper {
            font-size: 0.85rem;
            color: #ccc;
        }
        .phpinfo-wrapper table {
            width: 100% !important;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: rgba(0,0,0,0.2) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
        }
        .phpinfo-wrapper td, .phpinfo-wrapper th {
            border: 1px solid rgba(255,255,255,0.05) !important;
            padding: 8px 12px !important;
            word-break: break-all;
        }
        .phpinfo-wrapper .h {
            background-color: rgba(13, 110, 253, 0.1) !important;
            color: #fff !important;
            font-weight: bold;
            text-align: left;
        }
        .phpinfo-wrapper .e {
            background-color: rgba(255, 255, 255, 0.02) !important;
            color: #aaa !important;
            width: 30% !important;
            font-weight: 600;
        }
        .phpinfo-wrapper .v {
            background-color: transparent !important;
            color: #ddd !important;
        }
        .phpinfo-wrapper img {
            display: none; /* Hide PHP logo */
        }
        .phpinfo-wrapper hr {
            border: 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin: 20px 0;
        }
        h1, h2 {
            color: #fff;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container info-container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>PHP Konfigurace</h2>
            <a href="settings.php" class="btn btn-outline-light border-opacity-25 px-4 rounded-pill btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Zpět do nastavení
            </a>
        </div>

        <div class="glass-card">
            <div class="phpinfo-wrapper">
                <?php
                ob_start();
                phpinfo();
                $pinfo = ob_get_contents();
                ob_end_clean();

                // Extract body content
                $pinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $pinfo);
                
                // Clean up some styles that might conflict
                $pinfo = str_replace('width="600"', 'width="100%"', $pinfo);
                
                echo $pinfo;
                ?>
            </div>
        </div>
        
        <div class="text-center mt-4 text-white-50 small">
            <p>Tato stránka je viditelná pouze pro přihlášeného administrátora.</p>
        </div>
    </div>
</body>
</html>
