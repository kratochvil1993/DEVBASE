<?php
require_once 'includes/functions.php';

// Check if notes are enabled
if (getSetting('notes_enabled', '1') == '0') {
    header('Location: index.php');
    exit;
}

// Fetch notes
$currentSort = isset($_GET['sort']) ? $_GET['sort'] : 'custom';
$notes = getAllNotes($currentSort, 1);
$languages = getAllLanguages();
$allNoteTags = getAllTags('note');

$geminiApiKey = getSetting('gemini_api_key');
$aiEnabled = getSetting('ai_enabled', '0') == '1' && (!empty($geminiApiKey) || !empty(getSetting('openai_api_key')) || !empty(getSetting('custom_ai_endpoint')));

// Identify used tags for filtering directly from DB
$usedTags = [];
$tagsQuery = "SELECT DISTINCT t.* FROM tags t 
              JOIN note_tags nt ON t.id = nt.tag_id 
              JOIN notes n ON nt.note_id = n.id 
              WHERE n.is_archived = 1 
              ORDER BY t.sort_order ASC, t.name ASC";
$tagsResult = $conn->query($tagsQuery);
if ($tagsResult) {
    while ($tag = $tagsResult->fetch()) {
        $usedTags[] = $tag;
    }
}

include 'includes/header.php';
?>

<style>
/* Styling for viewing note content and Quill editor to match aesthetics */
#viewNoteContent, .ql-editor {
    line-height: 1.6;
}
#viewNoteContent p, #viewNoteContent ol, #viewNoteContent ul, #viewNoteContent blockquote,
.ql-editor p, .ql-editor ol, .ql-editor ul, .ql-editor blockquote,
.quill-preview p, .quill-preview ol, .quill-preview ul, .quill-preview blockquote {
    margin-bottom: 0.8rem !important;
}
#viewNoteContent strong, #viewNoteContent b,
.ql-editor strong, .ql-editor b,
.quill-preview strong, .quill-preview b {
    color: #00e582 !important;
}
#viewNoteContent h2, #viewNoteContent h3, #viewNoteContent h4,
.ql-editor h2, .ql-editor h3, .ql-editor h4 {
    margin-top: 1.5rem !important;
    margin-bottom: 0.75rem !important;
    color: #fff !important;
}

.ql-editor {
    color: #fff !important;
}


/* Note Preview (Thumbnail) Styling */
.note-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    background: rgba(0, 0, 0, 0.25) !important;
    backdrop-filter: blur(12px) !important;
}
.note-card:hover {
    border-color: rgba(142, 84, 233, 0.5) !important;
    background: rgba(0, 0, 0, 0.35) !important;
    transform: translateY(-3px) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
}
.quill-preview {
    max-height: 250px;
    overflow: hidden;
    position: relative;
    font-size: 0.9rem;
    line-height: 1.6;
    letter-spacing: 0.01em;
    color: rgba(255, 255, 255, 0.85);
}
.quill-preview h2, .quill-preview h3, .quill-preview h4 {
    font-size: 1.05rem !important;
    font-weight: 600 !important;
    margin-top: 0.8rem !important;
    margin-bottom: 0.4rem !important;
    color: #fff !important;
    padding-bottom: 2px;
}
.quill-preview ul, .quill-preview ol {
    padding-left: 1.2rem;
    margin-bottom: 0.5rem;
}
.quill-preview li {
    margin-bottom: 0.2rem;
}

#viewNoteContent ol, #viewNoteContent ul,
.ql-editor ol, .ql-editor ul,
.quill-preview ol, .quill-preview ul {
    padding-left: 1.5rem;
}
#viewNoteContent li, .ql-editor li, .quill-preview li {
    margin-bottom: 0 !important;
}

/* Fix for Quill's bullet lists that use <ol> tags */
#viewNoteContent ol li[data-list="bullet"],
.ql-editor ol li[data-list="bullet"],
.quill-preview ol li[data-list="bullet"] {
    list-style-type: disc !important;
}
#viewNoteContent ol[data-list="bullet"],
.ql-editor ol[data-list="bullet"],
.quill-preview ol[data-list="bullet"] {
    list-style-type: none;
}
</style>

