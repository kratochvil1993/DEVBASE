<?php
require_once 'includes/functions.php';

// Check if notes are enabled
if (getSetting('notes_enabled', '1') == '0') {
    header('Location: index.php');
    exit;
}

// Handle Note addition, update or delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $updated_id = '';
    if ($_POST['action'] == 'add_note') {
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $id = !empty($_POST['note_id']) ? $_POST['note_id'] : null;
        $is_locked = isset($_POST['is_locked']) ? 1 : 0;
        $note_id = saveNote($_POST['title'], $_POST['content'], null, $tags, $id, $is_locked);
        if ($note_id) $updated_id = $note_id;
    } elseif ($_POST['action'] == 'delete_note') {
        deleteNote($_POST['note_id']);
    } elseif ($_POST['action'] == 'toggle_pin') {
        toggleNotePin($_POST['note_id']);
        $updated_id = $_POST['note_id'];
    }
    
    $redirect_url = 'manage_notes.php';
    if ($updated_id) {
        $redirect_url .= '?updated_id=' . $updated_id;
    }
    header('Location: ' . $redirect_url);
    exit;
}

$currentSort = 'custom'; // Manage notes usually uses custom order
$notes = getAllNotes($currentSort);
$pinnedNotes = array_filter($notes, function($n) { return $n['is_pinned'] == 1; });
$otherNotes = array_filter($notes, function($n) { return $n['is_pinned'] == 0; });
$tags = getAllTags('note');
$languages = getAllLanguages();

include 'includes/header.php';
?>
<style>
    .manage-note-row:target {
        background: rgba(var(--bs-primary-rgb), 0.15) !important;
        outline: 1px solid rgba(var(--bs-primary-rgb), 0.3);
        transition: background 1s ease-in-out;
    }</style>
