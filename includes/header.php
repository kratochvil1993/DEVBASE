<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevBase - The OneNote Killer</title>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="./assets/fav/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/fav/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./assets/favfavicon-16x16.png">
    <link rel="manifest" href="./assets/fav/site.webmanifest">
    <link rel="mask-icon" href="./assets/fav/safari-pinned-tab.svg?v=1" color="#222">
    <link rel="shortcut icon" href="./assets/fav/favicon.ico">
    <meta name="msapplication-TileColor" content="#fff">
    <meta name="msapplication-config" content="./assets/fav/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Prism.js Syntax Highlighting (Tomorrow Night theme) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div id="bgcircle-pruple"></div>
<div id="bgcircle-primary"></div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-glass sticky-top">
    <div class="container-fluid">
        <button class="btn border-0 text-white me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand fw-bold" href="index.php">
            <span>    
                <img src="./assets/logo.webp" alt="DevBase Logo"  class="d-inline-block align-text-top logo" >
            </span>
            <span>DevBase</span>
        </a>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="themeToggle">
                <label class="form-check-label text-white small" for="themeToggle">Tmavý režim</label>
            </div>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start offcanvas-glass text-white" tabindex="-1" id="offcanvasNavbar" style="overflow: hidden;">
    <div id="bgcircle-primary"></div>
    <div id="bgcircle-pruple"></div>
    <div class="offcanvas-header border-bottom border-light border-opacity-25" style="position: relative; z-index: 1;">
        <h5 class="offcanvas-title fw-bold" id="offcanvasNavbarLabel">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" style="position: relative; z-index: 1;">
        <div class="d-grid gap-2">
<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
            <a href="index.php" class="sidebar-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                <i class="bi bi-house-door me-2"></i> Snipety
            </a>
            <a href="notes.php" class="sidebar-link <?php echo $currentPage == 'notes.php' ? 'active' : ''; ?>">
                <i class="bi bi-journal-text me-2"></i> Poznámky
            </a>
            <a href="manage.php" class="sidebar-link <?php echo $currentPage == 'manage.php' ? 'active' : ''; ?>">
                <i class="bi bi-list-task me-2"></i> Správa
            </a>
            <a href="settings.php" class="sidebar-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear me-2"></i> Nastavení
            </a>
            <a href="help.php" class="sidebar-link <?php echo $currentPage == 'help.php' ? 'active' : ''; ?>">
                <i class="bi bi-question-circle me-2"></i> Nápověda
            </a>

        </div>
    </div>
</div>

<main class="container-fluid py-4">
