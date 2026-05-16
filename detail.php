<?php
require_once __DIR__ . '/config/config.php';

$db = getDB();
$fileId = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM files WHERE id = ? AND is_active = 1');
$stmt->execute([$fileId]);
$file = $stmt->fetch();
if (!$file) { die('文件不存在'); }

