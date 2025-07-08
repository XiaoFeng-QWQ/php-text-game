<?php

/**
 * 处理异常
 */
function handleException(Throwable $e): void
{
    $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $errorTrace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

    echo sprintf(
        '<div class="alert alert-danger" role="alert">牛逼，编辑器炸了! <pre><code class="language-php">%s</code></pre><details><summary>Stack trace</summary><pre>%s</pre></details></div>',
        $errorMessage,
        $errorTrace
    );
}
// 设置异常处理器
set_exception_handler('handleException');

session_start();

// 初始化地图编辑器
if (!isset($_SESSION['地图编辑器'])) {
    $_SESSION['地图编辑器'] = [
        '当前场景' => '新场景',
        '场景列表' => [],
        '已保存' => false
    ];
}

// 处理编辑器命令
if (isset($_POST['编辑器命令'])) {
    $命令 = trim($_POST['编辑器命令']);

    if ($命令 === '保存场景') {
        // 保存当前场景数据
        $场景名称 = $_POST['场景名称'];
        $场景数据 = [
            '描述' => $_POST['场景描述'],
            '出口' => 解析出口($_POST['场景出口']),
            '物品' => 解析列表($_POST['场景物品']),
            '人物' => 解析人物($_POST['场景人物']),
            '特殊' => $_POST['场景特殊'],
            '脚本' => $_POST['场景脚本'] ?? ''
        ];

        $_SESSION['地图编辑器']['场景列表'][$场景名称] = $场景数据;
        $_SESSION['地图编辑器']['当前场景'] = $场景名称;
        $_SESSION['地图编辑器']['已保存'] = true;

        // 保存全局脚本和物品数据
        if (isset($_POST['全局脚本'])) {
            $_SESSION['全局脚本'] = $_POST['全局脚本'];
        }
        if (isset($_POST['物品数据'])) {
            $_SESSION['物品数据'] = 解析物品数据($_POST['物品数据']);
        }
    } elseif ($命令 === '新建场景') {
        $_SESSION['地图编辑器']['当前场景'] = '新场景';
        $_SESSION['地图编辑器']['已保存'] = false;
    } elseif ($命令 === '加载场景' && isset($_POST['加载场景名称'])) {
        $加载场景 = $_POST['加载场景名称'];
        if (isset($_SESSION['地图编辑器']['场景列表'][$加载场景])) {
            $_SESSION['地图编辑器']['当前场景'] = $加载场景;
            $_SESSION['地图编辑器']['已保存'] = true;
        }
    } elseif ($命令 === '删除场景' && isset($_POST['删除场景名称'])) {
        $删除场景 = $_POST['删除场景名称'];
        if (isset($_SESSION['地图编辑器']['场景列表'][$删除场景])) {
            unset($_SESSION['地图编辑器']['场景列表'][$删除场景]);
            if ($_SESSION['地图编辑器']['当前场景'] === $删除场景) {
                $_SESSION['地图编辑器']['当前场景'] = '新场景';
                $_SESSION['地图编辑器']['已保存'] = false;
            }
        }
    } elseif ($命令 === '导出地图') {
        // 导出为完整游戏世界结构
        $导出数据 = [
            '地图' => $_SESSION['地图编辑器']['场景列表'],
            '全局脚本' => $_SESSION['全局脚本'] ?? '',
            '数据' => [
                '物品数据' => $_SESSION['物品数据'] ?? []
            ]
        ];

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="游戏地图.json"');
        echo json_encode($导出数据, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($命令 === '导入地图' && isset($_FILES['地图文件'])) {
        $文件内容 = file_get_contents($_FILES['地图文件']['tmp_name']);
        $导入数据 = json_decode($文件内容, true);
        if ($导入数据) {
            $_SESSION['地图编辑器']['场景列表'] = $导入数据['地图'] ?? [];
            $_SESSION['全局脚本'] = $导入数据['全局脚本'] ?? '';
            $_SESSION['物品数据'] = $导入数据['数据']['物品数据'] ?? [];
            $_SESSION['地图编辑器']['当前场景'] = array_key_first($导入数据['地图'] ?? []) ?? '新场景';
            $_SESSION['地图编辑器']['已保存'] = true;
        }
    } elseif ($命令 === '测试脚本' && isset($_POST['测试脚本内容'])) {
        try {
            $测试结果 = 测试场景脚本($_POST['测试脚本内容']);
            $_SESSION['脚本测试结果'] = $测试结果;
        } catch (Exception $e) {
            $_SESSION['脚本测试结果'] = '脚本错误: ' . $e->getMessage();
        }
    } elseif ($命令 === '测试全局脚本' && isset($_POST['测试全局脚本内容'])) {
        try {
            $测试结果 = 测试全局脚本($_POST['测试全局脚本内容']);
            $_SESSION['全局脚本测试结果'] = $测试结果;
        } catch (Exception $e) {
            $_SESSION['全局脚本测试结果'] = '全局脚本错误: ' . $e->getMessage();
        }
    }
}

// 辅助函数
function 解析出口($出口文本)
{
    $出口 = [];
    $行 = explode("\n", trim($出口文本));
    foreach ($行 as $出口行) {
        $出口行 = trim($出口行);
        if (strpos($出口行, ':') !== false) {
            list($方向, $目标) = explode(':', $出口行, 2);
            $出口[trim($方向)] = trim($目标);
        }
    }
    return $出口;
}

function 解析列表($列表文本)
{
    $项目 = explode(',', trim($列表文本));
    return array_map('trim', array_filter($项目));
}

function 解析人物($人物文本)
{
    $人物 = [];
    $行 = explode("\n", trim($人物文本));
    foreach ($行 as $人物行) {
        $人物行 = trim($人物行);
        if (strpos($人物行, ':') !== false) {
            list($名称, $描述) = explode(':', $人物行, 2);
            $人物[trim($名称)] = trim($描述);
        }
    }
    return $人物;
}

function 解析物品数据($物品数据文本)
{
    $物品数据 = [];
    $块 = explode("\n\n", trim($物品数据文本));
    foreach ($块 as $物品块) {
        $行 = explode("\n", trim($物品块));
        $物品名称 = trim(str_replace(':', '', array_shift($行)));
        $物品属性 = [];

        foreach ($行 as $属性行) {
            if (strpos($属性行, ':') !== false) {
                list($属性名, $属性值) = explode(':', $属性行, 2);
                $物品属性[trim($属性名)] = trim($属性值);
            }
        }

        if (!empty($物品名称) && !empty($物品属性)) {
            $物品数据[$物品名称] = $物品属性;
        }
    }
    return $物品数据;
}

function 格式化出口($出口数组)
{
    $文本 = '';
    if (is_array($出口数组)) {
        foreach ($出口数组 as $方向 => $目标) {
            $文本 .= "{$方向}:{$目标}\n";
        }
    }
    return trim($文本);
}

function 格式化人物($人物数组)
{
    $文本 = '';
    if (is_array($人物数组)) {
        foreach ($人物数组 as $名称 => $描述) {
            $文本 .= "{$名称}:{$描述}\n";
        }
    }
    return trim($文本);
}

function 格式化物品数据($物品数据数组)
{
    $文本 = '';
    if (is_array($物品数据数组)) {
        foreach ($物品数据数组 as $物品名称 => $属性) {
            $文本 .= "{$物品名称}:\n";
            foreach ($属性 as $属性名 => $属性值) {
                $文本 .= "  {$属性名}: {$属性值}\n";
            }
            $文本 .= "\n";
        }
    }
    return trim($文本);
}

function 测试场景脚本($脚本代码)
{
    // 模拟游戏环境变量
    $玩家 = ['位置' => '测试场景', '物品' => ['剑', '钥匙'], '生命值' => 100, '攻击力' => 10, '分数' => 0];
    $场景 = ['物品' => ['金币', '药水'], '人物' => ['商人' => '出售各种物品']];

    // 使用输出缓冲捕获脚本输出
    ob_start();
    try {
        eval($脚本代码);
    } catch (ParseError $e) {
        ob_end_clean();
        throw new Exception("语法错误: " . $e->getMessage());
    } catch (Throwable $e) {
        ob_end_clean();
        throw new Exception("运行时错误: " . $e->getMessage());
    }
    $测试结果 = ob_get_clean();

    return $测试结果 ?: "脚本执行成功但无输出";
}

function 测试全局脚本($脚本代码)
{
    // 模拟游戏环境变量
    $游戏世界 = ['地图' => []];
    $游戏状态 = ['当前位置' => '测试场景', '背包' => [], '生命值' => 100, '攻击力' => 5, '分数' => 0];

    // 使用输出缓冲捕获脚本输出
    ob_start();
    try {
        eval($脚本代码);
    } catch (ParseError $e) {
        ob_end_clean();
        throw new Exception("语法错误: " . $e->getMessage());
    } catch (Throwable $e) {
        ob_end_clean();
        throw new Exception("运行时错误: " . $e->getMessage());
    }
    $测试结果 = ob_get_clean();

    return $测试结果 ?: "全局脚本执行成功但无输出";
}

// 获取当前场景数据
$当前场景 = $_SESSION['地图编辑器']['当前场景'] ?? '新场景';
$场景数据 = $_SESSION['地图编辑器']['场景列表'][$当前场景] ?? [
    '描述' => '',
    '出口' => [],
    '物品' => [],
    '人物' => [],
    '特殊' => '',
    '脚本' => ''
];

$全局脚本 = $_SESSION['全局脚本'] ?? '';
$物品数据 = $_SESSION['物品数据'] ?? [];
