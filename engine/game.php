<?php

/**
 * 处理异常
 */
function handleException(Throwable $e): void
{
    $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $errorTrace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

    echo sprintf(
        '<div class="alert alert-danger" role="alert">牛逼，游戏炸了! <pre><code class="language-php">%s</code></pre><details><summary>Stack trace</summary><pre>%s</pre></details></div>',
        $errorMessage,
        $errorTrace
    );
}
// 设置异常处理器
set_exception_handler('handleException');

try {
    session_start();
    // 重置游戏
    if (isset($_GET['重置'])) {
        session_destroy();
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    // 辅助函数
    function 运行全局脚本()
    {
        if (isset($_SESSION['游戏世界']['全局脚本'])) {
            try {
                eval($_SESSION['游戏世界']['全局脚本']);
            } catch (Throwable $e) {
                echo "地图作者太拉了，全局脚本炸了！ $e";
            }
        }
    }

    function 运行场景脚本($场景名称)
    {
        $输出 = '';
        if (isset($_SESSION['游戏世界']['地图'][$场景名称]['脚本'])) {
            ob_start();
            try {
                eval($_SESSION['游戏世界']['地图'][$场景名称]['脚本']);
            } catch (Throwable $e) {
                echo "地图作者太拉了，局部脚本炸了！ $e";
            }
            $输出 = ob_get_clean();
        }
        return $输出 ? "<br>" . trim($输出) : '';
    }

    function 调用全局函数($函数名, $参数 = [])
    {
        if (function_exists($函数名)) {
            return call_user_func_array($函数名, $参数);
        }
        return "系统错误: 功能不可用";
    }

    function 物品在背包中($物品)
    {
        return in_array($物品, $_SESSION["游戏状态"]["背包"]);
    }

    function 添加物品($物品)
    {
        $_SESSION["游戏状态"]["背包"][] = $物品;
        return "获得了" . $物品;
    }

    function 移除物品($物品)
    {
        $_SESSION["游戏状态"]["背包"] = array_diff($_SESSION["游戏状态"]["背包"], [$物品]);
        return "失去了" . $物品;
    }

    // 物品使用系统
    function 使用物品($物品)
    {
        if (!物品在背包中($物品)) {
            return "你的背包里没有{$物品}。";
        }

        $物品数据 = $_SESSION["游戏世界"]["数据"]["物品数据"][$物品] ?? [];

        // 检查使用次数限制
        if (isset($物品数据["使用次数"])) {
            $使用次数键 = "物品_{$物品}_使用次数";
            if (!isset($_SESSION["游戏状态"][$使用次数键])) {
                $_SESSION["游戏状态"][$使用次数键] = 0;
            }

            if ($物品数据["使用次数"] !== null && $_SESSION["游戏状态"][$使用次数键] >= $物品数据["使用次数"]) {
                return "这个{$物品}已经不能再使用了。";
            }

            $_SESSION["游戏状态"][$使用次数键]++;
        }

        if (isset($物品数据["使用效果"])) {
            return eval($物品数据["使用效果"]);
        }

        return "你使用了{$物品}，但似乎没什么效果。";
    }

    // 物品拾取系统
    function 拾取物品($物品, $当前位置)
    {
        $游戏世界 = $_SESSION["游戏世界"];

        if (!in_array($物品, $游戏世界["地图"][$当前位置]["物品"])) {
            return "这里没有{$物品}。";
        }

        $物品数据 = $游戏世界["数据"]["物品数据"][$物品] ?? [];

        // 检查物品数量限制
        if (isset($物品数据["最大数量"])) {
            $当前数量 = count(array_filter($_SESSION["游戏状态"]["背包"], function ($item) use ($物品) {
                return $item === $物品;
            }));

            if ($当前数量 >= $物品数据["最大数量"]) {
                return "你不能再携带更多的{$物品}了(最多{$物品数据["最大数量"]}个)。";
            }
        }

        $_SESSION["游戏状态"]["背包"][] = $物品;
        $游戏世界["地图"][$当前位置]["物品"] = array_diff(
            $游戏世界["地图"][$当前位置]["物品"],
            [$物品]
        );

        if (isset($物品数据["拾取效果"])) {
            return eval($物品数据["拾取效果"]);
        }

        return "你拾取了{$物品}。";
    }

    // 处理玩家命令
    if (isset($_POST['命令'])) {
        运行全局脚本(); // 每次处理玩家请求前初始化一次全局脚本
        $命令 = strtolower(trim($_POST['命令']));
        $响应 = '';
        $游戏世界 = $_SESSION['游戏世界'];

        // 移动命令
        if (preg_match('/^(去|走|移动|前往) (东|西|南|北|上|下|内|外)$/', $命令, $匹配)) {
            $方向 = $匹配[2];
            $当前位置 = $_SESSION['游戏状态']['当前位置'];

            if (isset($游戏世界['地图'][$当前位置]['出口'][$方向])) {
                $下一个位置 = $游戏世界['地图'][$当前位置]['出口'][$方向];
                $_SESSION['游戏状态']['当前位置'] = $下一个位置;
                $响应 = "你向{$方向}移动。";

                if (!isset($_SESSION['游戏状态']['已访问地点'][$下一个位置])) {
                    $_SESSION['游戏状态']['已访问地点'][$下一个位置] = true;
                    $响应 .= " 【新地点发现】";
                }

                // 执行新位置的脚本
                $响应 .= 运行场景脚本($下一个位置);
            } else {
                $响应 = "你不能往那个方向走。";
            }
        }
        // 查看命令
        elseif ($命令 === '查看' || $命令 === '观察') {
            $当前位置 = $_SESSION['游戏状态']['当前位置'];
            $响应 = $游戏世界['地图'][$当前位置]['描述'];

            if (!empty($游戏世界['地图'][$当前位置]['物品'])) {
                $物品列表 = [];
                foreach ($游戏世界['地图'][$当前位置]['物品'] as $物品) {
                    if (!in_array($物品, $_SESSION['游戏状态']['背包'])) {
                        $物品列表[] = "<span class='item'>{$物品}</span>";
                    } else {
                        $物品列表[] = "<span class='item-owned'>{$物品}(已拥有)</span>";
                    }
                }

                if (!empty($物品列表)) {
                    $响应 .= "<br>你可以看到: " . implode('、', $物品列表);
                }
            }

            if (!empty($游戏世界['地图'][$当前位置]['人物'])) {
                $响应 .= "<br>这里有以下人物: ";
                foreach ($游戏世界['地图'][$当前位置]['人物'] as $人物 => $描述) {
                    $响应 .= "<br>- {$人物}: {$描述}";
                }
            }

            if (isset($游戏世界['地图'][$当前位置]['特殊'])) {
                $响应 .= "<br>※ " . $游戏世界['地图'][$当前位置]['特殊'];
            }

            $响应 .= 运行场景脚本($当前位置);
        }
        // 拾取物品
        elseif (preg_match('/^(拿|捡|拾取|获取) (.+)$/', $命令, $匹配)) {
            $物品 = $匹配[2];
            $当前位置 = $_SESSION['游戏状态']['当前位置'];
            $响应 = 调用全局函数('拾取物品', [$物品, $当前位置]);
        }
        // 使用物品
        elseif (preg_match('/^(使用|打开) (.+)$/', $命令, $匹配)) {
            $物品 = $匹配[2];
            $响应 = 调用全局函数('使用物品', [$物品]);
        }
        // 查看背包
        elseif ($命令 === '背包' || $命令 === '物品') {
            if (empty($_SESSION['游戏状态']['背包'])) {
                $响应 = "你的背包是空的。";
            } else {
                $响应 = "你携带的物品: " . implode('、', $_SESSION['游戏状态']['背包']);
            }
        }
        // 查看状态
        elseif ($命令 === '状态') {
            $响应 = "生命值: {$_SESSION['游戏状态']['生命值']}/100 | ";
            $响应 .= "攻击力: {$_SESSION['游戏状态']['攻击力']} | ";
            $响应 .= "分数: {$_SESSION['游戏状态']['分数']}";

            if (!empty($_SESSION['游戏状态']['任务'])) {
                $响应 .= "<br>当前任务: " . $_SESSION['游戏状态']['任务'];
            }
        }
        // 与人物交谈
        elseif (preg_match('/^(问|交谈|说话|打听) (.+)$/', $命令, $匹配)) {
            $人物 = $匹配[2];
            $当前位置 = $_SESSION['游戏状态']['当前位置'];

            if (isset($游戏世界['地图'][$当前位置]['人物'][$人物])) {
                // 直接调用函数而不是通过调用全局函数
                if (function_exists('人物交谈')) {
                    $响应 = 调用全局函数('人物交谈', [$人物]);
                } else {
                    $响应 = "对话系统不可用";
                }
            } else {
                $响应 = "这里没有叫{$人物}的人。";
            }
        }
        // 战斗系统
        elseif (preg_match('/^(攻击|战斗|打) (.+)$/', $命令, $匹配)) {
            $目标 = $匹配[2];
            $响应 = 调用全局函数('战斗', [$目标]);
        }
        // 帮助命令
        elseif ($命令 === '帮助') {
            $响应 = "可用命令:<br>";
            $响应 .= "移动: 去 [方向](东、西、南、北、上、下、内、外)<br>";
            $响应 .= "观察: 查看、观察<br>";
            $响应 .= "物品: 拿 [物品]、使用 [物品]、背包<br>";
            $响应 .= "交互: 问 [人物]、攻击 [敌人]<br>";
            $响应 .= "信息: 状态、帮助、退出<br>";
            $响应 .= "提示: 注意场景描述中的细节，与人物交谈获取信息，合理使用物品。";
        }
        // 退出游戏
        elseif ($命令 === '退出') {
            $响应 = "游戏结束。你的最终得分: {$_SESSION['游戏状态']['分数']}";
            session_destroy();
        }
        // 无效命令
        else {
            $响应 = "无效命令。输入'帮助'查看可用命令。";
        }

        $_SESSION['上次响应'] = $响应;
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?')); //防止重复提交
    }
    $命令 = '';
    // 游戏初始化
    if (!isset($_SESSION['游戏世界'])) {
        // 检查是否有上传的地图文件
        if (isset($_FILES['地图文件'])) {
            $文件内容 = file_get_contents($_FILES['地图文件']['tmp_name']);
            $_SESSION['游戏世界'] = json_decode($文件内容, true);
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
        if (isset($_GET['使用默认地图'])) {
            if (isset($_GET['debug'])) {
                include __DIR__ . '/map_test.php'; // 改为测试地图
                初始化测试地图(); // 改为测试地图初始化函数
                header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
                exit;
            }
            include __DIR__ . '/map_default.php';
            初始化默认地图();
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
    } else {
        // 确保游戏状态已初始化
        if (!isset($_SESSION['游戏状态'])) {
            $_SESSION['游戏状态'] = [
                '当前位置' => array_key_first($_SESSION['游戏世界']['地图']),
                '背包' => [],
                '生命值' => 100,
                '攻击力' => 10,
                '分数' => 0,
                '已访问地点' => []
            ];
        }
    }
} catch (Throwable $e) {
    throw new Exception($e);
}
