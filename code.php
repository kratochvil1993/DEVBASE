<?php
require_once 'includes/functions.php';

if (getSetting('code_enabled', '1') !== '1') {
    header('Location: index.php');
    exit;
}

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'add') {
        $new_id = createScratchpad('Draft ' . (count(getAllScratchpads()) + 1));
        header("Location: code.php?id=$new_id");
        exit;
    }
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        deleteScratchpad($_GET['id']);
        header('Location: code.php');
        exit;
    }
}

// Handle save request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_code') {
        $content = $_POST['content'] ?? '';
        $id = $_POST['id'] ?? null;
        if ($id) {
            saveScratchpadContent($content, $id);
            
            // Handle rename if provided
            if (isset($_POST['name']) && !empty($_POST['name'])) {
                renameScratchpad($id, $_POST['name']);
            }
            
            header("Location: code.php?id=$id&saved=1");
            exit;
        }
    } elseif ($_POST['action'] == 'move_to_notes') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $tags = $_POST['tags'] ?? [];
        $scratchpad_id = $_POST['scratchpad_id'] ?? null;
        
        if ($title && $content && $scratchpad_id) {
            $saved_id = saveNote($title, $content, null, $tags);
            if ($saved_id) {
                deleteScratchpad($scratchpad_id);
                header("Location: code.php?note_moved=1");
                exit;
            }
        }
    } elseif ($_POST['action'] == 'move_to_snippets') {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $code = $_POST['code'] ?? '';
        $language_id = $_POST['language_id'] ?? null;
        $tags = $_POST['tags'] ?? [];
        $scratchpad_id = $_POST['scratchpad_id'] ?? null;
        
        if ($title && $code && $scratchpad_id) {
            $is_locked = isset($_POST['is_locked']) ? 1 : 0;
            $saved_id = saveSnippet($title, $description, $code, $language_id, $tags, null, $is_locked);
            if ($saved_id) {
                deleteScratchpad($scratchpad_id);
                header("Location: code.php?snippet_moved=1");
                exit;
            }
        }
    }
}

$scratchpads = getAllScratchpads();

// Determine active ID: 1. URL parameter, 2. Cookie, 3. First available scratchpad
$active_id = null;
if (isset($_GET['id'])) {
    $active_id = (int)$_GET['id'];
    setcookie('last_scratchpad_id', $active_id, time() + (86400 * 30), "/");
} elseif (isset($_COOKIE['last_scratchpad_id'])) {
    $active_id = (int)$_COOKIE['last_scratchpad_id'];
} else {
    $active_id = $scratchpads[0]['id'] ?? null;
}

// If active ID is not in list (e.g. deleted), fallback to first
$active_pad = null;
foreach ($scratchpads as $pad) {
    if ($pad['id'] == $active_id) {
        $active_pad = $pad;
        break;
    }
}

if (!$active_pad && !empty($scratchpads)) {
    $active_pad = $scratchpads[0];
    $active_id = $active_pad['id'];
    // Update cookie with the actual active ID if the previous one was invalid
    setcookie('last_scratchpad_id', $active_id, time() + (86400 * 30), "/");
}



$content = $active_pad ? $active_pad['content'] : '';
$pad_name = $active_pad ? $active_pad['name'] : 'Draft';
$allNoteTags = getAllTags('note');
$allSnippetTags = getAllTags('snippet');
$languages = getAllLanguages();

