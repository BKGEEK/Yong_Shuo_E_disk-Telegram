<?php
/**
 * 硬盘空间 - 后台登录
 */
require_once __DIR__ . '/../config/config.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin) {
            if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                $error = '账户已被锁定，请稍后再试';
            } elseif (password_verify($password, $admin['password'])) {
                $_SESSION[ADMIN_SESSION_KEY] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];

                $stmt = $db->prepare("UPDATE admins SET login_attempts = 0, locked_until = NULL, last_login = NOW(), last_login_ip = ? WHERE id = ?");
                $stmt->execute([getClientIP(), $admin['id']]);

                header('Location: dashboard.php');
                exit;
            } else {
                $attempts = $admin['login_attempts'] + 1;
                if ($attempts >= LOGIN_MAX_ATTEMPTS) {
                    $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
                    $stmt = $db->prepare("UPDATE admins SET login_attempts = ?, locked_until = ? WHERE id = ?");
                    $stmt->execute([$attempts, $lockedUntil, $admin['id']]);
                    $error = '登录失败次数过多，账户已被锁定15分钟';
                } else {
                    $stmt = $db->prepare("UPDATE admins SET login_attempts = ? WHERE id = ?");
                    $stmt->execute([$attempts, $admin['id']]);
                    $error = '用户名或密码错误';
                }
            }
        } else {
            $error = '用户名或密码错误';
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理登录 - <?= SITE_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "微软雅黑", "Microsoft YaHei", Arial, sans-serif;
            font-size: 13px;
            background: linear-gradient(180deg, #ffffff 0%, #f5f5f5 50%, #e8e8e8 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: linear-gradient(to right, #4a90d9, #5ba0e8, #6bb0f0);
            color: #fff;
            padding: 20px 25px;
            font-size: 22px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.15);
        }
        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .login-box {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 35px;
            width: 350px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .login-title h2 {
            font-size: 20px;
            margin-bottom: 8px;
        }
        .login-title p {
            color: #666;
            font-size: 13px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-size: 13px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: #5ba0e8;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to bottom, #5ba0e8, #4a90d9);
            color: #fff;
            border: 1px solid #3a80c9;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
            font-family: inherit;
        }
        .login-btn:hover {
            background: linear-gradient(to bottom, #6bb0f0, #5ba0e8);
        }
        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 18px;
            font-size: 13px;
        }
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
            color: #333;
            text-decoration: none;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
        }
        .back-btn:hover {
            background: linear-gradient(to bottom, #fff, #f9fafb);
        }
    </style>
</head>
<body>
    <div class="header">
        硬盘空间 - 管理后台
    </div>

    <div class="main">
        <div class="login-box">
            <div class="login-title">
                <h2>管理后台</h2>
                <p>请输入管理员账号登录</p>
            </div>

            <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" required placeholder="请输入用户名">
                </div>
                
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="password" required placeholder="请输入密码">
                </div>

                <button type="submit" class="login-btn">登 录</button>
            </form>

            <a href="../index.php" class="back-btn">返回前台</a>
        </div>
    </div>
</body>
</html>
