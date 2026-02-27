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
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        deleteScratchpad($_GET['id']);
        header('Location: notes_drafts.php');
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
    } elseif ($_POST['action'] == 'move_to_notes') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $tags = $_POST['tags'] ?? [];
        $scratchpad_id = $_POST['scratchpad_id'] ?? null;
        
        if ($title && $content && $scratchpad_id) {
            $saved_id = saveNote($title, $content, null, $tags);
            if ($saved_id) {
                deleteScratchpad($scratchpad_id);
                header("Location: notes_drafts.php?note_moved=1");
                exit;
            }
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

include 'includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="glass-card no-jump p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="flex-grow-1 me-4">
                    <div class="d-flex align-items-center mb-1">
                        <h4 class="text-white mb-0 me-3"><i class="bi bi-journal-text me-2"></i> </h4>
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
                    
                    <div class="dropdown">
                        <button class="btn btn-send-to px-3 dropdown-toggle text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-send me-2"></i> Poslat do
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark glass-dropdown border-light border-opacity-10">
                            <li><a class="dropdown-item" href="#" onclick="openAddToNotesModal()"><i class="bi bi-journal-plus me-2"></i> do Notes</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-add-snipet px-3" onclick="saveDraft()">
                        <i class="bi bi-save me-2"></i> Uložit
                    </button>
                </div>
            </div>

            <!-- Tab Bar -->
            <div class="d-flex align-items-center mb-0 overflow-auto tab-container">
                <?php foreach ($scratchpads as $pad): ?>
                    <div class="nav-tab-item <?php echo $pad['id'] == $active_id ? 'active' : ''; ?> me-1">
                        <a href="notes_drafts.php?id=<?php echo $pad['id']; ?>" class="nav-tab-link py-2 px-3">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            <?php echo htmlspecialchars($pad['name']); ?>
                        </a>
                        <?php if (count($scratchpads) > 1): ?>
                            <button type="button" class="btn-tab-close ms-0" onclick="confirmDelete(<?php echo $pad['id']; ?>, '<?php echo addslashes($pad['name']); ?>')">
                                <i class="bi bi-x"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <a href="notes_drafts.php?action=add" class="btn btn-add-tab ms-1" title="Nový draft">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>

            <div class="editor-container border border-light border-opacity-10 rounded-bottom overflow-hidden shadow-lg" style="border-top-left-radius: 0 !important; border-top-right-radius: 0 !important; background: rgba(40, 42, 54, 0.6);">
                <div id="quillMainEditor" style="height: 60vh; border: none; color: white;"></div>
            </div>
            
            <div class="mt-3 d-flex justify-content-between align-items-center text-white-50 small">
                <div>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Ctrl+S uložit</span>
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
    <input type="hidden" name="action" value="save_note_draft">
    <input type="hidden" name="id" value="<?php echo $active_id; ?>">
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
    background: rgba(0, 0, 0, 0.2);
}
.ql-container.ql-snow {
    border: none !important;
    font-family: 'Inter', sans-serif;
    font-size: 16px;
}
.ql-editor {
    padding: 20px;
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
                            <div id="modalQuillEditor" style="height: 300px; background: transparent; color: white;"></div>
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

    document.getElementById('addToNotesForm').addEventListener('submit', function() {
        if (modalQuill) {
            document.getElementById('noteContentInput').value = modalQuill.root.innerHTML;
        }
    });

    // Autosave logic
    let lastSavedContent = quill.root.innerHTML;
    let lastSavedName = document.getElementById('padName').value;
    const autosaveIndicator = document.getElementById('autosaveIndicator');

    function triggerAutosave() {
        if (!quill) return;
        const currentContent = quill.root.innerHTML;
        const currentName = document.getElementById('padName').value;
        const padId = "<?php echo $active_id; ?>";

        if (currentContent === lastSavedContent && currentName === lastSavedName) return;

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

    // Interval save (every 30s)
    setInterval(triggerAutosave, 30000);

    // Visibility change save (when user switches browser tab)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            triggerAutosave();
        }
    });

    // Intercept navigation links within the page (tabs)
    document.querySelectorAll('.nav-tab-link, .btn-add-tab').forEach(link => {
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
            window.location.href = 'notes_drafts.php?action=add';
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
    const text = quill.getText().trim();
    document.getElementById('charCount').textContent = 'Znaků: ' + text.length;
}

function saveDraft() {
    document.getElementById('formContent').value = quill.root.innerHTML;
    document.getElementById('formPadName').value = document.getElementById('padName').value;
    document.getElementById('saveForm').submit();
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

function confirmDelete(id, name) {
    if (confirm('Opravdu chcete smazat draft "' + name + '"?')) {
        window.location.href = 'notes_drafts.php?action=delete&id=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
