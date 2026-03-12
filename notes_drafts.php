<?php
require_once 'includes/functions.php';

// We'll use the same settings as notes for now, or maybe a separate one
if (getSetting('notes_enabled', '1') !== '1') {
    header('Location: index.php');
    exit;
}

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'add') {
        $new_id = createScratchpad('Nápad ' . (count(getAllScratchpads('note')) + 1), 'note');
        header("Location: notes_drafts.php?id=$new_id");
        exit;
    }

}

// Handle save request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_note_draft') {
        $content = $_POST['content'] ?? '';
        $id = $_POST['id'] ?? null;
        if ($id) {
            saveScratchpadContent($content, $id);
            
            // Handle rename if provided
            if (isset($_POST['name']) && !empty($_POST['name'])) {
                renameScratchpad($id, $_POST['name']);
            }
            
            header("Location: notes_drafts.php?id=$id&saved=1");
            exit;
        }
    }
}

$scratchpads = getAllScratchpads('note');

// Determine active ID: 1. URL parameter, 2. Cookie, 3. First available scratchpad
$active_id = null;
if (isset($_GET['id'])) {
    $active_id = (int)$_GET['id'];
    setcookie('last_note_draft_id', $active_id, time() + (86400 * 30), "/");
} elseif (isset($_COOKIE['last_note_draft_id'])) {
    $active_id = (int)$_COOKIE['last_note_draft_id'];
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
    setcookie('last_note_draft_id', $active_id, time() + (86400 * 30), "/");
}

$content = $active_pad ? $active_pad['content'] : '';
$pad_name = $active_pad ? $active_pad['name'] : 'Draft';
$allNoteTags = getAllTags('note');
$geminiApiKey = getSetting('gemini_api_key');
$openaiApiKey = getSetting('openai_api_key');
$aiEnabledSetting = getSetting('ai_enabled', '0') == '1';
$hasAi = $aiEnabledSetting && (!empty($geminiApiKey) || !empty($openaiApiKey));

