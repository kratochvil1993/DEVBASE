<?php
require_once 'includes/functions.php';

if (getSetting('code_enabled', '1') !== '1') {
    header('Location: index.php');
    exit;
}

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'add') {
        $new_id = createScratchpad('Draft ' . (count(getAllScratchpads('code')) + 1), 'code');
        header("Location: code.php?id=$new_id");
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

    }
}

$scratchpads = getAllScratchpads('code');

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
$geminiApiKey = getSetting('gemini_api_key');
$aiEnabled = getSetting('ai_enabled', '0') == '1' && (!empty($geminiApiKey) || !empty(getSetting('openai_api_key')));

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
                    <div id="saveToast" class="badge bg-success d-none align-items-center px-3 py-2 me-2">
                        <i class="bi bi-check-circle me-1"></i> Uloženo!
                    </div>
                    <div id="moveToast" class="badge bg-info d-none align-items-center px-3 py-2 me-2">
                        <i class="bi bi-journal-check me-1"></i> Přesunuto do poznámek!
                    </div>
                    <div id="snippetToast" class="badge bg-primary d-none align-items-center px-3 py-2 me-2">
                        <i class="bi bi-code-square me-1"></i> Přesunuto do snippetů!
                    </div>
                    
                    <?php if (isset($_GET['saved'])): ?>
                        <div class="badge bg-success d-flex align-items-center px-3 py-2 me-2 legacy-toast" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-check-circle me-1"></i> Uloženo!
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['note_moved'])): ?>
                        <div class="badge bg-info d-flex align-items-center px-3 py-2 me-2 legacy-toast" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-journal-check me-1"></i> Přesunuto do poznámek!
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['snippet_moved'])): ?>
                        <div class="badge bg-primary d-flex align-items-center px-3 py-2 me-2 legacy-toast" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-code-square me-1"></i> Přesunuto do snippetů!
                        </div>
                    <?php endif; ?>
                    <?php if ($aiEnabled): ?>
                    <div class="dropdown">
                        <button class="btn btn-ai px-3 dropdown-toggle text-white border-opacity-25 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="aiCodeBtn">
                            <i class="bi bi-robot me-1"></i> AI
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark glass-card border-light border-opacity-10 mt-2 shadow-lg">
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('explain_code')">
                                    <i class="bi bi-chat-left-text me-2 text-ai"></i> Vysvětlit kód
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('refactor_code')">
                                    <i class="bi bi-magic me-2 text-ai"></i> Refaktorovat
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('debug_code')">
                                    <i class="bi bi-bug me-2 text-ai"></i> Debugger
                                </a>
                            </li>
                        </ul>
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
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="openAddToSnippetsModal()"><i class="bi bi-code-slash me-2"></i> do Snippets</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="openAddToNotesModal()"><i class="bi bi-journal-plus me-2"></i> do Notes</a></li>
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
                    <div class="nav-tab-item <?php echo $pad['id'] == $active_id ? 'active' : ''; ?> me-1" data-id="<?php echo $pad['id']; ?>">
                        <a href="code.php?id=<?php echo $pad['id']; ?>" class="nav-tab-link py-2 px-3" onclick="switchTab(event, <?php echo $pad['id']; ?>)">
                            <i class="bi bi-file-earmark-code me-1"></i>
                            <span class="tab-name"><?php echo htmlspecialchars($pad['name']); ?></span>
                        </a>
                        <?php if (count($scratchpads) > 1): ?>
                            <button type="button" class="btn-tab-close ms-0" onclick="confirmDelete(<?php echo $pad['id']; ?>, '<?php echo addslashes($pad['name']); ?>')">
                                <i class="bi bi-x"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button onclick="addNewTab(event)" class="btn btn-add-tab ms-1" title="Nový draft" id="addTabBtn">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>

            <?php if ($aiEnabled): ?>
            <!-- AI Insight Box -->
            <div id="aiInsightBox" class="p-3 rounded-0 border-start border-end d-none" style="background: rgba(10, 10, 15, 0.6); border-color: rgba(142, 84, 233, 0.3) !important; backdrop-filter: blur(5px);">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-robot text-ai me-2"></i>
                    <span class="small fw-bold text-white-50 text-uppercase tracking-wider">AI Assistant</span>
                    <button type="button" class="btn-close btn-close-white ms-auto small" style="font-size: 0.5rem;" onclick="document.getElementById('aiInsightBox').classList.add('d-none')"></button>
                </div>
                <div id="aiInsightContent" class="text-white small lh-base" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap;"></div>
            </div>
            <?php endif; ?>

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
                <div id="autosaveIndicator" class="ms-3 text-white-50 small" style="transition: all 0.3s ease;">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Připraveno
                </div>
            </div>
        </div>
    </div>
