<?php
require_once __DIR__ . '/config/config.php';

$db = getDB();

$clientIP = getClientIP();
$stmt = $db->prepare("SELECT COUNT(*) FROM visit_logs WHERE ip_address = ? AND DATE(visited_at) = CURDATE()");
$stmt->execute([$clientIP]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $db->prepare("INSERT INTO visit_logs (ip_address, page, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$clientIP, $_SERVER['REQUEST_URI'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
}

$folderId = isset($_GET['folder']) ? (int)$_GET['folder'] : null;

function getFolderPath($db, $folderId) {
    $path = [];
    while ($folderId) {
        $stmt = $db->prepare("SELECT id, name, parent_id FROM folders WHERE id = ?");
        $stmt->execute([$folderId]);
        $folder = $stmt->fetch();
        if ($folder) {
            array_unshift($path, $folder);
            $folderId = $folder['parent_id'];
        } else {
            break;
        }
    }
    return $path;
}

function getSubFolders($db, $parentId) {
    if ($parentId === null) {
        $stmt = $db->prepare("SELECT * FROM folders WHERE parent_id IS NULL ORDER BY sort_order ASC, created_at ASC");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT * FROM folders WHERE parent_id = ? ORDER BY sort_order ASC, created_at ASC");
        $stmt->execute([$parentId]);
    }
    return $stmt->fetchAll();
}

function getFiles($db, $folderId) {
    if ($folderId === null) {
        $stmt = $db->prepare("SELECT * FROM files WHERE folder_id IS NULL AND is_active = 1 ORDER BY created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT * FROM files WHERE folder_id = ? AND is_active = 1 ORDER BY created_at DESC");
        $stmt->execute([$folderId]);
    }
    return $stmt->fetchAll();
}

function getCurrentFolder($db, $folderId) {
    if (!$folderId) return null;
    $stmt = $db->prepare("SELECT * FROM folders WHERE id = ?");
    $stmt->execute([$folderId]);
    return $stmt->fetch();
}

$breadcrumb = getFolderPath($db, $folderId);
$subFolders = getSubFolders($db, $folderId);
$files = getFiles($db, $folderId);
$currentFolder = getCurrentFolder($db, $folderId);

$stmt = $db->query("SELECT COUNT(*) as total FROM files WHERE is_active = 1");
$totalFiles = $stmt->fetch()['total'];
$stmt = $db->query("SELECT SUM(file_size) as total FROM files WHERE is_active = 1");
$usedSize = $stmt->fetch()['total'] ?? 0;
$stmt = $db->query("SELECT COUNT(*) as total FROM folders");
$totalFolders = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(DISTINCT ip_address) as total FROM visit_logs WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$onlineCount = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(DISTINCT ip_address) as total FROM visit_logs");
$totalVisitors = $stmt->fetch()['total'];
$stmt = $db->query("SELECT * FROM files WHERE is_active = 1 ORDER BY download_count DESC LIMIT 5");
$hotFiles = $stmt->fetchAll();
$stmt = $db->query("SELECT * FROM files WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
$recentFiles = $stmt->fetchAll();
$stmt = $db->query("SELECT SUM(download_count) as total FROM files");
$totalDownloads = $stmt->fetch()['total'] ?? 0;
$siteSettings = getSiteSettings();
$siteTitle = $siteSettings['site_title'];
$siteSubtitle = $siteSettings['site_subtitle'];
$siteNotice = $siteSettings['site_notice'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $currentFolder ? htmlspecialchars($currentFolder['name']) . ' - ' : '' ?><?= htmlspecialchars($siteTitle) ?> - <?= htmlspecialchars($siteSubtitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=22">
</head>
<body>
    <div class="header"><?= htmlspecialchars($siteTitle) ?> <span class="subtitle">- <?= htmlspecialchars($siteSubtitle) ?></span></div>
    <div class="status-bar">空间 <?= formatFileSize($usedSize) ?> | 文件 <?= $totalFiles ?> | 目录 <?= $totalFolders ?> | 在线 <?= $onlineCount ?> 人</div>
    <div class="main-wrapper">
        <div class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">网站公告</div>
                <div class="sidebar-content notice-content">
                    <?php foreach (explode("\n", $siteNotice) as $line): ?>
                    <p><?= htmlspecialchars($line) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">导航</div>
                <div class="sidebar-content">
                    <?php if (!empty($breadcrumb)): ?>
                        <a class="folder-link" href="index.php">返回根目录</a>
                        <?php $parentFolder = $breadcrumb[count($breadcrumb) - 1]['parent_id'] ?? null; ?>
                        <?php if ($parentFolder): ?>
                            <a class="folder-link" href="?folder=<?= (int)$parentFolder ?>">返回上级目录</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty">当前已是根目录</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">热门目录</div>
                <div class="sidebar-content">
                    <?php if (empty($subFolders)): ?>
                        <div class="empty">暂无目录</div>
                    <?php else: ?>
                        <div class="folder-list">
                            <?php foreach ($subFolders as $folder): ?>
                                <a href="?folder=<?= (int)$folder['id'] ?>" class="folder-link"><?= htmlspecialchars($folder['name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="content">
            <h2>其他目录</h2>
            <div class="folder-grid">
                <?php if (!empty($subFolders)): ?>
                    <?php foreach ($subFolders as $folder): ?>
                        <a class="folder-card" href="?folder=<?= (int)$folder['id'] ?>">
                            <div class="folder-name"><?= htmlspecialchars($folder['name']) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">暂无目录</div>
                <?php endif; ?>
            </div>
            <h2>文件列表</h2>
            <?php foreach ($files as $file): ?>
                <div><a href="download.php?id=<?= $file['id'] ?>"><?= htmlspecialchars($file['original_name']) ?></a></div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
