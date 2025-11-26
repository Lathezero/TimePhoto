# TimePhoto 视频管理系统

一个基于 PHP + MySQL 的视频上传、二维码分享与播放系统，支持用户注册登录、管理员审核、个人视频管理、封面更换、观看统计等功能。

## 功能特性

- 视频上传/删除与封面管理（用户仅能管理自己的视频）
- 视频二维码生成与下载，扫码直达播放页
- 首页视频列表与搜索，显示上传者与观看次数
- 管理后台：用户审核（通过/禁用/删除）、视频管理
- 用户注册需管理员审核通过后方可登录
- 播放页沉浸式播放器，支持全屏与信息面板

## 环境要求

- PHP 7.4+（建议）
- MySQL 5.7+/8.0+
- Web 服务器（Apache/Nginx，Windows 可用 phpStudy）
- 开启文件上传，建议启用 PHP GD（用于验证码）

## 目录结构

```
├── index.html              # 首页（视频列表、搜索、入口）
├── play.php                # 视频播放页
├── api.php                 # 后端接口（上传/列表/封面/删除/二维码）
├── config.php              # 系统配置（数据库、目录、站点 URL）
├── install.php             # 初始化数据库并创建默认管理员
├── update_db.php           # 数据库迁移脚本（补充缺失字段）
├── admin/                  # 管理后台
│   ├── index.php           # 后台视频列表
│   ├── login.php           # 管理员登录
│   ├── logout.php          # 管理员退出
│   └── users.php           # 用户审核与管理
├── user/                   # 用户端
│   ├── login.php           # 用户登录（含跳转首页/管理员登录）
│   ├── register.php        # 用户注册（默认待审核）
│   ├── dashboard.php       # 上传与“我的视频”（二维码/封面/删除）
│   ├── profile.php         # 资料页与快捷入口
│   └── captcha.php         # 注册验证码
├── uploads/                # 文件目录（首次访问自动创建）
│   ├── videos/             # 视频文件
│   ├── covers/             # 封面图片
│   └── qrcodes/            # 二维码图片
└── error/                  # 统一错误页（phpStudy）
```

## 安装与配置

- 创建数据库：
  - 在 MySQL 中执行：`CREATE DATABASE timephoto CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;`
- 配置数据库与站点：
  - 编辑 `config.php` 设置：`DB_HOST`、`DB_PORT`、`DB_NAME`、`DB_USER`、`DB_PASS`
  - `SITE_URL` 默认按请求动态推断，如需固定域名可设置环境变量或直接修改
- 初始化：
  - 访问 `install.php` 自动建表并创建管理员账户
  - 默认管理员：`admin` / `admin123`（登录后请尽快修改密码）
- 上传参数（主 php.ini）：
  - `upload_max_filesize=512M`
  - `post_max_size=600M`
  - `max_file_uploads=50`
  - `upload_tmp_dir=d:\phpstudy_pro\WWW\TimePhoto\tmp`（需提前创建并可写）
- 目录访问限制（站点 `.user.ini`）：
  - `open_basedir=d:\phpstudy_pro\WWW\TimePhoto\;C:\Windows\Temp\`

## 使用流程

- 首页：`index.html` 显示全部有效视频，可搜索与点击播放
- 用户注册：`user/register.php` 提交后为 `pending`，需管理员审核
- 管理员审核：`admin/users.php` 可“通过/禁用/删除”，通过后用户可登录
- 用户登录与管理：`user/login.php` 登录后进入 `user/dashboard.php`
  - 上传视频（仅普通用户）
  - 生成/查看二维码（弹窗显示，支持下载）
  - 更换封面（仅能操作自己的视频）
  - 删除视频（仅能操作自己的视频）
- 管理后台：`admin/index.php` 查看视频与二维码，支持封面与删除（全局权限）

## 权限与规则

- 用户登录态：`$_SESSION['user_logged_in']`，仅 `active` 状态用户可登录（`user/login.php:21-33`）
- 管理员登录态：`$_SESSION['admin_logged_in']`（`admin/login.php:21-25`）
- 上传接口需登录并记录归属 `user_id`（`api.php:33-43, 97-105`）
- 封面更新权限：仅管理员或视频归属者（`api.php:191-207`）

## API 接口

- `GET /api.php?action=list`：视频列表（含 `uploader_username`）（`api.php:122-129`）
- `GET /api.php?action=list_mine`：我的视频（登录态）（`api.php:132-147`）
- `GET /api.php?action=get_by_qr&qr=...`：按二维码获取（`api.php:149-169`）
- `POST /api.php?action=upload`：上传视频（登录态）（`api.php:32-116`）
- `POST /api.php?action=update_cover`：更新封面（`api.php:172-237`）
- `POST /api.php?action=delete`：删除视频（`api.php:311-365`）
- `POST /api.php?action=generate_qr`：重新生成二维码（`api.php:279-309`）

## 常见问题

- 上传失败错误代码 6：`UPLOAD_ERR_NO_TMP_DIR`
  - 在主 `php.ini` 设置 `upload_tmp_dir`，并重启 PHP 与 Web 服务
- 500 错误或前端提示“非JSON响应”：
  - 检查数据库连接与目录权限；接口强制返回 JSON（`api.php:9-11`）
- 列不存在错误（如 `Unknown column 'user_id'`）：
  - 访问 `update_db.php` 自动添加缺失列并展示当前表结构
- 二维码无法显示：
  - 优先使用本地 `uploads/qrcodes/`，否则在线生成；网络异常时弹窗内提供直接访问链接

## 安全建议

- 首次登录后台后立即修改管理员密码
- 生产环境启用 HTTPS，并限制 `open_basedir`
- 不要在仓库或代码中暴露数据库密码

## 版本与维护

- 当前实现基于 MySQL；如从旧版 SQLite 迁移，请通过 `update_db.php` 同步新增字段
- 建议定期备份数据库与 `uploads/` 目录

如需二次开发或集成更多登录方式（如 OAuth），可在 `user/` 与 `admin/` 目录基础上扩展逻辑。
