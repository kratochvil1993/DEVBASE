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
    } elseif ($_POST['action'] == 'toggle_notes') {
        $enabled = isset($_POST['notes_enabled']) ? '1' : '0';
        updateSetting('notes_enabled', $enabled);
    }
    header('Location: settings.php');
    exit;
}

$notesEnabled = getSetting('notes_enabled', '1');
$tags = getAllTags();
$languages = getAllLanguages();

include 'includes/header.php';
?>

<div class="container">


<div class="row">
    <div class="col-12 mb-4">
        <h2 class="text-white fw-bold">Nastavení</h2>
        <p class="text-white-50">Spravujte nastavení aplikace, štítky a jazyky.</p>
    </div>

    
    <!-- Tag Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <h4 class="text-white mb-4">Správa štítků</h4>
            
            <form method="POST" class="mb-4" id="tagForm">
                <input type="hidden" name="action" value="save_tag">
                <input type="hidden" name="id" id="tagId" value="">
                <div class="input-group">
                    <input type="color" id="tagColorPicker" class="form-control form-control-color bg-transparent border-light border-opacity-25" style="max-width: 50px;" title="Vyberte barvu nebo nechte prázdné">
                    <input type="text" name="color" id="tagColor" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="#hex (prázdné je bez barvy)" style="max-width: 180px;" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$" title="Barva musí začínat # a mít 3 nebo 6 hexadecimálních znaků (např. #fff nebo #ffcc00)">
                    <input type="text" name="name" id="tagName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Název štítku" required>
                    <button class="btn btn-add-snipet" type="submit" id="tagSubmitBtn">Přidat štítek</button>
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
                                <i class="bi bi-pencil"></i> 
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tento štítek smazat?');">
                                <input type="hidden" name="action" value="delete_tag">
                                <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                    <i class="bi bi-trash"></i> 
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
            <h4 class="text-white mb-4">Správa jazyků</h4>
            
            <form method="POST" class="mb-4" id="langForm">
                <input type="hidden" name="action" value="save_language">
                <input type="hidden" name="id" id="langId" value="">
                <div class="mb-3">
                    <input type="text" name="name" id="langName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none mb-2" placeholder="Název jazyka" required>
                    <input type="text" name="prism_class" id="langClass" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Třída Prism" required>
                </div>
                <button class="btn btn-add-snipet w-100" type="submit" id="langSubmitBtn">Přidat jazyk</button>
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
                                <i class="bi bi-pencil"></i> 
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Jste si jisti?');">
                                <input type="hidden" name="action" value="delete_language">
                                <input type="hidden" name="id" value="<?php echo $lang['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                    <i class="bi bi-trash"></i> 
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- General Settings -->
    <div class="col-12 mb-4">
        <div class="glass-card p-4">
            <h4 class="text-white mb-3">Obecné nastavení</h4>
            <form method="POST" id="settingsForm">
                <input type="hidden" name="action" value="toggle_notes">
                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                    <input class="form-check-input fs-4 ms-0" type="checkbox" name="notes_enabled" id="notesEnabledToggle" 
                           <?php echo $notesEnabled == '1' ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label text-white" for="notesEnabledToggle">
                        <span class="d-block fw-bold">Povolit sekci Notes</span>
                        <small class="text-white-50">Pokud je vypnuto, sekce Notes se nezobrazí v menu ani nebude přístupná.</small>
                    </label>
                </div>
            </form>
        </div>
    </div>

</div>
</div>

<?php include 'includes/footer.php'; ?>