<div class="row mb-5 align-items-center mt-2">
    <div class="col-lg-8 mx-auto">
        <h2 class="text-white mb-3">Archiv poznámek</h2>
        <div class="glass-card no-jump p-2 d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="flex-grow-1" style="max-width: 400px;">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="noteSearch" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Hledat v archivu...">
                </div>
            </div>

            <div class="d-flex flex-wrap  gap-2">
                <button class="btn btn-add-snipet rounded Xrounded-pill px-4 d-none" id="newNoteBtn" data-bs-toggle="modal" data-bs-target="#noteModal" onclick="openAddNoteModal()">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <button class="btn btn-edit-order rounded Xrounded-pill px-4 d-none" id="editOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-arrows-move me-2"></i> Upravit pořadí
                </button>
                <button class="btn btn-success rounded Xrounded-pill px-4 d-none" id="saveOrderBtn" onclick="toggleSortingMode()">
                    <i class="bi bi-check-lg me-2"></i> Hotovo
                </button>
                <div class="dropdown" id="sortDropdownContainer">
                    <button class="btn btn-outline-light rounded Xrounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-down me-1"></i> 
                        <?php 
                            switch($currentSort) {
                                case 'oldest': echo 'Nejstarší'; break;
                                case 'alpha_asc': echo 'Abecedně A-Z'; break;
                                case 'alpha_desc': echo 'Abecedně Z-A'; break;
                                case 'custom': echo 'Vlastní řazení'; break;
                                default: echo 'Nejnovější';
                            }
                        ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark glass-card border-light border-opacity-10">
                        <li><a class="dropdown-item <?php echo $currentSort == 'custom' ? 'active' : ''; ?>" href="notes.php?sort=custom">Vlastní řazení</a></li>
                        <li><a class="dropdown-item <?php echo $currentSort == 'newest' ? 'active' : ''; ?>" href="notes.php?sort=newest">Nejnovější</a></li>
                        <li><a class="dropdown-item <?php echo $currentSort == 'oldest' ? 'active' : ''; ?>" href="notes.php?sort=oldest">Nejstarší</a></li>
                        <li><hr class="dropdown-divider border-light border-opacity-10"></li>
                        <li><a class="dropdown-item <?php echo $currentSort == 'alpha_asc' ? 'active' : ''; ?>" href="notes.php?sort=alpha_asc">Abecedně A-Z</a></li>
                        <li><a class="dropdown-item <?php echo $currentSort == 'alpha_desc' ? 'active' : ''; ?>" href="notes.php?sort=alpha_desc">Abecedně Z-A</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($usedTags)): ?>
<div class="row mb-5">
    <div class="col-lg-8 mx-auto">
        <div id="noteTagFilters" class="d-flex flex-wrap gap-2 justify-content-center">
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
</div>
<?php endif; ?>