</div>

<form id="saveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="save_code">
    <input type="hidden" name="id" id="activeScratchpadId" value="<?php echo $active_id; ?>">
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
.btn-ai {
    background: rgba(142, 84, 233, 0.1);
    border: 1px solid rgba(142, 84, 233, 0.3);
    color: #fff;
    transition: all 0.3s ease;
}
.btn-ai:hover {
    background: rgba(142, 84, 233, 0.25);
    border-color: rgba(142, 84, 233, 0.5);
    color: #fff;
    box-shadow: 0 0 15px rgba(142, 84, 233, 0.2);
}
.text-ai {
    color: #a78bfa;
}
#aiInsightContent strong {
    color: #8e54e9;
    font-weight: 700;
}
.cm-color-preview {
    display: inline-block;
    width: 12px;
    height: 12px;
    margin-right: 4px;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 2px;
    vertical-align: middle;
    cursor: pointer;
}

.flash-purple {
    animation: purpleFlash 2s ease;
}
@keyframes purpleFlash {
    0% { box-shadow: 0 0 0px rgba(142, 84, 233, 0); }
    50% { box-shadow: 0 0 20px rgba(142, 84, 233, 0.5); border-color: rgba(142, 84, 233, 0.8) !important; }
    100% { box-shadow: 0 0 0px rgba(142, 84, 233, 0); }
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
}</style>

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
                    <input type="hidden" name="scratchpad_id" id="modalNoteScratchpadId" value="<?php echo $active_id; ?>">
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
                    <input type="hidden" name="scratchpad_id" id="modalSnippetScratchpadId" value="<?php echo $active_id; ?>">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="form-label text-white-50 small mb-0">Název snippetu</label>
                                <?php if ($aiEnabled): ?>
                                <button type="button" class="btn btn-sm btn-ai-action" onclick="generateAiField('generate_title', 'snippetTitleInput')" title="Generovat název">
                                    <i class="bi bi-magic me-1"></i> AI
                                </button>
                                <?php endif; ?>
                            </div>
                            <input type="text" name="title" id="snippetTitleInput" class="form-control form-control-ai text-white border-light border-opacity-25" required placeholder="Název snippetu...">
                        </div>
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="form-label text-white-50 small mb-0">Popis</label>
                                <?php if ($aiEnabled): ?>
                                <button type="button" class="btn btn-sm btn-ai-action" onclick="generateAiField('generate_description', 'snippetDescriptionInput')" title="Generovat popis">
                                    <i class="bi bi-magic me-1"></i> AI
                                </button>
                                <?php endif; ?>
                            </div>
                            <textarea name="description" id="snippetDescriptionInput" class="form-control form-control-ai text-white border-light border-opacity-25" rows="2" placeholder="Krátký popis..."></textarea>
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
let lastSavedContent;
let lastSavedName;

