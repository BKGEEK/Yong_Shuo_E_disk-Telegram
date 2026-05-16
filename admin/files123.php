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
        if ($_POST['action'] === 'delete' && isset($_POST['file_id'])) {
            $fileId = (int)$_POST['file_id'];
            $stmt = $db->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$fileId]);
            $message = '文件已删除';
            $messageType = 'success';
        } elseif ($_POST['action'] === 'toggle' && isset($_POST['file_id'])) {
            $fileId = (int)$_POST['file_id'];
            $stmt = $db->prepare("UPDATE files SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$fileId]);
            $message = '状态已更新';
            $messageType = 'success';
        }
    }
}
