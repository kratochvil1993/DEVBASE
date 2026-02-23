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
                    <button type="button" class="btn btn-copy px-4 me-2" onclick="copyCode(this)">
                        <i class="bi bi-clipboard me-2"></i> copy
                    </button>
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
                    <span><i class="bi bi-info-circle me-1"></i> Podporuje PHP, JS, HTML, CSS, SQL, Bash</span>
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
<link rel="stylesheet" href="assets/vendor/codemirror/addon/hint/show-hint.css">
<link rel="stylesheet" href="assets/vendor/codemirror/addon/dialog/dialog.css">
<link rel="stylesheet" href="assets/vendor/codemirror/addon/fold/foldgutter.css">
<script src="assets/vendor/codemirror/lib/codemirror.js"></script>

<!-- CodeMirror Addons -->
<script src="assets/vendor/codemirror/addon/edit/closebrackets.js"></script>
<script src="assets/vendor/codemirror/addon/edit/closetag.js"></script>
<script src="assets/vendor/codemirror/addon/hint/show-hint.js"></script>
<script src="assets/vendor/codemirror/addon/hint/xml-hint.js"></script>
<script src="assets/vendor/codemirror/addon/hint/javascript-hint.js"></script>
<script src="assets/vendor/codemirror/addon/hint/html-hint.js"></script>
<script src="assets/vendor/codemirror/addon/hint/css-hint.js"></script>
<script src="assets/vendor/codemirror/addon/search/search.js"></script>
<script src="assets/vendor/codemirror/addon/search/searchcursor.js"></script>
<script src="assets/vendor/codemirror/addon/search/jump-to-line.js"></script>
<script src="assets/vendor/codemirror/addon/dialog/dialog.js"></script>
<script src="assets/vendor/codemirror/addon/fold/foldcode.js"></script>
<script src="assets/vendor/codemirror/addon/fold/foldgutter.js"></script>
<script src="assets/vendor/codemirror/addon/fold/brace-fold.js"></script>
<script src="assets/vendor/codemirror/addon/fold/xml-fold.js"></script>

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
.CodeMirror-foldgutter {
    width: 0.7em;
}
.CodeMirror-foldgutter-open, .CodeMirror-foldgutter-folded {
    cursor: pointer;
    color: #6272a4;
}
.CodeMirror-hints {
    background: #282a36 !important;
    border: 1px solid #44475a !important;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.5);
}
.CodeMirror-hint {
    color: #f8f8f2 !important;
    padding: 4px 10px !important;
}
li.CodeMirror-hint-active {
    background: #44475a !important;
    color: #f8f8f2 !important;
}
.glass-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
}
.btn-copy {
    background: rgba(142, 84, 233, 0.15);
    border: 1px solid rgba(142, 84, 233, 0.4);
    color: #fff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-copy:hover {
    background: rgba(142, 84, 233, 0.3);
    border-color: rgba(142, 84, 233, 0.6);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(142, 84, 233, 0.2);
}
.btn-copy:active {
    transform: translateY(0);
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
        autoCloseBrackets: true,
        autoCloseTags: true,
        foldGutter: true,
        gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
        extraKeys: {
            "Ctrl-Space": "autocomplete",
            "Ctrl-Q": function(cm){ cm.foldCode(cm.getCursor()); }
        }
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

function copyCode(btn) {
    const content = editor.getValue();
    navigator.clipboard.writeText(content).then(() => {
        const originalHtml = btn.innerHTML;
        
        btn.innerHTML = '<i class="bi bi-check2 me-2"></i> Zkopírováno!';
        btn.classList.replace('btn-copy', 'btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.replace('btn-success', 'btn-copy');
        }, 2000);
    }).catch(err => {
        console.error('Chyba při kopírování: ', err);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
