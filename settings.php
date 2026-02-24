<?php
require_once 'includes/functions.php';

// Handle Tag actions
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'save_tag') {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        $color = !empty($_POST['color']) ? $_POST['color'] : null;
        $type = !empty($_POST['type']) ? $_POST['type'] : 'snippet';
        saveTag($_POST['name'], $color, $type, $id);
    } elseif ($_POST['action'] == 'delete_tag') {
        deleteTag($_POST['id']);
    } elseif ($_POST['action'] == 'save_language') {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        saveLanguage($_POST['name'], $_POST['prism_class'], $id);
    } elseif ($_POST['action'] == 'delete_language') {
        deleteLanguage($_POST['id']);
    } elseif ($_POST['action'] == 'toggle_snippets') {
        $enabled = isset($_POST['snippets_enabled']) ? '1' : '0';
        updateSetting('snippets_enabled', $enabled);
    } elseif ($_POST['action'] == 'toggle_notes') {
        $enabled = isset($_POST['notes_enabled']) ? '1' : '0';
        updateSetting('notes_enabled', $enabled);
    } elseif ($_POST['action'] == 'toggle_todos') {
        $enabled = isset($_POST['todos_enabled']) ? '1' : '0';
        updateSetting('todos_enabled', $enabled);
    } elseif ($_POST['action'] == 'toggle_code') {
        $enabled = isset($_POST['code_enabled']) ? '1' : '0';
        updateSetting('code_enabled', $enabled);
    } elseif ($_POST['action'] == 'toggle_todo_badge') {
        $enabled = isset($_POST['todo_badge_enabled']) ? '1' : '0';
        updateSetting('todo_badge_enabled', $enabled);
    } elseif ($_POST['action'] == 'toggle_theme_toggle') {
        $enabled = isset($_POST['theme_toggle_enabled']) ? '1' : '0';
        updateSetting('theme_toggle_enabled', $enabled);
    } elseif ($_POST['action'] == 'save_security') {
        $enabled = isset($_POST['security_enabled']) ? '1' : '0';
        $currentPassword = getSetting('app_password');
        $newPassword = $_POST['app_password'] ?? '';
        $confirmPassword = $_POST['app_password_confirm'] ?? '';

        if (!empty($newPassword) && $newPassword === $confirmPassword) {
            // Setting new password - always enable security as well
            $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
            updateSetting('app_password', $hashed_password);
            updateSetting('security_enabled', '1');
        } elseif (!empty($currentPassword)) {
            // Password already set, just toggle enable state
            updateSetting('security_enabled', $enabled);
        }
    } elseif ($_POST['action'] == 'reset_password') {
        updateSetting('app_password', '');
        updateSetting('security_enabled', '0');
    } elseif ($_POST['action'] == 'export_data') {
        $data = exportAllData();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'devbase_export_' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo $json;
        exit;
    } elseif ($_POST['action'] == 'import_data') {
        if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
            $json = file_get_contents($_FILES['import_file']['tmp_name']);
            $data = json_decode($json, true);
            $mode = $_POST['import_mode'] ?? 'append';
            if ($data) {
                importAllData($data, $mode);
                header('Location: settings.php?import=success');
                exit;
            }
        }
        header('Location: settings.php?import=error');
        exit;
    }
    header('Location: settings.php');
    exit;
}

$snippetsEnabled = getSetting('snippets_enabled', '1');
$notesEnabled = getSetting('notes_enabled', '1');
$todosEnabled = getSetting('todos_enabled', '1');
$securityEnabled = getSetting('security_enabled', '0');
$snippetTags = getAllTags('snippet');
$noteTags = getAllTags('note');
$todoTags = getAllTags('todo');
$languages = getAllLanguages();

include 'includes/header.php';
?>

<div class="container">


