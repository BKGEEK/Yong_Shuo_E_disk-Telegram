<?php
/**
 * 硬盘空间 - 系统配置
 */

session_start();

define('SITE_NAME', '硬盘空间');
define('SITE_URL', 'https://yingpankongjian.bosmao.cn');
define('TELEGRAM_BOT_TOKEN', '8840496904:AAE1QyaYYAqzOlLw5ZvLyFDSo_xWB4mB-sU');
define('TELEGRAM_CHAT_ID', '-1003825222791');
define('TELEGRAM_API_BASE', 'https://api.telegram.org');
define('TELEGRAM_FILE_BASE', 'https://api.telegram.org/file/bot');
define('MAX_UPLOAD_SIZE', 500 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', [
    'exe', 'msi', 'apk', 'ipa', 'dmg', 'deb', 'rpm', 'app', 'bat', 'cmd', 'sh', 'jar', 'run',
    'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'tgz', 'tbz2', 'lzma', 'cab', 'iso', 'img', 'dmg',
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'ods', 'odp', 'csv',
    'md', 'epub', 'mobi', 'azw', 'azw3', 'djvu', 'chm', 'wps', 'et', 'dps', 'xps', 'oxps',
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'ico', 'svg', 'tif', 'tiff', 'psd', 'ai', 'eps',
    'raw', 'cr2', 'nef', 'orf', 'sr2', 'heic', 'heif', 'avif', 'jfif', 'exif',
    'mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', 'mpeg', 'mpg', 'm4v', '3gp', '3g2',
    'rm', 'rmvb', 'vob', 'ts', 'mts', 'm2ts', 'divx', 'xvid', 'asf', 'ogv', 'f4v',
    'mp3', 'wav', 'flac', 'aac', 'ogg', 'wma', 'm4a', 'ape', 'alac', 'aiff', 'mid', 'midi',
    'opus', 'amr', 'ac3', 'dts', 'mka', 'ra', 'cda', 'pcm', 'au', 'snd',
    'ttf', 'otf', 'woff', 'woff2', 'eot', 'fon', 'fnt',
    'html', 'htm', 'css', 'js', 'json', 'xml', 'php', 'py', 'java', 'c', 'cpp', 'h', 'hpp',
    'cs', 'vb', 'go', 'rs', 'swift', 'kt', 'scala', 'rb', 'pl', 'lua', 'sql', 'yml', 'yaml',
    'ini', 'conf', 'cfg', 'log', 'gitignore', 'htaccess', 'env',
    'obj', 'fbx', 'stl', '3ds', 'blend', 'dae', 'gltf', 'glb', 'dwg', 'dxf', 'step', 'stp', 'iges',
    'db', 'sqlite', 'sqlite3', 'mdb', 'accdb', 'dbf',
    'vmdk', 'vdi', 'vhd', 'vhdx', 'ova', 'ovf', 'qcow2',
    'epub', 'mobi', 'azw', 'azw3', 'fb2', 'lit', 'lrf', 'pdb', 'tcr',
    'torrent', 'nfo', 'sfv', 'cue', 'bin', 'dat', 'bak', 'tmp', 'swf', 'fla'
]);
define('ADMIN_SESSION_KEY', 'disk_admin_logged');
define('CSRF_TOKEN_KEY', 'csrf_token');
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

require_once __DIR__ . '/database.php';

function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_KEY]) && hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
}

function isAdminLoggedIn() {
    return isset($_SESSION[ADMIN_SESSION_KEY]) && $_SESSION[ADMIN_SESSION_KEY] === true;
}

function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function getSiteSettings() {
    static $settings = null;
    if ($settings === null) {
        $db = getDB();
        $settings = [
            'site_title' => '硬盘空间',
            'site_subtitle' => '云端资源网盘',
            'site_notice' => "欢迎访问硬盘空间\n本网站提供各类资源下载，请合理使用。"
        ];
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
        }
    }
    return $settings;
}

function saveSiteSetting($key, $value) {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    return $stmt->execute([$key, $value]);
}

function telegramApiRequest($method, $data = [], $files = []) {
    if (!TELEGRAM_BOT_TOKEN) {
        return ['ok' => false, 'description' => 'Telegram bot token not configured'];
    }

    $url = TELEGRAM_API_BASE . '/bot' . TELEGRAM_BOT_TOKEN . '/' . $method;
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 30,
    ];

    if (!empty($files)) {
        $payload = $data;
        foreach ($files as $key => $path) {
            $payload[$key] = $path instanceof CURLFile ? $path : new CURLFile($path, null, basename($path));
        }
        $options[CURLOPT_POSTFIELDS] = $payload;
    } else {
        $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded'];
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'description' => $error];
    }

    curl_close($ch);
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid Telegram response'];
}

function telegramSendDocument($filePath, $caption = '', $fileName = '') {
    return telegramApiRequest('sendDocument', [
        'chat_id' => TELEGRAM_CHAT_ID,
        'caption' => $caption,
    ], ['document' => new CURLFile($filePath, null, $fileName !== '' ? $fileName : basename($filePath))]);
}
function telegramGetFileUrl($fileId) {
    $result = telegramApiRequest('getFile', ['file_id' => $fileId]);
    if (empty($result['ok']) || empty($result['result']['file_path'])) {
        return false;
    }
    return TELEGRAM_FILE_BASE . TELEGRAM_BOT_TOKEN . '/' . $result['result']['file_path'];
}




