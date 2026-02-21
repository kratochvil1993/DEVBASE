<?php
require_once 'includes/functions.php';

// Check if todos are enabled
if (getSetting('todos_enabled', '1') == '0') {
    header('Location: index.php');
    exit;
}

// Handle Todo addition, order save, or archive
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_todo') {
        saveTodo($_POST['text']);
    } elseif ($_POST['action'] == 'archive_todo') {
        archiveTodo($_POST['todo_id'], 1);
    } elseif ($_POST['action'] == 'delete_todo') {
        deleteTodo($_POST['todo_id']);
    }
    header('Location: todo.php');
    exit;
}

$todos = getAllTodos(0); // 0 = active

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
        
        <br>
        <br>

        <div class="d-flex flex-column gap-3" id="todosList">
            <?php if (empty($todos)): ?>
                <div class="text-center text-white-50 py-5 glass-card mt-3">
                    <i class="bi bi-check2-circle display-1 mb-3 d-block"></i>
                    <h3>Žádné aktivní úkoly!</h3>
                    <p>Máte hotovo. Přidejte si další úkol výše.</p>
                </div>
            <?php else: ?>
                <?php foreach ($todos as $todo): ?>
                    <div class="card glass-card todo-item" data-id="<?php echo $todo['id']; ?>">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <div class="d-flex align-items-center overflow-hidden flex-grow-1">
                                <form method="POST" class="me-3 mb-0 d-flex align-items-center" id="form_archive_<?php echo $todo['id']; ?>">
                                    <input type="hidden" name="action" value="archive_todo">
                                    <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                                    <input class="form-check-input m-0 fs-5 flex-shrink-0" type="checkbox" onclick="document.getElementById('form_archive_<?php echo $todo['id']; ?>').submit()" style="cursor: pointer;">
                                </form>
                                <span class="fs-5 text-truncate text-white"><?php echo htmlspecialchars($todo['text']); ?></span>
                            </div>
                            <div class="d-flex gap-2 action-btns">
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
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-end mt-3">
            <a href="archive_todos.php" class="text-white-50 text-decoration-none small"><i class="bi bi-archive me-1"></i> Zobrazit vyřízené úkoly</a>
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
</script>

<?php include 'includes/footer.php'; ?>
