<?php
require_once 'includes/functions.php';

// Handle Tag actions
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'save_tag') {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        $color = !empty($_POST['color']) ? $_POST['color'] : null;
        $type = !empty($_POST['type']) ? $_POST['type'] : 'snippet';
        saveTag($_POST['name'], $color, $type, $id);
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
    } elseif ($_POST['action'] == 'toggle_todos') {
        $enabled = isset($_POST['todos_enabled']) ? '1' : '0';
        updateSetting('todos_enabled', $enabled);
    }
    header('Location: settings.php');
    exit;
}

$notesEnabled = getSetting('notes_enabled', '1');
$todosEnabled = getSetting('todos_enabled', '1');
$snippetTags = getAllTags('snippet');
$noteTags = getAllTags('note');
$todoTags = getAllTags('todo');
$languages = getAllLanguages();

include 'includes/header.php';
?>

<div class="container">


<div class="row">
    <div class="col-12 mb-4">
        <h2 class="text-white fw-bold">Nastavení</h2>
        <p class="text-white-50">Spravujte nastavení aplikace, štítky a jazyky.</p>
    </div>

    <!-- General Settings -->
    <div class="col-12 mb-4">
        <div class="glass-card p-4">
            <h4 class="text-white mb-3">Obecné nastavení</h4>
            <form method="POST" id="settingsFormNotes" class="mb-3">
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

            <form method="POST" id="settingsFormTodos">
                <input type="hidden" name="action" value="toggle_todos">
                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                    <input class="form-check-input fs-4 ms-0" type="checkbox" name="todos_enabled" id="todosEnabledToggle" 
                           <?php echo $todosEnabled == '1' ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label text-white" for="todosEnabledToggle">
                        <span class="d-block fw-bold">Povolit sekci TODO</span>
                        <small class="text-white-50">Pokud je vypnuto, sekce TODO se nezobrazí v menu ani nebude přístupná.</small>
                    </label>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Snippet Tag Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white mb-0">Štítky kódů</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-edit-order rounded px-3" id="editSnippetTagsOrderBtn" onclick="toggleSnippetTagsSorting()">
                        <i class="bi bi-arrows-move me-1"></i> Upravit pořadí
                    </button>
                    <button class="btn btn-sm btn-success rounded px-3 d-none" id="saveSnippetTagsOrderBtn" onclick="toggleSnippetTagsSorting()">
                        <i class="bi bi-check-lg me-1"></i> Hotovo
                    </button>
                </div>
            </div>
            
            <form method="POST" class="mb-4" id="tagForm">
                <input type="hidden" name="action" value="save_tag">
                <input type="hidden" name="type" value="snippet">
                <input type="hidden" name="id" id="tagId" value="">
                <div class="input-group">
                    <input type="color" id="tagColorPicker" class="form-control form-control-color bg-transparent border-light border-opacity-25" style="max-width: 50px;" title="Vyberte barvu nebo nechte prázdné">
                    <input type="text" name="color" id="tagColor" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="#hex" style="max-width: 150px;" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$">
                    <input type="text" name="name" id="tagName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Název" required>
                    <button class="btn btn-add-snipet" type="submit" id="tagSubmitBtn">Přidat</button>
                </div>
            </form>

            <div class="list-group list-group-flush bg-transparent" style="max-height: 400px; overflow-y: auto;" id="snippetTagsList">
                <?php foreach ($snippetTags as $tag): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0 snippet-tag-row" data-id="<?php echo $tag['id']; ?>">
                        <span>
                            <?php if (!empty($tag['color'])): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: #fff;">
                            <?php else: ?>
                                <span class="badge bg-secondary">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
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

    <!-- Note Tag Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white mb-0">Štítky poznámek</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-edit-order rounded px-3" id="editNoteTagsOrderBtn" onclick="toggleNoteTagsSorting()">
                        <i class="bi bi-arrows-move me-1"></i> Upravit pořadí
                    </button>
                    <button class="btn btn-sm btn-success rounded px-3 d-none" id="saveNoteTagsOrderBtn" onclick="toggleNoteTagsSorting()">
                        <i class="bi bi-check-lg me-1"></i> Hotovo
                    </button>
                </div>
            </div>
            
            <form method="POST" class="mb-4" id="noteTagForm">
                <input type="hidden" name="action" value="save_tag">
                <input type="hidden" name="type" value="note">
                <input type="hidden" name="id" id="noteTagId" value="">
                <div class="input-group">
                    <input type="color" id="noteTagColorPicker" class="form-control form-control-color bg-transparent border-light border-opacity-25" style="max-width: 50px;" title="Vyberte barvu nebo nechte prázdné">
                    <input type="text" name="color" id="noteTagColor" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="#hex" style="max-width: 150px;" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$">
                    <input type="text" name="name" id="noteTagName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Název" required>
                    <button class="btn btn-add-snipet" type="submit" id="noteTagSubmitBtn">Přidat</button>
                </div>
            </form>

            <div class="list-group list-group-flush bg-transparent" style="max-height: 400px; overflow-y: auto;" id="noteTagsList">
                <?php foreach ($noteTags as $tag): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0 note-tag-row" data-id="<?php echo $tag['id']; ?>">
                        <span>
                            <?php if (!empty($tag['color'])): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: #fff;">
                            <?php else: ?>
                                <span class="badge bg-secondary">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        </span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editNoteTag(<?php echo json_encode($tag); ?>)'>
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

    <!-- Todo Tag Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white mb-0">Štítky úkolů</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-edit-order rounded px-3" id="editTodoTagsOrderBtn" onclick="toggleTodoTagsSorting()">
                        <i class="bi bi-arrows-move me-1"></i> Upravit pořadí
                    </button>
                    <button class="btn btn-sm btn-success rounded px-3 d-none" id="saveTodoTagsOrderBtn" onclick="toggleTodoTagsSorting()">
                        <i class="bi bi-check-lg me-1"></i> Hotovo
                    </button>
                </div>
            </div>
            
            <form method="POST" class="mb-4" id="todoTagForm">
                <input type="hidden" name="action" value="save_tag">
                <input type="hidden" name="type" value="todo">
                <input type="hidden" name="id" id="todoTagId" value="">
                <div class="input-group">
                    <input type="color" id="todoTagColorPicker" class="form-control form-control-color bg-transparent border-light border-opacity-25" style="max-width: 50px;" title="Vyberte barvu nebo nechte prázdné">
                    <input type="text" name="color" id="todoTagColor" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="#hex" style="max-width: 150px;" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$">
                    <input type="text" name="name" id="todoTagName" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" placeholder="Název" required>
                    <button class="btn btn-add-snipet" type="submit" id="todoTagSubmitBtn">Přidat</button>
                </div>
            </form>

            <div class="list-group list-group-flush bg-transparent" style="max-height: 400px; overflow-y: auto;" id="todoTagsList">
                <?php foreach ($todoTags as $tag): ?>
                    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0 todo-tag-row" data-id="<?php echo $tag['id']; ?>">
                        <span>
                            <?php if (!empty($tag['color'])): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: #fff;">
                            <?php else: ?>
                                <span class="badge bg-secondary">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        </span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editTodoTag(<?php echo json_encode($tag); ?>)'>
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
   

