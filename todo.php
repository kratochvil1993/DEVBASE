<?php
require_once 'includes/functions.php';

// Check if todos are enabled
if (getSetting('todos_enabled', '1') == '0') {
    header('Location: index.php');
    exit;
}

// Handle Todo addition, order save, edit or archive
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_todo') {
        saveTodo($_POST['text']);
    } elseif ($_POST['action'] == 'edit_todo') {
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        saveTodo($_POST['text'], $tags, $_POST['todo_id']);
    } elseif ($_POST['action'] == 'archive_todo') {
        archiveTodo($_POST['todo_id'], 1);
    } elseif ($_POST['action'] == 'delete_todo') {
        deleteTodo($_POST['todo_id']);
    }
    header('Location: todo.php');
    exit;
}

$todos = getAllTodos(0); // 0 = active
$allTags = getAllTags('todo');

// Identify used tags for filtering
$usedTags = [];
foreach ($todos as $todo) {
    if (!empty($todo['tags'])) {
        foreach ($todo['tags'] as $tag) {
            $usedTags[$tag['name']] = $tag;
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
    <div class="col-lg-8 mx-auto">
        <div class="glass-card p-2 d-flex flex-wrap gap-3 align-items-center justify-content-between mb-0">
            <form method="POST" id="addTodoForm" class="flex-grow-1" style="max-width: 400px; margin: 0;">
                <input type="hidden" name="action" value="add_todo">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white">
                        <i class="bi bi-check2-square"></i>
                    </span>
                    <input type="text" name="text" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Co je potřeba udělat?" required autocomplete="off">
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" form="addTodoForm" class="btn btn-add-snipet rounded px-4">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <button class="btn btn-edit-order rounded px-4" id="editOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-arrows-move me-2"></i> Upravit pořadí
                </button>
                <button class="btn btn-success rounded px-4 d-none" id="saveOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-check-lg me-2"></i> Hotovo
                </button>
            </div>
        </div>
        
        <?php if (!empty($usedTags)): ?>
        <div class="row mt-3 mb-5">
            <div class="col-12 d-flex flex-wrap gap-2 justify-content-center" id="tagFilters">
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
        <?php else: ?>
            <br>
        <?php endif; ?>

        <div class="d-flex flex-column gap-3" id="todosList">
            <?php if (empty($todos)): ?>
                <div class="text-center text-white-50 py-5 glass-card mt-3">
                    <i class="bi bi-check2-circle display-1 mb-3 d-block"></i>
                    <h3>Žádné aktivní úkoly!</h3>
                    <p>Máte hotovo. Přidejte si další úkol výše.</p>
                </div>
            <?php else: ?>
                <?php foreach ($todos as $todo): ?>
                    <div class="card glass-card todo-item" 
                         data-id="<?php echo $todo['id']; ?>"
                         data-tags="<?php echo htmlspecialchars(implode(',', array_column($todo['tags'], 'name'))); ?>">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center overflow-hidden flex-grow-1">
                                    <form method="POST" class="me-3 mb-0 d-flex align-items-center" id="form_archive_<?php echo $todo['id']; ?>">
                                        <input type="hidden" name="action" value="archive_todo">
                                        <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                                        <input class="form-check-input m-0 fs-5 flex-shrink-0" type="checkbox" onclick="document.getElementById('form_archive_<?php echo $todo['id']; ?>').submit()" style="cursor: pointer;">
                                    </form>
                                    <div class="d-flex flex-column overflow-hidden flex-grow-1">
                                        <?php if (!empty($todo['tags'])): ?>
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                <?php foreach ($todo['tags'] as $tag): ?>
                                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color'] ?? '#6c757d'); ?>; color: #fff; font-size: 0.7em;">
                                                        <?php echo htmlspecialchars($tag['name']); ?>
                                                    </span>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>
                                            <span class="fs-5 text-truncate text-white"><?php echo htmlspecialchars($todo['text']); ?></span>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 action-btns flex-shrink-0 ms-3">
                                    <button type="button" class="btn btn-sm btn-link text-white-50 p-0" onclick="openEditTodoModal(<?php echo htmlspecialchars(json_encode($todo), ENT_QUOTES, 'UTF-8'); ?>)">
                                        <i class="bi bi-pencil fs-5"></i>
                                    </button>
                                    <form method="POST" class="mb-0" onsubmit="return confirm('Opravdu chcete tento úkol smazat?');">
                                        <input type="hidden" name="action" value="delete_todo">
                                        <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Smazat navždy">
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-end mt-3">
            <a href="archive_todos.php" class="text-white-50 text-decoration-none small"><i class="bi bi-archive me-1"></i> Zobrazit vyřízené úkoly</a>
        </div>
    </div>
</div>

<!-- Edit Todo Modal -->
<div class="modal fade" id="editTodoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white">Upravit úkol</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editTodoForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_todo">
                    <input type="hidden" name="todo_id" id="editTodoId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Text úkolu</label>
                        <input type="text" name="text" id="editTodoText" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Štítky</label>
                        <div class="d-flex flex-wrap gap-2 pt-1">
                            <?php foreach ($allTags as $tag): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input todo-tag-checkbox" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="todo-tag-<?php echo $tag['id']; ?>">
                                    <label class="form-check-label text-white small" for="todo-tag-<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-add-snipet px-4">Uložit změny</button>
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
    const list = document.getElementById('todosList');
    const editBtn = document.getElementById('editOrderBtn');
    const saveBtn = document.getElementById('saveOrderBtn');
    const actionBtns = document.querySelectorAll('.action-btns');
    const checkboxes = document.querySelectorAll('.form-check-input');

    if (isSortingMode) {
        list.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        actionBtns.forEach(btn => btn.classList.add('d-none'));
        checkboxes.forEach(cb => cb.disabled = true);

        sortable = new Sortable(list, {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveTodosOrder();
            }
        });
    } else {
        list.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        actionBtns.forEach(btn => btn.classList.remove('d-none'));
        checkboxes.forEach(cb => cb.disabled = false);

        if (sortable) {
            sortable.destroy();
            sortable = null;
        }
    }
}

function saveTodosOrder() {
    const list = document.getElementById('todosList');
    const items = list.querySelectorAll('.todo-item');
    const order = [];
    items.forEach((item, index) => {
        order.push({
            id: item.dataset.id,
            order: index
        });
    });
    
    fetch('api_todos_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ order: order }),
    })
    .then(response => response.json())
    .then(data => {
        console.log('Todos order saved:', data);
    });
}

