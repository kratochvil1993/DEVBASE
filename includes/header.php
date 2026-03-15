<?php 
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']); 

// Security Check
if ($currentPage !== 'lock.php' && isAppLocked()) {
    header('Location: lock.php');
    exit;
}

$stats = getGlobalStats();

// Uvolni session zámek – session data jsou načtena, zbytek stránky může běžet paralelně s dalšími requesty
session_write_close();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, viewport-fit=cover">
    <title>DevBase - The OneNote Killer</title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="/./assets/fav/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/./assets/fav/favicon.svg" />
<link rel="shortcut icon" href="/./assets/fav/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/./assets/fav/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="DevBase" />
<link rel="manifest" href="/./assets/fav/site.webmanifest" />
    <meta name="theme-color" content="#1e1e1e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('PWA: Service Worker registered'))
                .catch(err => console.log('PWA: Service Worker failed', err));
        });
    }
    </script>
    
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
<body class="font-size-<?php echo getSetting('ui_font_size', 'normal'); ?>">

<div id="bgcircle-pruple"></div>
<div id="bgcircle-primary"></div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-glass sticky-top">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="btn btn-menu border-0 me-1 me-md-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <a class="navbar-brand fw-bold d-none d-md-block " href="index.php">
            <span>    
                <img src="./assets/logoAlt.png" alt="DevBase Logo"  class="d-inline-block align-text-top logo" >
            </span>
            <span>DevBase</span>
        </a>

        <div class=" d-flex position-absolute start-50 translate-middle-x">
            <div class="nav-toggle-group">
                <a href="index.php" id="nav-snippets-item" class="nav-toggle-btn <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?> <?php echo getSetting('snippets_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-code-slash me-0 me-lg-2"></i> <span class="d-none d-lg-inline">Snippets</span>
                </a>
                <a href="code.php" id="nav-code-item" class="nav-toggle-btn d-none d-md-flex <?php echo ($currentPage == 'code.php') ? 'active' : ''; ?> <?php echo getSetting('code_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-braces me-0 me-lg-2"></i> <span class="d-none d-lg-inline">Code</span>
                </a>
                <a href="notes_drafts.php" id="nav-drafts-item" class="nav-toggle-btn <?php echo ($currentPage == 'notes_drafts.php') ? 'active' : ''; ?> <?php echo getSetting('note_drafts_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-journal-plus me-0 me-lg-2"></i> <span class="d-none d-lg-inline">Drafts</span>
                </a>
                <a href="notes.php" id="nav-notes-item" class="nav-toggle-btn <?php echo $currentPage == 'notes.php' ? 'active' : ''; ?> <?php echo getSetting('notes_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-journal-text me-0 me-lg-2"></i> <span class="d-none d-lg-inline">Notes</span>
                </a>
                <a href="todo.php" id="nav-todo-item" class="nav-toggle-btn <?php echo $currentPage == 'todo.php' ? 'active' : ''; ?> d-flex align-items-center <?php echo getSetting('todos_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-check2-square me-0 me-lg-2"></i> <span class="d-none d-lg-inline">TODO</span>
                    <span id="nav-todo-badge-container" class="<?php echo getSetting('todo_badge_enabled', '1') == '0' ? 'd-none' : ''; ?>">
                        <?php 
                        if ($stats['total_todos'] > 0) {
                            echo '<span class="badge badge-todo ms-2">' . $stats['total_todos'] . '</span>';
                        }
                        ?>
                    </span>
                </a>
                <a href="inbox.php" id="nav-inbox-item" class="nav-toggle-btn <?php echo $currentPage == 'inbox.php' ? 'active' : ''; ?> <?php echo getSetting('inbox_enabled', '0') == '0' ? 'd-none' : ''; ?>">
                    <i class="bi bi-inbox me-0 me-lg-2"></i> <span class="d-none d-lg-inline">Inbox</span>
                    <span id="nav-inbox-badge-container">
                        <?php 
                        if ($stats['total_inbox_new'] > 0) {
                            echo '<span class="badge badge-todo ms-2">' . $stats['total_inbox_new'] . '</span>';
                        }
                        ?>
                    </span>
                </a>
            </div>
        </div>
        
        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">
            <div id="header-notifications-container">
                <?php include 'includes/header_notifications.php'; ?>
            </div>

            <div class="dropdown">
                <button class="btn btn-link text-white-50 p-0 d-none d-md-block" type="button" id="quickSettingsBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Rychlé nastavení">
                    <i class="bi bi-gear-fill fs-5"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-glass quick-settings-dropdown p-0 mt-2" aria-labelledby="quickSettingsBtn">
                    <div class="p-3 border-bottom border-light border-opacity-10">
                        <h6 class="mb-0 fw-bold">Rychlé nastavení</h6>
                    </div>
                    <div class="p-3">
                        <div class="mb-0">
                            <label class="form-label text-white-50 small fw-bold mb-2">Velikost písma</label>
                            <select class="form-select bg-transparent text-white border-light border-opacity-25 shadow-none" 
                                    onchange="updateQuickSetting('ui_font_size', this.value)">
                                <?php $currentFontSize = getSetting('ui_font_size', 'normal'); ?>
                                <option value="normal" class="bg-dark text-white" <?php echo $currentFontSize == 'normal' ? 'selected' : ''; ?>>Standardní</option>
                                <option value="large" class="bg-dark text-white" <?php echo $currentFontSize == 'large' ? 'selected' : ''; ?>>Větší</option>
                                <option value="huge" class="bg-dark text-white" <?php echo $currentFontSize == 'huge' ? 'selected' : ''; ?>>Velké</option>
                            </select>
                        </div>
                    </div>
                    <?php if (getSetting('ai_enabled', '0') == '1'): ?>
                    <?php 
                        $provider = getSetting('ai_provider', 'gemini');
                        if ($provider !== 'custom'):
                            $modelKey = ($provider === 'openai') ? 'openai_model' : 'gemini_model';
                            $currentModel = getSetting($modelKey);
                            $models = getAvailableAiModels($provider);
                    ?>
                    <div class="p-3 pt-0">
                        <div class="mb-0">
                            <label class="form-label text-white-50 small fw-bold mb-2">AI Model (<?php echo ($provider === 'openai' ? 'OpenAI' : 'Gemini'); ?>)</label>
                            <select class="form-select bg-transparent text-white border-light border-opacity-25 shadow-none" 
                                    onchange="updateQuickSetting('<?php echo $modelKey; ?>', this.value)">
                                <?php foreach ($models as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" class="bg-dark text-white" <?php echo $currentModel == $val ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <div class="p-2 border-top border-light border-opacity-10 text-center">
                        <a href="settings.php" class="btn btn-sm btn-link-settings w-100 py-1">
                            <i class="bi bi-gear me-1"></i> Přejít do nastavení
                        </a>
                    </div>
                </div>
            </div>

            <div class="form-check form-switch mb-0 <?php echo getSetting('theme_toggle_enabled', '1') == '1' ? '' : 'd-none'; ?>" id="headerThemeToggleContainer">
                    <input class="form-check-input" type="checkbox" id="themeToggle">
                    <label class="form-check-label text-white small d-none d-sm-inline-block" for="themeToggle">Dark</label>
            </div>

            <a href="logout.php" id="headerLockIcon" class="btn btn-sm btn-link text-white-50 p-0 d-none d-md-block <?php echo !empty(getSetting('app_password')) ? '' : 'd-none'; ?>" title="Odhlásit se">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>

    </div>
</nav>

<div class="offcanvas offcanvas-start offcanvas-glass text-white pt-5" tabindex="-1" id="offcanvasNavbar" style="overflow: hidden;">
    <div id="bgcircle-primary"></div>
    <div id="bgcircle-pruple"></div>
    <div class="offcanvas-header border-bottom border-light border-opacity-25" style="position: relative; z-index: 1;">
        <h5 class="offcanvas-title fw-bold" id="offcanvasNavbarLabel">Menu</h5>
        <a class="navbar-brand fw-bold d-md-none " href="index.php" >
            <span>    
                <img src="./assets/logoAlt.png" alt="DevBase Logo"  class="d-inline-block align-text-top logo" style="max-width: 20px;">
            </span>
            <span>DevBase</span>
        </a>
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
            <div class="border-top border-light border-opacity-10 my-2"></div>
            <a href="logout.php" class="sidebar-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Odhlásit se
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
            inboxBadgeContainer.innerHTML = '<span class="badge badge-todo ms-2">' + data.stats.total_inbox_new + '</span>';
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

function updateQuickSetting(key, val) {
    const formData = new FormData();
    formData.append('action', 'toggle_setting');
    formData.append('key', key);
    formData.append('value', val);

    fetch('api/api_settings_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (key === 'ui_font_size') {
                document.body.className = document.body.className.replace(/font-size-\w+/g, 'font-size-' + val);
                
                // If we are on settings page, update the select there too
                const settingsSelect = document.querySelector('select[name="ui_font_size"]');
                if (settingsSelect) {
                    settingsSelect.value = val;
                }
            }
            if (key === 'openai_model' || key === 'gemini_model') {
                const settingsSelect = document.querySelector('select[name="' + key + '"]');
                if (settingsSelect) {
                    settingsSelect.value = val;
                }
            }
        } else {
            console.error('Error updating setting:', data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
<main class="container-fluid">
