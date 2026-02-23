<?php
require_once 'includes/functions.php';

if (getSetting('code_enabled', '1') !== '1') {
    header('Location: index.php');
    exit;
}

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'add') {
        $new_id = createScratchpad('Draft ' . (count(getAllScratchpads()) + 1));
        header("Location: code.php?id=$new_id");
        exit;
    }
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        deleteScratchpad($_GET['id']);
        header('Location: code.php');
        exit;
    }
}

// Handle save request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_code') {
    $content = $_POST['content'] ?? '';
    $id = $_POST['id'] ?? null;
    if ($id) {
        saveScratchpadContent($content, $id);
        
        // Handle rename if provided
        if (isset($_POST['name']) && !empty($_POST['name'])) {
            renameScratchpad($id, $_POST['name']);
        }
        
        header('Location: code.php?id=' . $id . '&saved=1');
        exit;
    }
}

$scratchpads = getAllScratchpads();
$active_id = isset($_GET['id']) ? (int)$_GET['id'] : ($scratchpads[0]['id'] ?? null);

// If active ID is not in list (e.g. deleted), fallback to first
$active_pad = null;
foreach ($scratchpads as $pad) {
    if ($pad['id'] == $active_id) {
        $active_pad = $pad;
        break;
    }
}
if (!$active_pad && !empty($scratchpads)) {
    $active_pad = $scratchpads[0];
    $active_id = $active_pad['id'];
}

$content = $active_pad ? $active_pad['content'] : '';
$pad_name = $active_pad ? $active_pad['name'] : 'Draft';

include 'includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="flex-grow-1 me-4">
                    <div class="d-flex align-items-center mb-1">
                        <h4 class="text-white mb-0 me-3"><i class="bi bi-braces me-2"></i> </h4>
                        <input type="text" id="padName" class="form-control-plaintext text-white h4 mb-0 fw-bold p-0" 
                               value="<?php echo htmlspecialchars($pad_name); ?>" placeholder="Název draftu..."
                               style="border: none; outline: none; background: transparent;">
                    </div>                   
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (isset($_GET['saved'])): ?>
                        <div id="saveToast" class="badge bg-success d-flex align-items-center px-3 py-2 me-2" style="animation: fadeOut 3s forwards;">
                            <i class="bi bi-check-circle me-1"></i> Uloženo!
                        </div>
                    <?php endif; ?>
                    <button type="button" class="btn btn-copy px-3" onclick="copyCode(this)">
                        <i class="bi bi-clipboard me-2"></i> copy
                    </button>
                    <button type="button" class="btn btn-add-snipet px-3" onclick="saveCode()">
                        <i class="bi bi-save me-2"></i> Uložit
                    </button>
                </div>
            </div>

            <!-- Tab Bar -->
            <div class="d-flex align-items-center mb-0 overflow-auto tab-container px-1">
                <?php foreach ($scratchpads as $pad): ?>
                    <div class="nav-tab-item <?php echo $pad['id'] == $active_id ? 'active' : ''; ?> me-1">
                        <a href="code.php?id=<?php echo $pad['id']; ?>" class="nav-tab-link py-2 px-3">
                            <i class="bi bi-file-earmark-code me-1"></i>
                            <?php echo htmlspecialchars($pad['name']); ?>
                        </a>
                        <?php if (count($scratchpads) > 1): ?>
                            <button type="button" class="btn-tab-close ms-0" onclick="confirmDelete(<?php echo $pad['id']; ?>, '<?php echo addslashes($pad['name']); ?>')">
                                <i class="bi bi-x"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <a href="code.php?action=add" class="btn btn-add-tab ms-1" title="Nový draft">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>

            <div class="editor-container border border-light border-opacity-10 rounded-bottom overflow-hidden shadow-lg" style="border-top-left-radius: 0 !important; border-top-right-radius: 0 !important;">
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
    <input type="hidden" name="id" value="<?php echo $active_id; ?>">
    <input type="hidden" name="name" id="formPadName">
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
.tab-container::-webkit-scrollbar {
    height: 3px;
}
.tab-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}
.nav-tab-item {
    display: flex;
    align-items: center;
    background: rgba(40, 42, 54, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-bottom: none;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    padding: 0 4px 0 0;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.nav-tab-item:hover {
    background: rgba(255, 255, 255, 0.08);
}
.nav-tab-item.active {
    background: rgba(142, 84, 233, 0.3);
    border-color: rgba(255, 255, 255, 0.2);
    border-bottom: 2px solid #8e54e9;
}
.nav-tab-link {
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.85rem;
    display: block;
}
.nav-tab-item.active .nav-tab-link {
    color: #fff;
    font-weight: 500;
}
.btn-tab-close {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.3);
    font-size: 1rem;
    padding: 0 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
}
.btn-tab-close:hover {
    color: #ff5555;
    background: rgba(255, 85, 85, 0.1);
}
.btn-add-tab {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.4);
    border-radius: 5px;
    padding: 4px 8px;
    transition: all 0.2s ease;
}
.btn-add-tab:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
}
.CodeMirror {
    height: 65vh;
    font-size: 15px;
    background: rgba(40, 42, 54, 0.6) !important;
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
.glass-card:hover {
    transform: none !important;
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
        mode: 'php',
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
});

function updateCharCount() {
    const content = editor.getValue();
    document.getElementById('charCount').textContent = 'Znaků: ' + content.length;
}

function saveCode() {
    document.getElementById('formContent').value = editor.getValue();
    document.getElementById('formPadName').value = document.getElementById('padName').value;
    document.getElementById('saveForm').submit();
}

function confirmDelete(id, name) {
    if (confirm('Opravdu chcete smazat draft "' + name + '"?')) {
        window.location.href = 'code.php?action=delete&id=' + id;
    }
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
