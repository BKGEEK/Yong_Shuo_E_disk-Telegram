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
        if ($_POST['action'] === 'create' && !empty(trim($_POST['folder_name'] ?? ''))) {
            $folderName = trim($_POST['folder_name']);
            $stmt = $db->prepare('INSERT INTO folders (name, parent_id, sort_order) VALUES (?, ?, ?)');
            $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $stmt->execute([$folderName, $parentId, $sortOrder]);
            $message = '目录已创建';
            $messageType = 'success';
        } elseif ($_POST['action'] === 'delete' && isset($_POST['folder_id'])) {
            $folderId = (int)$_POST['folder_id'];
            $stmt = $db->prepare('DELETE FROM folders WHERE id = ?');
            $stmt->execute([$folderId]);
            $message = '文件夹已删除';
            $messageType = 'success';
        }
    }
}

$stmt = $db->query('SELECT f1.*, (SELECT COUNT(*) FROM files f2 WHERE f2.folder_id = f1.id) AS file_count FROM folders f1 ORDER BY f1.created_at DESC');
$folders = $stmt->fetchAll();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>目录管理 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/admin.css?v=3">
</head>
<body>
<div class="admin-container">
    <div class="admin-sidebar">
        <div class="logo">DG存储库管理</div>
        <ul class="admin-menu">
            <li><a href="dashboard.php"><span class="icon">◈</span>仪表盘</a></li>
            <li><a href="folders.php" class="active"><span class="icon">◈</span>目录管理</a></li>
            <li><a href="files.php"><span class="icon">◈</span>文件管理</a></li>
            <li><a href="upload.php"><span class="icon">◈</span>上传文件</a></li>
            <li><a href="stats.php"><span class="icon">◈</span>访问统计</a></li>
            <li><a href="settings.php"><span class="icon">◈</span>系统设置</a></li>
        </ul>
    </div>
    <div class="admin-main">
        <div class="card">
            <div class="card-header">创建目录</div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <form method="post" style="margin-bottom:20px">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="form-group">
                        <label>目录名称</label>
                        <input type="text" name="folder_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>上级目录</label>
                        <select name="parent_id" class="form-control">
                            <option value="">无</option>
                            <?php foreach ($folders as $folder): ?>
                            <option value="<?= (int)$folder['id'] ?>"><?= htmlspecialchars($folder['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>排序</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <button type="submit" name="action" value="create" class="btn">创建目录</button>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>文件数</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($folders as $folder): ?>
                        <tr>
                            <td><?= (int)$folder['id'] ?></td>
                            <td><?= htmlspecialchars($folder['name']) ?></td>
                            <td><?= (int)$folder['file_count'] ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('确定删除？')" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="folder_id" value="<?= (int)$folder['id'] ?>">
                                    <button type="submit" name="action" value="delete">删除</button>
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
