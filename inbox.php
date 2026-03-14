<?php
require_once 'includes/functions.php';

if (getSetting('inbox_enabled', '0') == '0') {
    header('Location: index.php');
    exit;
}

// Přesměrujeme API POST volání bokem pro asynchronní bez-reload operace z tlačítek the UI
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'delete_inbox_item') {
        $result = deleteInboxItem($_POST['id']);
        echo json_encode(['status' => $result ? 'success' : 'error']);
    } elseif ($_POST['action'] == 'clear_inbox') {
        clearInbox();
        header('Location: inbox.php');
    } elseif ($_POST['action'] == 'manual_import') {
        $resultId = importIntoItemFromInbox($_POST['id'], $_POST['target_type']);
        echo json_encode(['status' => $resultId ? 'success' : 'error']);
    }
    exit;
}

if (getSetting('inbox_enabled', '0') == '1') {
    $conn->query("UPDATE inbox_items SET is_seen = 1 WHERE is_seen = 0");
}

$items = getAllInboxItems();

include 'includes/header.php';
?>
<div class="container">
    <div class="row">
        <div class="col-12 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="text-white fw-bold mb-0">Inbox</h2>                
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-add-snipet rounded px-4 py-2" id="syncInboxBtn" onclick="syncInbox()">
                    <i class="bi bi-arrow-clockwise me-0 me-md-2"></i> <span class="d-none d-md-inline">Načíst nové</span>
                </button>
                <form method="POST" onsubmit="return confirm('Opravdu chcete vyčistit celý inbox?');" class="mb-0">
                    <input type="hidden" name="action" value="clear_inbox">
                    <button type="submit" class="btn btn-danger-glass rounded px-3 py-2">
                        <i class="bi bi-trash me-0 me-md-2"></i> <span class="d-none d-md-inline">Vymazat historii</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="row">
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
                <div class="d-flex flex-column gap-3 mb-5">
                    <?php foreach ($items as $item): ?>
                        <div class="inbox-item-wrapper" id="inbox-item-<?php echo $item['id']; ?>" onclick="showInboxDetail(<?php echo $item['id']; ?>)" style="cursor: pointer;">
                            <div class="glass-card todo-item p-3 d-flex align-items-center gap-3 border-light border-opacity-10">
                                <!-- Type Icon / Badge -->
                                <div class="flex-shrink-0 d-none d-sm-block">
                                    <div class="badge <?php 
                                        echo $item['target_type'] == 'note' ? 'badge-primary-glass' : 
                                            ($item['target_type'] == 'todo' ? 'badge-success-glass' : 
                                            ($item['target_type'] == 'draft' ? 'badge-info-glass' : 'badge-secondary-glass')); 
                                        ?> py-2 px-3 fw-normal" style="min-width: 80px;">
                                        <?php echo $item['target_type'] == 'unknown' ? 'Bez tagu' : '@' . $item['target_type']; ?>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <?php if ($item['is_imported']): ?>
                                            <i class="bi bi-check-circle-fill icon-success-glow" title="Úspěšně vytvořeno"></i>
                                        <?php endif; ?>
                                        <h5 class="text-white mb-0 fs-6 fw-bold text-truncate"><?php echo htmlspecialchars($item['subject']); ?></h5>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php /*
                                        <span class="text-white-50 small text-truncate" style="max-width: 150px;">
                                            <i class="bi bi-person-circle me-1"></i><?php 
                                                $parts = explode('@', $item['from_email']);
                                                echo htmlspecialchars($parts[0]); 
                                            ?>
                                        </span>
                                        <span class="text-white-50 small">•</span>
                                        */ ?>
                                        <span class="text-white-50 small">
                                            <i class="bi bi-clock me-1"></i><?php echo date('j. n. H:i', strtotime($item['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                                    <?php if (!$item['is_imported']): ?>
                                    <div class="d-none d-md-flex gap-2">
                                        <button class="btn btn-sm btn-primary-glass py-1 px-2" onclick="manualImport(<?php echo $item['id']; ?>, 'note', event)" title="Importovat jako Poznámku">
                                            <i class="bi bi-sticky"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success-glass py-1 px-2" onclick="manualImport(<?php echo $item['id']; ?>, 'todo', event)" title="Importovat jako Úkol">
                                            <i class="bi bi-check2-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info-glass py-1 px-2" onclick="manualImport(<?php echo $item['id']; ?>, 'draft', event)" title="Importovat jako Draft">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Mobile simple dropdown for actions -->
                                    <div class="dropdown d-md-none">
                                        <button class="btn btn-sm btn-add-snipet p-1 px-2" data-bs-toggle="dropdown" onclick="event.stopPropagation();">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-dark shadow-lg">
                                            <li><a class="dropdown-item small" href="#" onclick="manualImport(<?php echo $item['id']; ?>, 'note', event)"><i class="bi bi-sticky me-2"></i>Poznámka</a></li>
                                            <li><a class="dropdown-item small" href="#" onclick="manualImport(<?php echo $item['id']; ?>, 'todo', event)"><i class="bi bi-check2-square me-2"></i>Úkol</a></li>
                                            <li><a class="dropdown-item small" href="#" onclick="manualImport(<?php echo $item['id']; ?>, 'draft', event)"><i class="bi bi-file-earmark-text me-2"></i>Draft</a></li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <button class="btn btn-sm btn-link text-danger p-2 border-0" onclick="deleteInboxItem(<?php echo $item['id']; ?>, event)">
                                        <i class="bi bi-trash fs-5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Inbox Detail Modal -->
<div class="modal fade" id="inboxDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white" id="modalSubject">Detail zprávy</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-white">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div id="modalBadge"></div>
                    <div class="text-white-50 small">
                        <?php /*
                        <i class="bi bi-person-circle me-1"></i> <span id="modalFrom"></span>
                        <span class="mx-2">•</span>
                        */ ?>
                        <i class="bi bi-clock me-1"></i> <span id="modalDate"></span>
                    </div>
                </div>
                <div class="p-3 rounded bg-black bg-opacity-25" style="white-space: pre-wrap; font-family: 'Inter', sans-serif;" id="modalContent"></div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10" id="modalFooter">
                <!-- Actions will be injected here if not imported -->
            </div>
        </div>
    </div>
</div>

<script>
// Bezpečné The vložení databáze itemů pro javascript, the neduplikuje kód the v the onClick the elementech
const theGlobalInboxItems = <?php echo json_encode(array_column($items, null, 'id')); ?>;

function showInboxDetail(id) {
    const item = theGlobalInboxItems[id];
    if (!item) return;

    document.getElementById('modalSubject').innerText = item.subject;
    // document.getElementById('modalFrom').innerText = item.from_email;
    document.getElementById('modalDate').innerText = item.created_at;
    document.getElementById('modalContent').innerText = item.content;
    
    const badgeContainer = document.getElementById('modalBadge');
    const type = item.target_type;
    const badgeClass = type === 'note' ? 'badge-primary-glass' : (type === 'todo' ? 'badge-success-glass' : (type === 'draft' ? 'badge-info-glass' : 'badge-secondary-glass'));
    badgeContainer.innerHTML = `<span class="badge ${badgeClass} py-2 px-3 fw-normal">@${type}</span>`;
    
    const footer = document.getElementById('modalFooter');
    if (!item.is_imported) {
        footer.innerHTML = `
            <div class="d-flex gap-2 w-100 align-items-center">
                <button class="btn btn-primary-glass" onclick="manualImport(${item.id}, 'note', event)">+ Poznámka</button>
                <button class="btn btn-success-glass" onclick="manualImport(${item.id}, 'todo', event)">+ Úkol</button>
                <button class="btn btn-info-glass" onclick="manualImport(${item.id}, 'draft', event)">+ Draft</button>
                <button class="btn btn-outline-light ms-auto" data-bs-dismiss="modal">Zavřít</button>
            </div>
        `;
    } else {
        footer.innerHTML = '<button class="btn btn-outline-light ms-auto" data-bs-dismiss="modal">Zavřít</button>';
    }

    const modal = new bootstrap.Modal(document.getElementById('inboxDetailModal'));
    modal.show();
}

function manualImport(id, type, event) {
    if (event) event.stopPropagation();
    
    let btn = null;
    let oldContent = "";
    if (event && event.currentTarget) {
        btn = event.currentTarget;
        oldContent = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled = true;
    }

    const formData = new FormData();
    formData.append('action', 'manual_import');
    formData.append('id', id);
    formData.append('target_type', type);

    fetch('inbox.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Skryjeme open modal pokud jsme the importovali of vnitřku zprávy!
            const modalEl = document.getElementById('inboxDetailModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }

            // Místo the reloadu (window.location.reload()) aplikujeme animativní zahození zpracovaného emailu!
            const el = document.getElementById('inbox-item-' + id);
            if (el) {
                el.style.transition = 'all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                el.style.transform = 'translateX(100px)';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 400);
            }
        }
    })
    .catch(() => {
        alert("Při importu nastala chyba sítě the API");
        if (btn) {
            btn.innerHTML = oldContent;
            btn.disabled = false;
        }
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

function deleteInboxItem(id, event) {
    if (event) event.stopPropagation();
    if (!confirm('Opravdu chcete tento záznam z historie smazat?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_inbox_item');
    formData.append('id', id);

    fetch('inbox.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then((data) => {
        if (data.status === 'success') {
            const el = document.getElementById('inbox-item-' + id);
            if (el) {
                el.style.transition = 'all 0.3s ease';
                el.style.opacity = '0';
                el.style.transform = 'scale(0.8)';
                setTimeout(() => el.remove(), 300);
            }
        } else {
            alert('Nastala chyba serveru a položka the nebyla ze záznamu ostraněna.');
        }
    })
    .catch(() => alert('Přerušeno síťové spojení'));
}
</script>

<?php include 'includes/footer.php'; ?>
