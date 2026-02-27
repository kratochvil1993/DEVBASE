<?php
require_once 'includes/functions.php';

// Handle Snippet addition, update or delete (same logic as on index.php)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $updated_id = '';
    if ($_POST['action'] == 'add_snippet') {
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $id = !empty($_POST['snippet_id']) ? $_POST['snippet_id'] : null;
        $is_locked = isset($_POST['is_locked']) ? 1 : 0;
        $snippet_id = saveSnippet($_POST['title'], $_POST['description'], $_POST['code'], $_POST['language_id'], $tags, $id, $is_locked);
        if ($snippet_id) $updated_id = $snippet_id;
    } elseif ($_POST['action'] == 'delete_snippet') {
        deleteSnippet($_POST['snippet_id']);
    } elseif ($_POST['action'] == 'toggle_pin') {
        toggleSnippetPin($_POST['snippet_id']);
        $updated_id = $_POST['snippet_id'];
    }
    
    $redirect_url = 'manage.php';
    if ($updated_id) {
        $redirect_url .= '?updated_id=' . $updated_id;
    }
    header('Location: ' . $redirect_url);
    exit;
}

$snippets = getAllSnippets();
$pinnedSnippets = array_filter($snippets, function($s) { return ($s['is_pinned'] ?? 0) == 1; });
$otherSnippets = array_filter($snippets, function($s) { return ($s['is_pinned'] ?? 0) == 0; });
$tags = getAllTags();
$languages = getAllLanguages();
$geminiApiKey = getSetting('gemini_api_key');

include 'includes/header.php';
?>
<style>
    .manage-snippet-row:target {
        background: rgba(var(--bs-primary-rgb), 0.15) !important;
        outline: 1px solid rgba(var(--bs-primary-rgb), 0.3);
        transition: background 1s ease-in-out;
    }</style>
