<?php
/**
 * 重置管理员密码
 * 访问后删除此文件
 */
require_once __DIR__ . '/config/config.php';

$newPassword = '123456';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$db = getDB();

// 删除旧的admin用户
$db->exec("DELETE FROM admins WHERE username = 'admin'");

// 插入新的admin用户
$stmt = $db->prepare("INSERT INTO admins (username, password, email, login_attempts, locked_until) VALUES (?, ?, ?, 0, NULL)");
$stmt->execute(['admin', $hash, 'admin@example.com']);

echo "密码已重置！<br>";
echo "用户名: admin<br>";
echo "密码: 123456<br>";
echo "<br><strong style='color:red'>请立即删除此文件！</strong>";
?>
