<?php
/**
 * 硬盘空间 - 系统设置
 */
require_once __DIR__ . '/../config/config.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$message = '';
$messageType = '';

// 获取当前设置
$siteSettings = getSiteSettings();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (verifyCSRFToken($csrf)) {
        if ($_POST['action'] === 'change_password') {
            $oldPassword = $_POST['old_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            
            if (!password_verify($oldPassword, $admin['password'])) {
                $message = '原密码错误';
                $messageType = 'error';
            } elseif (strlen($newPassword) < 6) {
                $message = '新密码长度至少6位';
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = '两次输入的密码不一致';
                $messageType = 'error';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                $message = '密码修改成功';
                $messageType = 'success';
            }
        } elseif ($_POST['action'] === 'save_site_settings') {
            $siteTitle = trim($_POST['site_title'] ?? '');
            $siteSubtitle = trim($_POST['site_subtitle'] ?? '');
            $siteNotice = trim($_POST['site_notice'] ?? '');
            
            if ($siteTitle) {
                saveSiteSetting('site_title', $siteTitle);
                saveSiteSetting('site_subtitle', $siteSubtitle);
                saveSiteSetting('site_notice', $siteNotice);
                $message = '网站设置保存成功';
                $messageType = 'success';
                // 刷新设置
                $siteSettings = getSiteSettings();
            } else {
                $message = '网站标题不能为空';
                $messageType = 'error';
            }
        }
    }
}

// 获取系统信息
$phpVersion = phpversion();
$mysqlVersion = $db->query("SELECT VERSION()")->fetchColumn();
$uploadMaxSize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');

// 获取存储统计
$stmt = $db->query("SELECT SUM(file_size) as total FROM files");
$totalStorage = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM files");
$totalFiles = $stmt->fetch()['total'];

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - <?= SITE_NAME ?></title>
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
                <li><a href="stats.php"><span class="icon">◈</span>访问统计</a></li>
                <li><a href="settings.php" class="active"><span class="icon">◈</span>系统设置</a></li>
            </ul>
        </div>

        <div class="admin-main">
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- 网站设置 -->
            <div class="card">
                <div class="card-header">网站设置</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="save_site_settings">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>网站标题</label>
                                    <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($siteSettings['site_title']) ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>副标题</label>
                                    <input type="text" name="site_subtitle" class="form-control" value="<?= htmlspecialchars($siteSettings['site_subtitle']) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>网站公告</label>
                            <textarea name="site_notice" class="form-control" style="max-width:100%;min-height:100px;"><?= htmlspecialchars($siteSettings['site_notice']) ?></textarea>
                        </div>
                        <button type="submit" class="btn">保存设置</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <!-- 系统信息 -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">系统信息</div>
                        <div class="card-body">
                            <table class="table">
                                <tr><td>PHP版本</td><td><?= $phpVersion ?></td></tr>
                                <tr><td>MySQL版本</td><td><?= $mysqlVersion ?></td></tr>
                                <tr><td>上传限制</td><td><?= $uploadMaxSize ?></td></tr>
                                <tr><td>POST限制</td><td><?= $postMaxSize ?></td></tr>
                                <tr><td>文件总数</td><td><?= $totalFiles ?></td></tr>
                                <tr><td>存储空间</td><td><?= formatFileSize($totalStorage) ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 修改密码 -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">修改密码</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-group">
                                    <label>原密码</label>
                                    <input type="password" name="old_password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>新密码</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>确认密码</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn">修改密码</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 安全提示 -->
            <div class="card">
                <div class="card-header">安全提示</div>
                <div class="card-body">
                    <div class="alert alert-warning">1. 请定期修改管理员密码，建议使用强密码（包含大小写字母、数字和特殊字符）</div>
                    <div class="alert alert-warning">2. 请确保 uploads 目录不可直接执行PHP文件</div>
                    <div class="alert alert-warning">3. 建议配置HTTPS以保护数据传输安全</div>
                    <div class="alert alert-warning">4. 定期备份数据库和上传文件</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
