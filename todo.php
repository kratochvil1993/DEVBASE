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
        $is_locked = isset($_POST['is_locked']) ? 1 : 0;
        $saved_id = saveTodo($_POST['text'], $tags, null, $is_locked);
        if ($saved_id) {
            header('Location: todo.php?updated_id=' . $saved_id);
            exit;
        }
    } elseif ($_POST['action'] == 'edit_todo') {
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $is_locked = isset($_POST['is_locked']) ? 1 : 0;
        saveTodo($_POST['text'], $tags, $_POST['todo_id'], $is_locked);
        header('Location: todo.php?updated_id=' . $_POST['todo_id']);
        exit;
    } elseif ($_POST['action'] == 'archive_todo') {
        archiveTodo($_POST['todo_id'], 1);
    } elseif ($_POST['action'] == 'delete_todo') {
        deleteTodo($_POST['todo_id']);
    } elseif ($_POST['action'] == 'toggle_pin') {
        toggleTodoPin($_POST['todo_id']);
        header('Location: todo.php?updated_id=' . $_POST['todo_id']);
        exit;
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
        <div class="glass-card no-jump p-2 d-flex flex-wrap gap-3 align-items-center justify-content-between mb-0">
            <form method="POST" id="addTodoForm" class="flex-grow-1" style="max-width: 600px; margin: 0;">
                <input type="hidden" name="action" value="add_todo">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white">
                        <i class="bi bi-check2-square"></i>
                    </span>
                    <input type="text" name="text" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Přidat" required autocomplete="off">
                    <select name="tags[]" class="form-select bg-transparent border-0 border-start border-light border-opacity-25 text-white shadow-none" style="max-width: 140px; cursor: pointer;">
                        <option value="" style="background: #2b3035;" <?php echo empty($allTags) ? 'selected' : ''; ?>>Bez štítku</option>
                        <?php foreach ($allTags as $index => $tag): ?>
                            <option value="<?php echo $tag['id']; ?>" style="background: #2b3035;" <?php echo ($index === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="deadline" class="form-control bg-transparent border-0 border-start border-light border-opacity-25 text-white shadow-none" title="Termín splnění" style="max-width: 160px; cursor: pointer;">
                </div>

            </form>

            <div class="d-flex flex-wrap gap-2 ms-auto">
                <button type="submit" form="addTodoForm" class="btn btn-add-snipet rounded px-4" id="addTodoBtn">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <button class="btn btn-edit-order rounded px-4" id="editOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-arrows-move me-2"></i> Upravit pořadí
                </button>
                <?php if (getSetting('ai_enabled', '0') == '1'): ?>
                <button class="btn btn-ai rounded px-4" id="aiSummaryBtn">
                    <i class="bi bi-robot me-2"></i> AI Souhrn
                </button>
                <?php endif; ?>
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

        <div class="d-flex flex-column gap-3" id="todoMainContainer">
            <?php if (empty($todos)): ?>
                <div id="emptyState" class="text-center text-white-50 py-5 glass-card mt-3">
                    <i class="bi bi-check2-circle display-1 mb-3 d-block"></i>
                    <h3>Žádné aktivní úkoly!</h3>
                    <p>Máte hotovo. Přidejte si další úkol výše.</p>
                </div>
            <?php endif; ?>

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
            <div id="othersTodosContainer" class="<?php echo (empty($otherTodos) && !empty($pinnedTodos)) ? 'd-none' : ''; ?>">
                <h6 class="text-white-50 mb-3 px-1 <?php echo empty($pinnedTodos) ? 'd-none' : ''; ?>" id="othersHeader">OSTATNÍ</h6>
                <div class="d-flex flex-column gap-3" id="othersTodosList">
                    <?php foreach ($otherTodos as $todo): ?>
                        <?php include 'includes/todo_item_template.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="text-end mt-3">
            <a href="archive_todos.php" class="text-white-50 text-decoration-none small"><i class="bi bi-archive me-1"></i> Zobrazit vyřízené úkoly</a>
        </div>
    </div>
</div>

<!-- Edit Todo Modal -->
<div class="modal fade" id="editTodoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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
                        <div class="form-check form-switch card-text">
                            <input class="form-check-input" type="checkbox" name="is_locked" id="editTodoLocked" value="1">
                            <label class="form-check-label text-white-50 small" for="editTodoLocked">
                                <i class="bi bi-lock-fill me-1"></i> Skrýt obsah
                            </label>
                        </div>
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

<!-- AI Summary Modal -->
<div class="modal fade" id="aiSummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white"><i class="bi bi-robot me-2"></i> AI Bojový plán</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-white" id="aiSummaryContent" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-5">
                    <div class="spinner-border text-ai mb-3" role="status"></div>
                    <p class="text-white-50">AI analyzuje tvé úkoly a připravuje strategii...</p>
                </div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
            </div>
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
    const addForm = document.getElementById('addTodoForm');
    const addBtn = document.getElementById('addTodoBtn');

    if (isSortingMode) {
        const pinnedList = document.getElementById('pinnedTodosList');
        const othersList = document.getElementById('othersTodosList');
        if (pinnedList) pinnedList.classList.add('sorting-mode');
        if (othersList) othersList.classList.add('sorting-mode');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        addForm.classList.add('d-none');
        addBtn.classList.add('d-none');
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
        const pinnedList = document.getElementById('pinnedTodosList');
        const othersList = document.getElementById('othersTodosList');
        if (pinnedList) pinnedList.classList.remove('sorting-mode');
        if (othersList) othersList.classList.remove('sorting-mode');
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        addForm.classList.remove('d-none');
        addBtn.classList.remove('d-none');
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
    document.getElementById('editTodoLocked').checked = (todo.is_locked == 1 || todo.is_locked === true || todo.is_locked === "1");

    
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
// AJAX adding of todos
document.addEventListener('DOMContentLoaded', () => {
    const addTodoForm = document.getElementById('addTodoForm');
    const addTodoBtn = document.getElementById('addTodoBtn');
    const othersTodosList = document.getElementById('othersTodosList');
    const emptyState = document.getElementById('emptyState');
    const othersTodosContainer = document.getElementById('othersTodosContainer');
    const pinnedTodosContainer = document.getElementById('pinnedTodosContainer');

    if (addTodoForm) {
        addTodoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const originalBtnHtml = addTodoBtn.innerHTML;
            
            addTodoBtn.disabled = true;
            addTodoBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            fetch('api/api_todo_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Hide empty state if visible
                    if (emptyState) emptyState.remove();
                    
                    // Show others container if it was hidden
                    if (othersTodosContainer) othersTodosContainer.classList.remove('d-none');
                    
                    // Create a temporary element to hold the new HTML
                    const temp = document.createElement('div');
                    temp.innerHTML = data.html;
                    const newTodoItem = temp.firstElementChild;
                    
                    // Add animation
                    newTodoItem.style.opacity = '0';
                    newTodoItem.style.transform = 'translateY(20px)';
                    
                    if (othersTodosList) {
                        othersTodosList.prepend(newTodoItem);
                        
                        requestAnimationFrame(() => {
                            newTodoItem.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                            newTodoItem.style.opacity = '1';
                            newTodoItem.style.transform = 'translateY(0)';
                            
                            // Cleanup inline styles after animation to allow CSS animations (like jiggle) to work
                            setTimeout(() => {
                                newTodoItem.style.transform = '';
                                newTodoItem.style.transition = '';
                            }, 450);
                        });
                    }
                    
                    addTodoForm.reset();
                    
                    const pinnedVisible = pinnedTodosContainer && !pinnedTodosContainer.classList.contains('d-none');
                    const othersHeader = document.getElementById('othersHeader');
                    if (othersHeader && pinnedVisible) {
                        othersHeader.classList.remove('d-none');
                    }

                    newTodoItem.classList.add('flash-purple');
                    setTimeout(() => newTodoItem.classList.remove('flash-purple'), 2000);
                    
                    // Update header/sidebar stats
                    if (typeof updateGlobalStats === 'function') updateGlobalStats(data);
                } else {
                    alert('Chyba: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Nastala chyba při ukládání úkolu.');
            })
            .finally(() => {
                addTodoBtn.disabled = false;
                addTodoBtn.innerHTML = originalBtnHtml;
            });
        });
    }

    const editTodoForm = document.getElementById('editTodoForm');
    if (editTodoForm) {
        editTodoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn.innerHTML;
            const todoId = document.getElementById('editTodoId').value;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            fetch('api/api_todo_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const modalEl = document.getElementById('editTodoModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    const existingCard = document.getElementById('todo-card-' + todoId);
                    if (existingCard) {
                        const temp = document.createElement('div');
                        temp.innerHTML = data.html;
                        const newCard = temp.firstElementChild;
                        
                        existingCard.replaceWith(newCard);
                        newCard.classList.add('flash-purple');
                        setTimeout(() => newCard.classList.remove('flash-purple'), 2000);
                    } else {
                        window.location.reload();
                    }
                    
                    // Update header/sidebar stats
                    if (typeof updateGlobalStats === 'function') updateGlobalStats(data);
                } else {
                    alert('Chyba: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Nastala chyba při ukládání úkolu.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            });
        });
    }

    const tagButtons = document.querySelectorAll('#tagFilters .btn');
    let currentTag = (localStorage.getItem('todoTag') || 'all').trim();

    // Restore initial UI state
    if (tagButtons.length > 0) {
        tagButtons.forEach(btn => {
            if (btn.getAttribute('data-tag') === currentTag) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    const filterTodos = () => {
        const items = document.querySelectorAll('.todo-item');
        let delay = 0;
        let pinnedVisible = 0;
        let othersVisible = 0;
        
        items.forEach(item => {
            const tagsAttr = item.getAttribute('data-tags');
            const tags = tagsAttr ? tagsAttr.toLowerCase().split(',') : [];
            const matchesTag = currentTag === 'all' || tags.some(t => t.trim().toLowerCase() === currentTag.toLowerCase());

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

    // Initial filter application
    if (tagButtons.length > 0) {
        filterTodos();
    }

    tagButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tagButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTag = btn.getAttribute('data-tag');
            localStorage.setItem('todoTag', currentTag);
            filterTodos();
        });
    });
});

function toggleTodoPin(todoId, event) {
    if (event) event.stopPropagation();
    
    const formData = new FormData();
    formData.append('action', 'toggle_pin');
    formData.append('todo_id', todoId);

    fetch('api/api_todo_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const existingCard = document.getElementById('todo-card-' + todoId);
            if (existingCard) {
                const temp = document.createElement('div');
                temp.innerHTML = data.html;
                const newCard = temp.firstElementChild;
                
                const targetGridId = data.is_pinned ? 'pinnedTodosList' : 'othersTodosList';
                const targetGrid = document.getElementById(targetGridId);
                
                existingCard.remove();
                if (targetGrid) {
                    targetGrid.prepend(newCard);
                    newCard.classList.add('flash-purple');
                    setTimeout(() => newCard.classList.remove('flash-purple'), 2000);
                }
                
                updateTodoUIState();
            } else {
                window.location.reload();
            }
            
            // Update header/sidebar stats
            if (typeof updateGlobalStats === 'function') updateGlobalStats(data);
        } else {
            alert(data.message);
        }
    });
}

