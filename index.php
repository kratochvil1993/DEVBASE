<?php
require_once 'includes/functions.php';

// Simple check if database exists and tables are created
$check = $conn->query("SHOW TABLES LIKE 'snippets'");
if ($check->num_rows == 0) {
    header('Location: includes/init_db.php');
    exit;
}

// Handle Snippet addition, update or delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_snippet') {
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $id = !empty($_POST['snippet_id']) ? $_POST['snippet_id'] : null;
        saveSnippet($_POST['title'], $_POST['description'], $_POST['code'], $_POST['language_id'], $tags, $id);
    } elseif ($_POST['action'] == 'delete_snippet') {
        deleteSnippet($_POST['snippet_id']);
    }
    header('Location: index.php');
    exit;
}

$snippets = getAllSnippets();
$tags = getAllTags();
$languages = getAllLanguages();

include 'includes/header.php';
?>

<div class="row mb-3 align-items-center">
    <div class="col-md-8 mx-auto">
        <div class="glass-card p-2 d-flex gap-3">
            <div class="input-group flex-grow-1">
                <span class="input-group-text bg-transparent border-0 text-white">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="snippetSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat snipety...">
            </div>
            <button class="btn btn-add-snipet rounded px-3" data-bs-toggle="modal" data-bs-target="#addSnippetModal" title="Nový snipet">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-8 mx-auto d-flex flex-wrap gap-2 justify-content-center" id="tagFilters">
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
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
            </div>
        </div>
    </div>
</div>

<div class="row g-4" id="snippetsGrid">
    <?php if (empty($snippets)): ?>
        <div class="col-12 text-center text-white-50 py-5">
            <i class="bi bi-code-slash display-1 mb-3 d-block"></i>
            <h3>Nebyly nalezeny žádné snipety.</h3>
            <p>Začněte přidáním nějakých snipetů!</p>
            <a href="settings.php" class="btn btn-outline-light">Spravovat štítky a jazyky</a>
        </div>
    <?php else: ?>
        <?php foreach ($snippets as $index => $snippet): ?>
            <div class="col-md-6 col-lg-4 col-xl-3 snippet-card-wrapper">
                <div class="card glass-card h-100 snippet-card" 
                     data-tags="<?php echo htmlspecialchars(implode(',', array_column($snippet['tags'], 'name'))); ?>" 
                     onclick="openViewModal(<?php echo htmlspecialchars(json_encode($snippet), ENT_QUOTES, 'UTF-8'); ?>)">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title text-white mb-0"><?php echo htmlspecialchars($snippet['title']); ?></h5>
                            <div class="d-flex gap-2" onclick="event.stopPropagation()">
                                <button class="btn btn-sm btn-link text-white-50 p-0" 
                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($snippet), ENT_QUOTES, 'UTF-8'); ?>)"
                                        title="Upravit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tento snipet smazat?');">
                                    <input type="hidden" name="action" value="delete_snippet">
                                    <input type="hidden" name="snippet_id" value="<?php echo $snippet['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Smazat">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <p class="card-text text-white-50 small mb-3">
                            <?php echo htmlspecialchars($snippet['description']); ?>
                        </p>
                        
                        <div class="snippet-code-wrapper mb-3 flex-grow-1">
                            <button class="btn btn-sm btn-outline-light copy-btn" onclick="event.stopPropagation(); copyToClipboard(this, 'snippet-<?php echo $index; ?>')">
                                copy
                            </button>
                            <pre><code id="snippet-<?php echo $index; ?>" class="language-<?php echo htmlspecialchars($snippet['prism_class'] ?? 'none'); ?>"><?php echo htmlspecialchars($snippet['code']); ?></code></pre>
                        </div>
                        

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