include 'includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="glass-card no-jump p-3 p-lg-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-3">
                <div class="flex-grow-1 w-100 me-lg-4">
                    <div class="d-flex align-items-center mb-1">
                        <h4 class="text-white mb-0 me-3"><i class="bi bi-journal-text me-2"></i> </h4>
                        <input type="text" id="padName" class="form-control-plaintext text-white h4 mb-0 fw-bold p-0" 
                               value="<?php echo htmlspecialchars($pad_name); ?>" placeholder="Název draftu..."
                               style="border: none; outline: none; background: transparent;">
                    </div>                   
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap w-100 justify-content-start justify-content-lg-end">
                    <div id="saveToast" class="badge badge-success-glass d-none align-items-center px-3 py-2 me-2">
                        <i class="bi bi-check-circle me-1"></i> Uloženo!
                    </div>
                    <div id="moveToast" class="badge badge-info-glass d-none align-items-center px-3 py-2 me-2">
                        <i class="bi bi-journal-check me-1"></i> Přesunuto do poznámek!
                    </div>
                    
                    <?php if (isset($_GET['saved'])): ?>
                        <div class="badge badge-success-glass d-flex align-items-center px-3 py-2 me-2 legacy-toast" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-check-circle me-1"></i> Uloženo!
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['note_moved'])): ?>
                        <div class="badge badge-info-glass d-flex align-items-center px-3 py-2 me-2 legacy-toast" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-journal-check me-1"></i> Přesunuto do poznámek!
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($hasAi): ?>
                    <div class="dropdown">
                        <button class="btn btn-ai px-3 dropdown-toggle text-white border-opacity-25 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="aiBtn">
                            <i class="bi bi-robot me-0 me-lg-2"></i> <span class="d-none d-lg-inline">AI</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark glass-dropdown-ai border-light border-opacity-10 mt-2 shadow-lg">
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('structure_note')">
                                    <i class="bi bi-layout-text-sidebar-reverse me-2 text-ai"></i> Strukturovat poznámku
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('format_note')">
                                    <i class="bi bi-magic me-2 text-ai"></i> Zformátovat (AI)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('extract_todos')">
                                    <i class="bi bi-check2-square me-2 text-ai"></i> Extrahovat TODO úkoly
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('generate_tldr')">
                                    <i class="bi bi-lightning-charge me-2 text-ai"></i> Vytvořit TL;DR
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('grammar_check')">
                                    <i class="bi bi-spellcheck me-2 text-ai"></i> Kontrola pravopisu
                                </a>
                            </li>
                            <li class="border-top border-light border-opacity-10 my-1"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="toggleAiPromptBar(true)">
                                    <i class="bi bi-terminal me-2 text-ai"></i> Vlastní prompt...
                                </a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="dropdown">
                        <button class="btn btn-send-to px-3 dropdown-toggle text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-send me-0 me-lg-2"></i>  <span class="d-none d-lg-inline">Poslat do</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark glass-dropdown border-light border-opacity-10">
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="openAddToNotesModal()"><i class="bi bi-journal-plus me-2"></i> do Notes</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-add-snipet px-3" onclick="saveDraft()">
                        <i class="bi bi-save me-0 me-lg-2"></i> <span class="d-none d-lg-inline">Uložit</span>
                    </button>
                </div>
            </div>

            <!-- Tab Bar -->
            <div class="d-flex flex-column flex-lg-row align-items-lg-center mb-0 overflow-lg-auto tab-container gap-1 gap-lg-1">
                <?php foreach ($scratchpads as $pad): ?>
                    <div class="nav-tab-item <?php echo $pad['id'] == $active_id ? 'active' : ''; ?> me-1" data-id="<?php echo $pad['id']; ?>">
                        <a href="notes_drafts.php?id=<?php echo $pad['id']; ?>" class="nav-tab-link py-2 px-3" onclick="switchTab(event, <?php echo $pad['id']; ?>)">
                            <i class="bi bi-file-earmark-text me-1"></i>
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

            <?php if ($hasAi): ?>
            <!-- AI Prompt Bar -->
            <div id="aiPromptBar" class="p-2 border-start border-end d-none" style="background: rgba(142, 84, 233, 0.05); border-color: rgba(142, 84, 233, 0.2) !important; backdrop-filter: blur(5px);">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-transparent border-0 text-ai px-2"><i class="bi bi-robot"></i></span>
                    <input type="text" id="aiCustomPrompt" class="form-control bg-transparent text-white border-0 shadow-none ps-1" placeholder="Zadejte, co má AI s poznámkou udělat (např. 'přepiš do odrážek', 'přelož do AJ')..." onkeyup="if(event.key==='Enter') submitAiPrompt()">
                    <button class="btn btn-ai btn-sm px-3 rounded ms-2" type="button" onclick="submitAiPrompt()" id="aiPromptSubmitBtn">
                        Spustit
                    </button>
                    <button type="button" class="btn-close btn-close-white ms-2 small mt-1" style="font-size: 0.5rem;" onclick="toggleAiPromptBar(false)"></button>
                </div>
            </div>
            <?php endif; ?>

            <div id="aiInsightBox" class="p-3 d-none ai-insight-box m-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-robot text-ai me-2"></i>
                        <span class="small fw-bold text-white-50 text-uppercase tracking-wider">AI Assistant</span>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-light copy-btn px-2 py-0" onclick="copyToClipboard(this, 'aiInsightContent')" style="font-size: 0.65rem; position: relative; top: 0; right: 0;">
                            copy
                        </button>
                        <button type="button" class="btn-close btn-close-white small" style="font-size: 0.5rem;" onclick="document.getElementById('aiInsightBox').classList.add('d-none')"></button>
                    </div>
                </div>
                <div id="aiInsightContent" class="text-white small lh-base" style="max-height: 400px; overflow-y: auto;"></div>
            </div>

            <div class="editor-container border border-light border-opacity-10 rounded-bottom overflow-hidden shadow-lg" style="border-top-left-radius: 0 !important; border-top-right-radius: 0 !important; background: #282a36; position: relative;">
                <button type="button" class="btn btn-sm btn-copy px-3" onclick="copyNote(this)">
                    copy
                </button>
                <div id="quillMainEditor" style="height: 60vh; border: none; color: white; background: #282a36;"></div>
            </div>
            
            <div class="mt-3 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center text-white-50 small gap-2">
                <div class="d-none d-lg-block">
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Ctrl+S uložit</span>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Alt+N nový</span>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Alt+W zavřít</span>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Alt+←/→ taby</span>
                </div>
                <div class="d-flex justify-content-between align-items-center w-100 w-lg-auto gap-3">
                <div id="charCount">Znaků: 0</div>
                <div id="autosaveIndicator" class="ms-3 text-white-50 small" style="transition: all 0.3s ease;">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Připraveno
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="saveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="save_note_draft">
    <input type="hidden" name="id" id="activeScratchpadId" value="<?php echo $active_id; ?>">
    <input type="hidden" name="name" id="formPadName">
    <textarea name="content" id="formContent"></textarea>
