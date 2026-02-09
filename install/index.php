<?php
/**
 * 企业福利系统 - 一键安装程序
 * 全中文界面，傻瓜式安装
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 检查是否已安装
if (file_exists('../config.php')) {
    $config = include('../config.php');
    if (!empty($config['installed'])) {
        die('<h1 style="color:red;text-align:center;margin-top:100px;">系统已安装，如需重新安装请先删除 config.php 文件</h1>');
    }
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        // 测试数据库连接
        $db_host = trim($_POST['db_host'] ?? '');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = $_POST['db_pass'] ?? '';
        
        try {
            $pdo = new PDO("mysql:host={${db_host}};dbname={${db_name}};charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 保存到 session
            $_SESSION['db_config'] = [
                'host' => $db_host,
                'name' => $db_name,
                'user' => $db_user,
                'pass' => $db_pass
            ];
            
            header('Location: ?step=3');
            exit;
        } catch (PDOException $e) {
            $error = '数据库连接失败：' . $e->getMessage();
        }
    } elseif ($step === 3) {
        // 执行安装
        $admin_email = trim($_POST['admin_email'] ?? '');
        
        if (empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入正确的管理员邮箱';
        } else {
            $db = $_SESSION['db_config'] ?? null;
            if (!$db) {
                header('Location: ?step=2');
                exit;
            }
            
            try {
                $pdo = new PDO("mysql:host={${db['host']}};dbname={${db['name']}};charset=utf8mb4", $db['user'], $db['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 读取并执行 SQL
                $sql = file_get_contents(__DIR__ . '/database.sql');
                // 替换管理员邮箱
                $sql = str_replace('3327512620@qq.com', $admin_email, $sql);
                
                // 分割并执行 SQL 语句
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if (!empty($statement) && stripos($statement, '--') !== 0) {
                        $pdo->exec($statement);
                    }
                }
                
                // 生成配置文件
                $config_content = "<?php\nreturn [\n";
                $config_content .= "    'installed' => true,\n";
                $config_content .= "    'db_host' => '{${db['host']}}',\n";
                $config_content .= "    'db_name' => '{${db['name']}}',\n";
                $config_content .= "    'db_user' => '{${db['user']}}',\n";
                $config_content .= "    'db_pass' => '{${db['pass']}}',\n";
                $config_content .= "    'admin_email' => '{${admin_email}}',\n";
                $config_content .= "];\n";
                
                file_put_contents('../config.php', $config_content);
                
                // 清除 session
                unset($_SESSION['db_config']);
                
                header('Location: ?step=4');
                exit;
            } catch (PDOException $e) {
                $error = '安装失败：' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>企业福利系统 - 安装向导</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #D93025 0%, #a31515 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 24px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .steps {
            display: flex;
            justify-content: center;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .step-item {
            display: flex;
            align-items: center;
            margin: 0 10px;
            font-size: 14px;
            color: #999;
        }
        .step-item.active { color: #D93025; font-weight: bold; }
        .step-item.done { color: #28a745; }
        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ddd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            font-size: 14px;
        }
        .step-item.active .step-num { background: #D93025; }
        .step-item.done .step-num { background: #28a745; }
        .content { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #D93025;
        }
        .form-group small {
            display: block;
            margin-top: 6px;
            color: #666;
            font-size: 13px;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #D93025 0%, #a31515 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(217, 48, 37, 0.4);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error { background: #fee; color: #c00; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #060; border: 1px solid #cfc; }
        .check-list { list-style: none; }
        .check-list li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        .check-list li:last-child { border-bottom: none; }
        .check-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .check-icon.ok { background: #d4edda; color: #28a745; }
        .check-icon.fail { background: #f8d7da; color: #dc3545; }
        .success-box {
            text-align: center;
            padding: 40px 20px;
        }
        .success-box .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }
        .success-box h2 { color: #28a745; margin-bottom: 15px; }
        .success-box p { color: #666; margin-bottom: 20px; line-height: 1.6; }
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
        .btn-group .btn { flex: 1; }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 企业福利系统</h1>
            <p>一键安装向导 - 全程中文引导</p>
        </div>
        
        <div class="steps">
            <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'done' : 'active') : ''; ?>">
                <span class="step-num">1</span>环境检测
            </div>
            <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'done' : 'active') : ''; ?>">
                <span class="step-num">2</span>数据库
            </div>
            <div class="step-item <?php echo $step >= 3 ? ($step > 3 ? 'done' : 'active') : ''; ?>">
                <span class="step-num">3</span>管理员
            </div>
            <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">
                <span class="step-num">4</span>完成
            </div>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <!-- 第1步：环境检测 -->
                <h3 style="margin-bottom:20px;">📋 环境检测</h3>
                <?php
                $checks = [
                    'PHP版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                    'PDO扩展' => extension_loaded('pdo'),
                    'PDO MySQL扩展' => extension_loaded('pdo_mysql'),
                    'JSON扩展' => extension_loaded('json'),
                    'Session支持' => session_status() !== PHP_SESSION_DISABLED,
                    '根目录可写' => is_writable('../'),
                ];
                $all_pass = !in_array(false, $checks, true);
                ?>
                <ul class="check-list">
                    <?php foreach ($checks as $name => $pass): ?>
                    <li>
                        <span class="check-icon <?php echo $pass ? 'ok' : 'fail'; ?>">
                            <?php echo $pass ? '✓' : '✗'; ?>
                        </span>
                        <?php echo $name; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($all_pass): ?>
                    <a href="?step=2" class="btn" style="display:block;text-align:center;text-decoration:none;margin-top:20px;">
                        下一步 →
                    </a>
                <?php else: ?>
                    <div class="alert alert-error" style="margin-top:20px;">
                        环境检测未通过，请联系主机商解决上述问题
                    </div>
                <?php endif; ?>
                
            <?php elseif ($step === 2): ?>
                <!-- 第2步：数据库配置 -->
                <h3 style="margin-bottom:20px;">🗄️ 数据库配置</h3>
                <form method="post">
                    <div class="form-group">
                        <label>数据库主机</label>
                        <input type="text" name="db_host" value="localhost" required>
                        <small>一般填 localhost，老薛主机可能是其他地址</small>
                    </div>
                    <div class="form-group">
                        <label>数据库名称</label>
                        <input type="text" name="db_name" placeholder="在老薛后台创建的数据库名" required>
                        <small>需要先在老薛主机后台创建一个数据库</small>
                    </div>
                    <div class="form-group">
                        <label>数据库用户名</label>
                        <input type="text" name="db_user" required>
                        <small>老薛后台创建数据库时会显示</small>
                    </div>
                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" name="db_pass">
                        <small>老薛后台创建数据库时设置的密码</small>
                    </div>
                    <button type="submit" class="btn">测试连接并继续 →</button>
                </form>
                
            <?php elseif ($step === 3): ?>
                <!-- 第3步：设置管理员 -->
                <h3 style="margin-bottom:20px;">👤 设置管理员</h3>
                <form method="post">
                    <div class="form-group">
                        <label>管理员邮箱</label>
                        <input type="email" name="admin_email" value="3327512620@qq.com" required>
                        <small>这个邮箱将拥有最高管理权限，可以添加其他员工</small>
                    </div>
                    <button type="submit" class="btn">完成安装 →</button>
                </form>
                
            <?php elseif ($step === 4): ?>
                <!-- 第4步：安装完成 -->
                <div class="success-box">
                    <div class="icon">✓</div>
                    <h2>🎊 安装成功！</h2>
                    <p>
                        企业福利系统已成功安装！<br>
                        现在可以开始使用了。
                    </p>
                    <div class="btn-group">
                        <a href="../" class="btn" style="text-decoration:none;">进入首页</a>
                        <a href="../admin/" class="btn btn-secondary" style="text-decoration:none;">管理后台</a>
                    </div>
                    <p style="margin-top:30px;color:#999;font-size:13px;">
                        ⚠️ 为了安全，建议安装完成后删除 install 目录
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>