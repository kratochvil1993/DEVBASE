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
        $lang_id = !empty($_POST['language_id']) ? $_POST['language_id'] : null;
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        saveNote($_POST['title'], $_POST['content'], $lang_id, $tags, $id);
    } elseif ($_POST['action'] == 'delete_note') {
        deleteNote($_POST['note_id']);
    }
    $sortParam = isset($_GET['sort']) ? '?sort=' . $_GET['sort'] : '';
    header('Location: notes.php' . $sortParam);
    exit;
}

$currentSort = isset($_GET['sort']) ? $_GET['sort'] : 'custom';
$notes = getAllNotes($currentSort);
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
uasort($usedTags, function($a, $b) { return strcmp($a['name'], $b['name']); });

include 'includes/header.php';
?>

<div class="row mb-5 align-items-center">
    <div class="col-lg-8 mx-auto">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 glass-card p-2 px-3">
            <div class="flex-grow-1" style="max-width: 400px;">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="noteSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat v poznámkách...">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-add-snipet rounded-pill px-4 py-2" id="newNoteBtn" data-bs-toggle="modal" data-bs-target="#noteModal" onclick="openAddNoteModal()">
                    <i class="bi bi-plus-lg me-2"></i> Nová poznámka
                </button>
                <button class="btn btn-edit-order rounded-pill px-4 py-2" id="editOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-arrows-move me-2"></i> Upravit pořadí
                </button>
                <button class="btn btn-success rounded-pill px-4 py-2 d-none" id="saveOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-check-lg me-2"></i> Hotovo
                </button>
                <div class="dropdown" id="sortDropdownContainer">
                    <button class="btn btn-outline-light rounded-pill px-3 py-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
<div class="row mb-4">
    <div class="col-lg-8 mx-auto">
        <div id="noteTagFilters" class="d-flex flex-wrap gap-2 justify-content-center">
            <button class="btn btn-sm btn-outline-light rounded-pill px-3 active" data-tag="all">Vše</button>
            <?php foreach ($usedTags as $tag): ?>
                <button class="btn btn-sm btn-outline-light rounded-pill px-3" 
                        data-tag="<?php echo htmlspecialchars($tag['name']); ?>"
                        style="--tag-color: <?php echo $tag['color'] ? $tag['color'] : 'rgba(255,255,255,0.2)'; ?>">
                    <?php echo htmlspecialchars($tag['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4" id="notesGrid">
    <?php if (empty($notes)): ?>
        <div class="col-12 text-center text-white-50 py-5">
            <i class="bi bi-journal-x display-1 mb-3 d-block"></i>
            <h3>Zatím nemáte žádné poznámky.</h3>
            <p>Klikněte na tlačítko výše a vytvořte si první!</p>
        </div>
    <?php else: ?>
        <?php foreach ($notes as $note): 
            $tagNames = array_map(function($t) { return $t['name']; }, $note['tags']);
            $tagData = implode(',', $tagNames);
        ?>
            <div class="col-md-4 col-lg-6 note-item" data-id="<?php echo $note['id']; ?>" data-tags="<?php echo htmlspecialchars($tagData); ?>">
                <div class="card glass-card h-100 note-card" onclick="handleNoteClick(event, <?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title text-white mb-0 text-truncate">
                                <?php echo htmlspecialchars($note['title']); ?>
                            </h5>
                            <div class="d-flex gap-2 delete-btn-wrapper" onclick="event.stopPropagation()">
                                <button class="btn btn-sm btn-link text-white-50 p-0 edit-icon" 
                                        onclick="openEditNoteModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)"
                                        title="Upravit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tuto poznámku smazat?');">
                                    <input type="hidden" name="action" value="delete_note">
                                    <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Smazat">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <p class="card-text text-white-50 small mb-0" style="display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical; overflow: hidden; font-family: var(--bs-font-monospace);">
                            <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                        </p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pt-0">
                        <small class="text-white-25" style="font-size: 0.65rem;">
                            <?php echo date('d.m.Y H:i', strtotime($note['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
                    <pre class="m-0 border-0 bg-transparent rounded-0" style="max-height: 70vh;"><code id="viewNoteContent" class="language-none"></code></pre>
                </div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <small id="viewNoteDate" class="text-white-25 m-0"></small>
                    <span id="viewNoteLang" class="badge tag-badge m-0"></span>
                </div>
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
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
                        <div class="col-md-8">
                            <label class="form-label text-white-50 small">Název</label>
                            <input type="text" name="title" id="noteTitleInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required placeholder="Napište název...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50 small">Jazyk (volitelně)</label>
                            <select name="language_id" id="noteLanguageInput" class="form-select bg-transparent text-white border-light border-opacity-25 shadow-none">
                                <option value="" class="bg-dark">Bez formátování</option>
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?php echo $lang['id']; ?>" class="bg-dark" data-prism="<?php echo $lang['prism_class']; ?>">
                                        <?php echo htmlspecialchars($lang['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Obsah</label>
                            <textarea name="content" id="noteContentInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" rows="10" required placeholder="Napište vaši poznámku..."></textarea>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
let sortable = null;
let isSortingMode = false;

function toggleSortingMode() {
    isSortingMode = !isSortingMode;
    const grid = document.getElementById('notesGrid');
    const editBtn = document.getElementById('editOrderBtn');
    const saveBtn = document.getElementById('saveOrderBtn');
    const newNoteBtn = document.getElementById('newNoteBtn');
    const sortDropdown = document.getElementById('sortDropdownContainer');
    const deleteBtnWrappers = document.querySelectorAll('.delete-btn-wrapper');

    if (isSortingMode) {
        grid.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        newNoteBtn.classList.add('opacity-50', 'pe-none');
        sortDropdown.classList.add('opacity-50', 'pe-none');
        deleteBtnWrappers.forEach(el => el.classList.add('d-none'));

        sortable = new Sortable(grid, {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveOrder();
            }
        });
    } else {
        grid.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        newNoteBtn.classList.remove('opacity-50', 'pe-none');
        sortDropdown.classList.remove('opacity-50', 'pe-none');
        deleteBtnWrappers.forEach(el => el.classList.remove('d-none'));

        if (sortable) {
            sortable.destroy();
            sortable = null;
        }
        
        window.location.href = 'notes.php?sort=custom';
    }
}

function saveOrder() {
    const grid = document.getElementById('notesGrid');
    const items = grid.querySelectorAll('.note-item');
    const order = [];
    items.forEach((item, index) => {
        order.push({
            id: item.dataset.id,
            order: index
        });
    });
    
    fetch('api_notes_order.php', {
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
    const langEl = document.getElementById('viewNoteLang');
    const tagsWrapper = document.getElementById('viewNoteTags');
    
    titleEl.innerText = note.title;
    contentEl.textContent = note.content;
    dateEl.innerText = 'Vytvořeno: ' + new Date(note.created_at).toLocaleString('cs-CZ');
    
    // Set language class for Prism
    contentEl.className = note.prism_class ? 'language-' + note.prism_class : 'language-none';
    langEl.innerText = note.language_name || 'Bez formátování';
    langEl.style.display = note.language_name ? 'inline-block' : 'none';

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
        Prism.highlightElement(contentEl);
    }
    
    var myModal = new bootstrap.Modal(document.getElementById('viewNoteModal'));
    myModal.show();
}

function copyNoteContent(btn) {
    const content = document.getElementById('viewNoteContent').textContent;
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
    document.getElementById('noteLanguageInput').value = '';
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
    document.getElementById('noteLanguageInput').value = note.language_id || '';
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
        } else {
            note.style.display = 'none';
            note.style.animation = 'none';
        }
    });
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
