<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevBase - The OneNote Killer</title>
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
                <label class="form-check-label text-white small" for="themeToggle">Dark Mode</label>
            </div>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start offcanvas-glass text-white" tabindex="-1" id="offcanvasNavbar">
    <div class="offcanvas-header border-bottom border-light border-opacity-25">
        <h5 class="offcanvas-title fw-bold" id="offcanvasNavbarLabel">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="d-grid gap-2">
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-house-door me-2"></i> Dashboard (Úvod)
            </a>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear me-2"></i> Settings (Nastavení)
            </a>
            <a href="help.php" class="sidebar-link">
                <i class="bi bi-question-circle me-2"></i> Help (Napověda)
            </a>
            <div class="mt-4 px-3">
                <h6 class="text-uppercase small opacity-50">Tags</h6>
                <!-- Tags will be populated here if needed -->
            </div>
        </div>
    </div>
</div>

<main class="container-fluid py-4">
