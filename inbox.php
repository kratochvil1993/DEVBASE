<?php
require_once 'includes/functions.php';

if (getSetting('inbox_enabled', '0') == '0') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'delete_inbox_item') {
        deleteInboxItem($_POST['id']);
    } elseif ($_POST['action'] == 'clear_inbox') {
        clearInbox();
    } elseif ($_POST['action'] == 'manual_import') {
        importIntoItemFromInbox($_POST['id'], $_POST['target_type']);
    }
    header('Location: inbox.php');
    exit;
}

$items = getAllInboxItems();

include 'includes/header.php';
?>
<div class="container">
    <div class="row">
        <div class="col-12 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="text-white fw-bold mb-0">Inbox</h2>
                <p class="text-white-50 small mb-0">Přehled importovaných zpráv z e-mailu.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-warning rounded px-4 py-2" id="syncInboxBtn" onclick="syncInbox()">
                    <i class="bi bi-arrow-clockwise me-2"></i> Načíst nové
                </button>
                <form method="POST" onsubmit="return confirm('Opravdu chcete vyčistit celý inbox?');" class="mb-0">
                    <input type="hidden" name="action" value="clear_inbox">
                    <button type="submit" class="btn btn-outline-danger border-0 rounded px-3 py-2">
                        <i class="bi bi-trash me-1"></i> Vymazat historii
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>  
    <div class="col-12">
        <div id="syncStatus" class="alert alert-info d-none bg-info bg-opacity-10 border-info border-opacity-25 text-info mb-4">
             <span class="spinner-border spinner-border-sm me-2"></span> Synchronizuji s e-mailem...
        </div>

        <?php if (empty($items)): ?>
            <div class="glass-card text-center py-5">
                <i class="bi bi-mailbox display-1 text-white-50 mb-3 d-block"></i>
                <h4 class="text-white-50">Inbox je prázdný</h4>
                <p class="text-white-50 small">Zkuste „Načíst nové“ nebo pošlete e-mail s tagem @note, @todo nebo @draft.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($items as $item): ?>
                    <div class="col-12 col-md-6 col-lg-4 inbox-item-wrapper" id="inbox-item-<?php echo $item['id']; ?>">
                        <div class="glass-card h-100 p-3 d-flex flex-column border-light border-opacity-10">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="badge <?php 
                                    echo $item['target_type'] == 'note' ? 'bg-primary' : 
                                        ($item['target_type'] == 'todo' ? 'bg-success' : 
                                        ($item['target_type'] == 'draft' ? 'bg-info' : 'bg-secondary')); 
                                    ?> mb-0">
                                    <?php echo $item['target_type'] == 'unknown' ? 'Bez tagu' : '@' . $item['target_type']; ?>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <?php if ($item['is_imported']): ?>
                                        <span class="text-success" title="Úspěšně vytvořeno"><i class="bi bi-check-circle-fill"></i></span>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick="deleteInboxItem(<?php echo $item['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <h5 class="text-white mb-2 fs-6 fw-bold"><?php echo htmlspecialchars($item['subject']); ?></h5>
                            <div class="text-white-50 small mb-3 flex-grow-1" style="max-height: 120px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical;">
                                <?php echo nl2br(htmlspecialchars($item['content'])); ?>
                            </div>
                            
                            <?php if (!$item['is_imported']): ?>
                            <div class="mb-3 d-flex flex-wrap gap-1">
                                <button class="btn btn-sm btn-outline-primary py-0" onclick="manualImport(<?php echo $item['id']; ?>, 'note')">+ Poznámka</button>
                                <button class="btn btn-sm btn-outline-success py-0" onclick="manualImport(<?php echo $item['id']; ?>, 'todo')">+ Úkol</button>
                                <button class="btn btn-sm btn-outline-info py-0" onclick="manualImport(<?php echo $item['id']; ?>, 'draft')">+ Draft</button>
                            </div>
                            <?php endif; ?>

                            <div class="mt-auto pt-3 border-top border-light border-opacity-10 d-flex justify-content-between align-items-center">
                                <span class="text-white-50" style="font-size: 0.7rem;">
                                    <i class="bi bi-clock me-1"></i> <?php echo date('d.m. H:i', strtotime($item['created_at'])); ?>
                                </span>
                                <span class="text-white-50 small text-truncate ms-2" style="max-width: 150px;" title="<?php echo htmlspecialchars($item['from_email']); ?>">
                                    <i class="bi bi-person-circle me-1"></i> <?php 
                                        $parts = explode('@', $item['from_email']);
                                        echo htmlspecialchars($parts[0]); 
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function manualImport(id, type) {
    const formData = new FormData();
    formData.append('action', 'manual_import');
    formData.append('id', id);
    formData.append('target_type', type);

    fetch('inbox.php', {
        method: 'POST',
        body: formData
    }).then(() => {
        window.location.reload();
    });
}
function syncInbox() {
    const btn = document.getElementById('syncInboxBtn');
    const status = document.getElementById('syncStatus');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Synchronizuji...';
    status.classList.remove('d-none');
    status.className = 'alert alert-info bg-info bg-opacity-10 border-info border-opacity-25 text-info mb-4';
    status.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Synchronizuji s e-mailem...';

    fetch('api/api_inbox_sync.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.count > 0) {
                    window.location.reload();
                } else {
                    status.className = 'alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success mb-4';
                    status.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + data.message;
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    setTimeout(() => {
                        status.classList.add('d-none');
                    }, 4000);
                }
            } else {
                status.className = 'alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger mb-4';
                status.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + data.message;
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Sync error:', error);
            status.className = 'alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger mb-4';
            status.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Kritická chyba při komunikaci se serverem.';
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

function deleteInboxItem(id) {
    if (!confirm('Opravdu chcete tento záznam z historie smazat?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_inbox_item');
    formData.append('id', id);

    fetch('inbox.php', {
        method: 'POST',
        body: formData
    }).then(() => {
        const el = document.getElementById('inbox-item-' + id);
        if (el) {
            el.parentElement.style.transition = 'all 0.3s ease';
            el.parentElement.style.opacity = '0';
            el.parentElement.style.transform = 'scale(0.8)';
            setTimeout(() => el.remove(), 300);
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