include 'includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="glass-card no-jump p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="flex-grow-1 me-4">
                    <div class="d-flex align-items-center mb-1">
                        <h4 class="text-white mb-0 me-3"><i class="bi bi-braces me-2"></i> </h4>
                        <input type="text" id="padName" class="form-control-plaintext text-white h4 mb-0 fw-bold p-0" 
                               value="<?php echo htmlspecialchars($pad_name); ?>" placeholder="Název draftu..."
                               style="border: none; outline: none; background: transparent;">
                    </div>                   
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (isset($_GET['saved'])): ?>
                        <div id="saveToast" class="badge bg-success d-flex align-items-center px-3 py-2 me-2" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-check-circle me-1"></i> Uloženo!
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['note_moved'])): ?>
                        <div id="moveToast" class="badge bg-info d-flex align-items-center px-3 py-2 me-2" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-journal-check me-1"></i> Přesunuto do poznámek!
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['snippet_moved'])): ?>
                        <div id="snippetToast" class="badge bg-primary d-flex align-items-center px-3 py-2 me-2" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-code-square me-1"></i> Přesunuto do snippetů!
                        </div>
                    <?php endif; ?>
                    <button type="button" class="btn btn-copy px-3" onclick="copyCode(this)">
                        <i class="bi bi-clipboard me-2"></i> copy
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-send-to px-3 dropdown-toggle text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-send me-2"></i> Poslat do
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark glass-dropdown border-light border-opacity-10">
                            <li><a class="dropdown-item" href="#" onclick="openAddToSnippetsModal()"><i class="bi bi-code-slash me-2"></i> do Snippets</a></li>
                            <li><a class="dropdown-item" href="#" onclick="openAddToNotesModal()"><i class="bi bi-journal-plus me-2"></i> do Notes</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-add-snipet px-3" onclick="saveCode()">
                        <i class="bi bi-save me-2"></i> Uložit
                    </button>
                </div>
            </div>

            <!-- Tab Bar -->
            <div class="d-flex align-items-center mb-0 overflow-auto tab-container">
                <?php foreach ($scratchpads as $pad): ?>
                    <div class="nav-tab-item <?php echo $pad['id'] == $active_id ? 'active' : ''; ?> me-1">
                        <a href="code.php?id=<?php echo $pad['id']; ?>" class="nav-tab-link py-2 px-3">
                            <i class="bi bi-file-earmark-code me-1"></i>
                            <?php echo htmlspecialchars($pad['name']); ?>
                        </a>
                        <?php if (count($scratchpads) > 1): ?>
                            <button type="button" class="btn-tab-close ms-0" onclick="confirmDelete(<?php echo $pad['id']; ?>, '<?php echo addslashes($pad['name']); ?>')">
                                <i class="bi bi-x"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <a href="code.php?action=add" class="btn btn-add-tab ms-1" title="Nový draft">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>

            <div class="editor-container border border-light border-opacity-10 rounded-bottom overflow-hidden shadow-lg" style="border-top-left-radius: 0 !important; border-top-right-radius: 0 !important;">
                <textarea id="codeEditor"><?php echo htmlspecialchars($content); ?></textarea>
            </div>
            
            <div class="mt-3 d-flex justify-content-between align-items-center text-white-50 small">
                <div>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Ctrl+S uložit</span>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Alt+L focus</span>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Alt+N nový</span>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Alt+W zavřít</span>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Alt+←/→ taby</span>
                </div>
                <div id="charCount">Znaků: 0</div>
            </div>
        </div>
    </div>
</div>

<form id="saveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="save_code">
    <input type="hidden" name="id" value="<?php echo $active_id; ?>">
    <input type="hidden" name="name" id="formPadName">
    <textarea name="content" id="formContent"></textarea>
</form>

<!-- CodeMirror Assets -->
<link rel="stylesheet" href="assets/vendor/codemirror/lib/codemirror.css">
<link rel="stylesheet" href="assets/vendor/codemirror/theme/dracula.css">
<link rel="stylesheet" href="assets/vendor/codemirror/addon/hint/show-hint.css">
<link rel="stylesheet" href="assets/vendor/codemirror/addon/dialog/dialog.css">
<link rel="stylesheet" href="assets/vendor/codemirror/addon/fold/foldgutter.css">
<script src="assets/vendor/codemirror/lib/codemirror.js"></script>

