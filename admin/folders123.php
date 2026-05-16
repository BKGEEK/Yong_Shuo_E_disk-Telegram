<?php
require_once __DIR__ . '/../config/config.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verifyCSRFToken($csrf)) {
        if ($_POST['action'] === 'delete' && isset($_POST['folder_id'])) {
            $folderId = (int)$_POST['folder_id'];
            $stmt = $db->prepare("DELETE FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $message = '文件夹已删除';
            $messageType = 'success';
        }
    }
}
