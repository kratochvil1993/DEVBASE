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
    } elseif ($days == 0) {
        // Checking also Time if set
        if (!empty($todo['deadline_time'])) {
            $now_time = date('H:i:s');
            if ($todo['deadline_time'] < $now_time) {
                $deadlineStatus = 'deadline-passed';
            } else {
                $deadlineStatus = 'deadline-approaching';
            }
        } else {
            $deadlineStatus = 'deadline-passed'; // No time means generally today, let's treat as passed/urgent
        }
    } elseif ($days == 1) {
        $deadlineStatus = 'deadline-approaching';
    }
} elseif (!empty($todo['deadline_time'])) {
    // Only time exists
    $now_time = date('H:i:s');
    if ($todo['deadline_time'] < $now_time) {
        $deadlineStatus = 'deadline-passed';
    } else {
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
                    <?php if ($deadlineDateFormatted || !empty($todo['deadline_time'])): ?>
                        <small class="text-white-50 mt-1 d-flex align-items-center gap-2">
                            <span>
                                <?php if ($deadlineDateFormatted): ?>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?php echo $deadlineDateFormatted; ?>
                                <?php endif; ?>
                                <?php if (!empty($todo['deadline_time'])): ?>
                                    <span class="ms-1 text-white opacity-75"><i class="bi bi-clock me-1" style="font-size: 0.9em;"></i><?php echo substr($todo['deadline_time'], 0, 5); ?></span>
                                <?php endif; ?>
                            </span>
                            <?php if ($deadlineStatus == 'deadline-passed'): ?>
                                <span class="badge bg-danger ms-1" style="font-size: 0.7em;">Po termínu</span>
                            <?php elseif ($deadlineStatus == 'deadline-approaching'): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7em;">Blíží se</span>
                            <?php endif; ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="action-btns flex-shrink-0 ms-3">
                <!-- Desktop style (md+) -->
                <div class="d-none d-md-flex gap-2">
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

                <!-- Mobile style (<md) -->
                <div class="dropdown d-md-none">
                    <button class="btn btn-sm btn-link text-white-50 p-0" data-bs-toggle="dropdown" aria-expanded="false" onclick="event.stopPropagation();">
                        <i class="bi bi-three-dots-vertical fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border border-light border-opacity-10">
                        <li>
                            <a class="dropdown-item small py-2 <?php echo $todo['is_pinned'] ? 'text-warning' : ''; ?>" href="javascript:void(0)" onclick="toggleTodoPin(<?php echo $todo['id']; ?>, event)">
                                <i class="bi <?php echo $todo['is_pinned'] ? 'bi-pin-angle-fill' : 'bi-pin-angle'; ?> me-2"></i>
                                <?php echo $todo['is_pinned'] ? 'Odepnout' : 'Připnout'; ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small py-2" href="javascript:void(0)" onclick="openEditTodoModal(<?php echo htmlspecialchars(json_encode($todo), ENT_QUOTES, 'UTF-8'); ?>)">
                                <i class="bi bi-pencil me-2"></i> Upravit
                            </a>
                        </li>
                        <li><hr class="dropdown-divider border-light border-opacity-10"></li>
                        <li>
                            <a class="dropdown-item small py-2 text-danger" href="javascript:void(0)" onclick="deleteTodoItem(<?php echo $todo['id']; ?>, event)">
                                <i class="bi bi-trash me-2"></i> Smazat
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