<div class="row">
    <div class="col-12 mb-4">
        <h2 class="text-white fw-bold">Nastavení</h2>        
    </div>

    <!-- General Settings -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <h4 class="text-white mb-3">Obecné nastavení</h4>
            <form method="POST" id="settingsFormSnippets" class="mb-3">
                <input type="hidden" name="action" value="toggle_snippets">
                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                    <input class="form-check-input fs-4 ms-0" type="checkbox" name="snippets_enabled" id="snippetsEnabledToggle" 
                           <?php echo $snippetsEnabled == '1' ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label text-white" for="snippetsEnabledToggle">
                        <span class="d-block fw-bold">Povolit sekci Snippety</span>
                        <small class="text-white-50">Zobrazit nebo skrýt sekci se snipety kódu.</small>
                    </label>
                </div>
            </form>
            <form method="POST" id="settingsFormNotes" class="mb-3">
                <input type="hidden" name="action" value="toggle_notes">
                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                    <input class="form-check-input fs-4 ms-0" type="checkbox" name="notes_enabled" id="notesEnabledToggle" 
                           <?php echo $notesEnabled == '1' ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label text-white" for="notesEnabledToggle">
                        <span class="d-block fw-bold">Povolit sekci Notes</span>
                        <small class="text-white-50">Zobrazit nebo skrýt sekci s poznámkami.</small>
                    </label>
                </div>
            </form>

            <form method="POST" id="settingsFormTodos" class="mb-3">
                <input type="hidden" name="action" value="toggle_todos">
                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                    <input class="form-check-input fs-4 ms-0" type="checkbox" name="todos_enabled" id="todosEnabledToggle" 
                           <?php echo $todosEnabled == '1' ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label text-white" for="todosEnabledToggle">
                        <span class="d-block fw-bold">Povolit sekci TODO</span>
                        <small class="text-white-50">Zobrazit nebo skrýt sekci s úkoly.</small>
                    </label>
                </div>
            </form>

            <form method="POST" id="settingsFormCode" class="mb-3">
                <input type="hidden" name="action" value="toggle_code">
                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                    <input class="form-check-input fs-4 ms-0" type="checkbox" name="code_enabled" id="codeEnabledToggle" 
                           <?php echo getSetting('code_enabled', '1') == '1' ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label text-white" for="codeEnabledToggle">
                        <span class="d-block fw-bold">Povolit sekci Code</span>
                        <small class="text-white-50">Zobrazit nebo skrýt sekci s editorem kódu.</small>
                    </label>
                </div>
            </form>

            <form method="POST" id="settingsFormTodoBadge" class="mb-3">
                <input type="hidden" name="action" value="toggle_todo_badge">
                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                    <input class="form-check-input fs-4 ms-0" type="checkbox" name="todo_badge_enabled" id="todoBadgeEnabledToggle" 
                           <?php echo getSetting('todo_badge_enabled', '1') == '1' ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label text-white" for="todoBadgeEnabledToggle">
                        <span class="d-block fw-bold">Zobrazovat badge u TODO</span>
                        <small class="text-white-50">Zobrazit počet aktivních úkolů v hlavní navigaci.</small>
                    </label>
                </div>
            </form>

            <form method="POST" id="settingsFormThemeToggle">
                <input type="hidden" name="action" value="toggle_theme_toggle">
                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                    <input class="form-check-input fs-4 ms-0" type="checkbox" name="theme_toggle_enabled" id="themeToggleEnabledToggle" 
                           <?php echo getSetting('theme_toggle_enabled', '1') == '1' ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label text-white" for="themeToggleEnabledToggle">
                        <span class="d-block fw-bold">Zobrazovat přepínač Dark modu</span>
                        <small class="text-white-50">Zobrazit nebo skrýt tlačítko pro změnu vzhledu v navigaci.</small>
                    </label>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <h4 class="text-white mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Zabezpečení</h4>
            <?php $hasPassword = !empty(getSetting('app_password')); ?>
            <div class="security-settings-container">
                <form method="POST" class="mb-0">
                    <input type="hidden" name="action" value="save_security">
                    
                    <div class="form-check form-switch d-flex align-items-center gap-3 ps-0 mb-4">
                        <input class="form-check-input fs-4 ms-0" type="checkbox" name="security_enabled" id="securityEnabledToggle" 
                               <?php echo $securityEnabled == '1' ? 'checked' : ''; ?>
                               <?php echo !$hasPassword ? 'disabled' : 'onchange="this.form.submit()"'; ?>>
                        <label class="form-check-label text-white" for="securityEnabledToggle">
                            <span class="d-block fw-bold">Povolit zámek aplikace</span>
                            <small class="text-white-50">Po aktivaci bude aplikace vyžadovat heslo při každém vstupu.</small>
                        </label>
                    </div>

                    <?php if (!$hasPassword): ?>
                        <div class="mb-3">
                            <label class="form-label text-white-50 small fw-bold">Nastavit heslo</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-transparent border-light border-opacity-25 text-white-50">
                                    <i class="bi bi-key-fill"></i>
                                </span>
                                <input type="password" name="app_password" id="app_password" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" 
                                       placeholder="Nové heslo..." required>
                            </div>
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-transparent border-light border-opacity-25 text-white-50">
                                    <i class="bi bi-shield-check"></i>
                                </span>
                                <input type="password" name="app_password_confirm" id="app_password_confirm" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" 
                                       placeholder="Kontrola hesla..." required>
                            </div>
                            
                            <button class="btn btn-primary w-100 mb-3" type="submit" id="saveSecurityBtn">
                                <i class="bi bi-shield-lock-fill me-2"></i>Uložit heslo a aktivovat zámek
                            </button>

                            <div id="passwordMatchMessage" class="small mb-2 d-none"></div>
                            
                            <div class="small text-warning mt-3">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Heslo zatím není nastaveno!
                            </div>
                        </div>
                    <?php endif; ?>
                </form>

                <?php if ($hasPassword): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="small text-success">
                            <i class="bi bi-check-circle-fill me-1"></i> Heslo je nastaveno
                        </div>
                        <form method="POST" onsubmit="return confirm('Opravdu chcete smazat heslo a vypnout zámek?');" class="d-inline">
                            <input type="hidden" name="action" value="reset_password">
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 py-0 shadow-none">
                                <i class="bi bi-trash me-1"></i> Resetovat heslo
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Snippet Tag Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white mb-0">Štítky kódů</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-edit-order rounded px-3" id="editSnippetTagsOrderBtn" onclick="toggleSnippetTagsSorting()">
                        <i class="bi bi-arrows-move me-1"></i> Upravit pořadí
                    </button>
                    <button class="btn btn-sm btn-success rounded px-3 d-none" id="saveSnippetTagsOrderBtn" onclick="toggleSnippetTagsSorting()">
                        <i class="bi bi-check-lg me-1"></i> Hotovo
                    </button>
                </div>
            </div>
            
            <form method="POST" class="mb-4" id="tagForm">
                <input type="hidden" name="action" value="save_tag">
                <input type="hidden" name="type" value="snippet">
                <input type="hidden" name="id" id="tagId" value="">
                <div class="input-group">
                    <input type="color" id="tagColorPicker" class="form-control form-control-color bg-transparent border-light border-opacity-25" style="max-width: 50px;" title="Vyberte barvu nebo nechte prázdné">
                    <input type="text" name="color" id="tagColor" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="#hex" style="max-width: 150px;" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$">
                    <input type="text" name="name" id="tagName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Název" required>
                    <button class="btn btn-add-snipet" type="submit" id="tagSubmitBtn">Přidat</button>
                </div>
            </form>

            <div class="list-group list-group-flush bg-transparent" style="max-height: 400px; overflow-y: auto;" id="snippetTagsList">
                <?php foreach ($snippetTags as $tag): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0 snippet-tag-row" data-id="<?php echo $tag['id']; ?>">
                        <span>
                            <?php if (!empty($tag['color'])): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: #fff;">
                            <?php else: ?>
                                <span class="badge bg-secondary">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        </span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editTag(<?php echo json_encode($tag); ?>)'>
                                <i class="bi bi-pencil"></i> 
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tento štítek smazat?');">
                                <input type="hidden" name="action" value="delete_tag">
                                <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                    <i class="bi bi-trash"></i> 
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Note Tag Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white mb-0">Štítky poznámek</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-edit-order rounded px-3" id="editNoteTagsOrderBtn" onclick="toggleNoteTagsSorting()">
                        <i class="bi bi-arrows-move me-1"></i> Upravit pořadí
                    </button>
                    <button class="btn btn-sm btn-success rounded px-3 d-none" id="saveNoteTagsOrderBtn" onclick="toggleNoteTagsSorting()">
                        <i class="bi bi-check-lg me-1"></i> Hotovo
                    </button>
                </div>
            </div>
            
            <form method="POST" class="mb-4" id="noteTagForm">
                <input type="hidden" name="action" value="save_tag">
                <input type="hidden" name="type" value="note">
                <input type="hidden" name="id" id="noteTagId" value="">
                <div class="input-group">
                    <input type="color" id="noteTagColorPicker" class="form-control form-control-color bg-transparent border-light border-opacity-25" style="max-width: 50px;" title="Vyberte barvu nebo nechte prázdné">
                    <input type="text" name="color" id="noteTagColor" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="#hex" style="max-width: 150px;" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$">
                    <input type="text" name="name" id="noteTagName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Název" required>
                    <button class="btn btn-add-snipet" type="submit" id="noteTagSubmitBtn">Přidat</button>
                </div>
            </form>

            <div class="list-group list-group-flush bg-transparent" style="max-height: 400px; overflow-y: auto;" id="noteTagsList">
                <?php foreach ($noteTags as $tag): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0 note-tag-row" data-id="<?php echo $tag['id']; ?>">
                        <span>
                            <?php if (!empty($tag['color'])): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: #fff;">
                            <?php else: ?>
                                <span class="badge bg-secondary">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        </span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editNoteTag(<?php echo json_encode($tag); ?>)'>
                                <i class="bi bi-pencil"></i> 
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tento štítek smazat?');">
                                <input type="hidden" name="action" value="delete_tag">
                                <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                    <i class="bi bi-trash"></i> 
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Todo Tag Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white mb-0">Štítky úkolů</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-edit-order rounded px-3" id="editTodoTagsOrderBtn" onclick="toggleTodoTagsSorting()">
                        <i class="bi bi-arrows-move me-1"></i> Upravit pořadí
                    </button>
                    <button class="btn btn-sm btn-success rounded px-3 d-none" id="saveTodoTagsOrderBtn" onclick="toggleTodoTagsSorting()">
                        <i class="bi bi-check-lg me-1"></i> Hotovo
                    </button>
                </div>
            </div>
            
            <form method="POST" class="mb-4" id="todoTagForm">
                <input type="hidden" name="action" value="save_tag">
                <input type="hidden" name="type" value="todo">
                <input type="hidden" name="id" id="todoTagId" value="">
                <div class="input-group">
                    <input type="color" id="todoTagColorPicker" class="form-control form-control-color bg-transparent border-light border-opacity-25" style="max-width: 50px;" title="Vyberte barvu nebo nechte prázdné">
                    <input type="text" name="color" id="todoTagColor" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="#hex" style="max-width: 150px;" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$">
                    <input type="text" name="name" id="todoTagName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Název" required>
                    <button class="btn btn-add-snipet" type="submit" id="todoTagSubmitBtn">Přidat</button>
                </div>
            </form>

            <div class="list-group list-group-flush bg-transparent" style="max-height: 400px; overflow-y: auto;" id="todoTagsList">
                <?php foreach ($todoTags as $tag): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0 todo-tag-row" data-id="<?php echo $tag['id']; ?>">
                        <span>
                            <?php if (!empty($tag['color'])): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: #fff;">
                            <?php else: ?>
                                <span class="badge bg-secondary">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        </span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editTodoTag(<?php echo json_encode($tag); ?>)'>
                                <i class="bi bi-pencil"></i> 
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tento štítek smazat?');">
                                <input type="hidden" name="action" value="delete_tag">
                                <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                    <i class="bi bi-trash"></i> 
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Language Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <h4 class="text-white mb-4">Správa jazyků</h4>
            
            <form method="POST" class="mb-4" id="langForm">
                <input type="hidden" name="action" value="save_language">
                <input type="hidden" name="id" id="langId" value="">
                <div class="mb-3">
                    <input type="text" name="name" id="langName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none mb-2" placeholder="Název jazyka" required>
                    <input type="text" name="prism_class" id="langClass" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Třída Prism" required>
                </div>
                <button class="btn btn-add-snipet w-100" type="submit" id="langSubmitBtn">Přidat jazyk</button>
            </form>

            <div class="list-group list-group-flush bg-transparent">
                <?php foreach ($languages as $lang): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0">
                        <div>
                            <span class="fw-bold"><?php echo htmlspecialchars($lang['name']); ?></span>
                            <small class="text-white-50 ms-2">(<?php echo htmlspecialchars($lang['prism_class']); ?>)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editLanguage(<?php echo json_encode($lang); ?>)'>
                                <i class="bi bi-pencil"></i> 
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Jste si jisti?');">
                                <input type="hidden" name="action" value="delete_language">
                                <input type="hidden" name="id" value="<?php echo $lang['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                    <i class="bi bi-trash"></i> 
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
   

    <!-- Backup and Restore -->
    <div class="col-12 mb-4">
        <div class="glass-card p-4">
            <h4 class="text-white mb-4"><i class="bi bi-cloud-arrow-down me-2 text-primary"></i>Záloha a obnovení dat</h4>
            
            <?php if (isset($_GET['import'])): ?>
                <?php if ($_GET['import'] == 'success'): ?>
                    <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i> Data byla úspěšně importována.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Při importu dat došlo k chybě. Zkontrolujte formát souboru.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <h5 class="text-white-50 small fw-bold mb-3 text-uppercase">Export dat</h5>
                    <p class="text-white-50 small mb-4">Stáhněte si všechna svá data (snippety, poznámky, úkoly, štítky i nastavení) v jednom JSON souboru pro účely zálohy nebo přenosu.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="export_data">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-download me-2"></i>Exportovat do JSON
                        </button>
                    </form>
                </div>
                
                <div class="col-md-6 border-start border-light border-opacity-10">
                    <h5 class="text-white-50 small fw-bold mb-3 text-uppercase">Import dat</h5>
                    <p class="text-white-50 small mb-4">Nahrajte data ze záložního JSON souboru. Vyberte, zda chcete data přidat k existujícím nebo vše přepsat.</p>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_data">
                        
                        <div class="mb-3">
                            <input type="file" name="import_file" id="importFile" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" accept=".json" required>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="import_mode" id="modeAppend" value="append" checked>
                                <label class="form-check-label text-white-50 small" for="modeAppend">Přidat k existujícím</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="import_mode" id="modeOverwrite" value="overwrite">
                                <label class="form-check-label text-white-50 small" for="modeOverwrite">Přepsat vše (smazat stávající)</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary px-4" onclick="return confirm('Jste si jisti? Import může změnit nebo přepsat vaše stávající data.')">
                            <i class="bi bi-upload me-2"></i>Importovat data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<!-- SortableJS -->