<!-- CodeMirror Addons -->
<script src="assets/vendor/codemirror/addon/edit/closebrackets.js"></script>
<script src="assets/vendor/codemirror/addon/edit/closetag.js"></script>
<script src="assets/vendor/codemirror/addon/hint/show-hint.js"></script>
<script src="assets/vendor/codemirror/addon/hint/xml-hint.js"></script>
<script src="assets/vendor/codemirror/addon/hint/javascript-hint.js"></script>
<script src="assets/vendor/codemirror/addon/hint/html-hint.js"></script>
<script src="assets/vendor/codemirror/addon/hint/css-hint.js"></script>
<script src="assets/vendor/codemirror/addon/search/search.js"></script>
<script src="assets/vendor/codemirror/addon/search/searchcursor.js"></script>
<script src="assets/vendor/codemirror/addon/search/jump-to-line.js"></script>
<script src="assets/vendor/codemirror/addon/dialog/dialog.js"></script>
<script src="assets/vendor/codemirror/addon/fold/foldcode.js"></script>
<script src="assets/vendor/codemirror/addon/fold/foldgutter.js"></script>
<script src="assets/vendor/codemirror/addon/fold/brace-fold.js"></script>
<script src="assets/vendor/codemirror/addon/fold/xml-fold.js"></script>

<!-- CodeMirror Modes -->
<script src="assets/vendor/codemirror/mode/xml/xml.js"></script>
<script src="assets/vendor/codemirror/mode/javascript/javascript.js"></script>
<script src="assets/vendor/codemirror/mode/css/css.js"></script>
<script src="assets/vendor/codemirror/mode/clike/clike.js"></script>
<script src="assets/vendor/codemirror/mode/php/php.js"></script>
<script src="assets/vendor/codemirror/mode/htmlmixed/htmlmixed.js"></script>

<style>
.tab-container::-webkit-scrollbar {
    height: 3px;
}
.tab-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}
.nav-tab-item {
    display: flex;
    align-items: center;
    background: rgba(40, 42, 54, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-bottom: none;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    padding: 0 4px 0 0;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.nav-tab-item:hover {
    background: rgba(255, 255, 255, 0.08);
}
.nav-tab-item.active {
    background: rgba(142, 84, 233, 0.3);
    border-color: rgba(255, 255, 255, 0.2);
    border-bottom: 2px solid #8e54e9;
}
.nav-tab-link {
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.85rem;
    display: block;
}
.nav-tab-item.active .nav-tab-link {
    color: #fff;
    font-weight: 500;
}
.btn-tab-close {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.3);
    font-size: 1rem;
    padding: 0 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
}
.btn-tab-close:hover {
    color: #ff5555;
    background: rgba(255, 85, 85, 0.1);
}
.btn-add-tab {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.4);
    border-radius: 5px;
    padding: 4px 8px;
    transition: all 0.2s ease;
}
.btn-add-tab:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
}
.CodeMirror {
    height: 65vh;
    font-size: 15px;
    background: rgba(40, 42, 54, 0.6) !important;
    backdrop-filter: blur(4px);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    padding: 10px;
}
.CodeMirror-gutters {
    background: rgba(40, 42, 54, 0.3) !important;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
}
.CodeMirror-foldgutter {
    width: 0.7em;
}
.CodeMirror-foldgutter-open, .CodeMirror-foldgutter-folded {
    cursor: pointer;
    color: #6272a4;
}
.CodeMirror-hints {
    background: #282a36 !important;
    border: 1px solid #44475a !important;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.5);
}
.CodeMirror-hint {
    color: #f8f8f2 !important;
    padding: 4px 10px !important;
}
li.CodeMirror-hint-active {
    background: #44475a !important;
    color: #f8f8f2 !important;
}
.glass-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
}
.glass-dropdown {
    background: rgba(25, 25, 25, 0.95) !important;
    backdrop-filter: blur(15px) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
    padding: 0.5rem 0 !important;
}
.dropdown-item {
    transition: all 0.2s ease !important;
    padding: 0.6rem 1.2rem !important;
}
.dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    transform: none !important;
}
.btn-copy {
    background: rgba(142, 84, 233, 0.15);
    border: 1px solid rgba(142, 84, 233, 0.4);
    color: #fff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-copy:hover {
    background: rgba(142, 84, 233, 0.3);
    border-color: rgba(142, 84, 233, 0.6);
    color: #fff;
    transform: none;
    box-shadow: 0 5px 15px rgba(142, 84, 233, 0.2);
}
.btn-copy:active {
    transform: translateY(0);
}
.btn-send-to {
    background: rgba(13, 202, 240, 0.15);
    border: 1px solid rgba(13, 202, 240, 0.4);
    color: #fff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-send-to:hover {
    background: rgba(13, 202, 240, 0.3);
    border-color: rgba(13, 202, 240, 0.6);
    color: #fff;
    transform: none;
    box-shadow: 0 5px 15px rgba(13, 202, 240, 0.2);
}
.btn-send-to:active {
    transform: translateY(0);
}
.btn-add-snipet:hover {
    transform: none !important;
}
@keyframes fadeOut {
    0% { opacity: 1; }
    80% { opacity: 1; }
    100% { opacity: 0; }
}
</style>