<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0">Správa poznámek</h2>
        </div>
    </div>

    <!-- Controls (Search, Buttons, Filters) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="glass-card no-jump p-2 mb-3">
                <div class="d-flex flex-nowrap gap-3 align-items-center justify-content-between">
                    <!-- Search Input -->
                    <div class="input-group flex-grow-1">
                        <span class="input-group-text bg-transparent border-0 text-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="manageNotesSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat">
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-shrink-0 gap-2 ">
                        <button class="btn btn-add-snipet rounded px-3 shadow-sm" id="newNoteBtn" data-bs-toggle="modal" data-bs-target="#addNoteModal" title="Nová poznámka" onclick="openAddNoteManageModal()">
                            <i class="bi bi-plus-lg "></i>
                        </button>    
                        <button class="btn btn-edit-order rounded px-3 shadow-sm" id="editOrderBtn" onclick="toggleSortingMode()">
                            <i class="bi bi-arrows-move me-1"></i> Upravit pořadí
                        </button>
                        <button class="btn btn-success rounded px-3 shadow-sm d-none" id="saveOrderBtn" onclick="toggleSortingMode()">
                            <i class="bi bi-check-lg me-1"></i> Hotovo
                        </button>                        
                    </div>
                </div>
            </div>
            
            <!-- Tag Filters (Below) -->
            <div class="d-flex flex-wrap justify-content-end gap-2" id="manageNotesTagFilters">
                <button class="btn btn-sm btn-outline-light rounded-pill px-3 active" data-tag="all" style="--tag-color: #fff;">Vše</button>
                <?php foreach ($tags as $tag): ?>
                    <button class="btn btn-sm rounded-pill px-3 <?php echo empty($tag['color']) ? 'btn-outline-light' : ''; ?>"
                            data-tag="<?php echo htmlspecialchars($tag['name']); ?>"
                            style="--tag-color: <?php echo !empty($tag['color']) ? htmlspecialchars($tag['color']) : '#fff'; ?>; <?php if (!empty($tag['color'])) echo 'background-color: ' . htmlspecialchars($tag['color']) . '; color: #fff; border-color: ' . htmlspecialchars($tag['color']) . ';'; ?>">
                        <?php echo htmlspecialchars($tag['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row" style="margin-bottom: 30vh;">
        <div class="col-12">
        <div class="glass-card p-0 no-jump overflow-hidden ">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-dark text-white mb-0 align-middle manage-notes-table" style="background: transparent;">
                    <thead class="border-bottom border-light border-opacity-25" style="background: rgba(255,255,255,0.05);">
                        <tr>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 5%;">ID</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 5%;"><i class="bi bi-pin-angle" title="Připnuto"></i></th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 40%;">Název</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 35%;">Štítky</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50 text-end" style="width: 15%;">Akce</th>
                        </tr>
                    </thead>
                    <tbody id="manageNotesPinnedGrid">
                        <?php if (!empty($pinnedNotes)): ?>
                            <tr class="section-header-row" data-section="pinned" style="background: rgba(255,193,7,0.05);">
                                <td colspan="5" class="px-4 py-2 border-bottom border-light border-opacity-10">
                                    <span class="text-warning small fw-bold"><i class="bi bi-pin-angle-fill me-2"></i>PŘIPNUTÉ</span>
                                </td>
                            </tr>
                            <?php foreach ($pinnedNotes as $note): ?>
                                <?php include 'includes/manage_note_row.php'; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tbody id="manageNotesGrid">
                        <?php if (!empty($pinnedNotes) && !empty($otherNotes)): ?>
                            <tr class="section-header-row" data-section="others" style="background: rgba(255,255,255,0.03);">
                                <td colspan="5" class="px-4 py-2 border-bottom border-light border-opacity-10">
                                    <span class="text-white-50 small fw-bold">OSTATNÍ</span>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php if (empty($notes)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-white-50 py-5">
                                    <i class="bi bi-journal-x fs-2 mb-3 d-block"></i>
                                    Zatím nemáte žádné poznámky
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($otherNotes as $note): ?>
                                <?php include 'includes/manage_note_row.php'; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>            
        </div>
    </div>
</div>




<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('manageNotesSearch');
    const tagButtons  = document.querySelectorAll('#manageNotesTagFilters .btn');
    let currentSearch = '';
    let currentTag    = 'all';

    const filterRows = () => {
        let pinnedVisible = 0;
        let othersVisible = 0;

        document.querySelectorAll('.manage-note-row').forEach(row => {
            const title = row.dataset.title || '';
            const content = row.dataset.content || '';
            const tags  = row.dataset.tags  ? row.dataset.tags.split(',') : [];

            const matchSearch = title.includes(currentSearch) ||
                                content.includes(currentSearch) ||
                                tags.some(t => t.includes(currentSearch));

            const matchTag = currentTag === 'all' || tags.includes(currentTag.toLowerCase());

            const isVisible = matchSearch && matchTag;
            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                if (row.closest('#manageNotesPinnedGrid')) pinnedVisible++;
                else othersVisible++;
            }
        });

        // Toggle section headers
        const pinnedHeader = document.querySelector('.section-header-row[data-section="pinned"]');
        const othersHeader = document.querySelector('.section-header-row[data-section="others"]');
        
        if (pinnedHeader) pinnedHeader.style.display = pinnedVisible > 0 ? '' : 'none';
        if (othersHeader) othersHeader.style.display = othersVisible > 0 ? '' : 'none';
        
        // Hide/show pinned grid completely if empty
        const pinnedGrid = document.getElementById('manageNotesPinnedGrid');
        if (pinnedGrid) pinnedGrid.style.display = pinnedVisible > 0 ? '' : 'none';
    };

    if (searchInput) {
        searchInput.addEventListener('input', e => {
            currentSearch = e.target.value.toLowerCase();
            filterRows();
        });
    }

    tagButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tagButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTag = btn.getAttribute('data-tag');
            filterRows();
        });
    });
});

function openAddNoteManageModal() {
    document.getElementById('noteModalTitle').innerText = 'Nová poznámka';
    document.getElementById('noteId').value = '';
    document.getElementById('noteTitleInput').value = '';
    document.getElementById('noteContentInput').value = '';
    if (typeof quillManager !== 'undefined') {
        quillManager.root.innerHTML = '';
    }
    document.getElementById('noteSubmitBtn').innerText = 'Uložit poznámku';

    const lockInput = document.getElementById('noteLockedInput');
    if (lockInput) lockInput.checked = false;

    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => cb.checked = false);
}

