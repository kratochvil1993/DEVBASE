<?php
require_once 'includes/functions.php';

// Synchronous POST handler removed for AJAX-ification


$snippets = getAllSnippets();
$pinnedSnippets = array_filter($snippets, function($s) { return ($s['is_pinned'] ?? 0) == 1; });
$otherSnippets = array_filter($snippets, function($s) { return ($s['is_pinned'] ?? 0) == 0; });
$allTags = getAllTags(); // For the modal
$languages = getAllLanguages();
$geminiApiKey = getSetting('gemini_api_key');


// Identify used tags for filtering
$usedTags = [];
foreach ($snippets as $snippet) {
    if (!empty($snippet['tags'])) {
        foreach ($snippet['tags'] as $tag) {
            $usedTags[$tag['name']] = $tag; // Use name as key for uniqueness and sort
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
    <div class="col-md-8 mx-auto">
        <div class="glass-card no-jump p-2 d-flex gap-3">
            <div class="input-group flex-grow-1" >

                <span class="input-group-text bg-transparent border-0 text-white">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="snippetSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat">
            </div>
            <button class="btn btn-add-snipet rounded px-3 ms-auto" onclick="openAddModal()" id="newSnippetBtn" title="Nový snipet">
                <i class="bi bi-plus-lg"></i>
            </button>
            <button class="btn btn-edit-order rounded px-4" id="editOrderBtn" onclick="toggleSortingMode()" style="text-wrap: nowrap;">
                <i class="bi bi-arrows-move me-2"></i> Upravit pořadí
            </button>
            <button class="btn btn-success rounded px-4 d-none" id="saveOrderBtn" onclick="toggleSortingMode()" style="text-wrap: nowrap;">
                <i class="bi bi-check-lg me-2"></i> Hotovo
            </button>
        </div>
    </div>
</div>

<?php if (!empty($usedTags)): ?>
<div class="row mb-5">
    <div class="col-md-8 mx-auto d-flex flex-wrap gap-2 justify-content-center" id="tagFilters">
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
<?php endif; ?>

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
                                <?php foreach ($allTags as $tag): ?>
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
                <?php if (!empty($geminiApiKey)): ?>
                <div class="dropdown ms-auto me-2">
                    <button class="btn btn-sm btn-ai rounded px-3 dropdown-toggle shadow-none border-opacity-25" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="aiSnippetBtn">
                        <i class="bi bi-robot me-1"></i> AI
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark glass-card border-light border-opacity-10 mt-2 shadow-lg">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiAction('explain_code')">
                                <i class="bi bi-chat-left-text me-2 text-ai"></i> Vysvětlit kód
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <?php if (!empty($geminiApiKey)): ?>
                <!-- AI Insight Box -->
                <div id="aiInsightBox" class="m-3 p-3 rounded-3 d-none" style="background: rgba(10, 10, 15, 0.9); border: 1px solid rgba(142, 84, 233, 0.5); backdrop-filter: blur(5px);">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-robot text-primary me-2"></i>
                        <span class="small fw-bold text-white-50 text-uppercase tracking-wider">AI Insight</span>
                        <button type="button" class="btn-close btn-close-white ms-auto small" style="font-size: 0.5rem;" onclick="document.getElementById('aiInsightBox').classList.add('d-none')"></button>
                    </div>
                    <div id="aiInsightContent" class="text-white small lh-base"></div>
                </div>
                <?php endif; ?>

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

<div id="pinnedSnippetsContainer" class="<?php echo empty($pinnedSnippets) ? 'd-none' : ''; ?>">
    <div class="col-12 mb-3">
        <h6 class="text-white-50 px-1"><i class="bi bi-pin-angle-fill me-2"></i> PŘIPNUTÉ</h6>
    </div>
    <div class="row g-4 mb-5" id="pinnedSnippetsGrid">
        <?php foreach ($pinnedSnippets as $index => $snippet): ?>
            <?php include 'includes/snippet_item_template.php'; ?>
        <?php endforeach; ?>
    </div>
</div>

<div id="othersSnippetsContainer">
    <div class="col-12 mb-3 <?php echo empty($pinnedSnippets) ? 'd-none' : ''; ?>" id="othersHeader">
        <h6 class="text-white-50 px-1">OSTATNÍ</h6>
    </div>
    <div class="row g-4" id="othersSnippetsGrid">
    <?php if (empty($snippets)): ?>
        <div class="col-12 text-center text-white-50 py-5">
            <i class="bi bi-code-slash display-1 mb-3 d-block"></i>
            <h3>Nebyly nalezeny žádné snipety.</h3>
            <p>Začněte přidáním nějakých snipetů!</p>
            <a href="settings.php" class="btn btn-outline-light">Spravovat štítky a jazyky</a>
        </div>
    <?php else: ?>
        <?php foreach ($otherSnippets as $index => $snippet): ?>
            <?php include 'includes/snippet_item_template.php'; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<!-- SortableJS -->
<script src="assets/vendor/sortablejs/Sortable.min.js"></script>
<script>
let sortablePinned = null;
let sortableOthers = null;
let isSortingMode = false;

function toggleSortingMode() {
    isSortingMode = !isSortingMode;
    const pinnedGrid = document.getElementById('pinnedSnippetsGrid');
    const othersGrid = document.getElementById('othersSnippetsGrid');
    const editBtn = document.getElementById('editOrderBtn');
    const saveBtn = document.getElementById('saveOrderBtn');
    const newSnippetBtn = document.getElementById('newSnippetBtn');
    const actionBtns = document.querySelectorAll('.action-btns-wrapper');

    if (isSortingMode) {
        if (pinnedGrid) pinnedGrid.classList.add('sorting-mode');
        if (othersGrid) othersGrid.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        newSnippetBtn.classList.add('opacity-50', 'pe-none');
        actionBtns.forEach(el => el.classList.add('d-none'));

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
        newSnippetBtn.classList.remove('opacity-50', 'pe-none');
        actionBtns.forEach(el => el.classList.remove('d-none'));

        if (sortablePinned) {
            sortablePinned.destroy();
            sortablePinned = null;
        }
        if (sortableOthers) {
            sortableOthers.destroy();
            sortableOthers = null;
        }
    }
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

function saveOrder() {
    const order = [];
    let currentIndex = 0;

    // Process pinned first
    const pinnedItems = document.querySelectorAll('#pinnedSnippetsGrid .snippet-card-wrapper');
    pinnedItems.forEach((item) => {
        order.push({
            id: item.dataset.id,
            order: currentIndex++
        });
    });

    // Then others
    const otherItems = document.querySelectorAll('#othersSnippetsGrid .snippet-card-wrapper');
    otherItems.forEach((item) => {
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
    
    document.getElementById('modalTitle').innerText = 'Přidat nový snipet';
    document.getElementById('snippetId').value = '';
    document.getElementById('snippetForm').reset();
    document.getElementById('submitBtn').innerText = 'Uložit snipet';
    
    // Explicitly clear inputs if reset() isn't enough for some browser states
    document.getElementById('snippetTitleInput').value = '';
    document.getElementById('snippetDescriptionInput').value = '';
    document.getElementById('snippetCodeInput').value = '';
    document.getElementById('snippetLockedInput').checked = false;
    
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
    
    // Tags
    const tagCheckboxes = document.querySelectorAll('#snippetForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => {
        cb.checked = snippet.tags ? snippet.tags.some(t => t.id == cb.value) : false;
    });

    document.getElementById('submitBtn').innerText = 'Uložit změny';
    
    const isLocked = (snippet.is_locked == 1 || snippet.is_locked === true || snippet.is_locked === "1");
    const lockInput = document.getElementById('snippetLockedInput');
    if (lockInput) {
        lockInput.checked = isLocked;
    }
    modal.show();
}

function toggleSnippetPin(id) {
    const formData = new FormData();
    formData.append('action', 'toggle_pin');
    formData.append('snippet_id', id);

    fetch('api/api_snippet_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById('snippet-card-' + id);
            const pinnedGrid = document.getElementById('pinnedSnippetsGrid');
            const othersGrid = document.getElementById('othersSnippetsGrid');
            
            // Remove from current grid
            card.remove();
            
            // Add to new grid
            const targetGrid = data.is_pinned ? pinnedGrid : othersGrid;
            targetGrid.insertAdjacentHTML('afterbegin', data.html);
            
            // Apply Prism highlighting on the new element
            const newCode = document.getElementById('snippet-' + id);
            if (newCode && typeof Prism !== 'undefined') {
                Prism.highlightElement(newCode);
            }
            
            // Highlight the card
            const newWrapper = document.getElementById('snippet-card-' + id);
            if (newWrapper) {
                const innerCard = newWrapper.querySelector('.snippet-card');
                if (innerCard) {
                    innerCard.classList.add('flash-purple');
                    setTimeout(() => innerCard.classList.remove('flash-purple'), 2000);
                }
            }
            
            updateEmptyStates();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteSnippetAjax(id) {
    if (!confirm('Opravdu chcete tento snipet smazat?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_snippet');
    formData.append('snippet_id', id);

    fetch('api/api_snippet_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById('snippet-card-' + id);
            if (card) {
                card.style.transform = 'scale(0.8)';
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    updateEmptyStates();
                }, 300);
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateEmptyStates() {
    const pinnedGrid = document.getElementById('pinnedSnippetsGrid');
    const othersGrid = document.getElementById('othersSnippetsGrid');
    const pinnedContainer = document.getElementById('pinnedSnippetsContainer');
    const othersHeader = document.getElementById('othersHeader');
    
    const pinnedCount = pinnedGrid.querySelectorAll('.snippet-card-wrapper').length;
    const othersCount = othersGrid.querySelectorAll('.snippet-card-wrapper').length;
    const totalCount = pinnedCount + othersCount;

    if (pinnedContainer) pinnedContainer.classList.toggle('d-none', pinnedCount === 0);
    if (othersHeader) othersHeader.classList.toggle('d-none', pinnedCount === 0 || othersCount === 0);
    
    // Remove empty message if it exists and we have items
    const emptyMsg = othersGrid.querySelector('.text-center.text-white-50.py-5');
    if (totalCount > 0 && emptyMsg) {
        emptyMsg.remove();
    }

    // If no snippets at all, show the empty message
    if (totalCount === 0) {
        othersGrid.innerHTML = `
            <div class="col-12 text-center text-white-50 py-5">
                <i class="bi bi-code-slash display-1 mb-3 d-block"></i>
                <h3>Nebyly nalezeny žádné snipety.</h3>
                <p>Začněte přidáním nějakých snipetů!</p>
                <a href="settings.php" class="btn btn-outline-light">Spravovat štítky a jazyky</a>
            </div>
        `;
    }
}

// Handle Form Submission
document.addEventListener('DOMContentLoaded', function() {
    const snippetForm = document.getElementById('snippetForm');
    if (snippetForm) {
        snippetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerText;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Ukládám...';
            
            const isEdit = formData.get('snippet_id') !== '';
            if (isEdit) {
                formData.set('action', 'edit_snippet');
            }

            fetch('api/api_snippet_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Hide modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addSnippetModal'));
                    if (modal) modal.hide();
                    
                    const snippetId = data.id;
                    const existingCard = document.getElementById('snippet-card-' + snippetId);
                    
                    if (existingCard) {
                        // Replace existing card
                        existingCard.outerHTML = data.html;
                    } else {
                        // Add new card (usually to others grid)
                        const grid = data.is_pinned ? document.getElementById('pinnedSnippetsGrid') : document.getElementById('othersSnippetsGrid');
                        grid.insertAdjacentHTML('afterbegin', data.html);
                    }
                    
                    // Re-highlight
                    const newCode = document.getElementById('snippet-' + snippetId);
                    if (newCode && typeof Prism !== 'undefined') {
                        Prism.highlightElement(newCode);
                    }
                    
                    // Flash effect
                    const newWrapper = document.getElementById('snippet-card-' + snippetId);
                    if (newWrapper) {
                        newWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        const innerCard = newWrapper.querySelector('.snippet-card');
                        if (innerCard) {
                            innerCard.classList.add('flash-purple');
                            setTimeout(() => innerCard.classList.remove('flash-purple'), 2000);
                        }
                    }
                    
                    updateEmptyStates();
                    form.reset();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Chyba při komunikaci se serverem.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            });
        });
    }
});




function openViewModal(snippet) {
    document.getElementById('viewModalTitle').innerText = snippet.title;
    document.getElementById('viewModalLanguage').innerText = snippet.language_name || 'Bez jazyka';
    
    const codeEl = document.getElementById('viewModalCode');
    codeEl.innerText = snippet.code;
    codeEl.className = 'language-' + (snippet.prism_class || 'none');
    
    // Tags
    const tagsWrapper = document.getElementById('viewModalTags');
    tagsWrapper.innerHTML = '';
    if (snippet.tags && snippet.tags.length > 0) {
        snippet.tags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'badge tag-badge';
            span.style.backgroundColor = tag.color || '#6c757d';
            span.textContent = tag.name;
            tagsWrapper.appendChild(span);
        });
    }

    // Edit button inside view modal
    document.getElementById('editSnippetFromViewBtn').onclick = function() {
        const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewSnippetModal'));
        if (viewModal) viewModal.hide();
        openEditModal(snippet);
    };

    if (typeof Prism !== 'undefined') {
        Prism.highlightElement(codeEl);
    }

    const myModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('viewSnippetModal'));
    
    // Reset AI box
    const insightBox = document.getElementById('aiInsightBox');
    const insightContent = document.getElementById('aiInsightContent');
    if (insightBox) insightBox.classList.add('d-none');
    if (insightContent) insightContent.innerHTML = '';
    
    myModal.show();

}

let aiTypingInterval = null;

function aiAction(action) {
    const code = document.getElementById('viewModalCode').innerText;
    const insightBox = document.getElementById('aiInsightBox');
    const insightContent = document.getElementById('aiInsightContent');
    const aiBtn = document.getElementById('aiSnippetBtn');

    if (!insightBox || !insightContent) return;

    // Close dropdown after selection
    const dropdownInstance = bootstrap.Dropdown.getInstance(aiBtn);
    if (dropdownInstance) dropdownInstance.hide();


    // Clear previous typing
    if (aiTypingInterval) clearInterval(aiTypingInterval);
    
    insightBox.classList.remove('d-none');
    insightContent.innerHTML = '<div class="d-flex align-items-center gap-2 py-2"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="text-white-50">AI přemýšlí...</span></div>';
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

function typeWriter(text, container) {
    container.innerHTML = '';
    
    // Handle markdown-like formatting (basic)
    // First, convert Markdown to HTML if needed, but for simplicity we can use a basic approach
    // or just show it. Let's use a small helper for basic markdown (bullet points)
    let formattedText = text.replace(/\n/g, '<br>').replace(/\* /g, '• ');
    
    let i = 0;
    const speed = 2; // ms per char
    
    function type() {
        if (i < formattedText.length) {
            // If we encounter <br>, we want to add it all at once to avoid broken tags
            if (formattedText.substr(i, 4) === '<br>') {
                container.innerHTML += '<br>';
                i += 4;
            } else {
                container.innerHTML += formattedText.charAt(i);
                i++;
            }
            aiTypingInterval = setTimeout(type, speed);
            
            // Scroll modal to top if needed, but usually it stays there
            const modalBody = document.querySelector('#viewSnippetModal .modal-body');
            // modalBody.scrollTop = 0; // Optional: ensure we are at the top
        }
    }
    type();
}

if (document.getElementById('viewSnippetModal')) {
    document.getElementById('viewSnippetModal').addEventListener('hidden.bs.modal', function () {
        if (aiTypingInterval) clearInterval(aiTypingInterval);
        const insightBox = document.getElementById('aiInsightBox');
        const insightContent = document.getElementById('aiInsightContent');
        if (insightBox) insightBox.classList.add('d-none');
        if (insightContent) insightContent.innerHTML = '';
    });
}

// Search and Tag filtering
const snippetSearchInput = document.getElementById('snippetSearch');
const tagFilters = document.querySelectorAll('#tagFilters button');

function filterSnippets() {
    if (!snippetSearchInput) return;
    
    const searchTerm = snippetSearchInput.value.toLowerCase().trim();
    const activeTagBtn = document.querySelector('#tagFilters button.active');
    const activeTag = activeTagBtn ? activeTagBtn.dataset.tag : 'all';
    const currentCards = document.querySelectorAll('.snippet-card-wrapper');

    let pinnedCount = 0;
    let othersCount = 0;

    currentCards.forEach(card => {
        const title = (card.querySelector('.card-title')?.innerText || '').toLowerCase();
        const desc = (card.querySelector('.card-text')?.innerText || '').toLowerCase();
        // Try multiple ways to get tags
        const tagsRaw = card.getAttribute('data-tags') || card.querySelector('.snippet-card')?.dataset?.tags || '';
        const cardTags = tagsRaw.toLowerCase().split(',').map(t => t.trim());

        const matchesSearch = !searchTerm || title.includes(searchTerm) || desc.includes(searchTerm) || tagsRaw.toLowerCase().includes(searchTerm);
        const matchesTag = activeTag === 'all' || cardTags.includes(activeTag.toLowerCase());

        if (matchesSearch && matchesTag) {
            card.classList.remove('d-none');
            if (card.closest('#pinnedSnippetsGrid')) pinnedCount++;
            else othersCount++;
        } else {
            card.classList.add('d-none');
        }
    });

    // Toggle visibility of empty containers
    const pinnedContainer = document.getElementById('pinnedSnippetsContainer');
    const othersContainer = document.getElementById('othersSnippetsContainer');
    const othersHeader = document.getElementById('othersHeader');

    if (pinnedContainer) pinnedContainer.classList.toggle('d-none', pinnedCount === 0);
    if (othersContainer) {
        othersContainer.classList.toggle('d-none', othersCount === 0 && pinnedCount === 0);
        if (othersHeader) othersHeader.classList.toggle('d-none', pinnedCount === 0 || othersCount === 0);
    }
}

if (snippetSearchInput) {
    snippetSearchInput.addEventListener('input', filterSnippets);
}

tagFilters.forEach(btn => {
    btn.addEventListener('click', () => {
        tagFilters.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterSnippets();
    });
});

function copyToClipboard(btn, elementId) {
    const text = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(text).then(() => {
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
<?php include 'includes/footer.php'; ?>