<!-- Add to Notes Modal -->
<div class="modal fade" id="addToNotesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white">Přidat draft do poznámek</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="addToNotesForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="move_to_notes">
                    <input type="hidden" name="scratchpad_id" value="<?php echo $active_id; ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Název poznámky</label>
                            <input type="text" name="title" id="noteTitleInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required placeholder="Napište název...">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Obsah</label>
                            <div id="quillEditor" style="height: 300px; background: transparent; color: white;"></div>
                            <input type="hidden" name="content" id="noteContentInput">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small d-block">Štítky</label>
                            <div class="d-flex flex-wrap gap-2 p-3 rounded border border-light border-opacity-10">
                                <?php if (empty($allNoteTags)): ?>
                                    <p class="text-white-50 small mb-0 w-100 text-center">Žádné štítky nejsou definovány.</p>
                                <?php else: ?>
                                    <?php foreach ($allNoteTags as $tag): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="noteTag<?php echo $tag['id']; ?>">
                                            <label class="form-check-label text-white-50 small" for="noteTag<?php echo $tag['id']; ?>">
                                                <span class="badge" style="background-color: <?php echo $tag['color'] ? htmlspecialchars($tag['color']) : '#6c757d'; ?>">
                                                    <?php echo htmlspecialchars($tag['name']); ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-add-snipet px-4">Vytvořit poznámku a smazat draft</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add to Snippets Modal -->
<div class="modal fade" id="addToSnippetsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white">Přidat draft do snippetů</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="addToSnippetsForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="move_to_snippets">
                    <input type="hidden" name="scratchpad_id" value="<?php echo $active_id; ?>">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50 small">Název snippetu</label>
                            <input type="text" name="title" id="snippetTitleInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required placeholder="Název snippetu...">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label text-white-50 small">Popis</label>
                            <textarea name="description" id="snippetDescriptionInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" rows="2" placeholder="Krátký popis..."></textarea>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_locked" id="snippetLockedInput" value="1">
                                <label class="form-check-label text-white-50 small" for="snippetLockedInput">
                                    <i class="bi bi-lock-fill me-1"></i> Skrýt obsah
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Jazyk</label>
                            <select name="language_id" id="snippetLanguageSelect" class="form-select bg-transparent text-white border-light border-opacity-25 shadow-none">
                                <option value="" class="text-dark">Vybrat jazyk</option>
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?php echo $lang['id']; ?>" class="text-dark"><?php echo htmlspecialchars($lang['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small d-block">Štítky</label>
                            <div class="d-flex flex-wrap gap-2 p-2 rounded border border-light border-opacity-10">
                                <?php if (empty($allSnippetTags)): ?>
                                    <p class="text-white-50 small mb-0 w-100 text-center">Není štítek.</p>
                                <?php else: ?>
                                    <?php foreach ($allSnippetTags as $tag): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="snipTag<?php echo $tag['id']; ?>">
                                            <label class="form-check-label text-white-50 small" for="snipTag<?php echo $tag['id']; ?>">
                                                <span class="badge" style="background-color: <?php echo $tag['color'] ? htmlspecialchars($tag['color']) : '#6c757d'; ?>">
                                                    <?php echo htmlspecialchars($tag['name']); ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Kód</label>
                            <textarea name="code" id="snippetCodeInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none font-monospace" rows="10" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-add-snipet px-4">Vytvořit snippet a smazat draft</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editor;
let quill;

