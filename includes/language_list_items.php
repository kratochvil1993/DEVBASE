<?php foreach ($languages as $lang): ?>
    <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-light border-opacity-10 px-0">
        <div>
            <span class="fw-bold"><?php echo htmlspecialchars($lang['name']); ?></span>
            <small class="text-white-50 ms-2">(<?php echo htmlspecialchars($lang['prism_class']); ?>)</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" onclick='editLanguage(<?php echo json_encode($lang); ?>)'>
                <i class="bi bi-pencil"></i> 
            </button>
            <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="deleteLanguageAjax(<?php echo $lang['id']; ?>)">
                <i class="bi bi-trash"></i> 
            </button>
        </div>
    </div>
<?php endforeach; ?>
