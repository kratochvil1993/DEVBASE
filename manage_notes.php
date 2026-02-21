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
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $id = !empty($_POST['note_id']) ? $_POST['note_id'] : null;
        $language_id = !empty($_POST['language_id']) ? $_POST['language_id'] : null;
        saveNote($_POST['title'], $_POST['content'], $language_id, $tags, $id);
    } elseif ($_POST['action'] == 'delete_note') {
        deleteNote($_POST['note_id']);
    }
    header('Location: manage_notes.php');
    exit;
}

$notes = getAllNotes('custom');
$tags = getAllTags('note');
$languages = getAllLanguages();

include 'includes/header.php';
?>
<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0">Správa poznámek</h2>
        </div>
    </div>

    <!-- Controls (Search, Buttons, Filters) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="glass-card p-3 mb-3">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <!-- Search Input -->
                    <div class="input-group flex-grow-1" style="max-width: 400px;">
                        <span class="input-group-text bg-transparent border-0 text-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="manageNotesSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat poznámky...">
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 ">
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
                <button class="btn btn-sm btn-outline-light rounded-pill px-3 active" data-tag="all">Vše</button>
                <?php foreach ($tags as $tag): ?>
                    <button class="btn btn-sm rounded-pill px-3 <?php echo empty($tag['color']) ? 'btn-outline-light' : ''; ?>"
                            data-tag="<?php echo htmlspecialchars($tag['name']); ?>"
                            <?php if (!empty($tag['color'])) echo 'style="background-color: ' . htmlspecialchars($tag['color']) . '; color: #fff; border-color: ' . htmlspecialchars($tag['color']) . ';"'; ?>>
                        <?php echo htmlspecialchars($tag['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
        <div class="glass-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-dark text-white mb-0 align-middle manage-notes-table" style="background: transparent;">
                    <thead class="border-bottom border-light border-opacity-25" style="background: rgba(255,255,255,0.05);">
                        <tr>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 5%;">ID</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50">Název</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 15%;">Jazyk</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 30%;">Štítky</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50 text-end" style="width: 15%;">Akce</th>
                        </tr>
                    </thead>
                    <tbody id="manageNotesGrid">
                        <?php if (empty($notes)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-white-50 py-5">
                                    <i class="bi bi-journal-x fs-2 mb-3 d-block"></i>
                                    Zatím nemáte žádné poznámky
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notes as $index => $note): ?>
                                <tr class="manage-note-row"
                                    data-id="<?php echo $note['id']; ?>"
                                    data-title="<?php echo strtolower(htmlspecialchars($note['title'])); ?>"
                                    data-content="<?php echo strtolower(htmlspecialchars($note['content'])); ?>"
                                    data-tags="<?php echo strtolower(htmlspecialchars(implode(',', array_column($note['tags'], 'name')))); ?>">
                                    <td class="px-4 py-3"><span class="text-white-50 small">#<?php echo $note['id']; ?></span></td>
                                    <td class="px-4 py-3 fw-medium">
                                        <?php echo htmlspecialchars($note['title']); ?>
                                        <div class="small text-white-50 fw-light mt-1 text-truncate" style="max-width: 350px;">
                                            <?php echo htmlspecialchars(substr($note['content'], 0, 100)) . (strlen($note['content']) > 100 ? '...' : ''); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (!empty($note['language_name'])): ?>
                                            <span class="badge border border-light border-opacity-25 text-white fw-normal font-monospace">
                                                <?php echo htmlspecialchars($note['language_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-white-50 small fst-italic">Bez formátování</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php if (empty($note['tags'])): ?>
                                                <span class="text-white-50 small fst-italic">Bez štítku</span>
                                            <?php else: ?>
                                                <?php foreach ($note['tags'] as $tag): ?>
                                                    <span class="badge tag-badge fw-normal"
                                                          <?php if (!empty($tag['color'])) echo 'style="background-color: ' . htmlspecialchars($tag['color']) . '; color: #fff; border-color: ' . htmlspecialchars($tag['color']) . ';"'; ?>>
                                                        <?php echo htmlspecialchars($tag['name']); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-light border-0 px-2" 
                                                onclick='openViewNoteManageModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)'
                                                title="Zobrazit poznámku">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-light border-0 px-2" 
                                                onclick='openEditNoteManageModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)'
                                                title="Upravit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tuto poznámku smazat?');">
                                            <input type="hidden" name="action" value="delete_note">
                                            <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Smazat">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
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
        document.querySelectorAll('.manage-note-row').forEach(row => {
            const title = row.dataset.title || '';
            const content = row.dataset.content || '';
            const tags  = row.dataset.tags  ? row.dataset.tags.split(',') : [];

            const matchSearch = title.includes(currentSearch) ||
                                content.includes(currentSearch) ||
                                tags.some(t => t.includes(currentSearch));

            const matchTag = currentTag === 'all' || tags.includes(currentTag.toLowerCase());

            row.style.display = (matchSearch && matchTag) ? '' : 'none';
        });
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
    document.getElementById('noteLanguageInput').value = '';
    document.getElementById('noteSubmitBtn').innerText = 'Uložit poznámku';

    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => cb.checked = false);
}

function openEditNoteManageModal(note) {
    document.getElementById('noteModalTitle').innerText = 'Upravit poznámku';
    document.getElementById('noteId').value = note.id;
    document.getElementById('noteTitleInput').value = note.title;
    document.getElementById('noteContentInput').value = note.content;
    document.getElementById('noteLanguageInput').value = note.language_id || '';
    document.getElementById('noteSubmitBtn').innerText = 'Uložit změny';

    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => {
        cb.checked = note.tags.some(t => t.name == cb.nextElementSibling.innerText.trim());
    });
    
    var myModal = new bootstrap.Modal(document.getElementById('addNoteModal'));
    myModal.show();
}

function openViewNoteManageModal(note) {
    document.getElementById('viewNoteModalTitle').innerText = note.title;
    document.getElementById('viewNoteLanguage').innerText = note.language_name || 'Bez formátování';
    document.getElementById('viewNoteLanguage').style.display = note.language_name ? 'inline-block' : 'none';
    
    const contentEl = document.getElementById('viewNoteContent');
    contentEl.textContent = note.content;
    contentEl.className = note.prism_class ? 'language-' + note.prism_class : 'language-none';
    
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

    if (note.prism_class === 'markdown' && window.marked) {
        document.getElementById('viewNotePre').style.display = 'none';
        const markdownDiv = document.getElementById('viewNoteMarkdown');
        markdownDiv.style.display = 'block';
        markdownDiv.innerHTML = marked.parse(note.content);
        if (window.Prism) {
            markdownDiv.querySelectorAll('pre code').forEach((block) => {
                Prism.highlightElement(block);
            });
        }
    } else {
        document.getElementById('viewNoteMarkdown').style.display = 'none';
        document.getElementById('viewNotePre').style.display = 'block';
        if (typeof Prism !== 'undefined') {
            Prism.highlightElement(contentEl);
        }
    }
    
    var myModal = new bootstrap.Modal(document.getElementById('viewNoteModal'));
    myModal.show();
}

</script>

<!-- Add/Edit Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white" id="noteModalTitle">Nová poznámka</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="noteForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_note">
                    <input type="hidden" name="note_id" id="noteId" value="">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Název</label>
                        <input type="text" id="noteTitleInput" name="title" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Jazyk</label>
                            <select id="noteLanguageInput" name="language_id" class="form-select bg-transparent text-white border-light border-opacity-25 shadow-none">
                                <option value="" class="text-dark">Bez formátování</option>
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?php echo $lang['id']; ?>" class="text-dark"><?php echo htmlspecialchars($lang['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Štítky</label>
                            <div class="d-flex flex-wrap gap-2 pt-1">
                                <?php foreach ($tags as $tag): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="tag-<?php echo $tag['id']; ?>">
                                        <label class="form-check-label text-white small" for="tag-<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Obsah</label>
                        <textarea id="noteContentInput" name="content" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none font-monospace" rows="10" required></textarea>
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white mb-0" id="viewNoteModalTitle">Zobrazit poznámku</h5>
                <span class="badge tag-badge ms-3" id="viewNoteLanguage"></span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="snippet-code-wrapper position-relative m-3">
                    <button class="btn btn-sm btn-outline-light copy-btn shadow-sm z-3" onclick="copyToClipboard(this, 'viewNoteContent')" style="position: absolute; right: 10px; top: 10px; z-index: 10;">
                        copy
                    </button>
                    <div id="viewNoteMarkdown" class="p-3 text-white markdown-preview" style="display: none; overflow-x: auto;"></div>
                    <pre id="viewNotePre" class="m-0"><code id="viewNoteContent" class=""></code></pre>
                </div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10 d-flex justify-content-between align-items-center">
                <div id="viewNoteTags" class="snippet-tags m-0"></div>
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
            </div>
        </div>
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
    const grid = document.getElementById('manageNotesGrid');
    const editBtn = document.getElementById('editOrderBtn');
    const saveBtn = document.getElementById('saveOrderBtn');
    const newNoteBtn = document.getElementById('newNoteBtn');
    const actionButtons = document.querySelectorAll('.manage-note-row td:last-child button, .manage-note-row td:last-child form');

    if (isSortingMode) {
        grid.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        newNoteBtn.classList.add('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.add('opacity-0', 'pe-none'));

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
        
        actionButtons.forEach(btn => btn.classList.remove('opacity-0', 'pe-none'));

        if (sortable) {
            sortable.destroy();
            sortable = null;
        }
    }
}

function saveOrder() {
    const grid = document.getElementById('manageNotesGrid');
    const items = grid.querySelectorAll('.manage-note-row');
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
</script>

<?php include 'includes/footer.php'; ?>
