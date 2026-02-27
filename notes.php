<?php
require_once 'includes/functions.php';

// Check if notes are enabled
if (getSetting('notes_enabled', '1') == '0') {
    header('Location: index.php');
    exit;
}

// Handle Note addition, update or delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_note') {
        $id = !empty($_POST['note_id']) ? $_POST['note_id'] : null;
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $is_locked = isset($_POST['is_locked']) ? 1 : 0;
        $saved_id = saveNote($_POST['title'], $_POST['content'], null, $tags, $id, $is_locked);
        if ($saved_id) {
            $sortParam = isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : '';
            header('Location: notes.php?updated_id=' . $saved_id . $sortParam);
            exit;
        }
    } elseif ($_POST['action'] == 'delete_note') {
        deleteNote($_POST['note_id']);
    } elseif ($_POST['action'] == 'archive_note') {
        archiveNote($_POST['note_id'], 1);
    } elseif ($_POST['action'] == 'toggle_pin') {
        toggleNotePin($_POST['note_id']);
        $sortParam = isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : '';
        header('Location: notes.php?updated_id=' . $_POST['note_id'] . $sortParam);
        exit;
    }
    $sortParam = isset($_GET['sort']) ? '?sort=' . $_GET['sort'] : '';
    header('Location: notes.php' . $sortParam);
    exit;
}

$currentSort = isset($_GET['sort']) ? $_GET['sort'] : 'custom';
$notes = getAllNotes($currentSort);
$pinnedNotes = array_filter($notes, function($n) { return $n['is_pinned'] == 1; });
$otherNotes = array_filter($notes, function($n) { return $n['is_pinned'] == 0; });
$languages = getAllLanguages();
$allNoteTags = getAllTags('note');
$geminiApiKey = getSetting('gemini_api_key');

// Identify used tags for filtering
$usedTags = [];
foreach ($notes as $note) {
    if (!empty($note['tags'])) {
        foreach ($note['tags'] as $tag) {
            $usedTags[$tag['id']] = $tag;
        }
    }
}
uasort($usedTags, function($a, $b) {
    if ($a['sort_order'] == $b['sort_order']) {
        return strcmp($a['name'], $b['name']);
    }
    return $a['sort_order'] <=> $b['sort_order'];
});

include 'includes/header.php';
?>

<div class="row mb-3 align-items-center">
    <div class="col-lg-8 mx-auto">
        <div class="glass-card no-jump p-2 d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="flex-grow-1" >
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="noteSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-add-snipet rounded Xrounded-pill px-4" id="newNoteBtn" onclick="openAddNoteModal()">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <button class="btn btn-edit-order rounded Xrounded-pill px-4" id="editOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-arrows-move me-2"></i> Upravit pořadí
                </button>
                <button class="btn btn-success rounded Xrounded-pill px-4 d-none" id="saveOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-check-lg me-2"></i> Hotovo
                </button>
                <div class="dropdown" id="sortDropdownContainer">
                    <button class="btn btn-outline-light rounded Xrounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-down me-1"></i> 
                        <?php 
                            switch($currentSort) {
                                case 'oldest': echo 'Nejstarší'; break;
                                case 'alpha_asc': echo 'Abecedně A-Z'; break;
                                case 'alpha_desc': echo 'Abecedně Z-A'; break;
                                case 'custom': echo 'Vlastní řazení'; break;
                                default: echo 'Nejnovější';
                            }
                        ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark glass-card border-light border-opacity-10">
                        <li><a class="dropdown-item <?php echo $currentSort == 'custom' ? 'active' : ''; ?>" href="notes.php?sort=custom">Vlastní řazení</a></li>
                        <li><a class="dropdown-item <?php echo $currentSort == 'newest' ? 'active' : ''; ?>" href="notes.php?sort=newest">Nejnovější</a></li>
                        <li><a class="dropdown-item <?php echo $currentSort == 'oldest' ? 'active' : ''; ?>" href="notes.php?sort=oldest">Nejstarší</a></li>
                        <li><hr class="dropdown-divider border-light border-opacity-10"></li>
                        <li><a class="dropdown-item <?php echo $currentSort == 'alpha_asc' ? 'active' : ''; ?>" href="notes.php?sort=alpha_asc">Abecedně A-Z</a></li>
                        <li><a class="dropdown-item <?php echo $currentSort == 'alpha_desc' ? 'active' : ''; ?>" href="notes.php?sort=alpha_desc">Abecedně Z-A</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($usedTags)): ?>
