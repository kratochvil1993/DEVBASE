<?php foreach ($tags as $tag): ?>
    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0 <?php echo $type; ?>-tag-row" data-id="<?php echo $tag['id']; ?>">
        <span>
            <?php if (!empty($tag['color'])): ?>
                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: #fff;">
            <?php else: ?>
                <span class="badge bg-secondary">
            <?php endif; ?>
            <?php echo htmlspecialchars($tag['name']); ?>
            </span>
        </span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" 
                    onclick='<?php echo ($type === "snippet" ? "editTag" : ($type === "note" ? "editNoteTag" : "editTodoTag")); ?>(<?php echo json_encode($tag); ?>)'>
                <i class="bi bi-pencil"></i> 
            </button>
            <div class="d-inline">
                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="deleteTagAjax(<?php echo $tag['id']; ?>, '<?php echo $type; ?>')">
                    <i class="bi bi-trash"></i> 
                </button>
            </div>
        </div>
    </div>
<?php endforeach; ?>