</div>
</div>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
let snippetSortable = null;
let isSnippetSortingMode = false;

function toggleSnippetTagsSorting() {
    isSnippetSortingMode = !isSnippetSortingMode;
    const list = document.getElementById('snippetTagsList');
    const editBtn = document.getElementById('editSnippetTagsOrderBtn');
    const saveBtn = document.getElementById('saveSnippetTagsOrderBtn');
    const form = document.getElementById('tagForm');
    const actionButtons = list.querySelectorAll('.d-flex.gap-2'); // edit / delete buttons wrapper

    if (isSnippetSortingMode) {
        list.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        form.classList.add('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.add('opacity-0', 'pe-none'));

        snippetSortable = new Sortable(list, {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveTagsOrder('snippetTagsList', '.snippet-tag-row');
            }
        });
    } else {
        list.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        form.classList.remove('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.remove('opacity-0', 'pe-none'));

        if (snippetSortable) {
            snippetSortable.destroy();
            snippetSortable = null;
        }
    }
}

let noteSortable = null;
let isNoteSortingMode = false;

function toggleNoteTagsSorting() {
    isNoteSortingMode = !isNoteSortingMode;
    const list = document.getElementById('noteTagsList');
    const editBtn = document.getElementById('editNoteTagsOrderBtn');
    const saveBtn = document.getElementById('saveNoteTagsOrderBtn');
    const form = document.getElementById('noteTagForm');
    const actionButtons = list.querySelectorAll('.d-flex.gap-2'); // edit / delete buttons wrapper

    if (isNoteSortingMode) {
        list.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        form.classList.add('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.add('opacity-0', 'pe-none'));

        noteSortable = new Sortable(list, {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveTagsOrder('noteTagsList', '.note-tag-row');
            }
        });
    } else {
        list.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        form.classList.remove('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.remove('opacity-0', 'pe-none'));

        if (noteSortable) {
            noteSortable.destroy();
            noteSortable = null;
        }
    }
}

let todoSortable = null;
let isTodoSortingMode = false;

function toggleTodoTagsSorting() {
    isTodoSortingMode = !isTodoSortingMode;
    const list = document.getElementById('todoTagsList');
    const editBtn = document.getElementById('editTodoTagsOrderBtn');
    const saveBtn = document.getElementById('saveTodoTagsOrderBtn');
    const form = document.getElementById('todoTagForm');
    const actionButtons = list.querySelectorAll('.d-flex.gap-2'); // edit / delete buttons wrapper

    if (isTodoSortingMode) {
        list.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        form.classList.add('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.add('opacity-0', 'pe-none'));

        todoSortable = new Sortable(list, {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveTagsOrder('todoTagsList', '.todo-tag-row');
            }
        });
    } else {
        list.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        form.classList.remove('opacity-50', 'pe-none');
        
        actionButtons.forEach(btn => btn.classList.remove('opacity-0', 'pe-none'));

        if (todoSortable) {
            todoSortable.destroy();
            todoSortable = null;
        }
    }
}

function saveTagsOrder(listId, rowSelector) {
    const list = document.getElementById(listId);
    const items = list.querySelectorAll(rowSelector);
    const order = [];
    items.forEach((item, index) => {
        order.push({
            id: item.dataset.id,
            order: index
        });
    });
    
    fetch('api_tags_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ order: order }),
    })
    .then(response => response.json())
    .then(data => {
        console.log(listId + ' order saved:', data);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
