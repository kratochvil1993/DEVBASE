<?php
require_once 'includes/functions.php';

// Handle Snippet addition, update or delete (same logic as on index.php)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_snippet') {
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $id = !empty($_POST['snippet_id']) ? $_POST['snippet_id'] : null;
        saveSnippet($_POST['title'], $_POST['description'], $_POST['code'], $_POST['language_id'], $tags, $id);
    } elseif ($_POST['action'] == 'delete_snippet') {
        deleteSnippet($_POST['snippet_id']);
    }
    header('Location: manage.php');
    exit;
}

$snippets = getAllSnippets();
$tags = getAllTags();
$languages = getAllLanguages();

include 'includes/header.php';
?>
<div class="container">
<div class="row align-items-center mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="text-white mb-0"><i class="bi bi-list-task me-2"></i>Správa snipetů</h2>
            <button class="btn btn-light rounded px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSnippetModal" title="Nový snipet">
                <i class="bi bi-plus-lg me-1"></i> Nový snipet
            </button>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-12">
        <div class="glass-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-dark text-white mb-0 align-middle" style="background: transparent;">
                    <thead class="border-bottom border-light border-opacity-25" style="background: rgba(255,255,255,0.05);">
                        <tr>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 5%;">ID</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50">Název</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 15%;">Jazyk</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50" style="width: 30%;">Štítky</th>
                            <th scope="col" class="py-3 px-4 fw-normal text-white-50 text-end" style="width: 15%;">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($snippets)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-white-50 py-5">
                                    <i class="bi bi-inbox fs-2 mb-3 d-block"></i>
                                    Zatím nemáte žádné snipety
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($snippets as $index => $snippet): ?>
                                <tr class="border-bottom border-light border-opacity-10">
                                    <td class="px-4 py-3"><span class="text-white-50 small">#<?php echo $snippet['id']; ?></span></td>
                                    <td class="px-4 py-3 fw-medium">
                                        <?php echo htmlspecialchars($snippet['title']); ?>
                                        <?php if(!empty($snippet['description'])): ?>
                                            <div class="small text-white-50 fw-light mt-1 text-truncate" style="max-width: 350px;">
                                                <?php echo htmlspecialchars($snippet['description']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (!empty($snippet['language_name'])): ?>
                                            <span class="badge border border-light border-opacity-25 text-white fw-normal font-monospace">
                                                <?php echo htmlspecialchars($snippet['language_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-white-50 small fst-italic">Bez jazyka</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php if (empty($snippet['tags'])): ?>
                                                <span class="text-white-50 small fst-italic">Bez štítku</span>
                                            <?php else: ?>
                                                <?php foreach ($snippet['tags'] as $tag): ?>
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
                                                onclick='openViewModal(<?php echo htmlspecialchars(json_encode($snippet), ENT_QUOTES, 'UTF-8'); ?>)'
                                                title="Zobrazit snipet">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-light border-0 px-2" 
                                                onclick='openEditModal(<?php echo htmlspecialchars(json_encode($snippet), ENT_QUOTES, 'UTF-8'); ?>)'
                                                title="Upravit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tento snipet smazat?');">
                                            <input type="hidden" name="action" value="delete_snippet">
                                            <input type="hidden" name="snippet_id" value="<?php echo $snippet['id']; ?>">
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
                    <button type="submit" class="btn btn-light px-4" id="submitBtn">Uložit snipet</button>
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
</div>

<?php include 'includes/footer.php'; ?>
