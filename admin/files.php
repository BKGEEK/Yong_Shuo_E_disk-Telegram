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
            $stmt = $db->prepare('DELETE FROM files WHERE id = ?');
            $stmt->execute([$fileId]);
            $message = '仅数据库记录已删除';
            $messageType = 'success';
        } elseif ($_POST['action'] === 'toggle' && isset($_POST['file_id'])) {
            $fileId = (int)$_POST['file_id'];
            $stmt = $db->prepare('UPDATE files SET is_active = NOT is_active WHERE id = ?');
            $stmt->execute([$fileId]);
            $message = '状态已更新';
            $messageType = 'success';
        }
    }
}

$stmt = $db->query('SELECT f.*, fo.name AS folder_name FROM files f LEFT JOIN folders fo ON f.folder_id = fo.id ORDER BY f.created_at DESC');
$files = $stmt->fetchAll();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件管理 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/admin.css?v=3">
</head>
<body>
<div class="admin-container">
    <div class="admin-sidebar">
        <div class="logo">DG存储库管理</div>
        <ul class="admin-menu">
            <li><a href="dashboard.php"><span class="icon">◈</span>仪表盘</a></li>
            <li><a href="folders.php"><span class="icon">◈</span>目录管理</a></li>
            <li><a href="files.php" class="active"><span class="icon">◈</span>文件管理</a></li>
            <li><a href="upload.php"><span class="icon">◈</span>上传文件</a></li>
            <li><a href="stats.php"><span class="icon">◈</span>访问统计</a></li>
            <li><a href="settings.php"><span class="icon">◈</span>系统设置</a></li>
        </ul>
    </div>
    <div class="admin-main">
        <div class="card">
            <div class="card-header">文件管理</div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>文件名</th>
                            <th>分类</th>
                            <th>大小</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?= (int)$file['id'] ?></td>
                            <td><?= htmlspecialchars($file['original_name']) ?></td>
                            <td><?= htmlspecialchars($file['folder_name'] ?: '根目录') ?></td>
                            <td><?= htmlspecialchars(formatFileSize((int)$file['file_size'])) ?></td>
                            <td><?= $file['is_active'] ? '启用' : '禁用' ?></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="file_id" value="<?= (int)$file['id'] ?>">
                                    <button type="submit" name="action" value="toggle">切换</button>
                                </form>
                                <form method="post" style="display:inline" onsubmit="return confirm('确定只删除数据库记录？不会删除TG文件')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="file_id" value="<?= (int)$file['id'] ?>">
                                    <button type="submit" name="action" value="delete">删除记录</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
