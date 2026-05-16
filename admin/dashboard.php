<?php
/**
 * 硬盘空间 - 后台仪表盘
 */
require_once __DIR__ . '/../config/config.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// 统计数据
$stmt = $db->query("SELECT COUNT(*) as total FROM files WHERE is_active = 1");
$totalFiles = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM folders");
$totalFolders = $stmt->fetch()['total'];

$stmt = $db->query("SELECT SUM(download_count) as total FROM files");
$totalDownloads = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(DISTINCT ip_address) as total FROM visit_logs WHERE DATE(visited_at) = CURDATE()");
$todayVisits = $stmt->fetch()['total'];

// 热门下载
$stmt = $db->query("SELECT * FROM files WHERE is_active = 1 ORDER BY download_count DESC LIMIT 10");
$hotFiles = $stmt->fetchAll();

// 最近下载
$recentDownloads = [];
try {
    $stmt = $db->query("SELECT dl.*, f.original_name FROM download_logs dl JOIN files f ON dl.file_id = f.id ORDER BY dl.downloaded_at DESC LIMIT 10");
    $recentDownloads = $stmt->fetchAll();
} catch (Exception $e) {}

// 文件类型统计
$stmt = $db->query("SELECT extension, COUNT(*) as count FROM files WHERE is_active = 1 GROUP BY extension ORDER BY count DESC LIMIT 10");
$typeStats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/admin.css?v=3">
</head>
<body>
    <div class="admin-header">
        <div class="logo">硬盘空间 - 管理后台</div>
        <div class="user-info">
            <span>欢迎, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
            <a href="logout.php" class="logout-btn">退出登录</a>
        </div>
    </div>

    <div class="admin-container">
        <div class="admin-sidebar">
            <ul class="admin-menu">
                <li><a href="dashboard.php" class="active"><span class="icon">◈</span>仪表盘</a></li>
                <li><a href="folders.php"><span class="icon">◈</span>目录管理</a></li>
                <li><a href="files.php"><span class="icon">◈</span>文件管理</a></li>
                <li><a href="upload.php"><span class="icon">◈</span>上传文件</a></li>
                <li><a href="stats.php"><span class="icon">◈</span>访问统计</a></li>
                <li><a href="settings.php"><span class="icon">◈</span>系统设置</a></li>
            </ul>
        </div>

        <div class="admin-main">
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="number"><?= $totalFiles ?></div>
                    <div class="label">文件总数</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $totalFolders ?></div>
                    <div class="label">目录数量</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $totalDownloads ?></div>
                    <div class="label">总下载次数</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $todayVisits ?></div>
                    <div class="label">今日访客</div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">热门下载 TOP10</div>
                        <div class="card-body">
                            <table class="table">
                                <tr><th>文件名</th><th width="80">下载次数</th></tr>
                                <?php if (empty($hotFiles)): ?>
                                <tr><td colspan="2" class="empty">暂无数据</td></tr>
                                <?php else: ?>
                                <?php foreach ($hotFiles as $file): ?>
                                <tr>
                                    <td><?= htmlspecialchars($file['original_name']) ?></td>
                                    <td><?= $file['download_count'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="card">
                        <div class="card-header">最近下载记录</div>
                        <div class="card-body">
                            <table class="table">
                                <tr><th>文件名</th><th width="100">IP地址</th></tr>
                                <?php if (empty($recentDownloads)): ?>
                                <tr><td colspan="2" class="empty">暂无数据</td></tr>
                                <?php else: ?>
                                <?php foreach ($recentDownloads as $dl): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dl['original_name']) ?></td>
                                    <td><?= $dl['ip_address'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">文件类型统计</div>
                <div class="card-body">
                    <table class="table">
                        <tr><th>文件类型</th><th>文件数量</th></tr>
                        <?php if (empty($typeStats)): ?>
                        <tr><td colspan="2" class="empty">暂无数据</td></tr>
                        <?php else: ?>
                        <?php foreach ($typeStats as $stat): ?>
                        <tr>
                            <td><?= strtoupper($stat['extension'] ?: '未知') ?></td>
                            <td><?= $stat['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
