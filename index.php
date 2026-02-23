<?php
require_once 'includes/functions.php';

// Handle Snippet addition, update or delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_snippet') {
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $id = !empty($_POST['snippet_id']) ? $_POST['snippet_id'] : null;
        saveSnippet($_POST['title'], $_POST['description'], $_POST['code'], $_POST['language_id'], $tags, $id);
    } elseif ($_POST['action'] == 'delete_snippet') {
        deleteSnippet($_POST['snippet_id']);
    } elseif ($_POST['action'] == 'toggle_pin') {
        toggleSnippetPin($_POST['snippet_id']);
    }
    header('Location: index.php');
    exit;
}

$snippets = getAllSnippets();
$pinnedSnippets = array_filter($snippets, function($s) { return ($s['is_pinned'] ?? 0) == 1; });
$otherSnippets = array_filter($snippets, function($s) { return ($s['is_pinned'] ?? 0) == 0; });
$allTags = getAllTags(); // For the modal
$languages = getAllLanguages();

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
        <div class="glass-card p-2 d-flex gap-3">
            <div class="input-group flex-grow-1" style="max-width: 161px;">

                <span class="input-group-text bg-transparent border-0 text-white">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="snippetSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat snipety...">
            </div>
            <button class="btn btn-add-snipet rounded px-3 ms-auto" data-bs-toggle="modal" data-bs-target="#addSnippetModal" id="newSnippetBtn" title="Nový snipet">
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white" id="modalTitle">Přidat nový snipet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="snippetForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_snippet">
                    <input type="hidden" name="snippet_id" id="snippetId" value="">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Název</label>
                        <input type="text" name="title" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Popis</label>
                        <textarea name="description" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" rows="2"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Jazyk</label>
                            <select name="language_id" class="form-select bg-transparent text-white border-light border-opacity-25 shadow-none">
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
                        <textarea name="code" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none font-monospace" rows="10" required></textarea>
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
    <div class="modal-dialog modal-xl">
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
</script>
<?php include 'includes/footer.php'; ?>