<div class="row g-4" id="notesGrid">
    <?php if (empty($notes)): ?>
        <div class="col-12 text-center text-white-50 py-5">
            <i class="bi bi-archive display-1 mb-3 d-block"></i>
            <h3>Žádné poznámky v archivu.</h3>
        </div>
    <?php else: ?>
        <?php foreach ($notes as $note): 
            $tagNames = array_map(function($t) { return $t['name']; }, $note['tags']);
            $tagData = implode(',', $tagNames);
        ?>
            <div class="col-md-4 col-lg-6 note-item <?php echo ($note['is_locked'] ?? 0) ? 'locked' : ''; ?>" data-id="<?php echo $note['id']; ?>" id="note-card-<?php echo $note['id']; ?>" data-tags="<?php echo htmlspecialchars($tagData); ?>">
                <div class="card glass-card h-100 note-card" onclick="handleNoteClick(event, <?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title text-white mb-0 text-truncate">
                                <?php if ($note['is_locked'] ?? 0): ?>
                                    <i class="bi bi-lock-fill me-1 small opacity-50"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($note['title']); ?>
                            </h5>
                            <div class="d-flex gap-2 delete-btn-wrapper" onclick="event.stopPropagation()">
                                <button class="btn btn-sm btn-link text-white-50 p-0 edit-icon" 
                                        onclick="openEditNoteModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)"
                                        title="Upravit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-link text-success p-0" 
                                        onclick="unarchiveNote(<?php echo $note['id']; ?>, event)"
                                        title="Obnovit z archivu">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" 
                                        onclick="deleteNoteAjax(<?php echo $note['id']; ?>, event)"
                                        title="Smazat">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-text text-white-50 small mb-0 quill-preview">
                            <?php echo $note['content']; ?>
                        </div>
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
                <div class="d-flex align-items-center gap-3">
                    <h5 class="modal-title text-white" id="viewNoteModalTitle">
                        Prohlížení poznámky
                    </h5>
                    <div id="viewNoteTags" class="d-flex gap-1 flex-wrap"></div>
                </div>
                
                <?php if ($aiEnabled): ?>
                <div class="dropdown ms-auto me-2">
                    <button class="btn btn-sm btn-ai rounded px-3 dropdown-toggle shadow-none border-opacity-25" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="aiNoteBtn">
                        <i class="bi bi-robot me-1"></i> AI
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark glass-dropdown-ai border-light border-opacity-10 mt-2 shadow-lg">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiNoteAction('summarize_note')">
                                <i class="bi bi-list-task me-2 text-ai"></i> Shrnutí (body)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiNoteAction('format_note')">
                                <i class="bi bi-magic me-2 text-ai"></i> Zformátovat poznámku
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiNoteAction('structure_note')">
                                <i class="bi bi-diagram-3 me-2 text-ai"></i> Strukturovat (brain dump)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)" onclick="aiNoteAction('grammar_check')">
                                <i class="bi bi-spellcheck me-2 text-ai"></i> Kontrola pravopisu
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <?php if ($aiEnabled): ?>
                <!-- AI Insight Box for Notes -->
                <div id="aiNoteInsightBox" class="m-3 p-3 rounded-3 d-none ai-insight-box" style="background: rgba(10, 10, 15, 0.9); border: 1px solid rgba(142, 84, 233, 0.5); backdrop-filter: blur(5px);">
                    <div class="d-flex align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-robot text-primary me-2"></i>
                            <span class="small fw-bold text-white-50 text-uppercase tracking-wider">AI Insight</span>
                        </div>
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-light copy-btn px-2 py-0" onclick="copyToClipboard(this, 'aiNoteInsightContent')" style="font-size: 0.65rem; position: relative; top: 0; right: 0;">
                                copy
                            </button>
                            <button type="button" class="btn-close btn-close-white small" style="font-size: 0.5rem;" onclick="document.getElementById('aiNoteInsightBox').classList.add('d-none')"></button>
                        </div>
                    </div>
                    <div id="aiNoteInsightContent" class="text-white small lh-base" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap;"></div>
                </div>
                <?php endif; ?>
                <div class="position-relative">
                    <button class="btn btn-sm btn-outline-light copy-btn shadow-sm z-3" onclick="copyNoteContent(this)" style="position: absolute; right: 10px; top: 10px; z-index: 10;">
                        copy
                    </button>
                    <div id="viewNoteContent" class="p-3" style="max-height: 70vh; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <small id="viewNoteDate" class="text-white-25 m-0"></small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-add-snipet px-3" id="editNoteFromViewBtn">
                        <i class="bi bi-pencil me-1"></i> Upravit
                    </button>
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zavřít</button>
                </div>
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
                    <div class="row g-3">
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="form-label text-white-50 small mb-0">Název</label>
                                <?php if ($aiEnabled): ?>
                                <button type="button" class="btn btn-sm btn-ai-action" onclick="generateAiNoteTitle()" title="Generovat název">
                                    <i class="bi bi-magic me-1"></i> AI
                                </button>
                                <?php endif; ?>
                            </div>
                            <input type="text" name="title" id="noteTitleInput" class="form-control form-control-ai text-white border-light border-opacity-25" required placeholder="Napište název...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_locked" id="noteLockedInput" value="1">
                                <label class="form-check-label text-white-50 small" for="noteLockedInput">
                                    <i class="bi bi-lock-fill me-1"></i> Skrýt obsah
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Obsah</label>
                            <div id="quillEditor" style="height: 300px; background: transparent; color: white;"></div>
                            <input type="hidden" name="content" id="noteContentInput">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small d-block">Štítky</label>
                            <div class="d-flex flex-wrap gap-2 p-3 rounded border border-light border-opacity-10">
                                <?php if (empty($allNoteTags)): ?>
                                    <p class="text-white-50 small mb-0 w-100 text-center">Žádné štítky nejsou definovány. Přidejte je v nastavení.</p>
                                <?php else: ?>
                                    <?php foreach ($allNoteTags as $tag): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="noteTag<?php echo $tag['id']; ?>">
                                            <label class="form-check-label text-white-50 small" for="noteTag<?php echo $tag['id']; ?>">
                                                <span class="badge" style="background-color: <?php echo $tag['color'] ? htmlspecialchars($tag['color']) : '#6c757d'; ?>">
                                                    <?php echo htmlspecialchars($tag['name']); ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
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

