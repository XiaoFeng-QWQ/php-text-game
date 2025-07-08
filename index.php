<?php include __DIR__ . '/engine/game.php' ?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP文字游戏</title>
    <style>
        * {
            font-family: "新宋体";
        }

        body {
            font-family: 'Microsoft YaHei', '微软雅黑', sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
            line-height: 1.6;
            background-color: #f8f4e9;
            background-image: url(/data/common/rice-paper-2.png);
        }

        a {
            color: #8b4513;
        }

        .output {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #d4c9a8;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
            font-family: 'Microsoft YaHei', '微软雅黑', sans-serif;
            font-size: 16px;
        }

        .location-title {
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }

        .location-description {
            margin: 15px 0;
            line-height: 1.5;
        }

        .items-list,
        .characters-section {
            margin: 15px 0;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .character {
            margin-bottom: 10px;
        }

        .character-name {
            font-weight: bold;
        }

        .special-notice {
            margin: 15px 0;
            color: #ff6600;
            font-style: italic;
        }

        .script-output {
            margin: 15px 0;
        }

        .command-response {
            margin-top: 15px;
            color: #3366cc;
            font-weight: bold;
        }

        .item {
            color: #009900;
            font-weight: bold;
        }

        .input-form {
            display: flex;
            margin-bottom: 15px;
        }

        #命令 {
            flex-grow: 1;
            padding: 10px;
            font-family: 'Microsoft YaHei', '微软雅黑', sans-serif;
            font-size: 16px;
            border: 1px solid #d4c9a8;
        }

        button {
            padding: 10px 20px;
            background-color: #8b4513;
            color: white;
            border: none;
            cursor: pointer;
            font-family: 'Microsoft YaHei', '微软雅黑', sans-serif;
            font-size: 16px;
            margin-left: 10px;
        }

        button:hover {
            background-color: #a0522d;
        }

        .controls {
            text-align: right;
            margin-bottom: 15px;
        }

        h1 {
            text-align: center;
            color: #8b4513;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .script-output {
            color: #666;
            font-style: italic;
        }

        .item-owned {
            color: #999;
            text-decoration: line-through;
        }

        .map-upload {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f0e6cc;
            border-radius: 5px;
        }

        .map-upload label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="game-container">
        <h1>PHP文字游戏</h1>

        <?php if (!isset($_SESSION['游戏世界'])): ?>
            <div class="map-upload" id="welcome">
                <form method="POST" enctype="multipart/form-data">
                    <label for="地图文件">上传地图文件 (JSON格式):</label>
                    <input type="file" name="地图文件" id="地图文件" accept=".json" required>
                    <button type="submit">加载地图</button>
                </form>
                <p>或 <a href="?使用默认地图=true">使用默认地图开始游戏</a> 或 <a href="/edit.php">制作地图!</a></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['游戏世界'])): ?>
            <div class="output" id="game-output">
                <?php
                $当前位置 = $_SESSION['游戏状态']['当前位置'];
                $游戏世界 = $_SESSION['游戏世界'];
                $当前地图 = $游戏世界['地图'][$当前位置];

                // 显示位置名称
                echo "<div class='location-title'>◆ 当前位置: {$当前位置} ◆</div>";

                // 显示场景描述
                echo "<p class='location-description'>{$当前地图['描述']}</p>";

                // 显示物品
                if (!empty($当前地图['物品'])) {
                    if (!in_array($当前地图['物品'][0], $_SESSION['游戏状态']['背包'])) {
                        echo "<p class='items-list'>你可以看到: <span class='item'>"
                            . implode('</span>、<span class="item">', $当前地图['物品'])
                            . "</span></p>";
                    }
                } elseif ($命令 === '背包' || $命令 === '物品') {
                    if (empty($_SESSION['游戏状态']['背包'])) {
                        $响应 = "你的背包是空的。";
                    } else {
                        $物品计数 = array_count_values($_SESSION['游戏状态']['背包']);
                        $物品列表 = [];
                        foreach ($物品计数 as $物品 => $数量) {
                            $物品列表[] = "<span class='item'>{$物品}×{$数量}</span>";
                        }
                        $响应 = "你携带的物品: " . implode('、', $物品列表);
                    }
                }

                // 显示人物
                if (!empty($当前地图['人物'])) {
                    echo "<div class='characters-section'>"
                        . "<p class='section-title'>这里有以下人物:</p>";

                    foreach ($当前地图['人物'] as $人物 => $描述) {
                        echo "<div class='character'>"
                            . "<span class='character-name'>{$人物}</span>: "
                            . "<span class='character-desc'>{$描述}</span>"
                            . "</div>";
                    }
                    echo "</div>";
                }

                // 显示特殊信息
                if (isset($当前地图['特殊'])) {
                    echo "<div class='special-notice'>※ " . $当前地图['特殊'] . "</div>";
                }

                // 执行场景脚本并显示输出
                $场景脚本输出 = 运行场景脚本($当前位置);
                if ($场景脚本输出) {
                    echo "<div class='script-output'>{$场景脚本输出}</div>";
                }

                // 显示上次命令的响应
                if (isset($_SESSION['上次响应'])) {
                    echo "<div class='command-response'>》 " . $_SESSION['上次响应'] . "</div>";
                }
                ?>
            </div>

            <form method="POST" class="input-form" onsubmit="document.getElementById('命令').focus(); return true;">
                <input type="text" id="命令" name="命令" placeholder="输入命令..." autofocus>
                <button type="submit">执行</button>
            </form>

            <div class="controls">
                <button onclick="location.href='?重置=true'">重新开始游戏</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // 自动滚动到输出底部
        var output = document.getElementById('game-output');
        if (output) {
            output.scrollTop = output.scrollHeight;
            document.getElementById('命令').focus();
        }
    </script>

    <?php include __DIR__ . '/data/common/footer.php' ?>
</body>

</html>