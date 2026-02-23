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
        $tags = isset($_POST['tags']) ? (array)$_POST['tags'] : [];
        saveTodo($_POST['text'], $tags);
    } elseif ($_POST['action'] == 'edit_todo') {
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        saveTodo($_POST['text'], $tags, $_POST['todo_id']);
    } elseif ($_POST['action'] == 'archive_todo') {
        archiveTodo($_POST['todo_id'], 1);
    } elseif ($_POST['action'] == 'delete_todo') {
        deleteTodo($_POST['todo_id']);
    } elseif ($_POST['action'] == 'toggle_pin') {
        toggleTodoPin($_POST['todo_id']);
    }
    header('Location: todo.php');
    exit;
}

$todos = getAllTodos(0); // 0 = active
$pinnedTodos = array_filter($todos, function($t) { return $t['is_pinned'] == 1; });
$otherTodos = array_filter($todos, function($t) { return $t['is_pinned'] == 0; });
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
            <form method="POST" id="addTodoForm" class="flex-grow-1" style="max-width: 600px; margin: 0;">
                <input type="hidden" name="action" value="add_todo">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white">
                        <i class="bi bi-check2-square"></i>
                    </span>
                    <input type="text" name="text" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Co je potřeba udělat?" required autocomplete="off">
                    <select name="tags[]" class="form-select bg-transparent border-0 border-start border-light border-opacity-25 text-white shadow-none" style="max-width: 140px; cursor: pointer;">
                        <option value="" style="background: #2b3035;" <?php echo empty($allTags) ? 'selected' : ''; ?>>Bez štítku</option>
                        <?php foreach ($allTags as $index => $tag): ?>
                            <option value="<?php echo $tag['id']; ?>" style="background: #2b3035;" <?php echo ($index === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="deadline" class="form-control bg-transparent border-0 border-start border-light border-opacity-25 text-white shadow-none" title="Termín splnění" style="max-width: 160px; cursor: pointer;">
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

        <div class="d-flex flex-column gap-3">
            <?php if (empty($todos)): ?>
                <div class="text-center text-white-50 py-5 glass-card mt-3">
                    <i class="bi bi-check2-circle display-1 mb-3 d-block"></i>
                    <h3>Žádné aktivní úkoly!</h3>
                    <p>Máte hotovo. Přidejte si další úkol výše.</p>
                </div>
            <?php else: ?>
                <!-- Pinned Todos -->
                <div id="pinnedTodosContainer" class="<?php echo empty($pinnedTodos) ? 'd-none' : ''; ?> mb-4">
                    <h6 class="text-white-50 mb-3 px-1"><i class="bi bi-pin-angle-fill me-2"></i> PŘIPNUTÉ</h6>
                    <div class="d-flex flex-column gap-3" id="pinnedTodosList">
                        <?php foreach ($pinnedTodos as $todo): ?>
                            <?php include 'includes/todo_item_template.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Other Todos -->
                <div id="othersTodosContainer">
                    <h6 class="text-white-50 mb-3 px-1 <?php echo empty($pinnedTodos) ? 'd-none' : ''; ?>" id="othersHeader">OSTATNÍ</h6>
                    <div class="d-flex flex-column gap-3" id="othersTodosList">
                        <?php foreach ($otherTodos as $todo): ?>
                            <?php include 'includes/todo_item_template.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
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
                        <label class="form-label text-white-50 small">Termín splnění (Deadline)</label>
                        <input type="date" name="deadline" id="editTodoDeadline" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none">
                    </div>


                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Štítky</label>
                        <div class="d-flex flex-wrap gap-2 pt-1">
                            <?php foreach ($allTags as $tag): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input todo-tag-checkbox" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="todo-tag-<?php echo $tag['id']; ?>">
                                    <label class="form-check-label text-white-50 small" for="todo-tag-<?php echo $tag['id']; ?>">
                                        <span class="badge" style="background-color: <?php echo $tag['color'] ? htmlspecialchars($tag['color']) : '#6c757d'; ?>">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </span>
                                    </label>
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
<script src="assets/vendor/sortablejs/Sortable.min.js"></script>
<script>
let sortablePinned = null;
let sortableOthers = null;
let isSortingMode = false;

function toggleSortingMode() {
    isSortingMode = !isSortingMode;
    const pinnedList = document.getElementById('pinnedTodosList');
    const othersList = document.getElementById('othersTodosList');
    const editBtn = document.getElementById('editOrderBtn');
    const saveBtn = document.getElementById('saveOrderBtn');
    const actionBtns = document.querySelectorAll('.action-btns');
    const checkboxes = document.querySelectorAll('.form-check-input');

    if (isSortingMode) {
        document.querySelectorAll('.d-flex.flex-column.gap-3').forEach(list => list.classList.add('sorting-mode'));
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        actionBtns.forEach(btn => btn.classList.add('d-none'));
        checkboxes.forEach(cb => cb.disabled = true);

        const sortableConfig = {
            animation: 150,
            ghostClass: 'glass-card-moving',
            onEnd: function() {
                saveTodosOrder();
            }
        };

        if (pinnedList) sortablePinned = new Sortable(pinnedList, sortableConfig);
        if (othersList) sortableOthers = new Sortable(othersList, sortableConfig);
        
    } else {
        document.querySelectorAll('.d-flex.flex-column.gap-3').forEach(list => list.classList.remove('sorting-mode'));
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        actionBtns.forEach(btn => btn.classList.remove('d-none'));
        checkboxes.forEach(cb => cb.disabled = false);

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

function saveTodosOrder() {
    const orderItems = [];
    let currentIndex = 0;

    // Process pinned first
    const pinnedItems = document.querySelectorAll('#pinnedTodosList .todo-item');
    pinnedItems.forEach((item) => {
        orderItems.push({
            id: item.dataset.id,
            order: currentIndex++
        });
    });

    // Then others
    const otherItems = document.querySelectorAll('#othersTodosList .todo-item');
    otherItems.forEach((item) => {
        orderItems.push({
            id: item.dataset.id,
            order: currentIndex++
        });
    });
    
    fetch('api/api_todos_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ order: orderItems }),
    })
    .then(response => response.json())
    .then(data => {
        console.log('Todos order saved:', data);
    });
}

function openEditTodoModal(todo) {
    document.getElementById('editTodoId').value = todo.id;
    document.getElementById('editTodoText').value = todo.text;
    document.getElementById('editTodoDeadline').value = todo.deadline || '';

    
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
        let pinnedVisible = 0;
        let othersVisible = 0;
        
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
                if (item.classList.contains('pinned')) pinnedVisible++;
                else othersVisible++;
            } else {
                item.style.display = 'none';
                item.style.animation = 'none';
            }
        });

        // Toggle headers
        const pinnedContainer = document.getElementById('pinnedTodosContainer');
        const othersHeader = document.getElementById('othersHeader');
        
        if (pinnedContainer) {
            pinnedContainer.classList.toggle('d-none', pinnedVisible === 0);
        }
        if (othersHeader) {
            othersHeader.classList.toggle('d-none', pinnedVisible === 0 || othersVisible === 0);
        }
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
