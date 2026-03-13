<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Jen POST požadavky.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'toggle_setting') {
    $key = $_POST['key'] ?? '';
    $value = $_POST['value'] ?? '0';
    
    // Validate key to prevent arbitrary setting updates if needed, 
    // but updateSetting already handles basic safety.
    if (!empty($key)) {
        if (updateSetting($key, $value)) {
            echo json_encode(['status' => 'success', 'message' => 'Nastavení uloženo.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Chyba při ukládání do DB.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chybí klíč nastavení.']);
    }
} elseif ($action === 'save_tag' || $action === 'delete_tag') {
    $type = !empty($_POST['type']) ? $_POST['type'] : 'snippet';

    if ($action === 'save_tag') {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        $name = $_POST['name'] ?? '';
        $color = !empty($_POST['color']) ? $_POST['color'] : null;
        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Název štítku je povinný.']);
            exit;
        }
        $res = saveTag($name, $color, $type, $id);
    } else {
        $id = $_POST['id'] ?? null;
        $res = $id ? deleteTag($id) : false;
    }

    if ($res !== false) {
        $tags = getAllTags($type);
        ob_start();
        include '../includes/tag_list_items.php';
        $html = ob_get_clean();
        
        echo json_encode([
            'status' => 'success', 
            'type' => $type,
            'html' => $html, 
            'message' => 'Štítek byl ' . ($action === 'save_tag' ? 'uložen.' : 'smazán.')
        ]);
    } else {
        $dbError = $conn->error ?? 'Neznámá chyba databáze';
        echo json_encode(['status' => 'error', 'message' => 'Chyba při operaci se štítkem: ' . $dbError]);
    }
} elseif ($action === 'save_language' || $action === 'delete_language') {
    if ($action === 'save_language') {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        $name = $_POST['name'] ?? '';
        $prism_class = $_POST['prism_class'] ?? '';
        
        if (empty($name) || empty($prism_class)) {
            echo json_encode(['status' => 'error', 'message' => 'Název i třída Prism jsou povinné.']);
            exit;
        }
        
        $res = saveLanguage($name, $prism_class, $id);
    } else {
        $id = $_POST['id'] ?? null;
        $res = $id ? deleteLanguage($id) : false;
    }

    if ($res !== false) {
        $languages = getAllLanguages();
        ob_start();
        include '../includes/language_list_items.php';
        $html = ob_get_clean();
        
        echo json_encode([
            'status' => 'success', 
            'html' => $html, 
            'message' => 'Jazyk byl ' . ($action === 'save_language' ? 'uložen.' : 'smazán.')
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při operaci s jazykem.']);
    }
} elseif ($action === 'save_security') {
    $success = true;
    
    // Uložit uživatelské jméno
    if (isset($_POST['app_username']) && !empty($_POST['app_username'])) {
        updateSetting('app_username', $_POST['app_username']);
    }

    // Uložit heslo jen pokud bylo zadáno
    if (isset($_POST['app_password']) && !empty($_POST['app_password'])) {
        $hashed = password_hash($_POST['app_password'], PASSWORD_DEFAULT);
        updateSetting('app_password', $hashed);
    }

    echo json_encode(['status' => 'success', 'message' => 'Přihlašovací údaje uloženy.']);
} elseif ($action === 'save_gemini_config' || $action === 'save_openai_config' || $action === 'save_custom_ai_config' || $action === 'save_ai_provider' || $action === 'save_imap_config' || $action === 'save_smtp_config') {
    $success = true;
    foreach ($_POST as $key => $value) {
        if ($key === 'action') continue;
        if (!updateSetting($key, $value)) {
            $success = false;
        }
    }

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Konfigurace byla uložena.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při ukládání konfigurace.']);
    }
} elseif ($action === 'reset_password') {
    $default_hash = password_hash('admin', PASSWORD_DEFAULT);
    if (updateSetting('app_username', 'admin') && updateSetting('app_password', $default_hash)) {
        echo json_encode(['status' => 'success', 'message' => 'Resetováno na admin / admin.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při resetu.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
}