document.addEventListener('DOMContentLoaded', function() {
    editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
        lineNumbers: true,
        mode: 'php',
        theme: 'dracula',
        tabSize: 4,
        indentUnit: 4,
        lineWrapping: true,
        viewportMargin: Infinity,
        matchBrackets: true,
        autoCloseBrackets: true,
        autoCloseTags: true,
        foldGutter: true,
        gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
        extraKeys: {
            "Ctrl-Space": "autocomplete",
            "Ctrl-Q": function(cm){ cm.foldCode(cm.getCursor()); }
        }
    });

    updateCharCount();
    editor.on('change', updateCharCount);

    // Quill for Modal
    quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Napište vaši poznámku...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'clean']
            ]
        }
    });

    document.getElementById('addToNotesForm').addEventListener('submit', function() {
        if (quill) {
            document.getElementById('noteContentInput').value = quill.root.innerHTML;
        }
    });

    // Shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if (document.getElementById('addToNotesModal').classList.contains('show')) {
                document.getElementById('addToNotesForm').requestSubmit();
            } else if (document.getElementById('addToSnippetsModal').classList.contains('show')) {
                document.getElementById('addToSnippetsForm').requestSubmit();
            } else {
                saveCode();
            }
        }
        
        // Option + L focus editor
        if (e.altKey && e.code === 'KeyL') {
            e.preventDefault();
            if (editor) {
                editor.focus();
            }
        }

        // Option + N new scratchpad
        if (e.altKey && e.code === 'KeyN') {
            e.preventDefault();
            window.location.href = 'code.php?action=add';
        }

        // Option + W close current scratchpad
        if (e.altKey && e.code === 'KeyW') {
            e.preventDefault();
            const activeTab = document.querySelector('.nav-tab-item.active');
            if (activeTab) {
                const closeBtn = activeTab.querySelector('.btn-tab-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            }
        }

        // Option + Right/Left arrow for tab switching
        if (e.altKey && (e.code === 'ArrowRight' || e.code === 'ArrowLeft')) {
            const tabs = Array.from(document.querySelectorAll('.nav-tab-link'));
            const activeIndex = tabs.findIndex(tab => tab.closest('.nav-tab-item').classList.contains('active'));
            
            if (activeIndex !== -1 && tabs.length > 1) {
                e.preventDefault();
                let nextIndex;
                if (e.code === 'ArrowRight') {
                    nextIndex = (activeIndex + 1) % tabs.length;
                } else {
                    nextIndex = (activeIndex - 1 + tabs.length) % tabs.length;
                }
                window.location.href = tabs[nextIndex].href;
            }
        }
    });
});

function updateCharCount() {
    const content = editor.getValue();
    document.getElementById('charCount').textContent = 'Znaků: ' + content.length;
}

function saveCode() {
    document.getElementById('formContent').value = editor.getValue();
    document.getElementById('formPadName').value = document.getElementById('padName').value;
    document.getElementById('saveForm').submit();
}

function openAddToNotesModal() {
    const title = document.getElementById('padName').value;
    const content = editor.getValue();
    
    document.getElementById('noteTitleInput').value = title;
    
    if (quill) {
        // Clear and insert content as a code block
        const escapedContent = content.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        quill.clipboard.dangerouslyPasteHTML('<pre>' + escapedContent + '</pre>');
    }
    
    const modalEl = document.getElementById('addToNotesModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function openAddToSnippetsModal() {
    const title = document.getElementById('padName').value;
    const content = editor.getValue();
    
    document.getElementById('snippetTitleInput').value = title;
    document.getElementById('snippetCodeInput').value = content;
    const lockInput = document.getElementById('snippetLockedInput');
    if (lockInput) lockInput.checked = false;
    
    const modalEl = document.getElementById('addToSnippetsModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function confirmDelete(id, name) {
    if (confirm('Opravdu chcete smazat draft "' + name + '"?')) {
        window.location.href = 'code.php?action=delete&id=' + id;
    }
}

function copyCode(btn) {
    const content = editor.getValue();
    navigator.clipboard.writeText(content).then(() => {
        const originalHtml = btn.innerHTML;
        
        btn.innerHTML = '<i class="bi bi-check2 me-2"></i> Zkopírováno!';
        btn.classList.replace('btn-copy', 'btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.replace('btn-success', 'btn-copy');
        }, 2000);
    }).catch(err => {
        console.error('Chyba při kopírování: ', err);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
