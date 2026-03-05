<?php 
$reminders = [];
$criticalCount = 0;
$warningCount = 0;
$totalReminders = 0;

if (getSetting('todos_enabled', '1') == '1') {
    $reminders = getTodoReminders();
    $criticalCount = count($reminders['critical']);
    $warningCount = count($reminders['warning']);
    $totalReminders = $criticalCount + $warningCount;
}
$grandTotal = $totalReminders;

if ($grandTotal > 0):
    $badgeClass = ($criticalCount > 0) ? 'bg-danger' : 'bg-warning text-dark';
    $pulseClass = ($criticalCount > 0) ? 'pulse-red' : '';
?>
    <div class="dropdown">
        <button id="notificationBellBtn" class="btn btn-link text-white p-0 position-relative <?php echo $pulseClass; ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell-fill fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill <?php echo $badgeClass; ?>" style="font-size: 0.6rem;">
                <?php echo $grandTotal; ?>
            </span>
        </button>
        <div class="dropdown-menu dropdown-menu-end dropdown-menu-glass p-0 mt-2" style="width: 280px;">
            <div class="p-3 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Upozornění</h6>
                <span class="badge <?php echo $badgeClass; ?>"><?php echo $grandTotal; ?> celkem</span>
            </div>
            <div class="reminder-list" style="max-height: 350px; overflow-y: auto;">
                
                <!-- Todo Badges -->
                <?php if ($criticalCount > 0): ?>
                    <div class="px-3 py-2 bg-danger bg-opacity-10 small fw-bold text-danger-emphasis">Po termínu / Právě teď</div>
                    <?php foreach ($reminders['critical'] as $todo): ?>
                        <a class="dropdown-item px-3 py-2 border-bottom border-light border-opacity-10" href="todo.php#todo-card-<?php echo $todo['id']; ?>">
                            <div class="text-truncate fw-medium"><?php echo htmlspecialchars($todo['text']); ?></div>
                            <small class="text-danger">
                                <i class="bi bi-calendar-x me-1"></i> <?php echo date('j. n. Y', strtotime($todo['deadline'])); ?>
                                <?php if (!empty($todo['deadline_time'])): ?>
                                    <span class="ms-1 fw-bold"><i class="bi bi-clock me-1"></i><?php echo substr($todo['deadline_time'], 0, 5); ?></span>
                                <?php endif; ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($warningCount > 0): ?>
                    <div class="px-3 py-2 bg-warning bg-opacity-10 small fw-bold text-warning-emphasis">Dnes / Zítra vyprší</div>
                    <?php foreach ($reminders['warning'] as $todo): ?>
                        <a class="dropdown-item px-3 py-2 border-bottom border-light border-opacity-10" href="todo.php#todo-card-<?php echo $todo['id']; ?>">
                            <div class="text-truncate fw-medium"><?php echo htmlspecialchars($todo['text']); ?></div>
                            <small class="text-warning">
                                <i class="bi bi-calendar-event me-1"></i> <?php echo date('j. n. Y', strtotime($todo['deadline'])); ?>
                                <?php if (!empty($todo['deadline_time'])): ?>
                                    <span class="ms-1 fw-bold"><i class="bi bi-clock me-1"></i><?php echo substr($todo['deadline_time'], 0, 5); ?></span>
                                <?php endif; ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="p-2 text-center">
                <a href="todo.php" class="btn btn-sm btn-link text-white-50 text-decoration-none" style="font-size: 0.75rem;">Zobrazit všechny úkoly</a>
            </div>
        </div>
    </div>
<?php endif; ?>
