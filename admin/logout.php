<?php
/**
 * 硬盘空间 - 退出登�?
 */
require_once __DIR__ . '/../config/config.php';

// 清除会话
$_SESSION = [];
session_destroy();

header('Location: index.php');
exit;
