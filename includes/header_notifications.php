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

$newInboxItems = [];
$inboxCount = 0;
if (getSetting('inbox_enabled', '0') == '1') {
    $newInboxItems = getNewInboxItems(5);
    $inboxCount = (int)getSetting('total_inbox_new', 0); 
    // Wait, I updated getGlobalStats but I can also just check the database here or use the count from getGlobalStats if available.
    // Actually, getGlobalStats is called in header.php and stored in $stats.
    $inboxCount = $stats['total_inbox_new'] ?? 0;
}

$grandTotal = $totalReminders + $inboxCount;

if ($grandTotal > 0):
    $badgeClass = ($criticalCount > 0 || $inboxCount > 0) ? 'bg-danger' : 'bg-warning text-dark';
    $pulseClass = ($criticalCount > 0 || $inboxCount > 0) ? 'pulse-red' : '';
?>
    <div class="dropdown">
        <button class="btn btn-link text-white p-0 position-relative <?php echo $pulseClass; ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                
                <!-- Inbox Items -->
                <?php if ($inboxCount > 0): ?>
                    <div class="px-3 py-2 bg-primary bg-opacity-10 small fw-bold text-primary-emphasis d-flex justify-content-between">
                        <span>Nové v Inboxu</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $inboxCount; ?></span>
                    </div>
                    <?php foreach ($newInboxItems as $item): ?>
                        <a class="dropdown-item px-3 py-2 border-bottom border-light border-opacity-10" href="inbox.php">
                            <div class="text-truncate fw-medium"><?php echo htmlspecialchars($item['subject']); ?></div>
                            <small class="text-white-50"><i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($item['from_email']); ?></small>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($inboxCount > 5): ?>
                        <div class="px-3 py-1 text-center small text-white-50 border-bottom border-light border-opacity-10">
                            + dalšíc <?php echo $inboxCount - 5; ?> zpráv
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Todo Badges -->
                <?php if ($criticalCount > 0): ?>
                    <div class="px-3 py-2 bg-danger bg-opacity-10 small fw-bold text-danger-emphasis">Po termínu / Dnes</div>
                    <?php foreach ($reminders['critical'] as $todo): ?>
                        <a class="dropdown-item px-3 py-2 border-bottom border-light border-opacity-10" href="todo.php#todo-card-<?php echo $todo['id']; ?>">
                            <div class="text-truncate fw-medium"><?php echo htmlspecialchars($todo['text']); ?></div>
                            <small class="text-danger"><i class="bi bi-calendar-x me-1"></i> <?php echo date('j. n. Y', strtotime($todo['deadline'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($warningCount > 0): ?>
                    <div class="px-3 py-2 bg-warning bg-opacity-10 small fw-bold text-warning-emphasis">Zítra vyprší</div>
                    <?php foreach ($reminders['warning'] as $todo): ?>
                        <a class="dropdown-item px-3 py-2 border-bottom border-light border-opacity-10" href="todo.php#todo-card-<?php echo $todo['id']; ?>">
                            <div class="text-truncate fw-medium"><?php echo htmlspecialchars($todo['text']); ?></div>
                            <small class="text-warning"><i class="bi bi-calendar-event me-1"></i> <?php echo date('j. n. Y', strtotime($todo['deadline'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="p-2 text-center d-flex justify-content-around">
                <a href="todo.php" class="btn btn-sm btn-link text-white-50 text-decoration-none" style="font-size: 0.75rem;">Úkoly</a>
                <a href="inbox.php" class="btn btn-sm btn-link text-white-50 text-decoration-none" style="font-size: 0.75rem;">Inbox</a>
            </div>
        </div>
    </div>
<?php endif; ?>