function openEditTodoModal(todo) {
    document.getElementById('editTodoId').value = todo.id;
    document.getElementById('editTodoText').value = todo.text;
    
    // Check checkboxes
    const checkboxes = document.querySelectorAll('.todo-tag-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    
    if (todo.tags) {
        todo.tags.forEach(tag => {
            const cb = document.getElementById('todo-tag-' + tag.id);
            if (cb) cb.checked = true;
        });
    }
    
    const modal = new bootstrap.Modal(document.getElementById('editTodoModal'));
    modal.show();
}

// Tag filtering logic
document.addEventListener('DOMContentLoaded', () => {
    const tagButtons = document.querySelectorAll('#tagFilters .btn');
    let currentTag = 'all';

    const filterTodos = () => {
        const items = document.querySelectorAll('.todo-item');
        let delay = 0;
        
        items.forEach(item => {
            const tagsAttr = item.getAttribute('data-tags');
            const tags = tagsAttr ? tagsAttr.toLowerCase().split(',') : [];
            const matchesTag = currentTag === 'all' || tags.includes(currentTag.toLowerCase());

            if (matchesTag) {
                item.style.display = 'block';
                item.style.animation = 'none';
                item.offsetHeight; /* trigger reflow */
                item.style.animation = `popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${delay}ms both`;
                delay += 40;
            } else {
                item.style.display = 'none';
                item.style.animation = 'none';
            }
        });
    };

    tagButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tagButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTag = btn.getAttribute('data-tag');
            filterTodos();
        });
    });
    
    // Add searching capability inline with add form?
    // Since the add form behaves like a search but adds, and user requested tags behavior similar
    // to search, we just added tags filter. We can also add client-side search if needed later.
});
</script>

<?php include 'includes/footer.php'; ?>