<!-- SortableJS -->
<script src="assets/vendor/sortablejs/Sortable.min.js"></script>

<script>
let sortable = null;
let isSortingMode = false;
let currentViewedNote = null;

let quill;

document.addEventListener('DOMContentLoaded', function() {
    quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Napište vaši poznámku...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'clean']
            ]
        }
    });

    document.getElementById('noteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (quill) {
            document.getElementById('noteContentInput').value = quill.root.innerHTML;
        }

        const formData = new FormData(this);
        const submitBtn = document.getElementById('noteSubmitBtn');
        const originalBtnHtml = submitBtn.innerHTML;
        const noteId = document.getElementById('noteId').value;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Ukládám...';

        fetch('api/api_note_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const modalEl = document.getElementById('noteModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                if (noteId) {
                    const existingCard = document.getElementById('note-card-' + noteId);
                    if (existingCard) {
                        const temp = document.createElement('div');
                        temp.innerHTML = data.html;
                        const newCard = temp.firstElementChild;
                        existingCard.replaceWith(newCard);
                        
                        const innerCard = newCard.querySelector('.note-card');
                        if (innerCard) {
                            innerCard.classList.add('flash-purple');
                            setTimeout(() => innerCard.classList.remove('flash-purple'), 2000);
                        }
                    } else {
                        window.location.reload();
                    }
                }
                filterNotes();
            } else {
                alert('Chyba: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Nastala chyba při ukládání poznámky.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        });
    });

    // Copy on selection functionality
    const contentArea = document.getElementById('viewNoteContent');
    if (contentArea) {
        contentArea.addEventListener('mouseup', function() {
            const selection = window.getSelection().toString();
            if (selection && selection.length > 0) {
                navigator.clipboard.writeText(selection).then(() => {
                    // Optional: Show a subtle toast or log
                    console.log('Selection copied to clipboard');
                }).catch(err => console.error('Failed to copy selection', err));
            }
        });
    }

    const modalViewEl = document.getElementById('viewNoteModal');
    if (modalViewEl) {
        modalViewEl.addEventListener('hidden.bs.modal', function () {
            if (aiNoteTypingInterval) clearInterval(aiNoteTypingInterval);
            const insightBox = document.getElementById('aiNoteInsightBox');
            const insightContent = document.getElementById('aiNoteInsightContent');
            if (insightBox) insightBox.classList.add('d-none');
            if (insightContent) insightContent.innerHTML = '';
        });
    }
});

