<?php
/**
 * 硬盘空间 - 访问统计
 */
require_once __DIR__ . '/../config/config.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// 今日统计 - 按独立IP计算
$stmt = $db->query("SELECT COUNT(DISTINCT ip_address) as total FROM visit_logs WHERE DATE(visited_at) = CURDATE()");
$todayVisits = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM download_logs WHERE DATE(downloaded_at) = CURDATE()");
$todayDownloads = $stmt->fetch()['total'];

// 总统计 - 按独立IP计算
$stmt = $db->query("SELECT COUNT(DISTINCT ip_address) as total FROM visit_logs");
$totalVisits = $stmt->fetch()['total'];

$stmt = $db->query("SELECT SUM(download_count) as total FROM files");
$totalDownloads = $stmt->fetch()['total'] ?? 0;

// 热门下载TOP20
$stmt = $db->query("
    SELECT f.*, fo.name as folder_name 
    FROM files f 
    LEFT JOIN folders fo ON f.folder_id = fo.id 
    WHERE f.is_active = 1 
    ORDER BY f.download_count DESC 
    LIMIT 20
");
$hotFiles = $stmt->fetchAll();

// IP访问排行
$stmt = $db->query("
    SELECT ip_address, COUNT(*) as visit_count, MAX(visited_at) as last_visit
    FROM visit_logs 
    GROUP BY ip_address 
    ORDER BY visit_count DESC 
    LIMIT 20
");
$ipStats = $stmt->fetchAll();

// 下载IP排行
$stmt = $db->query("
    SELECT ip_address, COUNT(*) as download_count, MAX(downloaded_at) as last_download
    FROM download_logs 
    GROUP BY ip_address 
    ORDER BY download_count DESC 
    LIMIT 20
");
$downloadIpStats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>访问统计 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/admin.css?v=3">
</head>
<body>
    <div class="admin-header">
        <div class="logo"><?= SITE_NAME ?> - 管理后台</div>
        <div class="user-info">
            <span>欢迎, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
            <a href="logout.php" class="logout-btn">退出登录</a>
        </div>
    </div>

    <div class="admin-container">
        <div class="admin-sidebar">
            <ul class="admin-menu">
                <li><a href="dashboard.php"><span class="icon">◈</span>仪表盘</a></li>
                <li><a href="folders.php"><span class="icon">◈</span>目录管理</a></li>
                <li><a href="files.php"><span class="icon">◈</span>文件管理</a></li>
                <li><a href="upload.php"><span class="icon">◈</span>上传文件</a></li>
                <li><a href="stats.php" class="active"><span class="icon">◈</span>访问统计</a></li>
                <li><a href="settings.php"><span class="icon">◈</span>系统设置</a></li>
            </ul>
        </div>

        <div class="admin-main">
            <!-- 统计概览 -->
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="number"><?= $todayVisits ?></div>
                    <div class="label">今日访客(IP)</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $totalVisits ?></div>
                    <div class="label">总访客数(IP)</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $todayDownloads ?></div>
                    <div class="label">今日下载</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $totalDownloads ?></div>
                    <div class="label">总下载次数</div>
                </div>
            </div>

            <div class="row">
                <!-- 热门下载 -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">热门下载 TOP20</div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>文件名</th>
                                        <th>下载次数</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($hotFiles)): ?>
                                    <tr><td colspan="3" class="empty">暂无数据</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($hotFiles as $i => $file): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($file['original_name']) ?></td>
                                        <td><?= $file['download_count'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- IP访问排行 -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">访问IP排行</div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>IP地址</th>
                                        <th>访问次数</th>
                                        <th>最后访问</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ipStats)): ?>
                                    <tr><td colspan="4" class="empty">暂无数据</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($ipStats as $i => $ip): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($ip['ip_address']) ?></td>
                                        <td><?= $ip['visit_count'] ?></td>
                                        <td><?= $ip['last_visit'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 下载IP排行 -->
            <div class="card">
                <div class="card-header">下载IP排行</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>IP地址</th>
                                <th>下载次数</th>
                                <th>最后下载</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($downloadIpStats)): ?>
                            <tr><td colspan="4" class="empty">暂无数据</td></tr>
                            <?php else: ?>
                            <?php foreach ($downloadIpStats as $i => $ip): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($ip['ip_address']) ?></td>
                                <td><?= $ip['download_count'] ?></td>
                                <td><?= $ip['last_download'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