function openEditNoteManageModal(note) {
    document.getElementById('noteModalTitle').innerText = 'Upravit poznámku';
    document.getElementById('noteId').value = note.id;
    document.getElementById('noteTitleInput').value = note.title;
    document.getElementById('noteContentInput').value = note.content;
    if (typeof quillManager !== 'undefined') {
        quillManager.root.innerHTML = note.content;
    }
    document.getElementById('noteSubmitBtn').innerText = 'Uložit změny';

    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => {
        cb.checked = note.tags.some(t => t.id == cb.value);
    });

    const isLocked = (note.is_locked == 1 || note.is_locked === true || note.is_locked === "1");
    const lockInput = document.getElementById('noteLockedInput');
    if (lockInput) lockInput.checked = isLocked;
    
    var myModal = new bootstrap.Modal(document.getElementById('addNoteModal'));
    myModal.show();
}

function openViewNoteManageModal(note) {
    document.getElementById('viewNoteModalTitle').innerText = note.title;
    
    const contentEl = document.getElementById('viewNoteContent');
    contentEl.innerHTML = note.content;
    contentEl.className = 'p-3';
    
    const tagsWrapper = document.getElementById('viewNoteTags');
    tagsWrapper.innerHTML = '';
    if (note.tags && note.tags.length > 0) {
        note.tags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'badge tag-badge me-1';
            span.style.backgroundColor = tag.color || '#6c757d';
            span.style.color = '#fff';
            span.textContent = tag.name;
            tagsWrapper.appendChild(span);
        });
    }

    // Highlight
    if (typeof Prism !== 'undefined') {
        const codeBlocks = contentEl.querySelectorAll('pre');
        codeBlocks.forEach(block => Prism.highlightElement(block));
    }

    // Edit button integration
    const editBtn = document.getElementById('editNoteFromViewBtn');
    if (editBtn) {
        editBtn.onclick = () => {
            const viewModalEl = document.getElementById('viewNoteModal');
            const viewModal = bootstrap.Modal.getInstance(viewModalEl);
            if (viewModal) viewModal.hide();
            openEditNoteManageModal(note);
        };
    }
    
    var myModal = new bootstrap.Modal(document.getElementById('viewNoteModal'));
    
    // Reset AI box
    const insightBox = document.getElementById('aiNoteInsightBox');
    const insightContent = document.getElementById('aiNoteInsightContent');
    if (insightBox) insightBox.classList.add('d-none');
    if (insightContent) insightContent.innerHTML = '';
    
    myModal.show();
}

let aiNoteTypingInterval = null;

function aiNoteAction(action) {
    const content = document.getElementById('viewNoteContent').innerText;
    const insightBox = document.getElementById('aiNoteInsightBox');
    const insightContent = document.getElementById('aiNoteInsightContent');
    const aiBtn = document.getElementById('aiNoteBtn');

    if (!insightBox || !insightContent) return;

    if (aiNoteTypingInterval) clearInterval(aiNoteTypingInterval);
    
    insightBox.classList.remove('d-none');
    insightContent.innerHTML = '<div class="d-flex align-items-center gap-2 py-2"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="text-white-50">AI přemýšlí...</span></div>';
    aiBtn.disabled = true;

    fetch('api/api_ai_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, content: content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            typeWriterNote(data.answer, insightContent);
            insightBox.classList.remove('flash-purple');
            void insightBox.offsetWidth;
            insightBox.classList.add('flash-purple');
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

function typeWriterNote(text, container) {
    container.innerHTML = '';
    let formattedText = text.trim().replace(/\n/g, '<br>').replace(/^\s*\* /gm, '• ').replace(/^\s*\*\B/gm, '• ');
    let i = 0;
    const speed = 2;
    
    function type() {
        if (i < formattedText.length) {
            if (formattedText.substr(i, 4) === '<br>') {
                container.innerHTML += '<br>';
                i += 4;
            } else {
                container.innerHTML += formattedText.charAt(i);
                i++;
            }
            aiNoteTypingInterval = setTimeout(type, speed);
            const modalBody = document.querySelector('#viewNoteModal .modal-body');
            if (modalBody) modalBody.scrollTop = modalBody.scrollHeight;
        }
    }
    type();
}

// Ensure AI box is cleared when modal closes
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('viewNoteModal');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (aiNoteTypingInterval) clearInterval(aiNoteTypingInterval);
            const insightBox = document.getElementById('aiNoteInsightBox');
            const insightContent = document.getElementById('aiNoteInsightContent');
            if (insightBox) insightBox.classList.add('d-none');
            if (insightContent) insightContent.innerHTML = '';
        });
    }
});

