<?php
require_once 'includes/functions.php';

// Check if todos are enabled
if (getSetting('todos_enabled', '1') == '0') {
    header('Location: index.php');
    exit;
}

// Handle Todo unarchive or delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'unarchive_todo') {
        archiveTodo($_POST['todo_id'], 0);
        header('Location: todo.php?updated_id=' . $_POST['todo_id']);
        exit;
    } elseif ($_POST['action'] == 'delete_todo') {
        deleteTodo($_POST['todo_id']);
    } elseif ($_POST['action'] == 'empty_archive') {
        global $conn;
        $conn->query("DELETE FROM todos WHERE is_archived = 1");
    }
    header('Location: archive_todos.php');
    exit;
}

$todos = getAllTodos(1); // 1 = archived

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-lg-8 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-white fw-bold mb-0">Archiv úkolů (TODO)</h2>
            <?php if (!empty($todos)): ?>
            <form method="POST" onsubmit="return confirm('Opravdu chcete VŠECHNY archivované úkoly trvale smazat?');">
                <input type="hidden" name="action" value="empty_archive">
                <button type="submit" class="btn btn-danger rounded px-4">
                    <i class="bi bi-trash-fill me-2"></i> Vysypat archiv
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php
        $allTags = getAllTags('todo');
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
                         data-id="<?php echo $todo['id']; ?>"
                         data-tags="<?php echo htmlspecialchars(implode(',', array_column($todo['tags'], 'name'))); ?>">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center overflow-hidden flex-grow-1 text-white-50 text-decoration-line-through">
                                    <form method="POST" class="me-3 mb-0 d-flex align-items-center" id="form_unarchive_<?php echo $todo['id']; ?>" title="Obnovit jako aktivní">
                                        <input type="hidden" name="action" value="unarchive_todo">
                                        <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                                        <input class="form-check-input m-0 fs-5 flex-shrink-0" type="checkbox" onclick="document.getElementById('form_unarchive_<?php echo $todo['id']; ?>').submit()" style="cursor: pointer;" checked>
                                    </form>
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
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 action-btns flex-shrink-0 ms-3">
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
        
        <!-- <div class="text-start mt-3">
            <a href="todo.php" class="text-white btn btn-sm btn-outline-light"><i class="bi bi-arrow-left me-1"></i> Zpět na aktivní úkoly</a>
        </div> -->
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
</script>

<?php include 'includes/footer.php'; ?>
