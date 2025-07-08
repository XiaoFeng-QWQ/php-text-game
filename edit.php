<?php include __DIR__ . '/engine/edit.php' ?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP文字游戏地图制作器</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', '微软雅黑', sans-serif;
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

        .container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2,
        h3 {
            color: #8b4513;
            border-bottom: 1px solid #d4c9a8;
            padding-bottom: 10px;
        }

        .panel {
            background-color: #fff;
            border: 1px solid #d4c9a8;
            padding: 15px;
        }

        button,
        input[type="submit"] {
            padding: 8px 15px;
            background-color: #8b4513;
            color: white;
            border: none;
            cursor: pointer;
            font-family: 'Microsoft YaHei', '微软雅黑', sans-serif;
            border-radius: 3px;
        }

        button:hover,
        input[type="submit"]:hover {
            background-color: #a0522d;
        }

        input,
        textarea,
        select {
            border: 1px solid #d4c9a8;
            padding: 8px;
            margin-bottom: 10px;
            width: 100%;
            font-family: 'Microsoft YaHei', '微软雅黑', sans-serif;
        }

        .scene-item {
            border-bottom: 1px dashed #d4c9a8;
            padding: 10px 0;
        }

        .scene-item:last-child {
            border-bottom: none;
        }

        .active {
            background-color: #d2691e;
            color: white;
            padding: 2px;
        }

        .CodeMirror {
            border: 1px solid #d4c9a8;
            height: auto;
            font-family: 'Microsoft YaHei', '微软雅黑', monospace;
        }

        .item {
            color: #9932cc;
            font-weight: bold;
        }

        .special {
            color: #d2691e;
            font-style: italic;
        }

        footer {
            margin-top: 30px;
            padding: 15px 0;
            text-align: center;
            border-top: 1px solid #d4c9a8;
            color: #8b4513;
            font-size: 14px;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
</head>

<body>
    <div class="container">
        <h1>PHP文字游戏地图制作器 <button id="toggleView" onclick="toggleViewMode()">切换到3D视图(BETA)</button></h1>
        <div style="display: flex; gap: 20px;">
            <!-- 左侧面板 - 场景列表 -->
            <div id="editor2D" class="panel" style="flex: 1; display: block;">
                <h2>场景列表</h2>
                <div style=" max-height: 500px; overflow-y: auto; border: 1px solid #d4c9a8; padding: 10px; margin-bottom: 15px;">
                    <?php if (empty($_SESSION['地图编辑器']['场景列表'])): ?>
                        <p style="color: #666; font-style: italic;">暂无场景</p>
                    <?php else: ?>
                        <?php foreach ($_SESSION['地图编辑器']['场景列表'] ?? [] as $名称 => $数据): ?>
                            <div class="scene-item">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong><?= $名称 ?></strong>
                                    <div style="display: flex; gap: 5px;">
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="加载场景名称" value="<?= $名称 ?>">
                                            <button type="submit" name="编辑器命令" value="加载场景">编辑</button>
                                        </form>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="删除场景名称" value="<?= $名称 ?>">
                                            <button type="submit" name="编辑器命令" value="删除场景" onclick="return confirm('确定删除此场景吗？')">删除</button>
                                        </form>
                                    </div>
                                </div>
                                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                    <p>物品: <?= implode(', ', $数据['物品'] ?? []) ?></p>
                                    <p>出口: <?= implode(', ', array_map(fn($k, $v) => "$k:$v", array_keys($数据['出口'] ?? []), $数据['出口'] ?? [])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="padding-top: 15px; margin-top: 15px; border-top: 1px solid #d4c9a8;">
                    <h3>导入/导出</h3>
                    <form method="POST" enctype="multipart/form-data" style="margin-bottom: 15px;">
                        <input type="file" name="地图文件" accept=".json" style="margin-bottom: 10px;">
                        <button type="submit" name="编辑器命令" value="导入地图">导入地图</button>
                    </form>
                    <form method="POST">
                        <button type="submit" name="编辑器命令" value="导出地图">导出地图为JSON</button>
                    </form>
                </div>
            </div>

            <!-- 右侧面板 - 编辑器和预览 -->
            <div style="flex: 2; display: flex; flex-direction: column; gap: 15px;">
                <form method="post" class="panel">
                    <h2>场景编辑器</h2>

                    <h3>全局设置</h3>

                    <div style="margin-bottom: 15px;">
                        <label for="全局脚本" style="display: block; margin-bottom: 5px; font-weight: bold;">全局脚本 (PHP代码 | 函数内return返回数据):</label>
                        <textarea id="全局脚本" name="全局脚本"><?= $全局脚本 ?></textarea>
                        <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <button type="button" onclick="测试全局脚本()" style="background-color: #d2691e;">测试全局脚本</button>
                            <?php if (isset($_SESSION['全局脚本测试结果'])): ?>
                                <span style="color: <?= strpos($_SESSION['全局脚本测试结果'], '错误') !== false ? '#d9534f' : '#5cb85c' ?>;">
                                    <?= $_SESSION['全局脚本测试结果'] ?>
                                </span>
                                <?php unset($_SESSION['全局脚本测试结果']); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="物品数据" style="display: block; margin-bottom: 5px; font-weight: bold;">物品数据 (格式: 物品名称: 属性: 值):</label>
                        <textarea id="物品数据" name="物品数据" style="height: 150px; font-family: monospace;" placeholder="例如:
火把:
  使用效果: return '你点燃了火把';
  最大数量: 1

草药:
  拾取效果: return '你采集了一些草药';
  使用效果: $_SESSION['游戏状态']['生命值'] += 20; return '恢复了20点生命值';
  最大数量: 3"><?= 格式化物品数据($物品数据) ?></textarea>
                    </div>

                    <input type="hidden" name="编辑器命令" value="保存场景">

                    <div style="margin-bottom: 15px;">
                        <label for="场景名称" style="display: block; margin-bottom: 5px; font-weight: bold;">场景名称:</label>
                        <input type="text" id="场景名称" name="场景名称" value="<?= $当前场景 ?>" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="场景描述" style="display: block; margin-bottom: 5px; font-weight: bold;">场景描述:</label>
                        <textarea id="场景描述" name="场景描述" style="height: 100px;" required><?= $场景数据['描述'] ?? '' ?></textarea>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="场景出口" style="display: block; margin-bottom: 5px; font-weight: bold;">出口 (格式: 方向:目标场景，每行一个):</label>
                        <textarea id="场景出口" name="场景出口" style="height: 100px;"><?= 格式化出口($场景数据['出口'] ?? []) ?></textarea>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="场景物品" style="display: block; margin-bottom: 5px; font-weight: bold;">物品 (逗号分隔):</label>
                        <input type="text" id="场景物品" name="场景物品" value="<?= implode(', ', $场景数据['物品'] ?? []) ?>">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="场景人物" style="display: block; margin-bottom: 5px; font-weight: bold;">人物 (格式: 名称:描述，每行一个):</label>
                        <textarea id="场景人物" name="场景人物" style="height: 100px;"><?= 格式化人物($场景数据['人物'] ?? []) ?></textarea>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="场景特殊" style="display: block; margin-bottom: 5px; font-weight: bold;">特殊信息:</label>
                        <input type="text" id="场景特殊" name="场景特殊" value="<?= $场景数据['特殊'] ?? '' ?>">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="场景脚本" style="display: block; margin-bottom: 5px; font-weight: bold;">自定义脚本 (PHP代码):</label>
                        <textarea id="场景脚本" name="场景脚本" style="height: 200px; font-family: monospace;" placeholder="在此处编写场景交互逻辑..."><?= $场景数据['脚本'] ?? '' ?></textarea>

                        <div style="background-color: #f0e6cc; padding: 10px; border-radius: 5px; border: 1px solid #d4c9a8; margin-top: 10px;">
                            <h4 style="font-weight: bold; color: #8b4513; margin-bottom: 5px;">脚本编写指南：</h4>
                            <ul style="color: #8b4513; padding-left: 20px; margin: 0;">
                                <li><strong>可用变量：</strong> <code>$玩家</code>, <code>$场景</code>, <code>$_SESSION</code></li>
                                <li><strong>常用函数：</strong> <code>物品在背包中()</code>, <code>添加物品()</code>, <code>移除物品()</code></li>
                                <li><strong>安全提示：</strong> 请勿编写危险代码(如文件操作、数据库查询等)</li>
                            </ul>
                        </div>

                        <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <button type="button" onclick="测试脚本()" style="background-color: #d2691e;">测试脚本</button>
                            <?php if (isset($_SESSION['脚本测试结果'])): ?>
                                <span style="color: <?= strpos($_SESSION['脚本测试结果'], '错误') !== false ? '#d9534f' : '#5cb85c' ?>;">
                                    <?= $_SESSION['脚本测试结果'] ?>
                                </span>
                                <?php unset($_SESSION['脚本测试结果']); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" style="background-color: #5cb85c;">保存场景</button>
                        <button type="submit" name="编辑器命令" value="新建场景">新建场景</button>
                        <?php if (!($_SESSION['地图编辑器']['已保存'] ?? false)): ?>
                            <span style="color: #d9534f; font-weight: bold; display: flex; align-items: center; padding: 0 10px;">(未保存)</span>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- 场景预览 -->
                <div class="panel">
                    <h2>当前场景预览</h2>
                    <div style="background-color: #f8f4e9; padding: 15px; border-radius: 5px; border: 1px solid #d4c9a8;">
                        <h3 style="color: #8b4513; font-size: 18px; margin-bottom: 10px;"><?= $当前场景 ?></h3>
                        <p style="margin-bottom: 15px; white-space: pre-line;"><?= $场景数据['描述'] ?? '' ?></p>

                        <?php if (!empty($场景数据['物品'])): ?>
                            <p style="margin-bottom: 10px;"><strong>物品:</strong> <?= implode(', ', $场景数据['物品']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($场景数据['人物'])): ?>
                            <p style="margin-bottom: 5px;"><strong>人物:</strong></p>
                            <ul style="margin-bottom: 15px; padding-left: 20px;">
                                <?php foreach ($场景数据['人物'] as $名称 => $描述): ?>
                                    <li style="margin-bottom: 5px;"><strong><?= $名称 ?>:</strong> <?= $描述 ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($场景数据['出口'])): ?>
                            <p style="margin-bottom: 5px;"><strong>出口:</strong></p>
                            <ul style="margin-bottom: 15px; padding-left: 20px;">
                                <?php foreach ($场景数据['出口'] as $方向 => $目标): ?>
                                    <li style="margin-bottom: 5px;"><?= $方向 ?> → <?= $目标 ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($场景数据['特殊'])): ?>
                            <p class="special">※ <?= $场景数据['特殊'] ?></p>
                        <?php endif; ?>

                        <?php if (!empty($场景数据['脚本'])): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #d4c9a8;">
                                <p style="font-size: 13px; color: #666;">脚本逻辑已附加</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- 3D编辑器部分 -->
        <div id="editor3D" style="display: none; height: 800px; border: 1px solid #d4c9a8;">
            <div style="display: flex; height: 100%;">
                <!-- 3D场景视图 -->
                <div style="width: 50%;" id="scene3D"></div>

                <!-- 3D编辑面板 -->
                <div class="panel" style="flex: 1; overflow-y: auto; width: 300px;">
                    <h2>3D场景工具[BETA] </h2>

                    <div class="panel" style="margin-bottom: 15px;">
                        <h3>场景节点</h3>
                        <div id="sceneNodes" style="max-height: 200px; overflow-y: auto;">
                            <!-- 动态生成的场景节点列表 -->
                        </div>
                        <div style="display: flex; gap: 5px; margin-top: 10px;">
                            <button onclick="addNewSceneNode()" style="flex: 1;">添加场景</button>
                            <button onclick="deleteSelectedNode()" style="flex: 1; background-color: #d9534f;">删除场景</button>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>属性编辑器</h3>
                        <div id="propertyEditor">
                            <p>选择场景元素进行编辑</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.min.js"></script>
    <script src="editScript.js"></script>
    <script>
        function loadSceneNodesFromSession() {
            const sceneList = <?php echo json_encode($_SESSION['地图编辑器']['场景列表'] ?? [], JSON_UNESCAPED_UNICODE); ?>;

            // 清空现有场景
            sceneGraph = {};
            document.getElementById('sceneNodes').innerHTML = '';

            // 首先创建所有场景节点（不处理连接）
            for (const [name, data] of Object.entries(sceneList)) {
                sceneGraph[name] = {
                    name: name,
                    data: data || {
                        描述: '这是一个新场景',
                        出口: {},
                        物品: [],
                        人物: {},
                        特殊: '',
                        脚本: ''
                    },
                    objects: []
                };

                // 仅创建基本组，不处理连接
                const sceneGroup = new THREE.Group();
                sceneGroup.name = name;
                sceneGroup.position.set(
                    Math.cos(Math.random() * Math.PI * 2) * (10 + Math.random() * 10),
                    0,
                    Math.sin(Math.random() * Math.PI * 2) * (10 + Math.random() * 10)
                );
                scene3D.add(sceneGroup);
                sceneGraph[name].group = sceneGroup;

                // 添加到UI列表
                const nodeElement = document.createElement('div');
                nodeElement.className = 'scene-node';
                nodeElement.textContent = name;
                nodeElement.dataset.sceneName = name;
                nodeElement.onclick = function() {
                    selectSceneNode(name);
                };
                document.getElementById('sceneNodes').appendChild(nodeElement);
            }

            // 然后为每个场景创建完整的3D表示（此时所有场景组都已存在）
            for (const [name, data] of Object.entries(sceneList)) {
                createSceneRepresentation(name, data);
            }

            // 如果没有场景，创建一个默认场景
            if (Object.keys(sceneList).length === 0) {
                addNewSceneNode();
            }
        }
    </script>
    <?php include __DIR__ . '/data/common/footer.php' ?>
</body>

</html>