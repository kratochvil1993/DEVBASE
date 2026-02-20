<?php
require_once 'includes/functions.php';

// Handle Tag actions
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'save_tag') {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        $color = !empty($_POST['color']) ? $_POST['color'] : null;
        saveTag($_POST['name'], $color, $id);
    } elseif ($_POST['action'] == 'delete_tag') {
        deleteTag($_POST['id']);
    } elseif ($_POST['action'] == 'save_language') {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        saveLanguage($_POST['name'], $_POST['prism_class'], $id);
    } elseif ($_POST['action'] == 'delete_language') {
        deleteLanguage($_POST['id']);
    }
    header('Location: settings.php');
    exit;
}

$tags = getAllTags();
$languages = getAllLanguages();

include 'includes/header.php';
?>

<div class="container">


<div class="row">
    <div class="col-12 mb-4">
        <h2 class="text-white fw-bold">Settings</h2>
        <p class="text-white-50">Manage your snippet categories and languages.</p>
    </div>

    <!-- Tag Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <h4 class="text-white mb-4">Tag Management</h4>
            
            <form method="POST" class="mb-4" id="tagForm">
                <input type="hidden" name="action" value="save_tag">
                <input type="hidden" name="id" id="tagId" value="">
                <div class="input-group">
                    <input type="color" id="tagColorPicker" class="form-control form-control-color bg-transparent border-light border-opacity-25" style="max-width: 50px;" title="Choose color or leave empty">
                    <input type="text" name="color" id="tagColor" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="#hex (empty is none)" style="max-width: 180px;">
                    <input type="text" name="name" id="tagName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Tag Name" required>
                    <button class="btn btn-outline-light" type="submit" id="tagSubmitBtn">Add Tag</button>
                </div>
            </form>

            <div class="list-group list-group-flush bg-transparent">
                <?php foreach ($tags as $tag): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0">
                        <span>
                            <?php if (!empty($tag['color'])): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: #fff;">
                            <?php else: ?>
                                <span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tag['name']); ?>
                            <?php if (!empty($tag['color'])): ?></span><?php else: ?></span><?php endif; ?>
                        </span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editTag(<?php echo json_encode($tag); ?>)'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this tag?');">
                                <input type="hidden" name="action" value="delete_tag">
                                <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Language Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <h4 class="text-white mb-4">Language Management</h4>
            
            <form method="POST" class="mb-4" id="langForm">
                <input type="hidden" name="action" value="save_language">
                <input type="hidden" name="id" id="langId" value="">
                <div class="mb-3">
                    <input type="text" name="name" id="langName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none mb-2" placeholder="Language Name" required>
                    <input type="text" name="prism_class" id="langClass" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Prism Class" required>
                </div>
                <button class="btn btn-outline-light w-100" type="submit" id="langSubmitBtn">Add Language</button>
            </form>

            <div class="list-group list-group-flush bg-transparent">
                <?php foreach ($languages as $lang): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0">
                        <div>
                            <span class="fw-bold"><?php echo htmlspecialchars($lang['name']); ?></span>
                            <small class="text-white-50 ms-2">(<?php echo htmlspecialchars($lang['prism_class']); ?>)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editLanguage(<?php echo json_encode($lang); ?>)'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete_language">
                                <input type="hidden" name="id" value="<?php echo $lang['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