function copyNoteContent(btn) {
    const content = document.getElementById('viewNoteContent').innerText;
    navigator.clipboard.writeText(content).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = 'copied!';
        btn.classList.replace('btn-outline-light', 'btn-success');
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-outline-light');
        }, 2000);
    });
}

</script>

<!-- Add/Edit Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white" id="noteModalTitle">Nová poznámka</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="noteForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_note">
                    <input type="hidden" name="note_id" id="noteId" value="">
                    <div class="row g-3 mb-3">
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="form-label text-white-50 small mb-0">Název</label>
                                <?php if (!empty($geminiApiKey)): ?>
                                <button type="button" class="btn btn-sm btn-ai-action" onclick="generateAiNoteTitle()" title="Generovat název">
                                    <i class="bi bi-magic me-1"></i> AI
                                </button>
                                <?php endif; ?>
                            </div>
                            <input type="text" id="noteTitleInput" name="title" class="form-control form-control-ai text-white border-light border-opacity-25" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_locked" id="noteLockedInput" value="1">
                                <label class="form-check-label text-white-50 small" for="noteLockedInput">
                                    <i class="bi bi-lock-fill me-1"></i> Skrýt obsah
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Štítky</label>
                            <div class="d-flex flex-wrap gap-2 pt-1 border border-light border-opacity-10 rounded p-3 bg-dark bg-opacity-25">
                                <?php foreach ($tags as $tag): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="tag-<?php echo $tag['id']; ?>">
                                        <label class="form-check-label text-white-50 small" for="tag-<?php echo $tag['id']; ?>">
                                            <span class="badge" style="background-color: <?php echo $tag['color'] ? htmlspecialchars($tag['color']) : '#6c757d'; ?>">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Obsah</label>
                        <div id="quillEditor" style="height: 300px; background: transparent; color: white;"></div>
                        <input type="hidden" name="content" id="noteContentInput">
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-add-snipet px-4" id="noteSubmitBtn">Uložit poznámku</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Note Modal -->
<div class="modal fade" id="viewNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white mb-0" id="viewNoteModalTitle">Zobrazit poznámku</h5>
                
                <?php if (!empty($geminiApiKey)): ?>
                <div class="dropdown ms-auto me-2">
                    <button class="btn btn-sm btn-ai rounded px-3 dropdown-toggle shadow-none border-opacity-25" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="aiNoteBtn">
                        <i class="bi bi-robot me-1"></i> AI
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark glass-card border-light border-opacity-10 mt-2 shadow-lg">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="#" onclick="aiNoteAction('summarize_note')">
                                <i class="bi bi-list-task me-2 text-ai"></i> Shrnutí (body)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="#" onclick="aiNoteAction('grammar_check')">
                                <i class="bi bi-spellcheck me-2 text-ai"></i> Kontrola pravopisu
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="position-relative">
                    <button class="btn btn-sm btn-outline-light copy-btn shadow-sm z-3" onclick="copyNoteContent(this)" style="position: absolute; right: 10px; top: 10px; z-index: 10;">
                        copy
                    </button>
                    <div id="viewNoteContent" class="p-3" style="max-height: 70vh; overflow-y: auto;"></div>
                </div>
                
                <?php if (!empty($geminiApiKey)): ?>
                <!-- AI Insight Box for Notes -->
                <div id="aiNoteInsightBox" class="m-3 p-3 rounded-3 d-none" style="background: rgba(142, 84, 233, 0.05); border: 1px solid rgba(142, 84, 233, 0.2); backdrop-filter: blur(5px);">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-robot text-primary me-2"></i>
                        <span class="small fw-bold text-white-50 text-uppercase tracking-wider">AI Insight</span>
                        <button type="button" class="btn-close btn-close-white ms-auto small" style="font-size: 0.5rem;" onclick="document.getElementById('aiNoteInsightBox').classList.add('d-none')"></button>
                    </div>
                    <div id="aiNoteInsightContent" class="text-white small lh-base"></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10 d-flex justify-content-between align-items-center">
                <div id="viewNoteTags" class="snippet-tags m-0"></div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-add-snipet px-3" id="editNoteFromViewBtn">
                        <i class="bi bi-pencil me-1"></i> Upravit
                    </button>
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- SortableJS -->
<script src="assets/vendor/sortablejs/Sortable.min.js"></script>
<script>
let sortable = null;
let isSortingMode = false;