function triggerAutosave() {
    if (!editor) return;
    const currentContent = editor.getValue();
    const currentName = document.getElementById('padName').value;
    const padId = document.getElementById('activeScratchpadId')?.value;

    if (!padId || (currentContent === lastSavedContent && currentName === lastSavedName)) return;

    const autosaveIndicator = document.getElementById('autosaveIndicator');
    if (autosaveIndicator) {
        autosaveIndicator.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Ukládám...';
    }

    window.fetchAutosave({
        id: padId,
        content: currentContent,
        name: currentName
    }, autosaveIndicator).then(res => {
        if (res && res.status === 'success') {
            lastSavedContent = currentContent;
            lastSavedName = currentName;
        }
    });
}

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
    
    // Color Picker Init
    initColorPicker();

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

    document.getElementById('addToNotesForm').addEventListener('submit', function(e) {
        if (quill) {
            document.getElementById('noteContentInput').value = quill.root.innerHTML;
        }

        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Přesouvám...';

        fetch('api/api_move_scratchpad.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const modalEl = document.getElementById('addToNotesModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                // Show toast
                const toast = document.getElementById('moveToast');
                if (toast) {
                    toast.classList.replace('d-none', 'd-flex');
                    toast.style.animation = 'none';
                    void toast.offsetWidth;
                    toast.style.animation = 'fadeOut 3s forwards';
                    setTimeout(() => toast.classList.replace('d-flex', 'd-none'), 3000);
                }

                // Remove the tab and switch
                const padId = formData.get('scratchpad_id');
                removeTabGracefully(padId);
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('Chyba při komunikaci se serverem'))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    document.getElementById('addToSnippetsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Přesouvám...';

        fetch('api/api_move_scratchpad.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const modalEl = document.getElementById('addToSnippetsModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                // Show toast
                const toast = document.getElementById('snippetToast');
                if (toast) {
                    toast.classList.replace('d-none', 'd-flex');
                    toast.style.animation = 'none';
                    void toast.offsetWidth;
                    toast.style.animation = 'fadeOut 3s forwards';
                    setTimeout(() => toast.classList.replace('d-flex', 'd-none'), 3000);
                }

                // Remove the tab and switch
                const padId = formData.get('scratchpad_id');
                removeTabGracefully(padId);
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('Chyba při komunikaci se serverem'))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    function removeTabGracefully(padId) {
        const tabItem = document.querySelector(`.nav-tab-item[data-id="${padId}"]`);
        if (tabItem) {
            const isActive = tabItem.classList.contains('active');
            const allTabs = Array.from(document.querySelectorAll('.nav-tab-item'));
            
            if (isActive && allTabs.length > 1) {
                const currentIndex = allTabs.indexOf(tabItem);
                const nextTab = allTabs[currentIndex + 1] || allTabs[currentIndex - 1];
                if (nextTab) {
                    switchTab(null, nextTab.getAttribute('data-id'));
                }
            }
            
            if (allTabs.length > 1) {
                tabItem.remove();
                updateTabControls();
            } else {
                // If it was the last tab, it wouldn't be deleted by the server 
                // but for UX we just clear it
                editor.setValue('');
                document.getElementById('padName').value = 'Draft';
            }
        }
    }

    // Autosave logic
    lastSavedContent = editor.getValue();
    lastSavedName = document.getElementById('padName').value;
    const autosaveIndicator = document.getElementById('autosaveIndicator');



    // Interval save (every 30s)
    setInterval(triggerAutosave, 30000);

    // Visibility change save (when user switches browser tab)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            triggerAutosave();
        }
    });

    // Intercept navigation links within the page (tabs)
    document.querySelectorAll('.btn-add-tab').forEach(link => {
        link.addEventListener('click', (e) => {
            triggerAutosave();
        });
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
            addNewTab(e);
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
            const tabItems = Array.from(document.querySelectorAll('.nav-tab-item'));
            const activeIndex = tabItems.findIndex(item => item.classList.contains('active'));
            
            if (activeIndex !== -1 && tabItems.length > 1) {
                e.preventDefault();
                let nextIndex;
                if (e.code === 'ArrowRight') {
                    nextIndex = (activeIndex + 1) % tabItems.length;
                } else {
                    nextIndex = (activeIndex - 1 + tabItems.length) % tabItems.length;
                }
                const nextId = tabItems[nextIndex].getAttribute('data-id');
                switchTab(null, nextId);
            }
        }
    });
});

function updateCharCount() {
    const content = editor.getValue();
    document.getElementById('charCount').textContent = 'Znaků: ' + content.length;
}