</form>

<!-- Styles shared with code.php for consistency -->
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
@media (max-width: 991.98px) {
    .nav-tab-item {
        border-radius: 8px !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        justify-content: space-between;
        padding-right: 8px;
    }
    .nav-tab-item.active {
        background: rgba(142, 84, 233, 0.15) !important;
        border-color: rgba(142, 84, 233, 0.4) !important;
    }
    .nav-tab-link {
        flex-grow: 1;
    }
    .btn-add-tab {
        width: 100%;
        padding: 10px !important;
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px dashed rgba(255, 255, 255, 0.2) !important;
        margin-top: 4px;
        margin-left: 0 !important;
    }
    .btn-tab-close {
        font-size: 1.25rem !important;
        padding: 4px 8px !important;
    }
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
.ql-toolbar.ql-snow {
    border: none !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
    background: #20222b; /* Slightly darker than editor for contrast */
}
.ql-container.ql-snow {
    border: none !important;
    font-family: 'Inter', sans-serif;
    font-size: 16px;
    background: #282a36;
}
.ql-editor {
    padding: 20px;
    background: #282a36;
}
.ql-editor p, .ql-editor ol, .ql-editor ul, .ql-editor blockquote {
    margin-bottom: 1.1rem !important;
}
.ql-editor strong, .ql-editor b {
    color: #00e582;
}
.ql-editor h2, .ql-editor h3, .ql-editor h4 {
    margin-top: 1.5rem !important;
    margin-bottom: 0.75rem !important;
}
.ql-editor.ql-blank::before {
    color: rgba(255, 255, 255, 0.3) !important;
    font-style: normal;
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
}
.btn-send-to {
    background: rgba(13, 202, 240, 0.15);
    border: 1px solid rgba(13, 202, 240, 0.4);
    color: #fff;
}
.btn-send-to:hover {
    background: rgba(13, 202, 240, 0.3);
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
.btn-copy {
    position: absolute;
    top: 70px;
    right: 20px;
    z-index: 10;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(8px);
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-copy:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.4);
    color: #ffffff;
    transform: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.btn-copy:active {
    transform: translateY(0);
}
.editor-container {
    scroll-margin-top: 100px;
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
                    <input type="hidden" name="scratchpad_id" id="modalNoteScratchpadId" value="<?php echo $active_id; ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Název poznámky</label>
                            <input type="text" name="title" id="noteTitleInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required placeholder="Napište název...">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Obsah</label>
                            <div id="modalQuillEditor" style="height: 300px; background: #282a36; color: white;"></div>
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

<script>
let quill;
let modalQuill;
let lastSavedContent;
let lastSavedName;
let aiTypingInterval = null;

function aiAction(action) {
    const content = quill.root.innerText.trim();
    const insightBox = document.getElementById('aiInsightBox');
    const insightContent = document.getElementById('aiInsightContent');
    const aiBtn = document.getElementById('aiBtn');

    if (!insightBox || !insightContent) return;
    
    // Close dropdown
    const dropdownInstance = bootstrap.Dropdown.getInstance(aiBtn);
    if (dropdownInstance) dropdownInstance.hide();

    if (aiTypingInterval) clearInterval(aiTypingInterval);
    
    insightBox.classList.remove('d-none');
    insightContent.innerHTML = '<div class="d-flex align-items-center gap-2 py-2"><div class="spinner-border spinner-border-sm text-ai" role="status"></div><span class="text-white-50">AI přemýšlí...</span></div>';
    aiBtn.disabled = true;

    fetch('api/api_ai_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, content: content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Visual feedback when text appears
            insightBox.classList.remove('flash-purple');
            void insightBox.offsetWidth;
            insightBox.classList.add('flash-purple');

            if (action === 'structure_note') {
                // For structure, we show a button to apply it
                typeWriter(data.answer, insightContent, () => {
                   const btn = document.createElement('button');
                   btn.className = 'btn btn-sm btn-ai mt-3 d-block';
                   btn.innerHTML = '<i class="bi bi-check2-all me-1"></i> Použít tento formát';
                   btn.onclick = () => {
                       const html = simpleMarkdownToHtml(data.answer);
                       quill.root.innerHTML = ""; quill.clipboard.dangerouslyPasteHTML(0, html);
                       insightBox.classList.add("d-none"); if (typeof triggerAutosave !== "undefined") triggerAutosave();
                   };
                   insightContent.appendChild(btn);
                });
            } else if (action === 'format_note' || action === 'grammar_check') {
                // For format and grammar, we show a button to apply it
                typeWriter(data.answer, insightContent, () => {
                   if (data.answer.trim() !== 'Text je gramaticky správně.') {
                       const btn = document.createElement('button');
                       btn.className = 'btn btn-sm btn-ai mt-3 d-block';
                       btn.innerHTML = action === 'format_note' ? '<i class="bi bi-check2-all me-1"></i> Použít toto formátování' : '<i class="bi bi-check2-all me-1"></i> Použít opravy';
                       btn.onclick = () => {
                           const html = simpleMarkdownToHtml(data.answer);
                           quill.root.innerHTML = ""; quill.clipboard.dangerouslyPasteHTML(0, html);
                           insightBox.classList.add("d-none"); if (typeof triggerAutosave !== "undefined") triggerAutosave();
                       };
                       insightContent.appendChild(btn);
                   }
                });
            } else if (action === 'extract_todos') {
                // Custom handling for todos
                displayExtractedTodos(data.answer, insightContent);
            } else {
                typeWriter(data.answer, insightContent);
            }
            
            // Visual feedback - moved to start of success
        } else {
            insightContent.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + data.message + '</div>';
        }
    })
    .catch(error => {
        insightContent.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Chyba při komunikaci s AI.</div>';
    })
    .finally(() => {
        aiBtn.disabled = false;
    });
}

function copyNote(btn) {
    const text = quill.getText().trim();
    navigator.clipboard.writeText(text).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = 'copied!';
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
        }, 2000);
    }).catch(err => {
        console.error('Chyba při kopírování: ', err);
    });
}

