<div class="col-md-6 col-lg-4 col-xl-3 snippet-card-wrapper <?php echo ($snippet['is_pinned'] ?? 0) ? 'pinned' : ''; ?> <?php echo ($snippet['is_locked'] ?? 0) ? 'locked' : ''; ?>" data-id="<?php echo $snippet['id']; ?>" id="snippet-card-<?php echo $snippet['id']; ?>">
    <div class="card glass-card h-100 snippet-card" 
         data-tags="<?php echo htmlspecialchars(implode(',', array_column($snippet['tags'] ?? [], 'name'))); ?>" 
         onclick="openViewModal(<?php echo htmlspecialchars(json_encode($snippet), ENT_QUOTES, 'UTF-8'); ?>)">
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title text-white mb-0">
                    <?php if ($snippet['is_locked'] ?? 0): ?>
                        <i class="bi bi-lock-fill me-1 small opacity-50"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($snippet['title']); ?>
                </h5>
                <div class="d-flex gap-2 action-btns-wrapper" onclick="event.stopPropagation()">
                    <button type="button" class="btn btn-sm btn-link <?php echo ($snippet['is_pinned'] ?? 0) ? 'text-warning' : 'text-white-50'; ?> p-0" 
                            onclick="toggleSnippetPin(<?php echo $snippet['id']; ?>)"
                            title="<?php echo ($snippet['is_pinned'] ?? 0) ? 'Odepnout' : 'Připnout'; ?>"
                            id="pin-btn-<?php echo $snippet['id']; ?>">
                        <i class="bi <?php echo ($snippet['is_pinned'] ?? 0) ? 'bi-pin-angle-fill' : 'bi-pin-angle'; ?>"></i>
                    </button>
                    <button class="btn btn-sm btn-link text-white-50 p-0" 
                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($snippet), ENT_QUOTES, 'UTF-8'); ?>)"
                            title="Upravit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" 
                            onclick="deleteSnippetAjax(<?php echo $snippet['id']; ?>)"
                            title="Smazat">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            
            <p class="card-text text-white-50 small mb-3 text-truncate-2">
                <?php echo htmlspecialchars($snippet['description']); ?>
            </p>
            
            <div class="snippet-code-wrapper mb-3 flex-grow-1">
                <button class="btn btn-sm btn-outline-light copy-btn" onclick="event.stopPropagation(); copyToClipboard(this, 'snippet-<?php echo $snippet['id']; ?>')">
                    copy
                </button>
                <pre class="language-<?php echo htmlspecialchars($snippet['prism_class'] ?? 'none'); ?>"><code id="snippet-<?php echo $snippet['id']; ?>" class="language-<?php echo htmlspecialchars($snippet['prism_class'] ?? 'none'); ?>"><?php echo htmlspecialchars($snippet['code']); ?></code></pre>
            </div>
        </div>
    </div>
</div>
