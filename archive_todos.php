<?php
require_once 'includes/functions.php';

// Check if todos are enabled
if (getSetting('todos_enabled', '1') == '0') {
    header('Location: index.php');
    exit;
}

$todos = getAllTodos(1); // 1 = archived

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center mt-2">
    <div class="col-lg-8 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-white fw-bold mb-0">Archiv TODO</h2>
            <div id="archiveActions" class="<?php echo empty($todos) ? 'd-none' : ''; ?>">
                <button type="button" class="btn btn-danger rounded px-4" onclick="emptyTodoArchive()">
                    <i class="bi bi-trash-fill me-2"></i> Vysypat archiv
                </button>
            </div>
        </div>

        <?php
        $usedTags = [];
        $tagsQuery = "SELECT DISTINCT t.* FROM tags t 
                      JOIN todo_tags tt ON t.id = tt.tag_id 
                      JOIN todos td ON tt.todo_id = td.id 
                      WHERE td.is_archived = 1 
                      ORDER BY t.sort_order ASC, t.name ASC";
        $tagsResult = $conn->query($tagsQuery);
        if ($tagsResult) {
            while ($tag = $tagsResult->fetch_assoc()) {
                $usedTags[] = $tag;
            }
        }
        ?>

        <?php if (!empty($usedTags)): ?>
        <div class="row mt-3 mb-4">
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
        <?php endif; ?>

        <div class="d-flex flex-column gap-3" id="todosList">
            <?php if (empty($todos)): ?>
                <div class="text-center text-white-50 py-5 glass-card mt-3">
                    <i class="bi bi-archive display-1 mb-3 d-block"></i>
                    <h3>Archiv je prázdný.</h3>
                    <p>Zatím jste žádné úkoly nevyřídili.</p>
                </div>
            <?php else: ?>
                <?php foreach ($todos as $todo): ?>
                    <div class="card glass-card todo-item" 
                         id="todo-card-<?php echo $todo['id']; ?>"
                         data-id="<?php echo $todo['id']; ?>"
                         data-tags="<?php echo htmlspecialchars(implode(',', array_column($todo['tags'], 'name'))); ?>">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center overflow-hidden flex-grow-1 text-white-50 text-decoration-line-through">
                                    <div class="me-3 mb-0 d-flex align-items-center" title="Obnovit jako aktivní">
                                        <input class="form-check-input m-0 fs-5 flex-shrink-0" type="checkbox" onclick="unarchiveTodoItem(<?php echo $todo['id']; ?>, event)" style="cursor: pointer;" checked>
                                    </div>
                                    <div class="d-flex flex-column overflow-hidden flex-grow-1">
                                        <?php if (!empty($todo['tags'])): ?>
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                <?php foreach ($todo['tags'] as $tag): ?>
                                                    <span class="badge opacity-75" style="background-color: <?php echo htmlspecialchars($tag['color'] ?? '#6c757d'); ?>; color: #fff; font-size: 0.7em;">
                                                        <?php echo htmlspecialchars($tag['name']); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="fs-5 text-truncate"><?php echo htmlspecialchars($todo['text']); ?></span>
                                        <?php if (!empty($todo['deadline'])): ?>
                                            <small class="text-white-50 mt-1">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                Termín: <?php echo date('j. n. Y', strtotime($todo['deadline'])); ?>
                                                <?php if (!empty($todo['deadline_time'])): ?>
                                                    <span class="ms-1 opacity-75"><i class="bi bi-clock me-1"></i><?php echo substr($todo['deadline_time'], 0, 5); ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 action-btns flex-shrink-0 ms-3">
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="deleteArchiveTodoItem(<?php echo $todo['id']; ?>, event)" title="Smazat navždy">
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tagButtons = document.querySelectorAll('#tagFilters .btn');
    let currentTag = (localStorage.getItem('archiveTodoTag') || 'all').trim();

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
            } else {
                item.style.display = 'none';
                item.style.animation = 'none';
            }
        });
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
            localStorage.setItem('archiveTodoTag', currentTag);
            filterTodos();
        });
    });
});

function unarchiveTodoItem(todoId, event) {
    if (event) event.stopPropagation();
    
    const formData = new FormData();
    formData.append('action', 'unarchive_todo');
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
                }, 300);
            }
        } else {
            alert(data.message);
        }
    });
}

function deleteArchiveTodoItem(todoId, event) {
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
                    checkEmptyArchive();
                }, 300);
            }
        } else {
            alert(data.message);
        }
    });
}

function emptyTodoArchive() {
    if (!confirm('Opravdu chcete VŠECHNY archivované úkoly trvale smazat?')) return;

    const formData = new FormData();
    formData.append('action', 'empty_archive');

    fetch('api/api_todo_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const list = document.getElementById('todosList');
            const items = list.querySelectorAll('.todo-item');
            
            items.forEach((item, index) => {
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => item.remove(), 300);
                }, index * 50);
            });
            
            setTimeout(() => {
                checkEmptyArchive();
            }, (items.length * 50) + 350);
        } else {
            alert(data.message);
        }
    });
}

function checkEmptyArchive() {
    const list = document.getElementById('todosList');
    if (list.querySelectorAll('.todo-item').length === 0) {
        list.innerHTML = `
            <div class="text-center text-white-50 py-5 glass-card mt-3">
                <i class="bi bi-archive display-1 mb-3 d-block"></i>
                <h3>Archiv je prázdný.</h3>
                <p>Zatím jste žádné úkoly nevyřídili.</p>
            </div>
        `;
        document.getElementById('archiveActions').classList.add('d-none');
        const tagFilters = document.getElementById('tagFilters');
        if (tagFilters) tagFilters.parentElement.remove();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
