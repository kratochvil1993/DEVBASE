<?php
require_once 'includes/functions.php';

if (getSetting('code_enabled', '1') !== '1') {
    header('Location: index.php');
    exit;
}

// Handle save request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_code') {
    $content = $_POST['content'] ?? '';
    saveScratchpadContent($content);
    header('Location: code.php?saved=1');
    exit;
}

$content = getScratchpadContent();

include 'includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="text-white mb-0"><i class="bi bi-braces me-2"></i> Code Scratchpad</h4>
                    <p class="text-white-50 small mb-0">Rychlý prostor pro váš kód nebo poznámky. Automatické barvy pro různé jazyky.</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if (isset($_GET['saved'])): ?>
                        <div id="saveToast" class="badge bg-success d-flex align-items-center px-3 py-2 me-2" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-check-circle me-1"></i> Uloženo!
                        </div>
                    <?php endif; ?>
                    <button type="button" class="btn btn-add-snipet px-4" onclick="saveCode()">
                        <i class="bi bi-save me-2"></i> Uložit kód
                    </button>
                </div>
            </div>

            <div class="editor-container border border-light border-opacity-10 rounded overflow-hidden shadow-lg">
                <textarea id="codeEditor"><?php echo htmlspecialchars($content); ?></textarea>
            </div>
            
            <div class="mt-3 d-flex justify-content-between align-items-center text-white-50 small">
                <div>
                    <span class="me-3"><i class="bi bi-keyboard me-1"></i> Ctrl+S pro uložení</span>
                    <span><i class="bi bi-info-circle me-1"></i> Podporuje PHP, JS, HTML, CSS, SQL</span>
                </div>
                <div id="charCount">Znaků: 0</div>
            </div>
        </div>
    </div>
</div>

<form id="saveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="save_code">
    <textarea name="content" id="formContent"></textarea>
</form>

<!-- CodeMirror Assets -->
<link rel="stylesheet" href="assets/vendor/codemirror/lib/codemirror.css">
<link rel="stylesheet" href="assets/vendor/codemirror/theme/dracula.css">
<script src="assets/vendor/codemirror/lib/codemirror.js"></script>

<!-- CodeMirror Modes -->
<script src="assets/vendor/codemirror/mode/xml/xml.js"></script>
<script src="assets/vendor/codemirror/mode/javascript/javascript.js"></script>
<script src="assets/vendor/codemirror/mode/css/css.js"></script>
<script src="assets/vendor/codemirror/mode/clike/clike.js"></script>
<script src="assets/vendor/codemirror/mode/php/php.js"></script>
<script src="assets/vendor/codemirror/mode/htmlmixed/htmlmixed.js"></script>

<style>
.CodeMirror {
    height: 70vh; /* Better height on large screens */
    font-size: 15px;
    background: rgba(40, 42, 54, 0.6) !important; /* Semi-transparent Dracula */
    backdrop-filter: blur(4px);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    padding: 10px;
}
.CodeMirror-gutters {
    background: rgba(40, 42, 54, 0.3) !important;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
}
.glass-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
}
@keyframes fadeOut {
    0% { opacity: 1; }
    80% { opacity: 1; }
    100% { opacity: 0; }
}
</style>

<script>
let editor;

document.addEventListener('DOMContentLoaded', function() {
    editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
        lineNumbers: true,
        mode: 'php', // Autodetect? Or just default to PHP since it handles HTML/JS/CSS too
        theme: 'dracula',
        tabSize: 4,
        indentUnit: 4,
        lineWrapping: true,
        viewportMargin: Infinity,
        matchBrackets: true,
        autoCloseBrackets: true
    });

    updateCharCount();
    editor.on('change', updateCharCount);

    // Shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveCode();
        }
    });

    // Language switcher based on content hint could be added here
});

function updateCharCount() {
    const content = editor.getValue();
    document.getElementById('charCount').textContent = 'Znaků: ' + content.length;
}

function saveCode() {
    document.getElementById('formContent').value = editor.getValue();
    document.getElementById('saveForm').submit();
}
</script>

<?php include 'includes/footer.php'; ?>