function archiveTodoItem(todoId, event) {
    if (event) event.stopPropagation();
    
    const formData = new FormData();
    formData.append('action', 'archive_todo');
    formData.append('todo_id', todoId);

    fetch('api/api_todo_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById('todo-card-' + todoId);
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    card.remove();
                    updateTodoUIState();
                }, 300);
            }
            
            // Update header/sidebar stats
            if (typeof updateGlobalStats === 'function') updateGlobalStats(data);
        } else {
            alert(data.message);
        }
    });
}

function deleteTodoItem(todoId, event) {
    if (event) event.stopPropagation();
    
    if (!confirm('Opravdu chcete tento úkol nenávratně smazat?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_todo');
    formData.append('todo_id', todoId);

    fetch('api/api_todo_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById('todo-card-' + todoId);
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    card.remove();
                    updateTodoUIState();
                }, 300);
            }
            
            // Update header/sidebar stats
            if (typeof updateGlobalStats === 'function') updateGlobalStats(data);
        } else {
            alert(data.message);
        }
    });
}

function updateTodoUIState() {
    const pinnedGrid = document.getElementById('pinnedTodosList');
    const othersGrid = document.getElementById('othersTodosList');
    const pinnedContainer = document.getElementById('pinnedTodosContainer');
    const othersContainer = document.getElementById('othersTodosContainer');
    const othersHeader = document.getElementById('othersHeader');
    const emptyState = document.getElementById('emptyState');
    const mainContainer = document.getElementById('todoMainContainer');

    if (!pinnedGrid || !othersGrid) return;

    const pinnedCount = pinnedGrid.querySelectorAll('.todo-item').length;
    const othersCount = othersGrid.querySelectorAll('.todo-item').length;

    // Show/Hide pinned container
    if (pinnedCount > 0) {
        pinnedContainer.classList.remove('d-none');
        if (othersHeader) othersHeader.classList.remove('d-none');
    } else {
        pinnedContainer.classList.add('d-none');
        if (othersHeader) othersHeader.classList.add('d-none');
    }

    // Show/Hide others container
    if (othersCount === 0 && pinnedCount > 0) {
        othersContainer.classList.add('d-none');
    } else {
        othersContainer.classList.remove('d-none');
    }

    // Handle empty state
    if (pinnedCount === 0 && othersCount === 0) {
        if (!emptyState) {
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'emptyState';
            emptyDiv.className = 'text-center text-white-50 py-5 glass-card mt-3';
            emptyDiv.innerHTML = `
                <i class="bi bi-check2-circle display-1 mb-3 d-block"></i>
                <h3>Žádné aktivní úkoly!</h3>
                <p>Máte hotovo. Přidejte si další úkol výše.</p>
            `;
            mainContainer.prepend(emptyDiv); // Prepend so it shows properly
        }
    } else if (emptyState) {
        emptyState.remove();
    }
}

// AI Summary logic
document.addEventListener('DOMContentLoaded', () => {
    const aiSummaryBtn = document.getElementById('aiSummaryBtn');
    if (aiSummaryBtn) {
        aiSummaryBtn.addEventListener('click', function() {
            const modalEl = document.getElementById('aiSummaryModal');
            const modal = new bootstrap.Modal(modalEl);
            const contentDiv = document.getElementById('aiSummaryContent');
            
            // Show loading state
            contentDiv.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-ai mb-3" role="status"></div>
                    <p class="text-white-50">AI analyzuje tvé úkoly a připravuje strategii...</p>
                </div>
            `;
            modal.show();
            
            fetch('api/api_ai_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'todo_summary', content: 'dummy' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Check if marked is available (it should be from footer)
                    if (typeof marked !== 'undefined') {
                        const html = (typeof marked.parse === 'function') ? marked.parse(data.answer) : marked(data.answer);
                        contentDiv.innerHTML = '<div class="markdown-preview">' + html + '</div>';
                    } else {
                        contentDiv.innerHTML = '<div style="white-space: pre-wrap;">' + data.answer + '</div>';
                    }
                } else {
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = `
                    <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Nastala technická chyba při spojení s AI.
                    </div>
                `;
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
