<tr class="manage-note-row"
    data-id="<?php echo $note['id']; ?>"
    data-title="<?php echo strtolower(htmlspecialchars($note['title'])); ?>"
    data-content="<?php echo strtolower(htmlspecialchars($note['content'])); ?>"
    data-tags="<?php echo strtolower(htmlspecialchars(implode(',', array_column($note['tags'], 'name')))); ?>">
    <td class="px-4 py-3"><span class="text-white-50 small">#<?php echo $note['id']; ?></span></td>
    <td class="px-4 py-3">
        <?php if ($note['is_pinned']): ?>
            <i class="bi bi-pin-angle-fill text-warning" title="Připnuto"></i>
        <?php else: ?>
            <i class="bi bi-pin-angle text-white-50 opacity-25" title="Nepřipnuto"></i>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3 fw-medium">
        <?php echo htmlspecialchars($note['title']); ?>
        <div class="small text-white-50 fw-light mt-1 text-truncate" style="max-width: 350px;">
            <?php echo htmlspecialchars(substr(strip_tags($note['content']), 0, 100)) . (strlen(strip_tags($note['content'])) > 100 ? '...' : ''); ?>
        </div>
    </td>
    <td class="px-4 py-3">
        <div class="d-flex flex-wrap gap-1">
            <?php if (empty($note['tags'])): ?>
                <span class="text-white-50 small fst-italic">Bez štítku</span>
            <?php else: ?>
                <?php foreach ($note['tags'] as $tag): ?>
                    <span class="badge tag-badge fw-normal"
                          <?php if (!empty($tag['color'])) echo 'style="background-color: ' . htmlspecialchars($tag['color']) . '; color: #fff; border-color: ' . htmlspecialchars($tag['color']) . ';"'; ?>>
                        <?php echo htmlspecialchars($tag['name']); ?>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </td>
    <td class="px-4 py-3 text-end text-nowrap">
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="toggle_pin">
            <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
            <button type="submit" class="btn btn-sm btn-outline-light border-0 px-2" title="<?php echo $note['is_pinned'] ? 'Odepnout' : 'Připnout'; ?>">
                <i class="bi bi-pin-angle<?php echo $note['is_pinned'] ? '-fill text-warning' : ''; ?>"></i>
            </button>
        </form>
        <button class="btn btn-sm btn-outline-light border-0 px-2" 
                onclick='openViewNoteManageModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)'
                title="Zobrazit poznámku">
            <i class="bi bi-eye"></i>
        </button>
        <button class="btn btn-sm btn-outline-light border-0 px-2" 
                onclick='openEditNoteManageModal(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)'
                title="Upravit">
            <i class="bi bi-pencil"></i>
        </button>
        <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tuto poznámku smazat?');">
            <input type="hidden" name="action" value="delete_note">
            <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Smazat">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    </td>
</tr>