let quillManager;

document.addEventListener('DOMContentLoaded', function() {
    quillManager = new Quill('#quillEditor', {
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
    
    document.getElementById('noteForm').addEventListener('submit', function() {
        if (quillManager) {
            document.getElementById('noteContentInput').value = quillManager.root.innerHTML;
        }
    });
});

function toggleSortingMode() {
    isSortingMode = !isSortingMode;
    const pinnedGrid = document.getElementById('manageNotesPinnedGrid');
    const grid = document.getElementById('manageNotesGrid');
    const editBtn = document.getElementById('editOrderBtn');
    const saveBtn = document.getElementById('saveOrderBtn');
    const newNoteBtn = document.getElementById('newNoteBtn');
    const actionButtons = document.querySelectorAll('.manage-note-row td:last-child button, .manage-note-row td:last-child form');
    const sectionHeaders = document.querySelectorAll('.section-header-row');

    if (isSortingMode) {
        if (pinnedGrid) pinnedGrid.classList.add('sorting-mode');
        grid.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        newNoteBtn.classList.add('opacity-50', 'pe-none');
        sectionHeaders.forEach(h => h.classList.add('opacity-50'));
        
        actionButtons.forEach(btn => btn.classList.add('opacity-0', 'pe-none'));

        const sortableConfig = {
            animation: 150,
            ghostClass: 'glass-card-moving',
            filter: '.section-header-row',
            onEnd: function() {
                saveOrder();
            }
        };

        if (pinnedGrid) sortablePinned = new Sortable(pinnedGrid, sortableConfig);
        sortable = new Sortable(grid, sortableConfig);
    } else {
        if (pinnedGrid) pinnedGrid.classList.remove('sorting-mode');
        grid.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        newNoteBtn.classList.remove('opacity-50', 'pe-none');
        sectionHeaders.forEach(h => h.classList.remove('opacity-50'));
        
        actionButtons.forEach(btn => btn.classList.remove('opacity-0', 'pe-none'));

        if (sortablePinned) {
            sortablePinned.destroy();
            sortablePinned = null;
        }
        if (sortable) {
            sortable.destroy();
            sortable = null;
        }
    }
}

function saveOrder() {
    const order = [];
    let currentIndex = 0;

    // Pinned items
    document.querySelectorAll('#manageNotesPinnedGrid .manage-note-row').forEach(item => {
        order.push({
            id: item.dataset.id,
            order: currentIndex++
        });
    });

    // Other items
    document.querySelectorAll('#manageNotesGrid .manage-note-row').forEach(item => {
        order.push({
            id: item.dataset.id,
            order: currentIndex++
        });
    });
    
    fetch('api/api_notes_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ order: order }),
    })
    .then(response => response.json())
    .then(data => {
        console.log('Order saved:', data);
    });
}

function generateAiNoteTitle() {
    const content = quillManager ? quillManager.root.innerText.trim() : "";
    const target = document.getElementById('noteTitleInput');
    
    if (!content || content === "") {
        alert('Nejdříve vložte obsah poznámky!');
        return;
    }

    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> AI';

    fetch('api/api_ai_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'generate_note_title', content: content })
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
</script>

<?php include 'includes/footer.php'; ?>
