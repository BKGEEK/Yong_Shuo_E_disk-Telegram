<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_GET['id'])) {
    die('文件ID不能为空');
}

$db = getDB();
$stmt = $db->prepare('SELECT * FROM files WHERE id = ? AND is_active = 1');
$stmt->execute([(int)$_GET['id']]);
$file = $stmt->fetch();

if (!$file) {
    die('文件不存在');
}

$fileUrl = telegramGetFileUrl($file['telegram_file_id'] ?? '');
if (!$fileUrl) {
    die('无法获取Telegram文件地址');
}

$downloadName = $file['telegram_file_name'] ?: $file['original_name'] ?: 'download';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');

$ch = curl_init($fileUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 300,
    CURLOPT_FAILONERROR => true,
    CURLOPT_HEADER => false,
    CURLOPT_WRITEFUNCTION => function ($ch, $data) {
        echo $data;
        return strlen($data);
    },
]);

if (curl_exec($ch) === false) {
    http_response_code(500);
    echo '文件下载失败：' . curl_error($ch);
}
curl_close($ch);