function simpleMarkdownToHtml(text) {
    if (!text) return '';
    text = text.trim();
    
    // Omezení vícenásobných prázdných řádků
    text = text.replace(/\n{3,}/g, '\n\n');
    
    const lines = text.split('\n');
    let currentListType = null; // null, 'ul', 'ol'
    let result = '';
    
    lines.forEach(line => {
        let trimmed = line.trim();
        
        // Prázdný řádek
        if (trimmed === '') {
            if (currentListType) { result += `</${currentListType}>`; currentListType = null; }
            return;
        }
        
        let content = trimmed;
        let tag = 'p';
        let isListItem = false;
        let newListType = null;

        // Identifikace odrážek (* nebo -)
        if (/^[\*\-\+]\s+/.test(trimmed)) {
            tag = 'li';
            isListItem = true;
            newListType = 'ul';
            content = trimmed.replace(/^[\*\-\+]\s+/, '');
        } 
        // Identifikace číslování (1. nebo 1))
        else if (/^\d+[\.\)]\s+/.test(trimmed)) {
            tag = 'li';
            isListItem = true;
            newListType = 'ol';
            content = trimmed.replace(/^\d+[\.\)]\s+/.test(trimmed) ? /^\d+[\.\)]\s+/ : /^\d+\s+/, '');
        }
        // Identifikace nadpisů
        else if (trimmed.startsWith('# ')) { tag = 'h2'; content = trimmed.substring(2); }
        else if (trimmed.startsWith('## ')) { tag = 'h3'; content = trimmed.substring(3); }
        else if (trimmed.startsWith('### ')) { tag = 'h4'; content = trimmed.substring(4); }
        
        // Formátování obsahu (bold, italic, code)
        content = content
            .replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>');
            
        if (isListItem) {
            // Pokud se mění typ seznamu (ul -> ol) nebo začínáme nový
            if (currentListType !== newListType) {
                if (currentListType) result += `</${currentListType}>`;
                result += `<${newListType}>`;
                currentListType = newListType;
            }
            result += '<li>' + content + '</li>';
        } else {
            // Ukončení seznamu pokud začíná jiný tag
            if (currentListType) {
                result += `</${currentListType}>`;
                currentListType = null;
            }
            result += `<${tag}>${content}</${tag}>`;
        }
    });
    
    if (currentListType) result += `</${currentListType}>`;
    return result;
}

