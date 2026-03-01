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

    if ($res) {
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
        echo json_encode(['status' => 'error', 'message' => 'Chyba při operaci se štítkem.']);
    }
} elseif ($action === 'save_gemini_config' || $action === 'save_openai_config' || $action === 'save_security' || $action === 'save_ai_provider') {
    $success = true;
    foreach ($_POST as $key => $value) {
        if ($key === 'action' || $key === 'app_password_confirm') continue;
        
        $val = $value;
        if ($key === 'app_password') {
            if (empty($value)) continue;
            $val = password_hash($value, PASSWORD_DEFAULT);
            updateSetting('security_enabled', '1');
        }

        if (!updateSetting($key, $val)) {
            $success = false;
        }
    }

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Konfigurace byla uložena.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při ukládání konfigurace.']);
    }
} elseif ($action === 'reset_password') {
    if (updateSetting('app_password', '') && updateSetting('security_enabled', '0')) {
        echo json_encode(['status' => 'success', 'message' => 'Zámek smazán.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při mazání hesla.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
}
