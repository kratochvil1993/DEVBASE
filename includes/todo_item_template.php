<?php
$deadlineStatus = '';
$deadlineDateFormatted = '';
if (!empty($todo['deadline'])) {
    $today = new DateTime('today');
    $deadline = new DateTime($todo['deadline']);
    $deadlineDateFormatted = $deadline->format('j. n. Y');
    $diff = $today->diff($deadline);
    $days = (int)$diff->format('%r%a');
    
    if ($days < 0) {
        $deadlineStatus = 'deadline-passed';
    } elseif ($days <= 1) {
        $deadlineStatus = 'deadline-approaching';
    }
}
?>
<div class="card glass-card todo-item <?php echo $todo['is_pinned'] ? 'pinned' : ''; ?> <?php echo $todo['is_locked'] ? 'locked' : ''; ?> <?php echo $deadlineStatus; ?>" 
     id="todo-card-<?php echo $todo['id']; ?>"
     data-id="<?php echo $todo['id']; ?>"
     data-is-locked="<?php echo $todo['is_locked']; ?>"
     data-deadline="<?php echo htmlspecialchars($todo['deadline'] ?? ''); ?>"
     data-tags="<?php echo htmlspecialchars(implode(',', array_column($todo['tags'] ?? [], 'name'))); ?>">

    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center overflow-hidden flex-grow-1">
                <div class="me-3 mb-0 d-flex align-items-center">
                    <input class="form-check-input m-0 fs-5 flex-shrink-0" type="checkbox" onclick="archiveTodoItem(<?php echo $todo['id']; ?>, event)" style="cursor: pointer;">
                </div>
                <div class="d-flex flex-column overflow-hidden flex-grow-1" style="cursor: pointer;" onclick="openViewTodoModal(<?php echo htmlspecialchars(json_encode($todo), ENT_QUOTES, 'UTF-8'); ?>)">
                    <?php if (!empty($todo['tags'])): ?>
                        <div class="d-flex flex-wrap gap-1 mb-1">
                            <?php foreach ($todo['tags'] as $tag): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color'] ?? '#6c757d'); ?>; color: #fff; font-size: 0.7em;">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <span class="fs-5 text-truncate text-white">
                        <?php if ($todo['is_locked']): ?>
                            <i class="bi bi-lock-fill me-1 small opacity-50"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($todo['text']); ?>
                        <?php if (!empty($todo['note'])): ?>
                            <i class="bi bi-file-earmark-text text-white-50 ms-2" style="font-size: 0.8em;" title="Obsahuje poznámku"></i>
                        <?php endif; ?>
                    </span>
                    <?php if ($deadlineDateFormatted): ?>
                        <small class="text-white-50 mt-1">
                            <i class="bi bi-calendar-event me-1"></i>
                            Termín: <?php echo $deadlineDateFormatted; ?>
                            <?php if ($deadlineStatus == 'deadline-passed'): ?>
                                <span class="badge bg-danger ms-1" style="font-size: 0.7em;">Po termínu</span>
                            <?php elseif ($deadlineStatus == 'deadline-approaching'): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7em;">Blíží se</span>
                            <?php endif; ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2 action-btns flex-shrink-0 ms-3">
                <button type="button" class="btn btn-sm btn-link <?php echo $todo['is_pinned'] ? 'text-warning' : 'text-white-50'; ?> p-0" 
                        onclick="toggleTodoPin(<?php echo $todo['id']; ?>, event)"
                        title="<?php echo $todo['is_pinned'] ? 'Odepnout' : 'Připnout'; ?>">
                    <i class="bi <?php echo $todo['is_pinned'] ? 'bi-pin-angle-fill' : 'bi-pin-angle'; ?> fs-5"></i>
                </button>

                <button type="button" class="btn btn-sm btn-link text-white-50 p-0" onclick="openEditTodoModal(<?php echo htmlspecialchars(json_encode($todo), ENT_QUOTES, 'UTF-8'); ?>)">
                    <i class="bi bi-pencil fs-5"></i>
                </button>
                
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="deleteTodoItem(<?php echo $todo['id']; ?>, event)" title="Smazat navždy">
                    <i class="bi bi-trash fs-5"></i>
                </button>
            </div>
        </div>
    </div>
</div>