function typeWriter(text, container, callback) {
    container.innerHTML = '';
    let i = 0;
    const speed = 2;
    let processedHtml = simpleMarkdownToHtml(text);
    let currentHtml = ''; 

    function type() {
        if (i < processedHtml.length) {
            if (processedHtml.charAt(i) === '<') {
                let tagEnd = processedHtml.indexOf('>', i);
                if (tagEnd !== -1) {
                    currentHtml += processedHtml.substring(i, tagEnd + 1);
                    i = tagEnd + 1;
                } else {
                    currentHtml += processedHtml.charAt(i);
                    i++;
                }
            } else {
                currentHtml += processedHtml.charAt(i);
                i++;
            }
            
            // Nastavením celého složeného řetězce zamezíme automatickému uzavírání neukončených tagů prohlížečem
            container.innerHTML = currentHtml;
            
            aiTypingInterval = setTimeout(type, speed);
            container.scrollTop = container.scrollHeight;
        } else if (callback) {
            callback();
        }
    }
    type();
}

function displayExtractedTodos(text, container) {
    container.innerHTML = '';
    const lines = text.split('\n');
    let found = false;
    
    const list = document.createElement('div');
    list.className = 'd-flex flex-column gap-1 mt-1';
    
    lines.forEach(line => {
        if (line.includes('[TODO]')) {
            found = true;
            const cleanText = line.replace('[TODO]', '').trim();
            const dateMatch = cleanText.match(/\((\d{4}-\d{2}-\d{2})\)/);
            let taskText = cleanText;
            let deadline = null;
            
            if (dateMatch) {
                deadline = dateMatch[1];
                taskText = cleanText.replace(dateMatch[0], '').trim();
            }
            
            const item = document.createElement('div');
            item.className = 'd-flex align-items-center justify-content-between p-2 rounded glass-card no-jump border border-light border-opacity-10 text-white';
            item.style.background = 'rgba(255, 255, 255, 0.03)';
            item.innerHTML = `
                <div class="d-flex align-items-center overflow-hidden flex-grow-1">
                    <div class="d-flex flex-column overflow-hidden">
                        <span class="fw-medium text-truncate px-1" style="font-size: 0.85rem;">${taskText}</span>
                        ${deadline ? `<div class="text-ai d-flex align-items-center px-1" style="font-size: 0.65rem; opacity: 0.8; margin-top: -2px;">
                            <i class="bi bi-calendar-event me-1"></i>${deadline}
                        </div>` : ''}
                    </div>
                </div>
                <button class="btn btn-sm p-0 d-flex align-items-center justify-content-center flex-shrink-0 ms-2" 
                        style="width: 26px; height: 26px; border-radius: 6px; background: rgba(0, 229, 130, 0.15); border: 1px solid rgba(0, 229, 130, 0.3); color: #00e582; transition: all 0.2s;"
                        onclick="addExtractedTodo(this, '${taskText.replace(/'/g, "\\'")}', '${deadline || ''}')"
                        onmouseover="this.style.background='rgba(0, 229, 130, 0.3)'; this.style.borderColor='rgba(0, 229, 130, 0.5)';"
                        onmouseout="this.style.background='rgba(0, 229, 130, 0.15)'; this.style.borderColor='rgba(0, 229, 130, 0.3)';"
                        title="Rychlé přidání">
                    <i class="bi bi-plus" style="font-size: 1.2rem;"></i>
                </button>
            `;
            list.appendChild(item);
        }
    });
    
    if (found) {
        container.innerHTML = '<div class="mb-2 text-white-50" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600; text-transform: uppercase;">Nalezené úkoly:</div>';
        container.appendChild(list);
    } else {
        container.innerText = text;
    }
}