function saveCode() {
    const currentContent = editor.getValue();
    const currentName = document.getElementById('padName').value;
    const padId = document.getElementById('activeScratchpadId')?.value;
    const saveToast = document.getElementById('saveToast');
    
    if (!padId) return;

    // Show temporary indicator
    const autosaveIndicator = document.getElementById('autosaveIndicator');
    if (autosaveIndicator) {
        autosaveIndicator.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Ukládám...';
    }

    window.fetchAutosave({
        id: padId,
        content: currentContent,
        name: currentName
    }, autosaveIndicator).then(res => {
        if (res && res.status === 'success') {
            lastSavedContent = currentContent;
            lastSavedName = currentName;
            
            // Show toast
            if (saveToast) {
                saveToast.classList.replace('d-none', 'd-flex');
                saveToast.style.animation = 'none';
                void saveToast.offsetWidth; // trigger reflow
                saveToast.style.animation = 'fadeOut 3s forwards';
                setTimeout(() => saveToast.classList.replace('d-flex', 'd-none'), 3000);
            }

            // Update tab name in UI if changed
            const activeTab = document.querySelector('.nav-tab-item.active .tab-name');
            if (activeTab) activeTab.textContent = currentName;
        }
    });

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
        const tabItem = document.querySelector(`.nav-tab-item[data-id="${id}"]`);
        if (!tabItem) return;

        const isActive = tabItem.classList.contains('active');
        
        // Show loading state on the close button if possible
        const closeBtn = tabItem.querySelector('.btn-tab-close');
        if (closeBtn) closeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 0.7rem; height: 0.7rem;"></span>';

        fetch('api/api_delete_scratchpad.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (isActive) {
                        // Switch to another tab before removing
                        const allTabs = Array.from(document.querySelectorAll('.nav-tab-item'));
                        const currentIndex = allTabs.indexOf(tabItem);
                        const nextTab = allTabs[currentIndex + 1] || allTabs[currentIndex - 1];
                        
                        if (nextTab) {
                            const nextId = nextTab.getAttribute('data-id');
                            switchTab(null, nextId);
                        }
                    }
                    
                    // Remove the tab element
                    tabItem.remove();
                    
                    // Update controls (hide close buttons if only one left)
                    updateTabControls();
                } else {
                    alert('Chyba při mazání: ' + data.message);
                    if (closeBtn) closeBtn.innerHTML = '<i class="bi bi-x"></i>';
                }
            })
            .catch(error => {
                console.error('Error deleting tab:', error);
                alert('Nastala chyba při mazání tabu.');
                if (closeBtn) closeBtn.innerHTML = '<i class="bi bi-x"></i>';
            });
    }
}

function updateTabControls() {
    const tabs = document.querySelectorAll('.nav-tab-item');
    tabs.forEach(tab => {
        const closeBtn = tab.querySelector('.btn-tab-close');
        if (tabs.length > 1) {
            // Ensure close button exists
            if (!closeBtn) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-tab-close ms-0';
                btn.innerHTML = '<i class="bi bi-x"></i>';
                btn.onclick = (e) => {
                    const id = tab.getAttribute('data-id');
                    const name = tab.querySelector('.tab-name').textContent;
                    confirmDelete(id, name);
                };
                tab.appendChild(btn);
            } else {
                closeBtn.style.display = '';
            }
        } else if (closeBtn) {
            // Hide or remove if only one tab left
            closeBtn.style.display = 'none';
        }
    });
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

let aiTypingInterval = null;

