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
$pinnedTodos = [];
$otherTodos = [];
$allTags = getAllTags('todo');

// Identify used tags for filtering and separate pinned/other
$usedTags = [];
foreach ($todos as $todo) {
    if ($todo['is_pinned'] == 1) {
        $pinnedTodos[] = $todo;
    } else {
        $otherTodos[] = $todo;
    }

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

<div class="container">
    <div class="row mb-3 align-items-center mt-2">
        <div class="col-xxl-12 col-lg-12 mx-auto">
            <div class="glass-card no-jump p-2 d-flex flex-column flex-lg-row gap-3 align-items-center justify-content-between mb-0">
                <div id="addTodoCollapse" class="collapse d-lg-block flex-grow-1 w-100 w-lg-auto" style="max-width: 600px;">
                    <form method="POST" id="addTodoForm" class="m-0">
                        <input type="hidden" name="action" value="add_todo">
                        <div class="input-group todo-input-group-mobile">
                            <span class="input-group-text bg-transparent border-0 text-white">
                                <i class="bi bi-check2-square"></i>
                            </span>
                            <input type="text" name="text" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Přidat úkol..." required autocomplete="off">
                            <select name="tags[]" class="form-select bg-transparent border-0 border-start border-light border-opacity-25 text-white shadow-none" style="max-width: 140px; cursor: pointer;">
                                <option value="" style="background: #2b3035;" <?php echo empty($allTags) ? 'selected' : ''; ?>>Bez štítku</option>
                                <?php foreach ($allTags as $index => $tag): ?>
                                    <option value="<?php echo $tag['id']; ?>" style="background: #2b3035;" <?php echo ($index === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" name="deadline" class="form-control bg-transparent border-0 border-start border-light border-opacity-25 text-white shadow-none" title="Termín splnění" style="max-width: 130px; cursor: pointer;">
                            <input type="time" name="deadline_time" class="form-control bg-transparent border-0 border-start border-light border-opacity-25 text-white shadow-none" title="Čas splnění" style="max-width: 80px; cursor: pointer;">
                        </div>
                        
                        <!-- Discreet hide button for mobile -->
                        <div class="text-center d-lg-none mt-2">
                            <button type="button" class="btn btn-link text-white-50 text-decoration-none p-0 d-inline-flex align-items-center gap-1" style="opacity: 0.6;" onclick="bootstrap.Collapse.getInstance(document.getElementById('addTodoCollapse'))?.hide()">
                                <i class="bi bi-chevron-up"></i>
                                <span style="font-size: 11px;">Skrýt</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="d-flex flex-wrap gap-2 ms-auto align-self-end align-self-lg-center">
                    <button type="submit" form="addTodoForm" class="btn btn-add-snipet rounded px-3 px-sm-4" id="addTodoBtn">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                    <button class="btn btn-edit-order rounded px-3 px-sm-4" id="editOrderBtn" onclick="toggleSortingMode()">
                        <i class="bi bi-arrows-move me-0 me-xl-2"></i> <span class="d-none d-xl-inline">Upravit pořadí</span>
                    </button>
                    <?php if (getSetting('ai_enabled', '0') == '1'): ?>
                    <button class="btn btn-ai rounded px-3 px-sm-4" id="aiSummaryBtn">
                        <i class="bi bi-robot me-0 me-xl-2"></i> <span class="d-none d-xl-inline">AI Souhrn</span>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-success rounded px-3 px-sm-4 d-none" id="saveOrderBtn" onclick="toggleSortingMode()">
                        <i class="bi bi-check-lg me-2"></i> Hotovo
                    </button>
                </div>
            </div>
            
            <?php if (!empty($usedTags)): ?>
            <div class="row mt-3 mb-2 mb-lg-5">
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
                 <div class="row justify-content-center mt-3 mt-lg-0">
                    <div class="col-lg-10">                    
                        <div id="pinnedTodosContainer" class="<?php echo empty($pinnedTodos) ? 'd-none' : ''; ?> mb-4">
                            <h6 class="text-white-50 mb-3 px-1"><i class="bi bi-pin-angle-fill me-2"></i> PŘIPNUTÉ</h6>
                            <div class="d-flex flex-column gap-3" id="pinnedTodosList">
                                <?php foreach ($pinnedTodos as $todo): ?>
                                    <?php include 'includes/todo_item_template.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                 </div>

                <!-- Other Todos -->
                 <div class="row justify-content-center">
                    <div class="col-lg-10">                    
                        <div id="othersTodosContainer" class="<?php echo (empty($otherTodos) && !empty($pinnedTodos)) ? 'd-none' : ''; ?>">
                            <h6 class="text-white-50 mb-3 px-1 <?php echo empty($pinnedTodos) ? 'd-none' : ''; ?>" id="othersHeader">OSTATNÍ</h6>
                            <div class="d-flex flex-column gap-3" id="othersTodosList">
                                <?php foreach ($otherTodos as $todo): ?>
                                    <?php include 'includes/todo_item_template.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                 </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">   
                    <div class="text-end mt-3 me-2">
                        <a href="archive_todos.php" class="text-white-50 text-decoration-none small"><i class="bi bi-archive me-1"></i> Zobrazit vyřízené úkoly</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Edit Todo Modal -->
<div class="modal fade" id="editTodoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
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

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50 small">Termín splnění (Deadline)</label>
                            <input type="date" name="deadline" id="editTodoDeadline" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50 small">Čas splnění</label>
                            <input type="time" name="deadline_time" id="editTodoDeadlineTime" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Poznámka</label>
                        <textarea name="note" id="editTodoNote" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" rows="3" placeholder="Volitelná poznámka k úkolu..."></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch card-text">
                            <input class="form-check-input" type="checkbox" name="is_locked" id="editTodoLocked" value="1">
                            <label class="form-check-label text-white-50 small" for="editTodoLocked">
                                <i class="bi bi-lock-fill me-1"></i> Skrýt obsah
                            </label>
                        </div>
                    </div>


                    <div class="mb-3" id="editTodoTagsContainer">
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

<!-- View Todo Modal -->
<div class="modal fade" id="viewTodoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white">Detail úkolu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h5 id="viewTodoText" class="text-white mb-2" style="white-space: pre-wrap; overflow-wrap: break-word;"></h5>
                    <div id="viewTodoTags" class="d-flex flex-wrap gap-1 mb-3"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white-50 small mb-1"><i class="bi bi-calendar-event me-1"></i> Termín splnění</label>
                    <div class="d-flex align-items-center gap-2">
                        <div id="viewTodoDeadline" class="text-white fs-6"></div>
                        <div id="viewTodoDeadlineTime" class="badge bg-light bg-opacity-10 text-white fw-normal fs-6"></div>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label text-white-50 small mb-1"><i class="bi bi-file-earmark-text me-1"></i> Poznámka</label>
                    <div id="viewTodoNote" class="text-white rounded p-3" style="background: rgba(0,0,0,0.2); min-height: 80px; white-space: pre-wrap;"></div>
                </div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
                <button type="button" class="btn btn-add-snipet px-4" id="viewTodoEditBtn">Upravit</button>
            </div>
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
let sortableSubtasks = [];
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
    const tagFilters = document.getElementById('tagFilters');

    if (isSortingMode) {
        // BEZPEČNOSTNÍ RESET FILTRŮ bez the .click() the animací a the ztráty frames
        if (typeof window.filterTodos === 'function') {
            const filterBtns = document.querySelectorAll('#tagFilters .btn');
            if (filterBtns.length > 0) {
                filterBtns.forEach(b => b.classList.remove('active'));
                const allBtn = document.querySelector('#tagFilters .btn[data-tag="all"]');
                if (allBtn) {
                    allBtn.classList.add('active');
                    window.currentTodoTag = 'all';
                }
            }
            window.filterTodos(false); // Okamžitý the render the UI
        }
        
        if (tagFilters) tagFilters.classList.add('d-none');

        if (pinnedList) pinnedList.classList.add('sorting-mode');
        if (othersList) othersList.classList.add('sorting-mode');
        document.querySelectorAll('.subtasks-container').forEach(c => c.classList.add('sorting-mode'));

        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        if (addForm) addForm.classList.add('d-none');
        if (addBtn) addBtn.classList.add('d-none');
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
        
        // Inicializace pro všechny kontejnery s podúkoly
        document.querySelectorAll('.subtasks-container').forEach(container => {
            sortableSubtasks.push(new Sortable(container, sortableConfig));
        });
        
    } else {
        if (tagFilters) tagFilters.classList.remove('d-none');
        if (pinnedList) pinnedList.classList.remove('sorting-mode');
        if (othersList) othersList.classList.remove('sorting-mode');
        document.querySelectorAll('.subtasks-container').forEach(c => c.classList.remove('sorting-mode'));

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
        sortableSubtasks.forEach(s => s.destroy());
        sortableSubtasks = [];
    }
}

function saveTodosOrder() {
    const orderItems = [];
    
    // Vybere všechny úkoly na stránce v aktuálním DOM pořadí (pinned první, pak ostatní)
    // Funguje to i pro podúkoly, protože sort_order se uplatní v rámci každého úrovně stromu
    const allItems = document.querySelectorAll('.todo-item');
    allItems.forEach((item, index) => {
        orderItems.push({
            id: item.dataset.id,
            order: index
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

function openViewTodoModal(todo) {
    document.getElementById('viewTodoText').innerText = todo.text;
    
    // Zobrazeni stitku
    const tagsContainer = document.getElementById('viewTodoTags');
    tagsContainer.innerHTML = '';
    if (todo.tags && todo.tags.length > 0) {
        todo.tags.forEach(tag => {
            const badge = document.createElement('span');
            badge.className = 'badge';
            badge.style.backgroundColor = tag.color || '#6c757d';
            badge.style.color = '#fff';
            badge.innerText = tag.name;
            tagsContainer.appendChild(badge);
        });
    }

    // Zobrazeni terminu
    const deadlineContainer = document.getElementById('viewTodoDeadline');
    const deadlineTimeContainer = document.getElementById('viewTodoDeadlineTime');
    
    if (todo.deadline) {
        const d = new Date(todo.deadline);
        deadlineContainer.innerText = d.toLocaleDateString('cs-CZ');
    } else {
        deadlineContainer.innerText = '';
    }

    if (todo.deadline_time) {
        deadlineTimeContainer.innerText = todo.deadline_time.substring(0, 5);
        deadlineTimeContainer.classList.remove('d-none');
    } else {
        deadlineTimeContainer.classList.add('d-none');
    }

    if (!todo.deadline && !todo.deadline_time) {
        deadlineContainer.innerHTML = '<span class="text-white-50">Neuveden</span>';
    }

    // Zobrazeni poznamky
    const noteContainer = document.getElementById('viewTodoNote');
    if (todo.note && todo.note.trim() !== '') {
        noteContainer.innerText = todo.note;
    } else {
        noteContainer.innerHTML = '<span class="text-white-50 fst-italic">Bez poznámky.</span>';
    }

    const editBtn = document.getElementById('viewTodoEditBtn');
    editBtn.onclick = function() {
        const modalEl = document.getElementById('viewTodoModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        openEditTodoModal(todo);
    };

    const modal = new bootstrap.Modal(document.getElementById('viewTodoModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('show.bs.modal', function () {
        const openDropdowns = document.querySelectorAll('.dropdown-toggle.show');
        openDropdowns.forEach(dd => {
            const instance = bootstrap.Dropdown.getInstance(dd);
            if (instance) instance.hide();
        });
    });
});

function addSubtask(parentId, event) {
    if (event) event.stopPropagation();
    
    // Clear and prepare form
    const form = document.getElementById('editTodoForm');
    form.reset();
    document.getElementById('editTodoId').value = '';
    document.getElementById('editTodoText').value = '';
    document.getElementById('editTodoDeadline').value = '';
    document.getElementById('editTodoDeadlineTime').value = '';
    document.getElementById('editTodoNote').value = '';
    document.getElementById('editTodoLocked').checked = false;
    
    // Parent ID visibility
    let parentInput = document.getElementById('editTodoParentId');
    if (!parentInput) {
        parentInput = document.createElement('input');
        parentInput.type = 'hidden';
        parentInput.name = 'parent_id';
        parentInput.id = 'editTodoParentId';
        form.appendChild(parentInput);
    }
    parentInput.value = parentId;
    
    // Reset checkboxes
    const checkboxes = document.querySelectorAll('.todo-tag-checkbox');
    checkboxes.forEach(cb => cb.checked = false);

    document.querySelector('#editTodoModal .modal-title').innerText = 'Přidat podúkol';
    const tagsContainer = document.getElementById('editTodoTagsContainer');
    if (tagsContainer) tagsContainer.classList.add('d-none');
    
    const modal = new bootstrap.Modal(document.getElementById('editTodoModal'));
    modal.show();
}

function openEditTodoModal(todo) {
    document.getElementById('editTodoId').value = todo.id;
    document.getElementById('editTodoText').value = todo.text;
    document.getElementById('editTodoDeadline').value = todo.deadline || '';
    document.getElementById('editTodoDeadlineTime').value = todo.deadline_time || '';
    document.getElementById('editTodoNote').value = todo.note || '';
    document.getElementById('editTodoLocked').checked = (todo.is_locked == 1 || todo.is_locked === true || todo.is_locked === "1");

    // Parent ID field
    let parentInput = document.getElementById('editTodoParentId');
    if (parentInput) parentInput.value = todo.parent_id || '';
    
    // Show/Hide tags based on subtask
    const tagsContainer = document.getElementById('editTodoTagsContainer');
    if (tagsContainer) {
        if (todo.parent_id) {
            tagsContainer.classList.add('d-none');
        } else {
            tagsContainer.classList.remove('d-none');
        }
    }
    
    if (todo.tags) {
        todo.tags.forEach(tag => {
            const cb = document.getElementById('todo-tag-' + tag.id);
            if (cb) cb.checked = true;
        });
    }

    document.querySelector('#editTodoModal .modal-title').innerText = 'Upravit úkol';
    
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
        // Intelligent Toggle Logic for Mobile
        addTodoBtn.addEventListener('click', (e) => {
            if (window.innerWidth < 992) {
                const collapseEl = document.getElementById('addTodoCollapse');
                const textInput = addTodoForm.querySelector('[name="text"]');
                const isFormVisible = collapseEl.classList.contains('show');

                if (!isFormVisible) {
                    // Open and focus
                    e.preventDefault();
                    new bootstrap.Collapse(collapseEl).show();
                    setTimeout(() => textInput.focus(), 350);
                } else if (!textInput.value.trim()) {
                    // Open but empty -> just hide
                    e.preventDefault();
                    bootstrap.Collapse.getInstance(collapseEl)?.hide();
                }
                // Else -> Proceed with normal form submission
            }
        });

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
                            newTodoItem.addEventListener('transitionend', () => {
                                newTodoItem.style.transform = '';
                                newTodoItem.style.transition = '';
                                updateTodoUIState();
                            }, { once: true });
                        });
                    }
                    
                    addTodoForm.reset();
                    
                    const pinnedVisible = pinnedTodosContainer && !pinnedTodosContainer.classList.contains('d-none');
                    const othersHeader = document.getElementById('othersHeader');
                    if (othersHeader && pinnedVisible) {
                        othersHeader.classList.remove('d-none');
                    }

                    // On small screens, hide the form again after successful submission
                    if (window.innerWidth < 992) {
                        const collapseEl = document.getElementById('addTodoCollapse');
                        bootstrap.Collapse.getInstance(collapseEl)?.hide();
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
            const parentId = formData.get('parent_id');
            
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

                    const existingCard = document.getElementById('todo-card-' + data.id);
                    if (existingCard) {
                        // UPDATE existing
                        const temp = document.createElement('div');
                        temp.innerHTML = data.html;
                        const newWrapper = temp.firstElementChild; // It's a todo-wrapper now
                        const newCard = newWrapper.querySelector('.todo-item');
                        
                        // If it's a wrapper, we should replace the whole wrapper or just the card
                        const oldWrapper = document.getElementById('todo-wrapper-' + data.id);
                        if (oldWrapper) {
                            oldWrapper.replaceWith(newWrapper);
                        } else {
                            existingCard.replaceWith(newCard);
                        }
                        
                        const actualNewCard = document.getElementById('todo-card-' + data.id);
                        if (actualNewCard) {
                             actualNewCard.classList.add('flash-purple');
                             setTimeout(() => actualNewCard.classList.remove('flash-purple'), 2000);
                        }
                    } else if (parentId) {
                        // NEW SUBTASK
                        const parentWrapper = document.getElementById('todo-wrapper-' + parentId);
                        if (parentWrapper) {
                            // Update parent's subtask count badge
                            const badge = parentWrapper.querySelector('.todo-item .subtask-count-badge');
                            if (badge) {
                                const countSpan = badge.querySelector('.count');
                                if (countSpan) {
                                    let currentCount = parseInt(countSpan.innerText) || 0;
                                    countSpan.innerText = currentCount + 1;
                                    badge.classList.remove('d-none');
                                }
                            }

                            let subContainer = parentWrapper.querySelector('.subtasks-container');
                            if (!subContainer) {
                                subContainer = document.createElement('div');
                                subContainer.className = 'subtasks-container ms-4 ms-md-5 mt-2 d-flex flex-column gap-2 border-start border-light border-opacity-10 ps-3';
                                parentWrapper.appendChild(subContainer);
                            }
                            const temp = document.createElement('div');
                            temp.innerHTML = data.html;
                            const newElem = temp.firstElementChild;
                            subContainer.prepend(newElem);
                            
                            const newCard = newElem.querySelector('.todo-item');
                            if (newCard) {
                                newCard.classList.add('flash-purple');
                                setTimeout(() => newCard.classList.remove('flash-purple'), 2000);
                            }
                        } else {
                            window.location.reload();
                        }
                    } else {
                        // NEW ROOT - append to others
                        const temp = document.createElement('div');
                        temp.innerHTML = data.html;
                        const newElem = temp.firstElementChild;
                        if (othersTodosList) {
                            othersTodosList.prepend(newElem);
                            const newCard = newElem.querySelector('.todo-item');
                            if (newCard) {
                                newCard.classList.add('flash-purple');
                                setTimeout(() => newCard.classList.remove('flash-purple'), 2000);
                            }
                        } else {
                            window.location.reload();
                        }
                    }
                    
                    // Update header/sidebar stats
                    if (typeof updateGlobalStats === 'function') updateGlobalStats(data);
                    updateTodoUIState();
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
    window.currentTodoTag = 'all'; // Globálně pro the sorting eventy a hash

    // Ensure initial UI state has 'all' active
    if (tagButtons.length > 0) {
        tagButtons.forEach(btn => {
            if (btn.getAttribute('data-tag') === 'all') {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    window.filterTodos = (forceAnimate = false) => {
        const items = document.querySelectorAll('.todo-item');
        let delay = 0;
        let pinnedVisible = 0;
        let othersVisible = 0;
        
        items.forEach(item => {
            // Memory Data Caching mechanismus jako u notes
            if (!item._tagCache) {
                const tagsAttr = item.getAttribute('data-tags');
                item._tagCache = tagsAttr ? tagsAttr.toLowerCase().split(',') : [];
            }
            const tags = item._tagCache;
            const matchesTag = window.currentTodoTag === 'all' || tags.some(t => t.trim().toLowerCase() === window.currentTodoTag.toLowerCase());

            const wasHidden = item.style.display === 'none';

            if (matchesTag) {
                item.style.display = 'block';
                if (forceAnimate || wasHidden) {
                    item.style.animation = 'none';
                    // item.offsetHeight; // ODSTRANĚNO: Brutální ztráta plynulosti the Layout Recalculation!
                    item.style.animation = `popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${delay}ms both`;
                    delay += 30; // 30ms the delay between list items
                }
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
        window.filterTodos(true);
    }

    tagButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tagButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            window.currentTodoTag = btn.getAttribute('data-tag');
            window.filterTodos(true);
        });
    });

    // Highlight todo from hash
    const highlightTodoFromHash = () => {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#todo-card-')) {
            const targetId = hash.substring(1);
            const element = document.getElementById(targetId);
            if (element) {
                // Ensure the todo is visible (clear filters if necessary)
                if (element.style.display === 'none') {
                    const allBtn = document.querySelector('#tagFilters .btn[data-tag="all"]');
                    if (allBtn && typeof window.filterTodos === 'function') {
                        allBtn.classList.add('active');
                        window.currentTodoTag = 'all';
                        document.querySelectorAll('#tagFilters .btn').forEach(b => { if(b !== allBtn) b.classList.remove('active'); });
                        window.filterTodos(false); // Okamžitě the UI bez the frame drop the re-renderování listu bloků
                    }
                }
                
                // apply animation
                element.classList.remove('updated-highlight');
                void element.offsetWidth; // trigger reflow
                element.classList.add('updated-highlight');

                // also scroll to it (built-in, but can be forced if needed)
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    };

    window.addEventListener('hashchange', highlightTodoFromHash);
    // Short delay to ensure popIn animations are done and elements are positioned
    setTimeout(highlightTodoFromHash, 100);

    // Auto-fill date when time is selected
    const addDeadlineTime = document.querySelector('#addTodoForm [name="deadline_time"]');
    const addDeadlineDate = document.querySelector('#addTodoForm [name="deadline"]');
    if (addDeadlineTime && addDeadlineDate) {
        addDeadlineTime.addEventListener('change', function() {
            if (this.value && !addDeadlineDate.value) {
                addDeadlineDate.value = new Date().toISOString().split('T')[0];
            }
        });
    }

    const editDeadlineTime = document.getElementById('editTodoDeadlineTime');
    const editDeadlineDate = document.getElementById('editTodoDeadline');
    if (editDeadlineTime && editDeadlineDate) {
        editDeadlineTime.addEventListener('change', function() {
            if (this.value && !editDeadlineDate.value) {
                editDeadlineDate.value = new Date().toISOString().split('T')[0];
            }
        });
    }

    // Zajištění, aby dropdowny nebyly překryty jinými kartami (z-index fix)
    document.addEventListener('show.bs.dropdown', function (event) {
        const parent = event.target.closest('.todo-item');
        if (parent) {
            parent.style.zIndex = '1060';
        }
    });

    document.addEventListener('hide.bs.dropdown', function (event) {
        const parent = event.target.closest('.todo-item');
        if (parent) {
            parent.style.zIndex = '';
        }
    });

    // Zajištění, že se zavřou všechny otevřené dropdowny, když se otevírá modální okno
    // Tím se také resetuje z-index (přes hide.bs.dropdown listener) a menu nezůstane nad modálem
    document.addEventListener('show.bs.modal', function () {
        const openDropdowns = document.querySelectorAll('.dropdown-toggle.show');
        openDropdowns.forEach(dd => {
            const instance = bootstrap.Dropdown.getInstance(dd);
            if (instance) instance.hide();
        });
    });
});

function toggleTodoPin(todoId, event) {
    
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
                const newElem = temp.firstElementChild; 
                
                const targetGridId = data.is_pinned ? 'pinnedTodosList' : 'othersTodosList';
                const targetGrid = document.getElementById(targetGridId);
                
                const oldWrapper = document.getElementById('todo-wrapper-' + todoId);
                if (oldWrapper) {
                    oldWrapper.remove();
                } else {
                    existingCard.remove();
                }

                if (targetGrid) {
                    targetGrid.prepend(newElem);
                    const newCard = newElem.querySelector('.todo-item');
                    if (newCard) {
                        newCard.classList.add('flash-purple');
                        setTimeout(() => newCard.classList.remove('flash-purple'), 2000);
                    }
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
            const wrapper = document.getElementById('todo-wrapper-' + todoId);
            
            // Decrement parent count
            if (wrapper && wrapper.parentNode && wrapper.parentNode.classList.contains('subtasks-container')) {
                const parentWrapper = wrapper.parentNode.closest('.todo-wrapper');
                if (parentWrapper) {
                    const badge = parentWrapper.querySelector('.todo-item .subtask-count-badge');
                    if (badge) {
                        const countSpan = badge.querySelector('.count');
                        if (countSpan) {
                            let curr = (parseInt(countSpan.innerText) || 0) - 1;
                            countSpan.innerText = Math.max(0, curr);
                            if (curr <= 0) badge.classList.add('d-none');
                        }
                    }
                }
            }

            const target = wrapper || card;
            if (target) {
                target.style.transition = 'all 0.3s ease';
                target.style.opacity = '0';
                target.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    target.remove();
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
            const wrapper = document.getElementById('todo-wrapper-' + todoId);
            
            // Fix: Decrement parent badge
            if (wrapper && wrapper.parentNode && wrapper.parentNode.classList.contains('subtasks-container')) {
                const pWrap = wrapper.parentNode.closest('.todo-wrapper');
                if (pWrap) {
                    const badge = pWrap.querySelector('.todo-item .subtask-count-badge');
                    if (badge) {
                        const countSpan = badge.querySelector('.count');
                        if (countSpan) {
                            let curr = (parseInt(countSpan.innerText) || 0) - 1;
                            countSpan.innerText = Math.max(0, curr);
                            if (curr <= 0) badge.classList.add('d-none');
                        }
                    }
                }
            }

            const target = wrapper || card;
            if (target) {
                target.style.transition = 'all 0.3s ease';
                target.style.opacity = '0';
                target.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    target.remove();
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

    // Počítáme jen úkoly, které aktuálně nejsou filtrem schované
    const getVisibleCount = (container) => {
        return Array.from(container.querySelectorAll('.todo-item')).filter(
            item => item.style.display !== 'none'
        ).length;
    };

    const pinnedCount = getVisibleCount(pinnedGrid);
    const othersCount = getVisibleCount(othersGrid);
    const totalCount = pinnedGrid.querySelectorAll('.todo-item').length + othersGrid.querySelectorAll('.todo-item').length;

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
    if (totalCount === 0) {
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
