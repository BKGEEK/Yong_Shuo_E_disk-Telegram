<?php
/**
 * 硬盘空间 - 文件上传
 */
require_once __DIR__ . '/../config/config.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$message = '';
$messageType = '';

$stmt = $db->query("SELECT * FROM folders ORDER BY name");
$folders = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        $message = '安全验证失败';
        $messageType = 'error';
    } else {
        $folderId = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $uploadedFiles = $_FILES['files'];
        $successCount = 0;
        $failCount = 0;
        $failReasons = [];

        for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
            $originalName = $uploadedFiles['name'][$i];

            if (($uploadedFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'PHP配置限制文件大小',
                    UPLOAD_ERR_FORM_SIZE => '表单限制文件大小',
                    UPLOAD_ERR_PARTIAL => '文件只上传了一部分',
                    UPLOAD_ERR_NO_FILE => '没有文件被上传',
                    UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
                    UPLOAD_ERR_CANT_WRITE => '文件写入失败',
                    UPLOAD_ERR_EXTENSION => 'PHP扩展阻止了上传',
                ];
                $failReasons[] = $originalName . ': ' . ($errorMessages[$uploadedFiles['error'][$i]] ?? '未知错误');
                $failCount++;
                continue;
            }

            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $fileSize = (int)$uploadedFiles['size'][$i];
            $tmpName = $uploadedFiles['tmp_name'][$i];
            $fileType = substr($uploadedFiles['type'][$i] ?? 'application/octet-stream', 0, 50);

            if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
                $failReasons[] = $originalName . ': 不支持的文件类型(' . $extension . ')';
                $failCount++;
                continue;
            }

            if ($fileSize > MAX_UPLOAD_SIZE) {
                $failReasons[] = $originalName . ': 文件太大';
                $failCount++;
                continue;
            }

            $caption = $description !== '' ? $description : $originalName;
            $telegramResult = telegramSendDocument($tmpName, $caption, $originalName);

            if (empty($telegramResult['ok'])) {
                $failReasons[] = $originalName . ': Telegram上传失败 - ' . ($telegramResult['description'] ?? '未知错误');
                $failCount++;
                continue;
            }

            $resultData = $telegramResult['result'] ?? [];
            $document = $resultData['document'] ?? [];
            $telegramFileId = $document['file_id'] ?? '';
            $telegramUniqueId = $document['file_unique_id'] ?? '';
            $telegramMessageId = $resultData['message_id'] ?? null;
            $telegramChatId = $resultData['chat']['id'] ?? TELEGRAM_CHAT_ID;

            if ($telegramFileId === '') {
                $failReasons[] = $originalName . ': Telegram返回数据缺失';
                $failCount++;
                continue;
            }

            $stmt = $db->prepare("INSERT INTO files (folder_id, original_name, stored_name, file_path, file_size, file_type, extension, description, storage_type, telegram_file_id, telegram_unique_id, telegram_message_id, telegram_chat_id, telegram_file_name, telegram_file_size, telegram_mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'telegram', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $folderId,
                $originalName,
                $originalName,
                $telegramFileId,
                $fileSize,
                $fileType,
                $extension,
                $description,
                $telegramFileId,
                $telegramUniqueId,
                $telegramMessageId,
                $telegramChatId,
                $document['file_name'] ?? $originalName,
                $document['file_size'] ?? $fileSize,
                $document['mime_type'] ?? $fileType,
            ]);
            $successCount++;
        }

        if ($successCount > 0) {
            $message = '成功上传 ' . $successCount . ' 个文件';
            if ($failCount > 0) {
                $message .= '，' . $failCount . ' 个失败';
            }
            $messageType = 'success';
        } else {
            $message = '上传失败';
            if (!empty($failReasons)) {
                $message .= '：' . implode('；', $failReasons);
            }
            $messageType = 'error';
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
    <title>上传文件 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/admin.css?v=3">
    <style>
    .upload-zone {
        border: 1px dashed #ccc;
        border-radius: 5px;
        padding: 50px 30px;
        text-align: center;
        background: #fafafa;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 20px;
    }
    .upload-zone:hover, .upload-zone.dragover {
        border-color: #5ba0e8;
        background: #f5f9ff;
    }
    .upload-zone input[type="file"] {
        display: none;
    }
    .upload-zone .text {
        font-size: 18px;
        color: #666;
        margin-bottom: 10px;
    }
    .upload-zone .hint {
        color: #999;
        font-size: 14px;
    }
    .upload-zone .filename {
        display: inline-block;
        margin: 5px;
        padding: 5px 10px;
        background: #e8f4ff;
        border-radius: 3px;
        font-size: 14px;
    }
    .upload-zone.file-selected {
        border-color: #5ba0e8;
        background: #f5f9ff;
    }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="admin-sidebar">
        <div class="logo">DG存储库管理</div>
        <ul class="admin-nav">
            <li><a href="dashboard.php"><span class="icon">?</span>仪表盘</a></li>
            <li><a href="folders.php"><span class="icon">?</span>目录管理</a></li>
            <li><a href="files.php"><span class="icon">?</span>文件管理</a></li>
            <li><a href="upload.php" class="active"><span class="icon">?</span>上传文件</a></li>
            <li><a href="stats.php"><span class="icon">?</span>访问统计</a></li>
            <li><a href="settings.php"><span class="icon">?</span>系统设置</a></li>
        </ul>
    </div>

    <div class="admin-main">
        <div class="card">
            <div class="card-header">上传文件</div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="form-group">
                        <label>选择目录</label>
                        <select name="folder_id" class="form-control">
                            <option value="">根目录</option>
                            <?php foreach ($folders as $folder): ?>
                            <option value="<?= (int)$folder['id'] ?>"><?= htmlspecialchars($folder['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="upload-zone" id="uploadZone">
                        <div class="text">点击选择文件 或 拖拽文件到此处</div>
                        <div class="hint">支持多选，单文件最大 500MB</div>
                        <input type="file" name="files[]" id="fileInput" multiple required onclick="event.stopPropagation()">
                        <div id="fileInfo"></div>
                    </div>

                    <div class="form-group">
                        <label>文件描述（可选）</label>
                        <textarea name="description" class="form-control" placeholder="添加文件描述..." style="min-height:80px;max-width:100%;"></textarea>
                    </div>

                    <button type="submit" class="btn">开始上传</button>
                </form>

                <div class="type-list">
                    <div class="category">安装包 / 可执行文件</div>
                    exe, msi, apk, ipa, dmg, deb, rpm, app, bat, cmd, sh, jar, run
                    <div class="category">压缩包</div>
                    zip, rar, 7z, tar, gz, bz2, xz, tgz, tbz2, lzma, cab, iso, img
                    <div class="category">文档</div>
                    pdf, doc, docx, xls, xlsx, ppt, pptx, txt, rtf, odt, ods, odp, csv, md, epub, mobi, azw, azw3, djvu, chm, wps, et, dps, xps
                    <div class="category">图片</div>
                    jpg, jpeg, png, gif, bmp, webp, ico, svg, tif, tiff, psd, ai, eps, raw, cr2, nef, heic, heif, avif
                    <div class="category">视频</div>
                    mp4, avi, mkv, mov, wmv, flv, webm, mpeg, mpg, m4v, 3gp, rm, rmvb, vob, ts, mts, m2ts, divx, xvid, asf, ogv, f4v
                    <div class="category">音频</div>
                    mp3, wav, flac, aac, ogg, wma, m4a, ape, alac, aiff, mid, midi, opus, amr, ac3, dts, mka, pcm
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var uploadZone = document.getElementById('uploadZone');
var fileInput = document.getElementById('fileInput');

uploadZone.addEventListener('click', function(e) {
    if (e.target !== fileInput) {
        fileInput.click();
    }
});

uploadZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

uploadZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});

uploadZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        showSelectedFiles(e.dataTransfer.files);
    }
});

fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        showSelectedFiles(this.files);
    }
});

function showSelectedFiles(files) {
    var html = '';
    var totalSize = 0;
    for (var i = 0; i < files.length; i++) {
        html += '<div class="filename">' + files[i].name + '</div>';
        totalSize += files[i].size;
    }
    html += '<div class="hint">共 ' + files.length + ' 个文件，' + formatSize(totalSize) + '</div>';
    document.getElementById('fileInfo').innerHTML = html;
    uploadZone.classList.add('file-selected');
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    return (bytes / 1024 / 1024 / 1024).toFixed(1) + ' GB';
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    if (!fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        alert('请先选择文件');
    }
});
</script>
</body>
</html>


