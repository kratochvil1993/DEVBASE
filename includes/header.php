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
                <?php if (getSetting('snippets_enabled', '1') == '1'): ?>
                <a href="index.php" class="nav-toggle-btn <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <i class="bi bi-code-slash me-2"></i> <span class="d-none d-md-inline">Snippets</span>
                </a>
                <?php endif; ?>
                <?php if (getSetting('code_enabled', '1') == '1'): ?>
                <a href="code.php" class="nav-toggle-btn <?php echo ($currentPage == 'code.php') ? 'active' : ''; ?>">
                    <i class="bi bi-braces me-2"></i> <span class="d-none d-md-inline">Code Drafts</span>
                </a>
                <?php endif; ?>
                <?php if (getSetting('note_drafts_enabled', '1') == '1'): ?>
                <a href="notes_drafts.php" class="nav-toggle-btn <?php echo ($currentPage == 'notes_drafts.php') ? 'active' : ''; ?>">
                    <i class="bi bi-journal-plus me-2"></i> <span class="d-none d-md-inline">Note Drafts</span>
                </a>
                <?php endif; ?>
                <?php if (getSetting('notes_enabled', '1') == '1'): ?>
                <a href="notes.php" class="nav-toggle-btn <?php echo $currentPage == 'notes.php' ? 'active' : ''; ?>">
                    <i class="bi bi-journal-text me-2"></i> <span class="d-none d-md-inline">Notes</span>
                </a>
                <?php endif; ?>
                <?php if (getSetting('todos_enabled', '1') == '1'): ?>
                <a href="todo.php" class="nav-toggle-btn <?php echo $currentPage == 'todo.php' ? 'active' : ''; ?> d-flex align-items-center">
                    <i class="bi bi-check2-square me-2"></i> <span class="d-none d-md-inline">TODO</span>
                    <?php 
                    if (getSetting('todo_badge_enabled', '1') == '1' && $stats['total_todos'] > 0) {
                        echo '<span class="badge badge-todo ms-2">' . $stats['total_todos'] . '</span>';
                    }
                    ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="ms-auto d-flex align-items-center gap-3">
        <?php if (getSetting('todos_enabled', '1') == '1'): 
            $reminders = getTodoReminders();
            $criticalCount = count($reminders['critical']);
            $warningCount = count($reminders['warning']);
            $totalReminders = $criticalCount + $warningCount;
            
            if ($totalReminders > 0):
                $badgeClass = ($criticalCount > 0) ? 'bg-danger' : 'bg-warning text-dark';
                $pulseClass = ($criticalCount > 0) ? 'pulse-red' : '';
        ?>
            <div class="dropdown">
                <button class="btn btn-link text-white p-0 position-relative <?php echo $pulseClass; ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill <?php echo $badgeClass; ?>" style="font-size: 0.6rem;">
                        <?php echo $totalReminders; ?>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-glass p-0 mt-2" style="width: 280px;">
                    <div class="p-3 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Upozornění</h6>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $totalReminders; ?> celkem</span>
                    </div>
                    <div class="reminder-list" style="max-height: 300px; overflow-y: auto;">
                        <?php if ($criticalCount > 0): ?>
                            <div class="px-3 py-2 bg-danger bg-opacity-10 small fw-bold text-danger-emphasis">Po termínu / Dnes</div>
                            <?php foreach ($reminders['critical'] as $todo): ?>
                                <a class="dropdown-item px-3 py-2 border-bottom border-light border-opacity-10" href="todo.php#todo-card-<?php echo $todo['id']; ?>">
                                    <div class="text-truncate fw-medium"><?php echo htmlspecialchars($todo['text']); ?></div>
                                    <small class="text-danger"><i class="bi bi-calendar-x me-1"></i> <?php echo date('j. n. Y', strtotime($todo['deadline'])); ?></small>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($warningCount > 0): ?>
                            <div class="px-3 py-2 bg-warning bg-opacity-10 small fw-bold text-warning-emphasis">Zítra vyprší</div>
                            <?php foreach ($reminders['warning'] as $todo): ?>
                                <a class="dropdown-item px-3 py-2 border-bottom border-light border-opacity-10" href="todo.php#todo-card-<?php echo $todo['id']; ?>">
                                    <div class="text-truncate fw-medium"><?php echo htmlspecialchars($todo['text']); ?></div>
                                    <small class="text-warning"><i class="bi bi-calendar-event me-1"></i> <?php echo date('j. n. Y', strtotime($todo['deadline'])); ?></small>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="p-2 text-center">
                        <a href="todo.php" class="btn btn-sm btn-link text-white-50 text-decoration-none" style="font-size: 0.75rem;">Zobrazit všechny úkoly</a>
                    </div>
                </div>
            </div>
        <?php 
            endif;
        endif; ?>

        <?php if (getSetting('theme_toggle_enabled', '1') == '1'): ?>
        <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="themeToggle">
                <label class="form-check-label text-white small" for="themeToggle">Dark</label>
        </div>
        <?php endif; ?>
        <?php 
        $hasAiKey = !empty(getSetting('gemini_api_key'));
        ?>
        <a href="settings.php" class="btn btn-sm btn-link <?php echo $hasAiKey ? 'text-ai' : 'text-white-50'; ?> p-0" title="<?php echo $hasAiKey ? 'AI Configured' : 'AI not configured'; ?>">
            <i class="bi bi-robot fs-5"></i>
        </a>
        <?php if (getSetting('security_enabled', '0') == '1'): ?>
                <a href="?lock=1" class="btn btn-sm btn-link text-white-50 p-0" title="Lock App">
                    <i class="bi bi-lock-fill fs-5"></i>
                </a>            
        <?php endif; ?>
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
            <?php if (getSetting('snippets_enabled', '1') == '1'): ?>
            <a href="manage.php" class="sidebar-link <?php echo $currentPage == 'manage.php' ? 'active' : ''; ?>">
                <i class="bi bi-list-task me-2"></i> Správa snippetů
            </a>
            <?php endif; ?>
            <?php if (getSetting('notes_enabled', '1') == '1'): ?>
            <a href="manage_notes.php" class="sidebar-link <?php echo $currentPage == 'manage_notes.php' ? 'active' : ''; ?>">
                <i class="bi bi-list-task me-2"></i> Správa poznámek
            </a>
            <a href="archive_notes.php" class="sidebar-link <?php echo $currentPage == 'archive_notes.php' ? 'active' : ''; ?>">
                <i class="bi bi-archive me-2"></i> Archiv poznámek
            </a>
            <?php endif; ?>
            <?php if (getSetting('todos_enabled', '1') == '1'): ?>            
            <a href="archive_todos.php" class="sidebar-link <?php echo $currentPage == 'archive_todos.php' ? 'active' : ''; ?>">
                <i class="bi bi-archive me-2"></i> Archiv TODO
            </a>
            <?php endif; ?>
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
                <div class="col-4">
                    <div class="glass-card no-jump p-2 text-center h-100" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                        <div class="text-white-50 mb-1" style="font-size: 0.6rem;">Snippetů</div>
                        <div class="h6 fw-bold text-white mb-0"><?php echo $stats['total_snippets']; ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="glass-card no-jump p-2 text-center h-100" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                        <div class="text-white-50 mb-1" style="font-size: 0.6rem;">Notes</div>
                        <div class="h6 fw-bold text-white mb-0"><?php echo $stats['total_notes']; ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="glass-card no-jump p-2 text-center h-100" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                        <div class="text-white-50 mb-1" style="font-size: 0.6rem;">Úkoly</div>
                        <div class="h6 fw-bold text-white mb-0"><?php echo $stats['total_todos']; ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


<main class="container-fluid py-4">