function unarchiveNote(noteId, event) {
    if (event) event.stopPropagation();
    
    const formData = new FormData();
    formData.append('action', 'archive_note'); // Passing 0 for status to unarchive
    formData.append('note_id', noteId);
    formData.append('status', '0');

    // api_note_handler uses archiveNote($id, 1) currently, let's check if it supports status
    // Wait, let's check api_note_handler.php again
    
    fetch('api/api_note_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById('note-card-' + noteId);
            if (card) {
                card.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                card.style.opacity = '0';
                card.style.transform = 'translateY(-20px) scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    if (document.querySelectorAll('.note-item').length === 0) {
                        const grid = document.getElementById('notesGrid');
                        grid.innerHTML = `
                            <div class="col-12 text-center text-white-50 py-5">
                                <i class="bi bi-archive display-1 mb-3 d-block"></i>
                                <h3>Žádné poznámky v archivu.</h3>
                            </div>
                        `;
                    }
                }, 400);
            }
        } else {
            alert(data.message);
        }
    });
}

function deleteNoteAjax(noteId, event) {
    if (event) event.stopPropagation();
    
    if (!confirm('Opravdu chcete tuto poznámku nenávratně smazat?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_note');
    formData.append('note_id', noteId);

    fetch('api/api_note_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById('note-card-' + noteId);
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    card.remove();
                    if (document.querySelectorAll('.note-item').length === 0) {
                        const grid = document.getElementById('notesGrid');
                        grid.innerHTML = `
                            <div class="col-12 text-center text-white-50 py-5">
                                <i class="bi bi-archive display-1 mb-3 d-block"></i>
                                <h3>Žádné poznámky v archivu.</h3>
                            </div>
                        `;
                    }
                }, 300);
            }
        } else {
            alert(data.message);
        }
    });
}

function saveOrder() {
    const grid = document.getElementById('notesGrid');
    const items = grid.querySelectorAll('.note-item');
    const order = [];
    items.forEach((item, index) => {
        order.push({
            id: item.dataset.id,
            order: index
        });
    });
    
    fetch('api/api_notes_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ order: order }),
    })
    .then(response => response.json())
    .then(data => {
        console.log('Order saved:', data);
    });
}

function handleNoteClick(event, note) {
    if (isSortingMode) return;
    openViewNoteModal(note);
}

function openViewNoteModal(note) {
    currentViewedNote = note;
    const titleEl = document.getElementById('viewNoteModalTitle');
    const contentEl = document.getElementById('viewNoteContent');
    const dateEl = document.getElementById('viewNoteDate');
    const tagsWrapper = document.getElementById('viewNoteTags');
    
    titleEl.innerText = note.title;
    contentEl.innerHTML = note.content;
    dateEl.innerText = 'Vytvořeno: ' + new Date(note.created_at).toLocaleString('cs-CZ');
    
    // Set class for styling
    contentEl.className = 'p-3';

    // Tags
    if (tagsWrapper) {
        tagsWrapper.innerHTML = '';
        if (note.tags && note.tags.length > 0) {
            note.tags.forEach(tag => {
                const span = document.createElement('span');
                span.className = 'badge';
                span.style.backgroundColor = tag.color || '#6c757d';
                span.style.fontSize = '0.7rem';
                span.textContent = tag.name;
                tagsWrapper.appendChild(span);
            });
        }
    }
    
    // Highlight
    if (typeof Prism !== 'undefined') {
        const codeBlocks = contentEl.querySelectorAll('pre');
        codeBlocks.forEach(block => Prism.highlightElement(block));
    }

    // Edit button integration
    const editBtn = document.getElementById('editNoteFromViewBtn');
    if (editBtn) {
        editBtn.onclick = () => {
            const viewModalEl = document.getElementById('viewNoteModal');
            const viewModal = bootstrap.Modal.getInstance(viewModalEl);
            if (viewModal) viewModal.hide();
            openEditNoteModal(note);
        };
    }
    
    const myModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('viewNoteModal'));
    
    // Reset AI box
    const insightBox = document.getElementById('aiNoteInsightBox');
    const insightContent = document.getElementById('aiNoteInsightContent');
    if (insightBox) insightBox.classList.add('d-none');
    if (insightContent) insightContent.innerHTML = '';
    
    myModal.show();
}

let aiNoteTypingInterval = null;

