<?php 
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']); 

// Manual Lock
if (isset($_GET['lock']) && $_GET['lock'] == '1') {
    $_SESSION['app_unlocked'] = false;
    unset($_SESSION['app_unlocked']);
    header('Location: lock.php');
    exit;
}

// Security Check
if ($currentPage !== 'lock.php' && isAppLocked()) {
    header('Location: lock.php');
    exit;
}

$stats = getGlobalStats();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevBase - The OneNote Killer</title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="/./assets/fav/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/./assets/fav/favicon.svg" />
<link rel="shortcut icon" href="/./assets/fav/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/./assets/fav/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="DevBase" />
<link rel="manifest" href="/./assets/fav/site.webmanifest" />
    
    <!-- Bootstrap 5.3 -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="assets/vendor/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts - Inter (Local) -->
    <link href="assets/vendor/inter/css/inter.css" rel="stylesheet">
    <!-- Prism.js Syntax Highlighting (Tomorrow Night theme) -->
    <link href="assets/vendor/prism/themes/prism-tomorrow.min.css" rel="stylesheet">
    <!-- Quill.js CSS -->
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>" rel="stylesheet">
</head>
<body>

<div id="bgcircle-pruple"></div>
<div id="bgcircle-primary"></div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-glass sticky-top">
    <div class="container-fluid">
        <button class="btn btn-menu border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand fw-bold d-none d-md-block " href="index.php">
            <span>    
                <img src="./assets/logoAlt.png" alt="DevBase Logo"  class="d-inline-block align-text-top logo" >
            </span>
            <span>DevBase</span>
        </a>

        <div class=" d-flex position-absolute start-50 translate-middle-x">
            <div class="nav-toggle-group">
                <a href="index.php" id="nav-snippets-item" class="nav-toggle-btn <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?> <?php echo getSetting('snippets_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-code-slash me-2"></i> <span class="d-none d-md-inline">Snippets</span>
                </a>
                <a href="code.php" id="nav-code-item" class="nav-toggle-btn <?php echo ($currentPage == 'code.php') ? 'active' : ''; ?> <?php echo getSetting('code_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-braces me-2"></i> <span class="d-none d-md-inline">Code</span>
                </a>
                <a href="notes_drafts.php" id="nav-drafts-item" class="nav-toggle-btn <?php echo ($currentPage == 'notes_drafts.php') ? 'active' : ''; ?> <?php echo getSetting('note_drafts_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-journal-plus me-2"></i> <span class="d-none d-md-inline">Drafts</span>
                </a>
                <a href="notes.php" id="nav-notes-item" class="nav-toggle-btn <?php echo $currentPage == 'notes.php' ? 'active' : ''; ?> <?php echo getSetting('notes_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-journal-text me-2"></i> <span class="d-none d-md-inline">Notes</span>
                </a>
                <a href="todo.php" id="nav-todo-item" class="nav-toggle-btn <?php echo $currentPage == 'todo.php' ? 'active' : ''; ?> d-flex align-items-center <?php echo getSetting('todos_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-check2-square me-2"></i> <span class="d-none d-md-inline">TODO</span>
                    <span id="nav-todo-badge-container" class="<?php echo getSetting('todo_badge_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                        <?php 
                        if ($stats['total_todos'] > 0) {
                            echo '<span class="badge badge-todo ms-2">' . $stats['total_todos'] . '</span>';
                        }
                        ?>
                    </span>
                </a>
                <a href="inbox.php" id="nav-inbox-item" class="nav-toggle-btn <?php echo $currentPage == 'inbox.php' ? 'active' : ''; ?> <?php echo getSetting('inbox_enabled', '0') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-inbox me-2"></i> <span class="d-none d-md-inline">Inbox</span>
                    <span id="nav-inbox-badge-container">
                        <?php 
                        if ($stats['total_inbox_new'] > 0) {
                            echo '<span class="badge badge-todo ms-2 bg-primary border-primary">' . $stats['total_inbox_new'] . '</span>';
                        }
                        ?>
                    </span>
                </a>
            </div>
        </div>
        
        <div class="ms-auto d-flex align-items-center gap-3">
        <div id="header-notifications-container">
            <?php include 'includes/header_notifications.php'; ?>
        </div>

        <div class="form-check form-switch mb-0 <?php echo getSetting('theme_toggle_enabled', '1') == '1' ? '' : 'd-none'; ?>" id="headerThemeToggleContainer">
                <input class="form-check-input" type="checkbox" id="themeToggle">
                <label class="form-check-label text-white small" for="themeToggle">Dark</label>
        </div>
        <a href="settings.php#section-ai" id="headerAiIcon" class="btn btn-sm btn-link text-ai p-0 <?php echo (getSetting('ai_enabled', '0') == '1' && (!empty(getSetting('gemini_api_key')) || !empty(getSetting('openai_api_key')))) ? '' : 'd-none'; ?>" title="AI Configured">
            <i class="bi bi-robot fs-5"></i>
        </a>
        <a href="?lock=1" id="headerLockIcon" class="btn btn-sm btn-link text-white-50 p-0 <?php echo getSetting('security_enabled', '0') == '1' ? '' : 'd-none'; ?>" title="Lock App">
            <i class="bi bi-lock-fill fs-5"></i>
        </a>
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
            <!--
            <a href="index.php" class="sidebar-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                <i class="bi bi-code-slash me-2"></i> Snippets
            </a>
            <?php if (getSetting('notes_enabled', '1') == '1'): ?>
            <a href="notes.php" class="sidebar-link <?php echo $currentPage == 'notes.php' ? 'active' : ''; ?>">
                <i class="bi bi-journal-text me-2"></i> Notes
            </a>
            <?php endif; ?>
            -->
            <a href="manage.php" id="side-snippets-manage" class="sidebar-link <?php echo $currentPage == 'manage.php' ? 'active' : ''; ?> <?php echo getSetting('snippets_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                <i class="bi bi-list-task me-2"></i> Správa snippetů
            </a>
            <a href="manage_notes.php" id="side-notes-manage" class="sidebar-link <?php echo $currentPage == 'manage_notes.php' ? 'active' : ''; ?> <?php echo getSetting('notes_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                <i class="bi bi-list-task me-2"></i> Správa poznámek
            </a>
            <a href="archive_notes.php" id="side-notes-archive" class="sidebar-link <?php echo $currentPage == 'archive_notes.php' ? 'active' : ''; ?> <?php echo getSetting('notes_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                <i class="bi bi-archive me-2"></i> Archiv poznámek
            </a>
            <a href="archive_todos.php" id="side-todos-archive" class="sidebar-link <?php echo $currentPage == 'archive_todos.php' ? 'active' : ''; ?> <?php echo getSetting('todos_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                <i class="bi bi-archive me-2"></i> Archiv TODO
            </a>
            <a href="settings.php" class="sidebar-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear me-2"></i> Nastavení
            </a>
            <a href="help.php" class="sidebar-link <?php echo $currentPage == 'help.php' ? 'active' : ''; ?>">
                <i class="bi bi-question-circle me-2"></i> Nápověda
            </a>

        </div>

        <div class="mt-4 pt-4 border-top border-light border-opacity-10">
            <h6 class="text-white-50 small text-uppercase fw-bold mb-3 px-2" style="font-size: 0.7rem; letter-spacing: 1px;">Statistiky</h6>
            
            <div class="row g-2 px-1">
                <div class="col-4 <?php echo getSetting('snippets_enabled', '1') == '0' ? 'd-none' : ''; ?>" id="stat-snippets-col">
                    <div class="glass-card no-jump p-2 text-center h-100" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                        <div class="text-white-50 mb-1" style="font-size: 0.6rem;">Snippetů</div>
                        <div class="h6 fw-bold text-white mb-0" id="sidebar-snippet-count"><?php echo $stats['total_snippets']; ?></div>
                    </div>
                </div>
                <div class="col-4 <?php echo getSetting('notes_enabled', '1') == '0' ? 'd-none' : ''; ?>" id="stat-notes-col">
                    <div class="glass-card no-jump p-2 text-center h-100" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                        <div class="text-white-50 mb-1" style="font-size: 0.6rem;">Notes</div>
                        <div class="h6 fw-bold text-white mb-0" id="sidebar-note-count"><?php echo $stats['total_notes']; ?></div>
                    </div>
                </div>
                <div class="col-4 <?php echo getSetting('todos_enabled', '1') == '0' ? 'd-none' : ''; ?>" id="stat-todos-col">
                    <div class="glass-card no-jump p-2 text-center h-100" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                        <div class="text-white-50 mb-1" style="font-size: 0.6rem;">Úkoly</div>
                        <div class="h6 fw-bold text-white mb-0" id="sidebar-todo-count"><?php echo $stats['total_todos']; ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-2 px-1 mt-1">
                <div class="col-6 <?php echo getSetting('code_enabled', '1') == '0' ? 'd-none' : ''; ?>" id="stat-code-drafts-col">
                    <div class="glass-card no-jump p-2 text-center h-100" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                        <div class="text-white-50 mb-1" style="font-size: 0.6rem;">Code Draftů</div>
                        <div class="h6 fw-bold text-white mb-0"><?php echo $stats['total_code_drafts'] ?? 0; ?></div>
                    </div>
                </div>
                <div class="col-6 <?php echo getSetting('note_drafts_enabled', '1') == '0' ? 'd-none' : ''; ?>" id="stat-note-drafts-col">
                    <div class="glass-card no-jump p-2 text-center h-100" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                        <div class="text-white-50 mb-1" style="font-size: 0.6rem;">Note Draftů</div>
                        <div class="h6 fw-bold text-white mb-0"><?php echo $stats['total_note_drafts'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


<script>
window.DevBase = {
    settings: {
        inbox_enabled: "<?php echo getSetting('inbox_enabled', '0'); ?>",
        inbox_auto_check: "<?php echo getSetting('inbox_auto_check', '0'); ?>"
    }
};

function updateGlobalStats(data) {
    if (!data) return;
    
    // Update notifications container (bell icon etc)
    const notificationsContainer = document.getElementById('header-notifications-container');
    if (notificationsContainer && data.nav_notifications_html !== undefined) {
        notificationsContainer.innerHTML = data.nav_notifications_html;
    }
    
    // Update TODO badge
    const badgeContainer = document.getElementById('nav-todo-badge-container');
    if (badgeContainer && data.stats) {
        if (data.stats.total_todos > 0) {
            badgeContainer.innerHTML = '<span class="badge badge-todo ms-2">' + data.stats.total_todos + '</span>';
        } else {
            badgeContainer.innerHTML = '';
        }
    }

    // Update Inbox badge (persistent on the nav item)
    const inboxBadgeContainer = document.getElementById('nav-inbox-badge-container');
    if (inboxBadgeContainer && data.stats) {
        if (data.stats.total_inbox_new > 0) {
            inboxBadgeContainer.innerHTML = '<span class="badge badge-todo ms-2 bg-primary border-primary">' + data.stats.total_inbox_new + '</span>';
        } else {
            inboxBadgeContainer.innerHTML = '';
        }
    }
    
    // Update sidebar todo count
    const sidebarTodoCount = document.getElementById('sidebar-todo-count');
    if (sidebarTodoCount && data.stats) {
        sidebarTodoCount.textContent = data.stats.total_todos;
    }
    
    // Update other stats if needed
    const sidebarSnippetCount = document.getElementById('sidebar-snippet-count');
    if (sidebarSnippetCount && data.stats) sidebarSnippetCount.textContent = data.stats.total_snippets;
    
    const sidebarNoteCount = document.getElementById('sidebar-note-count');
    if (sidebarNoteCount && data.stats) sidebarNoteCount.textContent = data.stats.total_notes;
}
</script>
<main class="container-fluid py-4">
