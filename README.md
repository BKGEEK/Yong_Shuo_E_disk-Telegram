# 硬盘空间 / DG存储库

这是一个基于 PHP + MySQL 的网盘系统，当前版本使用 **Telegram 作为文件存储后端**。

## 特性

- 后台上传文件直接发送到 Telegram
- 文件元数据保存在 MySQL
- 前台按目录浏览文件
- 支持文件下载、目录管理、文件管理、访问统计
- 支持 CSRF 校验和后台登录
- 此版本后台CSS异常待修复

## 环境要求

- PHP 7.4 或更高
- MySQL 5.7 或更高
- PHP 扩展：`PDO`、`curl`
- 可用的 Telegram Bot
- 建议开启 HTTPS

## 目录结构

- `index.php`：前台首页
- `download.php`：文件下载
- `admin/`：后台管理
- `config/`：系统配置与数据库连接
- `install/dkewl.sql`：数据库初始化脚本
- `assets/`：前台静态资源
- `admin/css/`：后台样式

## 安装步骤

1. 创建 MySQL 数据库
2. 导入 `install/dghost.sql`
3. 打开 `config/config.php`和`config/database.php`
4. 配置以下参数：
   - `TELEGRAM_BOT_TOKEN`
   - `TELEGRAM_CHAT_ID`
   - `SITE_URL`
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
5. 部署站点
6. 确认服务器允许 `curl` 访问 Telegram API

## 后台登录

- 地址：`/admin/`
- 默认账号：`admin`
- 默认密码：`123456`

登录后请尽快修改默认密码。

## 文件存储逻辑

### 上传

后台上传时，文件通过 Telegram Bot API 的 `sendDocument` 接口上传到指定聊天。

数据库保存：

- `original_name`
- `stored_name`
- `file_path`：Telegram `file_id`
- `storage_type`：`telegram`
- `telegram_file_id`
- `telegram_unique_id`
- `telegram_message_id`
- `telegram_chat_id`
- `telegram_file_name`
- `telegram_file_size`
- `telegram_mime_type`

### 下载

前台下载时：

1. 通过 `telegram_file_id` 调用 Telegram `getFile`
2. 获取真实文件地址
3. 流式转发给浏览器下载（中国大陆用户正常使用）

## 主要页面

- 前台首页：`index.php`
- 文件下载：`download.php`
- 后台首页：`admin/index.php`
- 文件上传：`admin/upload.php`
- 文件管理：`admin/files.php`
- 目录管理：`admin/folders.php`
- 访问统计：`admin/stats.php`
- 系统设置：`admin/settings.php`

## 常见问题

### 上传失败：`Bad Request: chat not found`

- 检查 `TELEGRAM_CHAT_ID` 是否正确
- 确认 Bot 已加入目标群组或频道
- 如果是频道，Bot 需要管理员权限

### 下载文件损坏

- 检查 `download.php` 是否完整
- 检查 `telegram_file_id` 是否正确
- 确认 PHP `curl` 扩展可用

### 后台统计 IP 不准确

- 当前统计读取的是服务器收到的客户端 IP
- 例如站点在 Cloudflare 上，需要额外读取 `CF-Connecting-IP`

## 注意事项

- 不要直接删除 Telegram 中的文件，否则数据库记录会失效
- `files.php` 当前仅删除数据库记录，不会删除 Telegram 实际文件
- 上传文件大小受 Telegram 和服务器 PHP 配置双重限制

## 安全建议

- 立即修改默认管理员密码
- 不要公开 `config/config.php` （本项目已默认配置）
- 定期备份数据库
- 建议启用 HTTPS

## 许可证

本项目未附带明确许可证，按你的实际使用范围自行管理。