function aiNoteAction(action) {
    const content = document.getElementById('viewNoteContent').innerText;
    const insightBox = document.getElementById('aiNoteInsightBox');
    const insightContent = document.getElementById('aiNoteInsightContent');
    const aiBtn = document.getElementById('aiNoteBtn');

    if (!insightBox || !insightContent) return;
    
    // Close dropdown after selection
    if (aiBtn) {
        const dropdownInstance = bootstrap.Dropdown.getInstance(aiBtn);
        if (dropdownInstance) dropdownInstance.hide();
    }

    if (aiNoteTypingInterval) clearInterval(aiNoteTypingInterval);
    
    insightBox.classList.remove('d-none');
    insightContent.innerHTML = '<div class="d-flex align-items-center gap-2 py-2"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="text-white-50">AI přemýšlí...</span></div>';
    if (aiBtn) aiBtn.disabled = true;

    fetch('api/api_ai_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, content: content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            typeWriterNote(data.answer, insightContent, () => {
                // Autokopie do schránky po dokončení generování
                navigator.clipboard.writeText(data.answer).catch(err => console.error("Auto-copy failed", err));
                
                const actionsNeedingApply = ['grammar_check', 'summarize_note', 'format_note', 'structure_note'];
                if (actionsNeedingApply.includes(action)) {
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-sm btn-ai mt-3 d-flex align-items-center gap-2';
                    btn.innerHTML = '<i class="bi bi-check2-all"></i> Použít tento text v poznámce';
                    btn.onclick = (e) => applyAiResultToNote(data.answer, e);
                    insightContent.appendChild(btn);
                }
            });
            insightBox.classList.remove('flash-purple');
            void insightBox.offsetWidth;
            insightBox.classList.add('flash-purple');
        } else {
            insightContent.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + data.message + '</div>';
        }
    })
    .catch(error => {
        insightContent.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Chyba při komunikaci s AI.</div>';
    })
    .finally(() => {
        if (aiBtn) aiBtn.disabled = false;
    });
}

function simpleMarkdownToHtml(text) {
    if (!text) return '';
    text = text.trim();
    
    // Limit multiple empty lines
    text = text.replace(/\n{3,}/g, '\n\n');
    
    const lines = text.split('\n');
    let currentListType = null; // null, 'ul', 'ol'
    let result = '';
    
    lines.forEach(line => {
        let trimmed = line.trim();
        
        // Empty line
        if (trimmed === '') {
            if (currentListType) { result += `</${currentListType}>`; currentListType = null; }
            return;
        }
        
        let content = trimmed;
        let tag = 'p';
        let isListItem = false;
        let newListType = null;

        // Identification of bullets (* or -)
        if (/^[\*\-\+]\s+/.test(trimmed)) {
            tag = 'li';
            isListItem = true;
            newListType = 'ul';
            content = trimmed.replace(/^[\*\-\+]\s+/, '');
        } 
        // Identification of numbering (1. or 1))
        else if (/^\d+[\.\)]\s+/.test(trimmed)) {
            tag = 'li';
            isListItem = true;
            newListType = 'ol';
            content = trimmed.replace(/^\d+[\.\)]\s+/, '');
        }
        // Identification of headers
        else if (trimmed.startsWith('# ')) { tag = 'h3'; content = trimmed.substring(2); }
        else if (trimmed.startsWith('## ')) { tag = 'h4'; content = trimmed.substring(3); }
        else if (trimmed.startsWith('### ')) { tag = 'h5'; content = trimmed.substring(4); }
        
        // Formatting content (bold, italic, code)
        content = content
            .replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>');
            
        if (isListItem) {
            if (currentListType !== newListType) {
                if (currentListType) result += `</${currentListType}>`;
                result += `<${newListType} class="ps-3 mb-2">`;
                currentListType = newListType;
            }
            result += '<li>' + content + '</li>';
        } else {
            if (currentListType) {
                result += `</${currentListType}>`;
                currentListType = null;
            }
            const className = tag.startsWith('h') ? 'mt-3 mb-2' : '';
            result += `<${tag} ${className ? 'class="'+className+'"' : ''}>${content}</${tag}>`;
        }
    });
    
    if (currentListType) result += `</${currentListType}>`;
    return result;
}