<div class="container">
<div class="row align-items-center mb-4">
    <div class="col-12">
        <h2 class="text-white mb-0">Správa snippetů</h2>
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
                    <input type="text" id="manageSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat">
                </div>

                <!-- Action Buttons -->
                <div class="d-flex flex-shrink-0 gap-2">
                    <button class="btn btn-add-snipet rounded px-3 shadow-sm" id="newSnippetBtn" onclick="openAddModal()" title="Nový snipet">
                        <i class="bi bi-plus-lg"></i>
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
        <div class="d-flex flex-wrap justify-content-end gap-2" id="manageTagFilters">
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
        <div class="glass-card p-0 no-jump overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-dark text-white mb-0 align-middle manage-snippets-table" style="background: transparent;">
                    <thead class="border-bottom border-light border-opacity-25" style="background: rgba(255,255,255,0.05);">
                        <tr>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 5%;">ID</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 5%;"><i class="bi bi-pin-angle" title="Připnuto"></i></th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 35%;">Název</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 15%;">Jazyk</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 25%;">Štítky</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50 text-end" style="width: 15%;">Akce</th>
                        </tr>
                    </thead>
                    <tbody id="managePinnedGrid">
                        <?php if (!empty($pinnedSnippets)): ?>
                            <tr class="section-header-row" data-section="pinned" style="background: rgba(255,193,7,0.05);">
                                <td colspan="6" class="px-4 py-2 border-bottom border-light border-opacity-10">
                                    <span class="text-warning small fw-bold"><i class="bi bi-pin-angle-fill me-2"></i>PŘIPNUTÉ</span>
                                </td>
                            </tr>
                            <?php foreach ($pinnedSnippets as $snippet): ?>
                                <?php include 'includes/manage_snippet_row.php'; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tbody id="manageGrid">
                        <?php if (!empty($pinnedSnippets) && !empty($otherSnippets)): ?>
                            <tr class="section-header-row" data-section="others" style="background: rgba(255,255,255,0.03);">
                                <td colspan="6" class="px-4 py-2 border-bottom border-light border-opacity-10">
                                    <span class="text-white-50 small fw-bold">OSTATNÍ</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (empty($snippets)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-white-50 py-5">
                                    <i class="bi bi-inbox fs-2 mb-3 d-block"></i>
                                    Zatím nemáte žádné snipety
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($otherSnippets as $snippet): ?>
                                <?php include 'includes/manage_snippet_row.php'; ?>
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
    const searchInput = document.getElementById('manageSearch');
    const tagButtons  = document.querySelectorAll('#manageTagFilters .btn');
    let currentSearch = '';
    let currentTag    = 'all';

    const filterRows = () => {
        let pinnedVisible = 0;
        let othersVisible = 0;

        document.querySelectorAll('.manage-row').forEach(row => {
            const title = row.dataset.title || '';
            const desc  = row.dataset.desc  || '';
            const tags  = row.dataset.tags  ? row.dataset.tags.split(',') : [];

            const matchSearch = title.includes(currentSearch) ||
                                desc.includes(currentSearch)  ||
                                tags.some(t => t.includes(currentSearch));

            const matchTag = currentTag === 'all' || tags.includes(currentTag.toLowerCase());

            const isVisible = matchSearch && matchTag;
            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                if (row.closest('#managePinnedGrid')) pinnedVisible++;
                else othersVisible++;
            }
        });

        // Toggle section headers
        const pinnedHeader = document.querySelector('.section-header-row[data-section="pinned"]');
        const othersHeader = document.querySelector('.section-header-row[data-section="others"]');
        
        if (pinnedHeader) pinnedHeader.style.display = pinnedVisible > 0 ? '' : 'none';
        if (othersHeader) othersHeader.style.display = othersVisible > 0 ? '' : 'none';

        // Hide/show pinned grid completely if empty
        const pinnedGrid = document.getElementById('managePinnedGrid');
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
</script>

<!-- SortableJS -->
<script src="assets/vendor/sortablejs/Sortable.min.js"></script>
<script>
let sortablePinned = null;
let sortable = null;
let isSortingMode = false;

function toggleSortingMode() {
    isSortingMode = !isSortingMode;
    const pinnedGrid = document.getElementById('managePinnedGrid');
    const grid = document.getElementById('manageGrid');
    const editBtn = document.getElementById('editOrderBtn');
    const saveBtn = document.getElementById('saveOrderBtn');
    const newBtn = document.getElementById('newSnippetBtn');
    const actionButtons = document.querySelectorAll('.manage-row td:last-child button, .manage-row td:last-child form');
    const sectionHeaders = document.querySelectorAll('.section-header-row');

    if (isSortingMode) {
        if (pinnedGrid) pinnedGrid.classList.add('sorting-mode');
        grid.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        newBtn.classList.add('opacity-50', 'pe-none');
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
        newBtn.classList.remove('opacity-50', 'pe-none');
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
    document.querySelectorAll('#managePinnedGrid .manage-row').forEach(item => {
        order.push({
            id: item.dataset.id,
            order: currentIndex++
        });
    });

    // Other items
    document.querySelectorAll('#manageGrid .manage-row').forEach(item => {
        order.push({
            id: item.dataset.id,
            order: currentIndex++
        });
    });
    
    fetch('api/api_snippets_order.php', {
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

function openAddModal() {
    const modalEl = document.getElementById('addSnippetModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    document.getElementById('snippetForm').reset();
    document.getElementById('modalTitle').innerText = 'Přidat nový snipet';
    document.getElementById('snippetId').value = '';
    
    const lockInput = document.getElementById('snippetLockedInput');
    if (lockInput) lockInput.checked = false;
    
    modal.show();
}

function openEditModal(snippet) {
    const modalEl = document.getElementById('addSnippetModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    document.getElementById('modalTitle').innerText = 'Upravit snipet';
    document.getElementById('snippetId').value = snippet.id;
    
    document.getElementById('snippetTitleInput').value = snippet.title || '';
    document.getElementById('snippetDescriptionInput').value = snippet.description || '';
    document.getElementById('snippetLanguageInput').value = snippet.language_id || '';
    document.getElementById('snippetCodeInput').value = snippet.code || '';
    
    const tagCheckboxes = document.querySelectorAll('#snippetForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => {
        cb.checked = snippet.tags ? snippet.tags.some(t => t.id == cb.value) : false;
    });
    
    document.getElementById('submitBtn').innerText = 'Uložit změny';

    const isLocked = (snippet.is_locked == 1 || snippet.is_locked === true || snippet.is_locked === "1");
    
    modal.show();

    const applyLockState = () => {
        const lockInput = document.getElementById('snippetLockedInput');
        if (lockInput) lockInput.checked = isLocked;
    };
    applyLockState();
    modalEl.addEventListener('shown.bs.modal', applyLockState, { once: true });
}

function openViewModal(snippet) {
    document.getElementById('viewModalTitle').innerText = snippet.title;
    document.getElementById('viewModalLanguage').innerText = snippet.language_name || 'Bez jazyka';
    
    const codeEl = document.getElementById('viewModalCode');
    const preEl = document.getElementById('viewModalPre');
    const mdEl = document.getElementById('viewModalMarkdown');
    
    codeEl.textContent = snippet.code;
    codeEl.className = 'language-' + (snippet.prism_class || 'none');
    
    if (snippet.prism_class === 'markdown' && typeof marked !== 'undefined') {
        mdEl.innerHTML = marked.parse(snippet.code);
        mdEl.style.display = 'block';
        preEl.style.display = 'none';
    } else {
        mdEl.style.display = 'none';
        preEl.style.display = 'block';
        if (typeof Prism !== 'undefined') {
            Prism.highlightElement(codeEl);
        }
    }

    const tagsWrapper = document.getElementById('viewModalTags');
    tagsWrapper.innerHTML = '';
    if (snippet.tags && snippet.tags.length > 0) {
        snippet.tags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'badge tag-badge me-1';
            span.style.backgroundColor = tag.color || '#6c757d';
            span.style.color = '#fff';
            span.textContent = tag.name;
            tagsWrapper.appendChild(span);
        });
    }

    const editBtn = document.getElementById('editSnippetFromViewBtn');
    if (editBtn) {
        editBtn.onclick = () => {
            const viewModalEl = document.getElementById('viewSnippetModal');
            const viewModal = bootstrap.Modal.getInstance(viewModalEl);
            if (viewModal) viewModal.hide();
            openEditModal(snippet);
        };
    }
    
    var myModal = new bootstrap.Modal(document.getElementById('viewSnippetModal'));
    myModal.show();
}

function copyToClipboard(btn, elementId) {
    const code = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(code).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = 'copied!';
        btn.classList.replace('btn-outline-light', 'btn-success');
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-outline-light');
        }, 2000);
    });
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
</script>

<!-- Add Snippet Modal -->
<div class="modal fade" id="addSnippetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white" id="modalTitle">Přidat nový snipet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="snippetForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_snippet">
                    <input type="hidden" name="snippet_id" id="snippetId" value="">
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="form-label text-white-50 small mb-0">Název</label>
                                <?php if (!empty($geminiApiKey)): ?>
                                <button type="button" class="btn btn-sm btn-ai-action" onclick="generateAiField('generate_title', 'snippetTitleInput')" title="Generovat název">
                                    <i class="bi bi-magic me-1"></i> AI
                                </button>
                                <?php endif; ?>
                            </div>
                            <input type="text" name="title" id="snippetTitleInput" class="form-control form-control-ai text-white border-light border-opacity-25" required placeholder="Název snipetu...">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="form-label text-white-50 small mb-0">Popis</label>
                                <?php if (!empty($geminiApiKey)): ?>
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
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Jazyk</label>
                            <select name="language_id" id="snippetLanguageInput" class="form-select bg-transparent text-white border-light border-opacity-25 shadow-none">
                                <option value="" class="text-dark">Vybrat jazyk</option>
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
                        <label class="form-label text-white-50 small">Kód</label>
                        <textarea name="code" id="snippetCodeInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none font-monospace" rows="10" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-add-snipet px-4" id="submitBtn">Uložit snipet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Snippet Modal -->
<div class="modal fade" id="viewSnippetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white mb-0" id="viewModalTitle">Zobrazit snipet</h5>
                <span class="badge tag-badge ms-3" id="viewModalLanguage"></span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="snippet-code-wrapper position-relative m-3">
                    <button class="btn btn-sm btn-outline-light copy-btn shadow-sm z-3" onclick="copyToClipboard(this, 'viewModalCode')" style="position: absolute; right: 10px; top: 10px; z-index: 10;">
                        copy
                    </button>
                    <div id="viewModalMarkdown" class="p-3 text-white markdown-preview" style="display: none; overflow-x: auto;"></div>
                    <pre id="viewModalPre" class="m-0"><code id="viewModalCode" class=""></code></pre>
                </div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10 d-flex justify-content-between align-items-center">
                <div id="viewModalTags" class="snippet-tags m-0"></div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-add-snipet px-3" id="editSnippetFromViewBtn">
                        <i class="bi bi-pencil me-1"></i> Upravit
                    </button>
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