function addExtractedTodo(btn, text, deadline) {
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    
    fetch('api/api_save_todo_simple.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text: text, deadline: deadline })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            btn.style.background = '#00e582';
            btn.style.color = '#fff';
            
            const parent = btn.closest('.glass-card');
            setTimeout(() => {
                if (parent) {
                    parent.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                    parent.style.opacity = '0';
                    parent.style.transform = 'translateX(30px)';
                    setTimeout(() => {
                        parent.remove();
                        // If all todos are gone, maybe hide the container?
                        const list = document.querySelector('#aiInsightContent .d-flex.flex-column');
                        if (list && list.children.length === 0) {
                            document.getElementById('aiInsightBox').classList.add('d-none');
                        }
                    }, 400);
                }
            }, 600);
        } else {
            alert(data.message);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    })
    .catch(error => {
        alert('Chyba při ukládání úkolu.');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}


function triggerAutosave() {
    if (!quill) return;
    const currentContent = quill.root.innerHTML;
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
            
            // Update tab name in UI if changed
            const activeTab = document.querySelector('.nav-tab-item.active .tab-name');
            if (activeTab) activeTab.textContent = currentName;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    quill = new Quill('#quillMainEditor', {
        theme: 'snow',
        placeholder: 'Začněte psát svou poznámku...',
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

    // Set initial content
    quill.root.innerHTML = <?php echo json_encode($content); ?>;
    
    updateCharCount();
    quill.on('text-change', updateCharCount);
    
    // Autosave on name change/blur
    const padNameInput = document.getElementById('padName');
    if (padNameInput) {
        padNameInput.addEventListener('blur', triggerAutosave);
        padNameInput.addEventListener('change', triggerAutosave);
    }

    // Quill for Modal
    modalQuill = new Quill('#modalQuillEditor', {
        theme: 'snow',
        placeholder: 'Obsah poznámky...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'clean']
            ]
        }
    });

    document.getElementById('addToNotesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const modal = bootstrap.Modal.getInstance(document.getElementById('addToNotesModal'));
        const scratchpad_id = document.getElementById('modalNoteScratchpadId').value;
        const moveToast = document.getElementById('moveToast');
        
        if (modalQuill) {
            document.getElementById('noteContentInput').value = modalQuill.root.innerHTML;
        }

        const formData = new FormData(form);
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Odesílám...';

        fetch('api/api_note_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                modal.hide();
                
                // Show toast
                if (moveToast) {
                    moveToast.classList.replace('d-none', 'd-flex');
                    moveToast.style.animation = 'none';
                    void moveToast.offsetWidth;
                    moveToast.style.animation = 'fadeOut 3s forwards';
                    setTimeout(() => moveToast.classList.replace('d-flex', 'd-none'), 3000);
                }

                // If successful, remove the tab and switch to another
                const tabItem = document.querySelector(`.nav-tab-item[data-id="${scratchpad_id}"]`);
                if (tabItem) {
                    // Try to find another tab to switch to
                    let nextTab = tabItem.nextElementSibling;
                    if (!nextTab || !nextTab.classList.contains('nav-tab-item')) {
                        nextTab = tabItem.previousElementSibling;
                    }
                    
                    tabItem.remove();
                    updateTabControls();
                    
                    if (nextTab && nextTab.classList.contains('nav-tab-item')) {
                        const nextId = nextTab.getAttribute('data-id');
                        switchTab(null, nextId);
                    } else {
                        // No more tabs, reload or add a new one? For now let's just reload to get the initial state
                        window.location.href = 'notes_drafts.php';
                    }
                }
            } else {
                alert(data.message || 'Chyba při přesunu draftu.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Chyba při komunikaci se serverem.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Vytvořit poznámku a smazat draft';
        });
    });

    // Autosave logic
    lastSavedContent = quill.root.innerHTML;
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
            } else {
                saveDraft();
            }
        }
        
        // Option + N new draft
        if (e.altKey && e.code === 'KeyN') {
            e.preventDefault();
            addNewTab(e);
        }

        // Option + W close current
        if (e.altKey && e.code === 'KeyW') {
            e.preventDefault();
            const activeTab = document.querySelector('.nav-tab-item.active');
            if (activeTab) {
                const closeBtn = activeTab.querySelector('.btn-tab-close');
                if (closeBtn) closeBtn.click();
            }
        }

        // Option + Up/Down arrow for tab switching
        if (e.altKey && (e.code === 'ArrowDown' || e.code === 'ArrowUp')) {
            const tabItems = Array.from(document.querySelectorAll('.nav-tab-item'));
            const activeIndex = tabItems.findIndex(item => item.classList.contains('active'));
            
            if (activeIndex !== -1 && tabItems.length > 1) {
                e.preventDefault();
                let nextIndex;
                if (e.code === 'ArrowDown') {
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
    const text = quill.getText().trim();
    document.getElementById('charCount').textContent = 'Znaků: ' + text.length;
}

function saveDraft() {
    const currentContent = quill.root.innerHTML;
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
    const content = quill.root.innerHTML;
    
    document.getElementById('noteTitleInput').value = title;
    if (modalQuill) {
        modalQuill.root.innerHTML = content;
    }
    
    const modalEl = document.getElementById('addToNotesModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function addNewTab(event) {
    if (event) event.preventDefault();
    
    const btn = document.getElementById('addTabBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

    fetch('api/api_create_scratchpad.php?type=note')
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
                    <a href="notes_drafts.php?id=${pad.id}" class="nav-tab-link py-2 px-3" onclick="switchTab(event, ${pad.id})">
                        <i class="bi bi-file-earmark-text me-1"></i>
                        <span class="tab-name">${pad.name}</span>
                    </a>
                    <button type="button" class="btn-tab-close ms-0" onclick="confirmDelete(${pad.id}, '${pad.name}')">
                        <i class="bi bi-x"></i>
                    </button>
                `;
                
                // Insert before the plus button
                tabContainer.insertBefore(newTab, btn);
                
                // Update controls
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
    
    // If clicking already active tab, just scroll on mobile and return
    if (currentId == id) {
        if (window.innerWidth < 992) {
            const editorContainer = document.querySelector('.editor-container');
            if (editorContainer) {
                editorContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        return;
    }

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
                quill.root.innerHTML = pad.content;
                document.getElementById('padName').value = pad.name;
                
                // Update hidden IDs
                activeIdInput.value = pad.id;
                document.getElementById('modalNoteScratchpadId').value = pad.id;
                
                // Update UI - active class
                document.querySelectorAll('.nav-tab-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('data-id') == pad.id) {
                        item.classList.add('active');
                    }
                });

                // Scroll to editor on mobile
                if (window.innerWidth < 992) {
                    const editorContainer = document.querySelector('.editor-container');
                    if (editorContainer) {
                        editorContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }

                // Update URL 
                const newUrl = `notes_drafts.php?id=${pad.id}`;
                window.history.pushState({id: pad.id}, pad.name, newUrl);
                
                // Update cookie
                document.cookie = `last_note_draft_id=${pad.id}; path=/; max-age=${86400 * 30}`;

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

function confirmDelete(id, name) {
    if (confirm('Opravdu chcete smazat draft "' + name + '"?')) {
        const tabItem = document.querySelector(`.nav-tab-item[data-id="${id}"]`);
        if (!tabItem) return;

        const isActive = tabItem.classList.contains('active');
        
        const closeBtn = tabItem.querySelector('.btn-tab-close');
        if (closeBtn) closeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 0.7rem; height: 0.7rem;"></span>';

        fetch('api/api_delete_scratchpad.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (isActive) {
                        const allTabs = Array.from(document.querySelectorAll('.nav-tab-item'));
                        const currentIndex = allTabs.indexOf(tabItem);
                        const nextTab = allTabs[currentIndex + 1] || allTabs[currentIndex - 1];
                        
                        if (nextTab) {
                            const nextId = nextTab.getAttribute('data-id');
                            switchTab(null, nextId);
                        }
                    }
                    tabItem.remove();
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
            closeBtn.style.display = 'none';
        }
    });
}

// Handle back/forward
window.onpopstate = function(event) {
    if (event.state && event.state.id) {
        switchTab(null, event.state.id);
    } else {
        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');
        if (id) switchTab(null, id);
    }
}

function toggleAiPromptBar(show) {
    const bar = document.getElementById('aiPromptBar');
    if (!bar) return;
    
    if (show) {
        bar.classList.remove('d-none');
        document.getElementById('aiCustomPrompt').focus();
    } else {
        bar.classList.add('d-none');
    }
}

function submitAiPrompt() {
    const promptInput = document.getElementById('aiCustomPrompt');
    const prompt = promptInput.value.trim();
    const content = quill.root.innerText.trim();
    const btn = document.getElementById('aiPromptSubmitBtn');
    
    if (!prompt) return;
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    const insightBox = document.getElementById('aiInsightBox');
    const insightContent = document.getElementById('aiInsightContent');
    insightBox.classList.remove('d-none');
    insightContent.innerHTML = '<div class="text-white-50"><i class="bi bi-hourglass-split me-2 pulse"></i> AI přemýšlí nad vaším zadáním...</div>';

    fetch('api/api_ai_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'custom_prompt', 
            content: content,
            prompt: prompt
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            typeWriter(data.answer, insightContent);
            insightBox.classList.remove('flash-purple');
            void insightBox.offsetWidth;
            insightBox.classList.add('flash-purple');
            promptInput.value = ''; 
        } else {
            insightContent.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i> ' + data.message + '</div>';
        }
    })
    .catch(err => {
        insightContent.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i> Chyba při komunikaci s AI.</div>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

</script>

<?php include 'includes/footer.php'; ?>