function typeWriterNote(text, container, callback) {
    container.innerHTML = '';
    let i = 0;
    const speed = 2;
    let processedHtml = simpleMarkdownToHtml(text);
    let currentHtml = ''; 

    function type() {
        if (i < processedHtml.length) {
            if (processedHtml.charAt(i) === '<') {
                let tagEnd = processedHtml.indexOf('>', i);
                if (tagEnd !== -1) {
                    currentHtml += processedHtml.substring(i, tagEnd + 1);
                    i = tagEnd + 1;
                } else {
                    currentHtml += processedHtml.charAt(i);
                    i++;
                }
            } else {
                currentHtml += processedHtml.charAt(i);
                i++;
            }
            
            container.innerHTML = currentHtml;
            
            aiNoteTypingInterval = setTimeout(type, speed);
            container.scrollTop = container.scrollHeight;
        } else if (callback) {
            callback();
        }
    }
    type();
}

function applyAiResultToNote(markdownText, event) {
    if (!currentViewedNote) return;

    if (!confirm('Opravdu chcete nahradit obsah poznámky tímto vygenerovaným textem?')) return;

    const newContentHtml = simpleMarkdownToHtml(markdownText);
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Aplikuji...';

    const formData = new FormData();
    formData.append('action', 'add_note');
    formData.append('note_id', currentViewedNote.id);
    formData.append('title', currentViewedNote.title);
    formData.append('content', newContentHtml);
    
    // Robust is_locked check
    const isLocked = currentViewedNote.is_locked == 1 || currentViewedNote.is_locked === true || currentViewedNote.is_locked === "1";
    if (isLocked) formData.append('is_locked', '1');
    
    if (currentViewedNote.tags && currentViewedNote.tags.length > 0) {
        currentViewedNote.tags.forEach(tag => {
            formData.append('tags[]', tag.id);
        });
    }

    fetch('api/api_note_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update the view modal
            document.getElementById('viewNoteContent').innerHTML = newContentHtml;
            // Update the stored note object
            currentViewedNote.content = newContentHtml;
            
            // Hide AI box
            document.getElementById('aiNoteInsightBox').classList.add('d-none');
            
            // Update the card on the grid
            const existingCard = document.getElementById('note-card-' + currentViewedNote.id);
            if (existingCard) {
                const temp = document.createElement('div');
                temp.innerHTML = data.html;
                const newCard = temp.firstElementChild;
                existingCard.replaceWith(newCard);
                
                const innerCard = newCard.querySelector('.note-card');
                if (innerCard) {
                    innerCard.classList.add('flash-purple');
                    setTimeout(() => innerCard.classList.remove('flash-purple'), 2000);
                }
            }
        } else {
            alert('Chyba: ' + data.message);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Chyba při komunikaci se serverem.');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}

function copyNoteContent(btn) {
    const content = document.getElementById('viewNoteContent').innerText;
    navigator.clipboard.writeText(content).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = 'copied!';
        btn.classList.replace('btn-outline-light', 'btn-success');
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-outline-light');
        }, 2000);
    });
}

function copyToClipboard(btn, elementId) {
    const content = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(content).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = 'copied!';
        btn.classList.replace('btn-outline-light', 'btn-success');
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-outline-light');
        }, 2000);
    });
}

function openAddNoteModal() {
    const modalEl = document.getElementById('noteModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    document.getElementById('noteModalTitle').innerText = 'Nová poznámka';
    document.getElementById('noteId').value = '';
    document.getElementById('noteTitleInput').value = '';
    document.getElementById('noteContentInput').value = '';
    quill.root.innerHTML = '';
    document.getElementById('noteSubmitBtn').innerText = 'Uložit poznámku';

    // Reset tags and lock
    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => cb.checked = false);
    document.getElementById('noteLockedInput').checked = false;
    
    modal.show();
}

