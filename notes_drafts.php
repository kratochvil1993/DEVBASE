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
                    <div id="saveToast" class="badge bg-success d-none align-items-center px-3 py-2 me-2">
                        <i class="bi bi-check-circle me-1"></i> Uloženo!
                    </div>
                    <div id="moveToast" class="badge bg-info d-none align-items-center px-3 py-2 me-2">
                        <i class="bi bi-journal-check me-1"></i> Přesunuto do poznámek!
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
                    <input type="hidden" name="scratchpad_id" id="modalNoteScratchpadId" value="<?php echo $active_id; ?>">
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
let lastSavedContent;
let lastSavedName;

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
};
</script>

<?php include 'includes/footer.php'; ?>
