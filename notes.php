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
        saveNote($_POST['title'], $_POST['content'], null, $tags, $id);
    } elseif ($_POST['action'] == 'delete_note') {
        deleteNote($_POST['note_id']);
    } elseif ($_POST['action'] == 'archive_note') {
        archiveNote($_POST['note_id'], 1);
    } elseif ($_POST['action'] == 'toggle_pin') {
        toggleNotePin($_POST['note_id']);
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
        <div class="glass-card p-2 d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="flex-grow-1" style="max-width: 400px;">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="noteSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat v poznámkách...">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-add-snipet rounded Xrounded-pill px-4" id="newNoteBtn" data-bs-toggle="modal" data-bs-target="#noteModal" onclick="openAddNoteModal()">
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="position-relative">
                    <button class="btn btn-sm btn-outline-light copy-btn shadow-sm z-3" onclick="copyNoteContent(this)" style="position: absolute; right: 10px; top: 10px; z-index: 10;">
                        copy
                    </button>
                    <div id="viewNoteContent" class="p-3" style="max-height: 70vh; overflow-y: auto;"></div>
                </div>
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
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Název</label>
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
    
    var myModal = new bootstrap.Modal(document.getElementById('viewNoteModal'));
    myModal.show();
}

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
    document.getElementById('noteModalTitle').innerText = 'Nová poznámka';
    document.getElementById('noteId').value = '';
    document.getElementById('noteTitleInput').value = '';
    document.getElementById('noteContentInput').value = '';
    quill.root.innerHTML = '';
    document.getElementById('noteSubmitBtn').innerText = 'Uložit poznámku';

    // Reset tags
    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => cb.checked = false);
}

function openEditNoteModal(note) {
    document.getElementById('noteModalTitle').innerText = 'Upravit poznámku';
    document.getElementById('noteId').value = note.id;
    document.getElementById('noteTitleInput').value = note.title;
    document.getElementById('noteContentInput').value = note.content;
    quill.root.innerHTML = note.content;
    document.getElementById('noteSubmitBtn').innerText = 'Uložit změny';

    // Set tags
    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => {
        cb.checked = note.tags.some(t => t.id == cb.value);
    });
    
    var myModal = new bootstrap.Modal(document.getElementById('noteModal'));
    myModal.show();
}

// Search and Tag filtering for notes
const noteSearchInput = document.getElementById('noteSearch');
const noteTagButtons = document.querySelectorAll('#noteTagFilters .btn');
let currentNoteSearch = '';
let currentNoteTag = 'all';

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
        
        const matchesSearch = title.includes(currentNoteSearch) || 
                             content.includes(currentNoteSearch) ||
                             tags.some(t => t.includes(currentNoteSearch));
        
        const matchesTag = currentNoteTag === 'all' || tags.includes(currentNoteTag.toLowerCase());

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

if (noteSearchInput) {
    noteSearchInput.addEventListener('input', function(e) {
        currentNoteSearch = e.target.value.toLowerCase();
        filterNotes();
    });
}

noteTagButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        noteTagButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentNoteTag = btn.getAttribute('data-tag');
        filterNotes();
    });
});
</script>

<?php include 'includes/footer.php'; ?>