<div class="row mb-5">
    <div class="col-lg-8 mx-auto">
        <div id="noteTagFilters" class="d-flex flex-wrap gap-2 justify-content-center">
            <button class="btn btn-sm btn-outline-light rounded-pill px-3 active" data-tag="all" style="--tag-color: #fff;">Vše</button>
            <?php foreach ($usedTags as $tag): ?>
                <button class="btn btn-sm rounded-pill px-3 <?php echo empty($tag['color']) ? 'btn-outline-light' : ''; ?>" 
                        data-tag="<?php echo htmlspecialchars($tag['name']); ?>"
                        style="--tag-color: <?php echo !empty($tag['color']) ? htmlspecialchars($tag['color']) : '#fff'; ?>; <?php if (!empty($tag['color'])) echo 'background-color: ' . htmlspecialchars($tag['color']) . '; color: #fff; border-color: ' . htmlspecialchars($tag['color']) . ';'; ?>">
                    <?php echo htmlspecialchars($tag['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="pinnedNotesContainer" class="<?php echo empty($pinnedNotes) ? 'd-none' : ''; ?>">
    <div class="col-12 mb-3">
        <h6 class="text-white-50 px-1"><i class="bi bi-pin-angle-fill me-2"></i> PŘIPNUTÉ</h6>
    </div>
    <div class="row g-4 mb-5" id="pinnedNotesGrid">
        <?php foreach ($pinnedNotes as $note): 
            $tagNames = array_map(function($t) { return $t['name']; }, $note['tags']);
            $tagData = implode(',', $tagNames);
        ?>
            <?php include 'includes/note_item_template.php'; ?>
        <?php endforeach; ?>
    </div>
</div>

<div id="othersNotesContainer">
    <div class="col-12 mb-3 <?php echo empty($pinnedNotes) ? 'd-none' : ''; ?>" id="othersHeader">
        <h6 class="text-white-50 px-1">OSTATNÍ</h6>
    </div>
    <div class="row g-4" id="othersNotesGrid">
        <?php if (empty($notes)): ?>
            <div class="col-12 text-center text-white-50 py-5">
                <i class="bi bi-journal-x display-1 mb-3 d-block"></i>
                <h3>Zatím nemáte žádné poznámky.</h3>
                <p>Klikněte na tlačítko výše a vytvořte si první!</p>
            </div>
        <?php else: ?>
            <?php foreach ($otherNotes as $note): 
                $tagNames = array_map(function($t) { return $t['name']; }, $note['tags']);
                $tagData = implode(',', $tagNames);
            ?>
                <?php include 'includes/note_item_template.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<div class="text-end mt-3">
    <a href="archive_notes.php" class="text-white-50 text-decoration-none small"><i class="bi bi-archive me-1"></i> Zobrazit archivované poznámky</a>
</div>


<!-- View Note Modal -->
<div class="modal fade" id="viewNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="modal-title text-white" id="viewNoteModalTitle">
                        Prohlížení poznámky
                    </h5>
                    <div id="viewNoteTags" class="d-flex gap-1 flex-wrap"></div>
                </div>
                
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
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <small id="viewNoteDate" class="text-white-25 m-0"></small>
                </div>
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

<!-- Note Modal (Add/Edit) -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
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
                    <div class="row g-3">
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="form-label text-white-50 small mb-0">Název</label>
                                <?php if (!empty($geminiApiKey)): ?>
                                <button type="button" class="btn btn-sm btn-ai-action py-0" onclick="generateAiNoteTitle()" title="Generovat název">
                                    <i class="bi bi-magic me-1"></i> AI
                                </button>
                                <?php endif; ?>
                            </div>
                            <input type="text" name="title" id="noteTitleInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required placeholder="Napište název...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_locked" id="noteLockedInput" value="1">
                                <label class="form-check-label text-white-50 small" for="noteLockedInput">
                                    <i class="bi bi-lock-fill me-1"></i> Skrýt obsah
                                </label>
                            </div>
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
                                    <p class="text-white-50 small mb-0 w-100 text-center">Žádné štítky nejsou definovány. Přidejte je v nastavení.</p>
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
                    <button type="submit" class="btn btn-add-snipet px-4" id="noteSubmitBtn">Uložit poznámku</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SortableJS -->
<script src="assets/vendor/sortablejs/Sortable.min.js"></script>

<script>
let sortablePinned = null;
let sortableOthers = null;
let isSortingMode = false;

let quill;

document.addEventListener('DOMContentLoaded', function() {
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

    document.getElementById('noteForm').addEventListener('submit', function() {
        if (quill) {
            document.getElementById('noteContentInput').value = quill.root.innerHTML;
        }
    });
});

function toggleSortingMode() {
    isSortingMode = !isSortingMode;
    const pinnedGrid = document.getElementById('pinnedNotesGrid');
    const othersGrid = document.getElementById('othersNotesGrid');
    const editBtn = document.getElementById('editOrderBtn');
    const saveBtn = document.getElementById('saveOrderBtn');
    const newNoteBtn = document.getElementById('newNoteBtn');
    const sortDropdown = document.getElementById('sortDropdownContainer');
    const deleteBtnWrappers = document.querySelectorAll('.delete-btn-wrapper');

    if (isSortingMode) {
        if (pinnedGrid) pinnedGrid.classList.add('sorting-mode');
        if (othersGrid) othersGrid.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        newNoteBtn.classList.add('opacity-50', 'pe-none');
        sortDropdown.classList.add('opacity-50', 'pe-none');
        deleteBtnWrappers.forEach(el => el.classList.add('d-none'));

        const sortableConfig = {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveOrder();
            }
        };

        if (pinnedGrid) sortablePinned = new Sortable(pinnedGrid, sortableConfig);
        if (othersGrid) sortableOthers = new Sortable(othersGrid, sortableConfig);
        
    } else {
        if (pinnedGrid) pinnedGrid.classList.remove('sorting-mode');
        if (othersGrid) othersGrid.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        newNoteBtn.classList.remove('opacity-50', 'pe-none');
        sortDropdown.classList.remove('opacity-50', 'pe-none');
        deleteBtnWrappers.forEach(el => el.classList.remove('d-none'));

        if (sortablePinned) {
            sortablePinned.destroy();
            sortablePinned = null;
        }
        if (sortableOthers) {
            sortableOthers.destroy();
            sortableOthers = null;
        }
        
        window.location.href = 'notes.php?sort=custom';
    }
}

function saveOrder() {
    const order = [];
    let currentIndex = 0;

    // Process pinned first
    const pinnedItems = document.querySelectorAll('#pinnedNotesGrid .note-item');
    pinnedItems.forEach((item) => {
        order.push({
            id: item.dataset.id,
            order: currentIndex++
        });
    });

    // Then others
    const otherItems = document.querySelectorAll('#othersNotesGrid .note-item');
    otherItems.forEach((item) => {
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

function handleNoteClick(event, note) {
    if (isSortingMode) return;
    openViewNoteModal(note);
}

function openViewNoteModal(note) {
    const titleEl = document.getElementById('viewNoteModalTitle');
    const contentEl = document.getElementById('viewNoteContent');
    const dateEl = document.getElementById('viewNoteDate');
    const tagsWrapper = document.getElementById('viewNoteTags');
    
    titleEl.innerText = note.title;
    contentEl.innerHTML = note.content;
    dateEl.innerText = 'Vytvořeno: ' + new Date(note.created_at).toLocaleString('cs-CZ');
    
    // Set class for styling
    contentEl.className = 'p-3';

    // Tags
    if (tagsWrapper) {
        tagsWrapper.innerHTML = '';
        if (note.tags && note.tags.length > 0) {
            note.tags.forEach(tag => {
                const span = document.createElement('span');
                span.className = 'badge';
                span.style.backgroundColor = tag.color || '#6c757d';
                span.style.fontSize = '0.7rem';
                span.textContent = tag.name;
                tagsWrapper.appendChild(span);
            });
        }
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
            openEditNoteModal(note);
        };
    }
    
    const myModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('viewNoteModal'));
    
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

function openAddNoteModal() {
    const modalEl = document.getElementById('noteModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    document.getElementById('noteModalTitle').innerText = 'Nová poznámka';
    document.getElementById('noteId').value = '';
    document.getElementById('noteTitleInput').value = '';
    document.getElementById('noteContentInput').value = '';
    quill.root.innerHTML = '';
    document.getElementById('noteSubmitBtn').innerText = 'Uložit poznámku';

    // Reset tags and lock
    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => cb.checked = false);
    document.getElementById('noteLockedInput').checked = false;
    
    modal.show();
}

function openEditNoteModal(note) {
    const modalEl = document.getElementById('noteModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    document.getElementById('noteModalTitle').innerText = 'Upravit poznámku';
    document.getElementById('noteId').value = note.id;
    document.getElementById('noteTitleInput').value = note.title;
    document.getElementById('noteContentInput').value = note.content;
    quill.root.innerHTML = note.content;
    document.getElementById('noteSubmitBtn').innerText = 'Uložit změny';

    // Set tags
    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => {
        cb.checked = note.tags ? note.tags.some(t => t.id == cb.value) : false;
    });
    
    // Set locked state robustly
    const isLocked = note.is_locked == 1 || note.is_locked === true || note.is_locked === "1";
    document.getElementById('noteLockedInput').checked = isLocked;
    
    modal.show();
}

function generateAiNoteTitle() {
    const content = quill ? quill.root.innerText.trim() : "";
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

// Search and Tag filtering for notes
const noteSearchInput = document.getElementById('noteSearch');
const noteTagButtons = document.querySelectorAll('#noteTagFilters .btn');
let currentNoteSearch = (localStorage.getItem('noteSearch') || '').toLowerCase().trim();
let currentNoteTag = (localStorage.getItem('noteTag') || 'all').trim();

// Restore initial UI state
if (noteSearchInput) {
    noteSearchInput.value = currentNoteSearch;
}
if (noteTagButtons.length > 0) {
    noteTagButtons.forEach(btn => {
        if (btn.getAttribute('data-tag') === currentNoteTag) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

const filterNotes = () => {
    const notes = document.querySelectorAll('.note-item');
    let delay = 0;
    let pinnedVisible = 0;
    let othersVisible = 0;
    
    notes.forEach(note => {
        const title = note.querySelector('.card-title').textContent.toLowerCase();
        const content = note.querySelector('.card-text').textContent.toLowerCase();
        const tagsAttr = note.getAttribute('data-tags');
        const tags = tagsAttr ? tagsAttr.toLowerCase().split(',') : [];
        
        const matchesSearch = currentNoteSearch === "" || 
                             title.includes(currentNoteSearch) || 
                             content.includes(currentNoteSearch) ||
                             tags.some(t => t.trim().toLowerCase().includes(currentNoteSearch));
        
        const matchesTag = currentNoteTag === 'all' || tags.some(t => t.trim().toLowerCase() === currentNoteTag.toLowerCase());

        if (matchesSearch && matchesTag) {
            note.style.display = 'block';
            note.style.animation = 'none';
            note.offsetHeight; /* trigger reflow */
            note.style.animation = `popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${delay}ms both`;
            delay += 40;
            if (note.classList.contains('pinned')) pinnedVisible++;
            else othersVisible++;
        } else {
            note.style.display = 'none';
            note.style.animation = 'none';
        }
    });

    // Toggle headers
    const pinnedContainer = document.getElementById('pinnedNotesContainer');
    const othersHeader = document.getElementById('othersHeader');
    
    if (pinnedContainer) {
        pinnedContainer.classList.toggle('d-none', pinnedVisible === 0);
    }
    if (othersHeader) {
        othersHeader.classList.toggle('d-none', pinnedVisible === 0 || othersVisible === 0);
    }
};

// Initial filter application
if (noteSearchInput || noteTagButtons.length > 0) {
    filterNotes();
}

if (noteSearchInput) {
    noteSearchInput.addEventListener('input', function(e) {
        currentNoteSearch = e.target.value.toLowerCase();
        localStorage.setItem('noteSearch', currentNoteSearch);
        filterNotes();
    });
}

noteTagButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        noteTagButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentNoteTag = btn.getAttribute('data-tag');
        localStorage.setItem('noteTag', currentNoteTag);
        filterNotes();
    });
});
</script>

<?php include 'includes/footer.php'; ?>
