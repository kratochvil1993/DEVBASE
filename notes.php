<?php
require_once 'includes/functions.php';

// Handle Note addition, update or delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_note') {
        $id = !empty($_POST['note_id']) ? $_POST['note_id'] : null;
        saveNote($_POST['title'], $_POST['content'], $id);
    } elseif ($_POST['action'] == 'delete_note') {
        deleteNote($_POST['note_id']);
    }
    header('Location: notes.php');
    exit;
}

$notes = getAllNotes();

include 'includes/header.php';
?>

<div class="row mb-5 align-items-center">
    <div class="col-md-8 mx-auto text-center">
        <h1 class="text-white fw-bold mb-4">Moje Poznámky</h1>
        <button class="btn btn-add-snipet rounded-pill px-4 py-2" data-bs-toggle="modal" data-bs-target="#noteModal" onclick="openAddNoteModal()">
            <i class="bi bi-plus-lg me-2"></i> Nová poznámka
        </button>
    </div>
</div>

<div class="row g-4" id="notesGrid">
    <?php if (empty($notes)): ?>
        <div class="col-12 text-center text-white-50 py-5">
            <i class="bi bi-journal-x display-1 mb-3 d-block"></i>
            <h3>Zatím nemáte žádné poznámky.</h3>
            <p>Klikněte na tlačítko výše a vytvořte si první!</p>
        </div>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
            <div class="col-md-4 col-lg-6">
                <div class="card glass-card h-100 note-card" onclick="openViewNoteModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title text-white mb-0 text-truncate"><?php echo htmlspecialchars($note['title']); ?></h6>
                            <div class="d-flex gap-2" onclick="event.stopPropagation()">
                                <button class="btn btn-sm btn-link text-white-50 p-0" 
                                        onclick="openEditNoteModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)"
                                        title="Upravit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tuto poznámku smazat?');">
                                    <input type="hidden" name="action" value="delete_note">
                                    <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Smazat">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <p class="card-text text-white-50 small mb-0" style="display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                        </p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pt-0">
                        <small class="text-white-25" style="font-size: 0.65rem;">
                            <?php echo date('d.m.Y H:i', strtotime($note['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- View Note Modal -->
<div class="modal fade" id="viewNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white" id="viewNoteModalTitle">Prohlížení poznámky</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="viewNoteContent" class="text-white white-space-pre-wrap py-3" style="font-size: 1.1rem; line-height: 1.6;"></div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10">
                <small id="viewNoteDate" class="text-white-25 me-auto"></small>
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
            </div>
        </div>
    </div>
</div>

<!-- Note Modal (Add/Edit) -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white" id="noteModalTitle">Nová poznámka</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="noteForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_note">
                    <input type="hidden" name="note_id" id="noteId" value="">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Název</label>
                        <input type="text" name="title" id="noteTitleInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" required placeholder="Napište název...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Obsah</label>
                        <textarea name="content" id="noteContentInput" class="form-control bg-transparent text-white border-light border-opacity-25 shadow-none" rows="12" required placeholder="Napište vaši poznámku..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-add-snipet px-4" id="noteSubmitBtn">Uložit poznámku</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openViewNoteModal(note) {
    document.getElementById('viewNoteModalTitle').innerText = note.title;
    document.getElementById('viewNoteContent').innerHTML = note.content.replace(/\n/g, '<br>');
    document.getElementById('viewNoteDate').innerText = 'Vytvořeno: ' + new Date(note.created_at).toLocaleString('cs-CZ');
    
    var myModal = new bootstrap.Modal(document.getElementById('viewNoteModal'));
    myModal.show();
}

function openAddNoteModal() {
    document.getElementById('noteModalTitle').innerText = 'Nová poznámka';
    document.getElementById('noteId').value = '';
    document.getElementById('noteTitleInput').value = '';
    document.getElementById('noteContentInput').value = '';
    document.getElementById('noteSubmitBtn').innerText = 'Uložit poznámku';
}

function openEditNoteModal(note) {
    document.getElementById('noteModalTitle').innerText = 'Upravit poznámku';
    document.getElementById('noteId').value = note.id;
    document.getElementById('noteTitleInput').value = note.title;
    document.getElementById('noteContentInput').value = note.content;
    document.getElementById('noteSubmitBtn').innerText = 'Uložit změny';
    
    var myModal = new bootstrap.Modal(document.getElementById('noteModal'));
    myModal.show();
}
</script>

<?php include 'includes/footer.php'; ?>
