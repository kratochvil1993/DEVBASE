<div class="col-md-4 col-lg-6 note-item <?php echo $note['is_pinned'] ? 'pinned' : ''; ?> <?php echo $note['is_locked'] ? 'locked' : ''; ?>" data-id="<?php echo $note['id']; ?>" id="note-card-<?php echo $note['id']; ?>" data-tags="<?php echo htmlspecialchars($tagData); ?>">
    <div class="card glass-card h-100 note-card" onclick="handleNoteClick(event, <?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title text-white mb-0 text-truncate">
                    <?php if ($note['is_locked']): ?>
                        <i class="bi bi-lock-fill me-1 small opacity-50"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($note['title']); ?>
                </h5>
                <div class="d-flex gap-2 delete-btn-wrapper" onclick="event.stopPropagation()">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_pin">
                        <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-link <?php echo $note['is_pinned'] ? 'text-warning' : 'text-white-50'; ?> p-0" title="<?php echo $note['is_pinned'] ? 'Odepnout' : 'Připnout'; ?>">
                            <i class="bi <?php echo $note['is_pinned'] ? 'bi-pin-angle-fill' : 'bi-pin-angle'; ?>"></i>
                        </button>
                    </form>
                    <button class="btn btn-sm btn-link text-white-50 p-0 edit-icon" 
                            onclick="openEditNoteModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)"
                            title="Upravit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" class="d-inline" title="Archivovat">
                        <input type="hidden" name="action" value="archive_note">
                        <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-link text-warning p-0">
                            <i class="bi bi-archive"></i>
                        </button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tuto poznámku smazat?');">
                        <input type="hidden" name="action" value="delete_note">
                        <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Smazat">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-text text-white-50 small mb-0 quill-preview" style="display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical; overflow: hidden;">
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