function aiAction(action) {
    const code = editor.getValue().trim();
    const insightBox = document.getElementById('aiInsightBox');
    const insightContent = document.getElementById('aiInsightContent');
    const aiBtn = document.getElementById('aiCodeBtn');

    if (!code) {
        alert('Editor je prázdný!');
        return;
    }

    if (!insightBox || !insightContent) return;

    // Uzavření AI dropdownu po kliknutí
    if (aiBtn) {
        const bsDropdown = bootstrap.Dropdown.getInstance(aiBtn);
        if (bsDropdown) bsDropdown.hide();
    }

    // Clear previous typing
    if (aiTypingInterval) clearInterval(aiTypingInterval);
    
    insightBox.classList.remove('d-none');
    insightContent.innerHTML = '<div class="d-flex align-items-center gap-2 py-2"><div class="spinner-border spinner-border-sm text-ai" role="status"></div><span class="text-white-50">AI zpracovává kód...</span></div>';
    aiBtn.disabled = true;

    fetch('api/api_ai_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, content: code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            typeWriter(data.answer, insightContent);
            insightBox.classList.remove('flash-purple');
            void insightBox.offsetWidth;
            insightBox.classList.add('flash-purple');
        } else {
            insightContent.innerHTML = '<div class="text-danger p-2"><i class="bi bi-exclamation-triangle me-2"></i>' + data.message + '</div>';
        }
    })
    .catch(error => {
        insightContent.innerHTML = '<div class="text-danger p-2"><i class="bi bi-exclamation-triangle me-2"></i>Chyba při komunikaci s AI.</div>';
    })
    .finally(() => {
        aiBtn.disabled = false;
    });
}

function typeWriter(text, container) {
    container.innerHTML = '';
    
    // Pre-processing markdown to HTML
    let processedHtml = text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/^\* /gm, '• ')
        .replace(/\n/g, '<br>');

    let i = 0;
    const speed = 5; // Slightly slower for better readability
    
    function type() {
        if (i < processedHtml.length) {
            // If we hit a tag, we need to append the whole tag at once
            if (processedHtml.charAt(i) === '<') {
                let tagEnd = processedHtml.indexOf('>', i);
                if (tagEnd !== -1) {
                    container.innerHTML += processedHtml.substring(i, tagEnd + 1);
                    i = tagEnd + 1;
                } else {
                    container.innerHTML += processedHtml.charAt(i);
                    i++;
                }
            } else {
                container.innerHTML += processedHtml.charAt(i);
                i++;
            }
            aiTypingInterval = setTimeout(type, speed);
            container.scrollTop = container.scrollHeight;
        }
    }
    type();
}

