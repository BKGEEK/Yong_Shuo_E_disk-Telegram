<?php
require_once __DIR__ . '/../config/config.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        $message = '安全验证失败';
        $messageType = 'error';
    } else {
        $fileId = (int)($_POST['file_id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM files WHERE id = ?');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if (!$file) {
            $message = '文件不存在';
            $messageType = 'error';
        } else {
            if (isset($_FILES['new_file']) && $_FILES['new_file']['error'] === UPLOAD_ERR_OK) {
                $uploadedFile = $_FILES['new_file'];
                $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                    $message = '不支持的文件类型';
                    $messageType = 'error';
                } else {
                    $telegramResult = telegramSendDocument($uploadedFile['tmp_name'], $uploadedFile['name']);
                    if (empty($telegramResult['ok'])) {
                        $message = 'Telegram上传失败：' . ($telegramResult['description'] ?? '未知错误');
                        $messageType = 'error';
                    } else {
                        $resultData = $telegramResult['result'] ?? [];
                        $document = $resultData['document'] ?? [];
                        $telegramFileId = $document['file_id'] ?? '';
                        $telegramUniqueId = $document['file_unique_id'] ?? '';
                        $telegramMessageId = $resultData['message_id'] ?? null;
                        $telegramChatId = $resultData['chat']['id'] ?? TELEGRAM_CHAT_ID;

                        if ($telegramFileId === '') {
                            $message = 'Telegram返回数据缺失';
                            $messageType = 'error';
                            return;
                        }

                        $stmt = $db->prepare('UPDATE files SET original_name = ?, stored_name = ?, file_path = ?, file_size = ?, file_type = ?, extension = ?, storage_type = ?, telegram_file_id = ?, telegram_unique_id = ?, telegram_message_id = ?, telegram_chat_id = ?, telegram_file_name = ?, telegram_file_size = ?, telegram_mime_type = ? WHERE id = ?');
                        $stmt->execute([
                            $uploadedFile['name'],
                            $uploadedFile['name'],
                            $telegramFileId,
                            $uploadedFile['size'],
                            $uploadedFile['type'] ?? 'application/octet-stream',
                            $ext,
                            'telegram',
                            $telegramFileId,
                            $telegramUniqueId,
                            $telegramMessageId,
                            $telegramChatId,
                            $document['file_name'] ?? $uploadedFile['name'],
                            $document['file_size'] ?? $uploadedFile['size'],
                            $document['mime_type'] ?? ($uploadedFile['type'] ?? 'application/octet-stream'),
                            $fileId
                        ]);
                        $message = '文件更新成功';
                        $messageType = 'success';
                    }
                }
            }
        }
    }
}
