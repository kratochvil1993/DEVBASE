<tr class="manage-row manage-snippet-row border-bottom border-light border-opacity-10"
    data-id="<?php echo $snippet['id']; ?>"
    data-title="<?php echo strtolower(htmlspecialchars($snippet['title'])); ?>"
    data-desc="<?php echo strtolower(htmlspecialchars($snippet['description'] ?? '')); ?>"
    data-tags="<?php echo strtolower(htmlspecialchars(implode(',', array_column($snippet['tags'], 'name')))); ?>">
    <td class="px-4 py-3"><span class="text-white-50 small">#<?php echo $snippet['id']; ?></span></td>
    <td class="px-4 py-3">
        <?php if ($snippet['is_pinned']): ?>
            <i class="bi bi-pin-angle-fill text-warning" title="Připnuto"></i>
        <?php else: ?>
            <i class="bi bi-pin-angle text-white-50 opacity-25" title="Nepřipnuto"></i>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3 fw-medium">
        <?php echo htmlspecialchars($snippet['title']); ?>
        <?php if(!empty($snippet['description'])): ?>
            <div class="small text-white-50 fw-light mt-1 text-truncate" style="max-width: 350px;">
                <?php echo htmlspecialchars($snippet['description']); ?>
            </div>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3">
        <?php if (!empty($snippet['language_name'])): ?>
            <span class="badge border border-light border-opacity-25 text-white fw-normal font-monospace">
                <?php echo htmlspecialchars($snippet['language_name']); ?>
            </span>
        <?php else: ?>
            <span class="text-white-50 small fst-italic">Bez jazyka</span>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3">
        <div class="d-flex flex-wrap gap-1">
            <?php if (empty($snippet['tags'])): ?>
                <span class="text-white-50 small fst-italic">Bez štítku</span>
            <?php else: ?>
                <?php foreach ($snippet['tags'] as $tag): ?>
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
            <input type="hidden" name="snippet_id" value="<?php echo $snippet['id']; ?>">
            <button type="submit" class="btn btn-sm btn-outline-light border-0 px-2" title="<?php echo $snippet['is_pinned'] ? 'Odepnout' : 'Připnout'; ?>">
                <i class="bi bi-pin-angle<?php echo $snippet['is_pinned'] ? '-fill text-warning' : ''; ?>"></i>
            </button>
        </form>
        <button class="btn btn-sm btn-outline-light border-0 px-2" 
                onclick='openViewModal(<?php echo htmlspecialchars(json_encode($snippet), ENT_QUOTES, 'UTF-8'); ?>)'
                title="Zobrazit snipet">
            <i class="bi bi-eye"></i>
        </button>
        <button class="btn btn-sm btn-outline-light border-0 px-2" 
                onclick='openEditModal(<?php echo htmlspecialchars(json_encode($snippet), ENT_QUOTES, 'UTF-8'); ?>)'
                title="Upravit">
            <i class="bi bi-pencil"></i>
        </button>
        <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete tento snipet smazat?');">
            <input type="hidden" name="action" value="delete_snippet">
            <input type="hidden" name="snippet_id" value="<?php echo $snippet['id']; ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Smazat">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    </td>
</tr>