function generateAiField(action, targetId) {
    const code = document.getElementById('snippetCodeInput').value.trim();
    const target = document.getElementById(targetId);
    
    if (!code) {
        alert('Nejdříve vložte kód!');
        return;
    }

    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> AI';

    fetch('api/api_ai_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, content: code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            target.value = data.answer.replace(/^\\s*[-*•]\\s*/, '').trim();
            target.classList.remove('flash-purple');
            void target.offsetWidth; // trigger reflow
            target.classList.add('flash-purple');
            setTimeout(() => {
                target.classList.remove('flash-purple');
            }, 2000);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Chyba při komunikaci s AI.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

function initColorPicker() {
    if (!editor) return;
    
    function updateColors() {
        const content = editor.getValue();
        const colorRegex = /#[0-9a-fA-F]{3,6}|rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(?:,\s*[\d\.]+\s*)?\)/g;
        
        // Clear old widgets
        editor.getAllMarks().forEach(mark => {
            if (mark.className === 'cm-color-mark') mark.clear();
        });

        let match;
        while ((match = colorRegex.exec(content)) !== null) {
            const start = editor.posFromIndex(match.index);
            const end = editor.posFromIndex(match.index + match[0].length);
            
            const badge = document.createElement('span');
            badge.className = 'cm-color-preview';
            badge.style.backgroundColor = match[0];
            
            badge.onclick = (e) => {
                const input = document.createElement('input');
                input.type = 'color';
                // Convert to hex if it's RGB for the native picker
                input.value = match[0].startsWith('#') ? (match[0].length === 4 ? '#' + match[0][1] + match[0][1] + match[0][2] + match[0][2] + match[0][3] + match[0][3] : match[0]) : '#ffffff';
                
                input.oninput = () => {
                    const newColor = input.value;
                    const range = mark.find();
                    if (range) {
                        editor.replaceRange(newColor, range.from, range.to);
                    }
                };
                input.click();
            };

            const mark = editor.markText(start, end, {
                replacedWith: (function() {
                    const wrapper = document.createElement('span');
                    wrapper.appendChild(badge);
                    wrapper.appendChild(document.createTextNode(match[0]));
                    return wrapper;
                })(),
                className: 'cm-color-mark',
                handleMouseEvents: true
            });
        }
    }

    editor.on('change', debounce(updateColors, 500));
    updateColors();
}

function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}


function addNewTab(event) {
    if (event) event.preventDefault();
    
    const btn = document.getElementById('addTabBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

    fetch('api/api_create_scratchpad.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const pad = data.data;
                
                // Create new tab element
                const tabContainer = document.querySelector('.tab-container');
                const newTab = document.createElement('div');
                newTab.className = 'nav-tab-item me-1';
                newTab.setAttribute('data-id', pad.id);
                newTab.innerHTML = `
                    <a href="code.php?id=${pad.id}" class="nav-tab-link py-2 px-3" onclick="switchTab(event, ${pad.id})">
                        <i class="bi bi-file-earmark-code me-1"></i>
                        <span class="tab-name">${pad.name}</span>
                    </a>
                    <button type="button" class="btn-tab-close ms-0" onclick="confirmDelete(${pad.id}, '${pad.name}')">
                        <i class="bi bi-x"></i>
                    </button>
                `;
                
                // Insert before the plus button
                tabContainer.insertBefore(newTab, btn);
                
                // Update controls (show close buttons if count > 1)
                updateTabControls();

                // Switch to it
                switchTab(null, pad.id);
            } else {
                alert('Chyba: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error adding tab:', error);
            alert('Chyba při vytváření tabu.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
}

function switchTab(event, id) {
    if (event) event.preventDefault();
    
    const activeIdInput = document.getElementById('activeScratchpadId');
    const currentId = activeIdInput.value;
    
    // Don't do anything if we're clicking the already active tab
    if (currentId == id) return;

    // Trigger autosave for the current tab first
    triggerAutosave();

    const autosaveIndicator = document.getElementById('autosaveIndicator');
    if (autosaveIndicator) {
        autosaveIndicator.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Načítám...';
    }

    // Fetch the new tab's content
    fetch(`api/api_get_scratchpad.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const pad = data.data;
                
                // Update editor and title
                editor.setValue(pad.content);
                document.getElementById('padName').value = pad.name;
                
                // Skrytí AI boxu při přepnutí tabu
                const insightBox = document.getElementById('aiInsightBox');
                if (insightBox) insightBox.classList.add('d-none');
                
                // Update hidden IDs
                activeIdInput.value = pad.id;
                document.getElementById('modalNoteScratchpadId').value = pad.id;
                document.getElementById('modalSnippetScratchpadId').value = pad.id;
                
                // Update UI - active class
                document.querySelectorAll('.nav-tab-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('data-id') == pad.id) {
                        item.classList.add('active');
                    }
                });

                // Update URL without page reload
                const newUrl = `code.php?id=${pad.id}`;
                window.history.pushState({id: pad.id}, pad.name, newUrl);
                
                // Update last_scratchpad_id cookie
                document.cookie = `last_scratchpad_id=${pad.id}; path=/; max-age=${86400 * 30}`;

                // Update last saved indicators
                lastSavedContent = pad.content;
                lastSavedName = pad.name;

                if (autosaveIndicator) {
                    autosaveIndicator.innerHTML = '<i class="bi bi-check-circle me-1"></i> Přepnuto';
                    setTimeout(() => {
                        autosaveIndicator.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Připraveno';
                    }, 2000);
                }
            } else {
                alert('Chyba při načítání: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error switching tab:', error);
            alert('Nastala chyba při přepínání tabu.');
        });
}

// Handle back/forward button in browser
window.onpopstate = function(event) {
    if (event.state && event.state.id) {
        switchTab(null, event.state.id);
    } else {
        // If no state, try to get ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');
        if (id) switchTab(null, id);
    }
};

</script>

<?php include 'includes/footer.php'; ?>