function openEditNoteModal(note) {
    const modalEl = document.getElementById('noteModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    document.getElementById('noteModalTitle').innerText = 'Upravit poznámku';
    document.getElementById('noteId').value = note.id;
    document.getElementById('noteTitleInput').value = note.title;
    document.getElementById('noteContentInput').value = note.content;
    
    // Use clipboard for better HTML handling
    if (quill) {
        quill.root.innerHTML = '';
        quill.clipboard.dangerouslyPasteHTML(0, note.content || '');
    }

    document.getElementById('noteSubmitBtn').innerText = 'Uložit změny';

    // Set tags
    const tagCheckboxes = document.querySelectorAll('#noteForm input[name="tags[]"]');
    tagCheckboxes.forEach(cb => {
        cb.checked = note.tags ? note.tags.some(t => t.id == cb.value) : false;
    });
    
    // Set locked state robustly
    const isLocked = note.is_locked == 1 || note.is_locked === true || note.is_locked === "1";
    document.getElementById('noteLockedInput').checked = isLocked;
    
    modal.show();
}

function generateAiNoteTitle() {
    // Current target from event if available
    const btn = window.event ? window.event.currentTarget : null;
    const content = quill ? quill.root.innerText.trim() : "";

    const target = document.getElementById('noteTitleInput');
    
    if (!content || content === "") {
        alert('Nejdříve vložte obsah poznámky!');
        return;
    }

    let originalHtml = "";
    if (btn) {
        originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> AI';
    }

    fetch('api/api_ai_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'generate_note_title', content: content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            target.value = data.answer.replace(/^\\s*[-*•]\\s*/, '').trim();
            target.classList.remove('flash-purple');
            void target.offsetWidth; // trigger reflow
            target.classList.add('flash-purple');
            setTimeout(() => {
                target.classList.remove('flash-purple');
            }, 2000);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Chyba při komunikaci s AI.');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
}

// Search and Tag filtering for notes
const noteSearchInput = document.getElementById('noteSearch');
const noteTagButtons = document.querySelectorAll('#noteTagFilters .btn');
let currentNoteSearch = localStorage.getItem('archiveNoteSearch') || '';
let currentNoteTag = localStorage.getItem('archiveNoteTag') || 'all';

// Restore initial UI state
if (noteSearchInput) {
    noteSearchInput.value = currentNoteSearch;
}
if (noteTagButtons.length > 0) {
    noteTagButtons.forEach(btn => {
        if (btn.getAttribute('data-tag') === currentNoteTag) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

const filterNotes = () => {
    const notes = document.querySelectorAll('.note-item');
    let delay = 0;
    
    notes.forEach(note => {
        const title = note.querySelector('.card-title').textContent.toLowerCase();
        const content = note.querySelector('.card-text').textContent.toLowerCase();
        const tagsAttr = note.getAttribute('data-tags');
        const tags = tagsAttr ? tagsAttr.toLowerCase().split(',') : [];
        
        const matchesSearch = title.includes(currentNoteSearch) || 
                             content.includes(currentNoteSearch) ||
                             tags.some(t => t.includes(currentNoteSearch));
        
        const matchesTag = currentNoteTag === 'all' || tags.includes(currentNoteTag.toLowerCase());

        if (matchesSearch && matchesTag) {
            note.style.display = 'block';
            note.style.animation = 'none';
            note.offsetHeight; /* trigger reflow */
            note.style.animation = `popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${delay}ms both`;
            delay += 40;
        } else {
            note.style.display = 'none';
            note.style.animation = 'none';
        }
    });
};

// Initial filter application
if (noteSearchInput || noteTagButtons.length > 0) {
    filterNotes();
}

if (noteSearchInput) {
    noteSearchInput.addEventListener('input', function(e) {
        currentNoteSearch = e.target.value.toLowerCase();
        localStorage.setItem('archiveNoteSearch', currentNoteSearch);
        filterNotes();
    });
}

noteTagButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        noteTagButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentNoteTag = btn.getAttribute('data-tag');
        localStorage.setItem('archiveNoteTag', currentNoteTag);
        filterNotes();
    });
});
</script>

<?php include 'includes/footer.php'; ?>
