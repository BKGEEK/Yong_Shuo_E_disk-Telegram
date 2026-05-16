<?php
/**
 * 留言API
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 提交留言
    $nickname = trim($_POST['nickname'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (empty($nickname)) {
        $nickname = '匿名';
    }
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => '留言内容不能为空']);
        exit;
    }
    
    if (mb_strlen($content) > 200) {
        echo json_encode(['success' => false, 'message' => '留言内容不能超过200字']);
        exit;
    }
    
    // 简单防刷：同IP 30秒内只能留言一次
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
    $stmt->execute([getClientIP()]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => '操作太频繁，请稍后再试']);
        exit;
    }
    
    $stmt = $db->prepare("INSERT INTO messages (nickname, content, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([
        htmlspecialchars($nickname),
        htmlspecialchars($content),
        getClientIP()
    ]);
    
    echo json_encode(['success' => true, 'message' => '留言成功']);
} else {
    // 获取留言列表
    $stmt = $db->query("SELECT * FROM messages WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 50");
    $messages = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $messages]);
}