<script src="assets/vendor/sortablejs/Sortable.min.js"></script>

<script>
let snippetSortable = null;
let isSnippetSortingMode = false;

function toggleSnippetTagsSorting() {
    isSnippetSortingMode = !isSnippetSortingMode;
    const list = document.getElementById('snippetTagsList');
    const editBtn = document.getElementById('editSnippetTagsOrderBtn');
    const saveBtn = document.getElementById('saveSnippetTagsOrderBtn');
    const form = document.getElementById('tagForm');
    const actionButtons = list.querySelectorAll('.d-flex.gap-2'); // edit / delete buttons wrapper

    if (isSnippetSortingMode) {
        list.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        form.classList.add('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.add('opacity-0', 'pe-none'));

        snippetSortable = new Sortable(list, {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveTagsOrder('snippetTagsList', '.snippet-tag-row');
            }
        });
    } else {
        list.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        form.classList.remove('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.remove('opacity-0', 'pe-none'));

        if (snippetSortable) {
            snippetSortable.destroy();
            snippetSortable = null;
        }
    }
}

let noteSortable = null;
let isNoteSortingMode = false;

function toggleNoteTagsSorting() {
    isNoteSortingMode = !isNoteSortingMode;
    const list = document.getElementById('noteTagsList');
    const editBtn = document.getElementById('editNoteTagsOrderBtn');
    const saveBtn = document.getElementById('saveNoteTagsOrderBtn');
    const form = document.getElementById('noteTagForm');
    const actionButtons = list.querySelectorAll('.d-flex.gap-2'); // edit / delete buttons wrapper

    if (isNoteSortingMode) {
        list.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        form.classList.add('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.add('opacity-0', 'pe-none'));

        noteSortable = new Sortable(list, {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveTagsOrder('noteTagsList', '.note-tag-row');
            }
        });
    } else {
        list.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        form.classList.remove('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.remove('opacity-0', 'pe-none'));

        if (noteSortable) {
            noteSortable.destroy();
            noteSortable = null;
        }
    }
}

let todoSortable = null;
let isTodoSortingMode = false;

function toggleTodoTagsSorting() {
    isTodoSortingMode = !isTodoSortingMode;
    const list = document.getElementById('todoTagsList');
    const editBtn = document.getElementById('editTodoTagsOrderBtn');
    const saveBtn = document.getElementById('saveTodoTagsOrderBtn');
    const form = document.getElementById('todoTagForm');
    const actionButtons = list.querySelectorAll('.d-flex.gap-2'); // edit / delete buttons wrapper

    if (isTodoSortingMode) {
        list.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        form.classList.add('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.add('opacity-0', 'pe-none'));

        todoSortable = new Sortable(list, {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveTagsOrder('todoTagsList', '.todo-tag-row');
            }
        });
    } else {
        list.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        form.classList.remove('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.remove('opacity-0', 'pe-none'));

        if (todoSortable) {
            todoSortable.destroy();
            todoSortable = null;
        }
    }
}

function saveTagsOrder(listId, rowSelector) {
    const list = document.getElementById(listId);
    const items = list.querySelectorAll(rowSelector);
    const order = [];
    items.forEach((item, index) => {
        order.push({
            id: item.dataset.id,
            order: index
        });
    });
    
    fetch('api/api_tags_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ order: order }),
    })
    .then(response => response.json())
    .then(data => {
        console.log(listId + ' order saved:', data);
    });
}

// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('app_password');
    const confirm = document.getElementById('app_password_confirm');
    const message = document.getElementById('passwordMatchMessage');
    const submitBtn = document.getElementById('saveSecurityBtn');

    if (password && confirm) {
        function validatePassword() {
            if (password.value === '' && confirm.value === '') {
                message.classList.add('d-none');
                submitBtn.disabled = false;
                return;
            }

            message.classList.remove('d-none');
            if (password.value === confirm.value) {
                message.textContent = 'Hesla se shodují';
                message.className = 'small mb-2 text-success';
                submitBtn.disabled = false;
            } else {
                message.textContent = 'Hesla se neshodují!';
                message.className = 'small mb-2 text-danger';
                // Only disable if we are actually trying to set a new password
                if (password.value !== '') {
                    submitBtn.disabled = true;
                } else {
                    submitBtn.disabled = false;
                }
            }
        }

        password.addEventListener('input', validatePassword);
        confirm.addEventListener('input', validatePassword);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
