// 初始化代码编辑器
const 脚本编辑器 = CodeMirror.fromTextArea(document.getElementById('场景脚本'), {
    mode: 'application/x-httpd-php',
    lineNumbers: true,
    matchBrackets: true,
    indentUnit: 4,
    lineWrapping: true
});

const 全局脚本编辑器 = CodeMirror.fromTextArea(document.getElementById('全局脚本'), {
    mode: 'application/x-httpd-php',
    lineNumbers: true,
    matchBrackets: true,
    indentUnit: 4,
    lineWrapping: true
});

// 脚本测试函数
function 测试脚本() {
    const 表单 = document.createElement('form');
    表单.method = 'POST';
    表单.style.display = 'none';

    const 命令输入 = document.createElement('input');
    命令输入.name = '编辑器命令';
    命令输入.value = '测试脚本';
    表单.appendChild(命令输入);

    const 脚本内容 = document.createElement('input');
    脚本内容.name = '测试脚本内容';
    脚本内容.value = 脚本编辑器.getValue();
    表单.appendChild(脚本内容);

    document.body.appendChild(表单);
    表单.submit();
}

function 测试全局脚本() {
    const 表单 = document.createElement('form');
    表单.method = 'POST';
    表单.style.display = 'none';

    const 命令输入 = document.createElement('input');
    命令输入.name = '编辑器命令';
    命令输入.value = '测试全局脚本';
    表单.appendChild(命令输入);

    const 脚本内容 = document.createElement('input');
    脚本内容.name = '测试全局脚本内容';
    脚本内容.value = 全局脚本编辑器.getValue();
    表单.appendChild(脚本内容);

    document.body.appendChild(表单);
    表单.submit();
}

// 页面加载后自动调整编辑器高度
window.addEventListener('load', () => {
    const 编辑器容器 = 脚本编辑器.getWrapperElement();
    编辑器容器.style.height = '200px';
    编辑器容器.style.transition = 'height 0.3s';

    编辑器容器.addEventListener('click', () => {
        编辑器容器.style.height = '400px';
    });

    document.addEventListener('click', (e) => {
        if (!编辑器容器.contains(e.target)) {
            编辑器容器.style.height = '200px';
        }
    });

    const 全局编辑器容器 = 全局脚本编辑器.getWrapperElement();
    全局编辑器容器.style.height = '200px';
    全局编辑器容器.style.transition = 'height 0.3s';

    全局编辑器容器.addEventListener('click', () => {
        全局编辑器容器.style.height = '400px';
    });

    document.addEventListener('click', (e) => {
        if (!全局编辑器容器.contains(e.target)) {
            全局编辑器容器.style.height = '200px';
        }
    });
});

// 3D编辑器核心变量
let scene3D, camera, renderer, controls, sceneManager;
let sceneGraph = {};
let selectedSceneNode = null;
let selectedObject = null;
let stats;

// 初始化3D编辑器
function init3DEditor() {
    // 创建场景
    scene3D = new THREE.Scene();
    scene3D.background = new THREE.Color(0xf0f0f0);

    // 创建相机
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(10, 10, 10);

    // 创建渲染器
    renderer = new THREE.WebGLRenderer({
        antialias: true
    });
    renderer.setSize(document.getElementById('scene3D').clientWidth, document.getElementById('scene3D').clientHeight);
    renderer.shadowMap.enabled = true;
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.outputEncoding = THREE.sRGBEncoding;
    document.getElementById('scene3D').appendChild(renderer.domElement);

    // 添加轨道控制器
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.25;
    controls.screenSpacePanning = false;
    controls.maxPolarAngle = Math.PI; // 允许相机旋转到下方
    controls.minDistance = 1;
    controls.maxDistance = 50;

    // 添加光源
    const ambientLight = new THREE.AmbientLight(0x404040);
    scene3D.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(1, 1, 1);
    directionalLight.castShadow = true;
    directionalLight.shadow.mapSize.width = 1024;
    directionalLight.shadow.mapSize.height = 1024;
    scene3D.add(directionalLight);

    // 添加网格地面
    const gridHelper = new THREE.GridHelper(20, 20);
    scene3D.add(gridHelper);

    // 初始化场景管理器
    sceneManager = {
        activeScene: null,
        scenes: {},
        setActive: function (name) {
            if (this.activeScene) {
                this.scenes[this.activeScene].visible = false;
            }
            this.activeScene = name;
            if (name && this.scenes[name]) {
                this.scenes[name].visible = true;
                this.focusCameraOnScene(name);
            }
        },
        addScene: function (name, group) {
            this.scenes[name] = group;
            group.visible = false;
        },
        focusCameraOnScene: function (name) {
            const sceneGroup = this.scenes[name];
            const bbox = new THREE.Box3().setFromObject(sceneGroup);
            const center = bbox.getCenter(new THREE.Vector3());
            const size = bbox.getSize(new THREE.Vector3());
            const maxDim = Math.max(size.x, size.y, size.z);
            const fov = camera.fov * (Math.PI / 180);
            const cameraZ = Math.abs(maxDim / Math.sin(fov / 2));

            // 平滑过渡
            gsap.to(camera.position, {
                x: center.x,
                y: center.y + 5,
                z: center.z + cameraZ * 1.2,
                duration: 0.5,
                onUpdate: () => {
                    controls.target.copy(center);
                    controls.update();
                }
            });
        }
    };

    // 加载场景节点
    loadSceneNodesFromSession();

    // 开始动画循环
    animate();
}

// 动画循环
function animate() {
    requestAnimationFrame(animate);

    // 只在需要时更新
    if (controls.enabled) {
        controls.update();
        renderer.render(scene3D, camera);
    }
}

// 添加场景节点到图形
function addSceneNodeToGraph(name, data) {
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

    // 创建3D表示
    createSceneRepresentation(name, sceneGraph[name].data);

    // 添加到UI列表
    const nodeElement = document.createElement('div');
    nodeElement.className = 'scene-node';
    nodeElement.textContent = name;
    nodeElement.dataset.sceneName = name;
    nodeElement.onclick = function () {
        selectSceneNode(name);
    };

    document.getElementById('sceneNodes').appendChild(nodeElement);
}

// 创建场景的3D表示
function createSceneRepresentation(name, data) {
    // 创建一个组来包含场景中的所有对象
    const sceneGroup = new THREE.Group();
    sceneGroup.name = name;

    // 随机位置分布，避免重叠
    const angle = Math.random() * Math.PI * 2;
    const distance = 10 + Math.random() * 10;
    sceneGroup.position.set(
        Math.cos(angle) * distance,
        0,
        Math.sin(angle) * distance
    );

    scene3D.add(sceneGroup);
    sceneManager.addScene(name, sceneGroup);

    // 创建场景边界框
    const bbox = new THREE.Box3().setFromObject(sceneGroup);
    const bboxHelper = new THREE.Box3Helper(bbox, 0xffff00);
    bboxHelper.visible = false;
    sceneGroup.add(bboxHelper);

    // 添加场景中心区域
    const centerArea = new THREE.Mesh(
        new THREE.CircleGeometry(1.5, 32),
        new THREE.MeshBasicMaterial({
            color: 0x00ff00,
            transparent: true,
            opacity: 0.3,
            side: THREE.DoubleSide
        })
    );
    centerArea.rotation.x = -Math.PI / 2;
    centerArea.name = `${name}_center_area`;
    sceneGroup.add(centerArea);

    // 添加场景中心标记
    const centerMarker = new THREE.Mesh(
        new THREE.BoxGeometry(1, 0.1, 1),
        new THREE.MeshBasicMaterial({
            color: 0x00ff00
        })
    );
    centerMarker.name = `${name}_center`;
    sceneGroup.add(centerMarker);

    // 添加场景名称标签
    const nameLabel = createTextLabel(name, 0x333333, 0.8);
    nameLabel.position.set(0, 2, 0);
    nameLabel.name = `${name}_label`;
    sceneGroup.add(nameLabel);

    // 添加出口标记
    if (data.出口) {
        const exitGroup = new THREE.Group();
        exitGroup.name = `${name}_exits`;

        Object.entries(data.出口).forEach(([direction, target], index) => {
            // 使用箭头表示出口
            const arrow = new THREE.ArrowHelper(
                new THREE.Vector3(0, 0, 1),
                new THREE.Vector3(0, 0, 0),
                1.5,
                0xff0000,
                0.3,
                0.2
            );

            // 设置方向
            switch (direction) {
                case '北': arrow.rotation.y = Math.PI; break;
                case '南': break;
                case '东': arrow.rotation.y = Math.PI / 2; break;
                case '西': arrow.rotation.y = -Math.PI / 2; break;
            }

            arrow.name = `${name}_exit_${direction}`;
            exitGroup.add(arrow);

            // 添加连接线
            if (target && sceneGraph[target] && sceneGraph[target].group) {
                const targetGroup = sceneGraph[target].group;
                const lineGeometry = new THREE.BufferGeometry().setFromPoints([
                    new THREE.Vector3(0, 0, 0),
                    targetGroup.position.clone().sub(sceneGroup.position)
                ]);
                const line = new THREE.Line(
                    lineGeometry,
                    new THREE.LineDashedMaterial({
                        color: 0xff0000,
                        dashSize: 0.5,
                        gapSize: 0.2,
                        linewidth: 1
                    })
                );
                line.computeLineDistances();
                line.name = `${name}_connection_${target}`;
                line.visible = false;
                sceneGroup.add(line);
            }
        });

        sceneGroup.add(exitGroup);
    }

    // 添加物品标记
    if (data.物品 && data.物品.length > 0) {
        const itemGroup = new THREE.Group();
        itemGroup.name = `${name}_items`;

        data.物品.forEach((item, index) => {
            // 使用不同形状表示物品
            let itemGeometry;
            switch (index % 3) {
                case 0: itemGeometry = new THREE.SphereGeometry(0.3); break;
                case 1: itemGeometry = new THREE.BoxGeometry(0.5, 0.5, 0.5); break;
                case 2: itemGeometry = new THREE.ConeGeometry(0.3, 0.6, 4); break;
            }

            const itemMaterial = new THREE.MeshPhongMaterial({
                color: 0x9932cc,
                shininess: 30
            });
            const itemMarker = new THREE.Mesh(itemGeometry, itemMaterial);
            itemMarker.castShadow = true;

            // 在场景周围均匀分布物品
            const angle = (index / data.物品.length) * Math.PI * 2;
            itemMarker.position.set(
                Math.cos(angle) * 1.5,
                0.3,
                Math.sin(angle) * 1.5
            );
            itemMarker.name = `${name}_item_${item}`;
            itemGroup.add(itemMarker);

            // 添加物品标签
            const itemLabel = createTextLabel(item, 0x9932cc, 0.5);
            itemLabel.position.copy(itemMarker.position);
            itemLabel.position.y += 0.8;
            itemLabel.name = `${name}_item_label_${item}`;
            itemGroup.add(itemLabel);
        });

        sceneGroup.add(itemGroup);
    }

    // 初始隐藏所有场景，只显示第一个
    sceneGroup.visible = (Object.keys(sceneGraph).length === 1);
    if (sceneGroup.visible) {
        sceneManager.setActive(name);
    }
}

// 创建文本标签
function createTextLabel(text, color, scale = 1) {
    const canvas = document.createElement('canvas');
    canvas.width = 512;
    canvas.height = 256;
    const context = canvas.getContext('2d');

    // 背景
    context.fillStyle = `rgba(${(color >> 16) & 0xff}, ${(color >> 8) & 0xff}, ${color & 0xff}, 0.7)`;
    context.fillRect(0, 0, canvas.width, canvas.height);

    // 文字
    context.font = 'Bold 48px Arial';
    context.fillStyle = 'white';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillText(text, canvas.width / 2, canvas.height / 2);

    const texture = new THREE.CanvasTexture(canvas);
    const spriteMaterial = new THREE.SpriteMaterial({
        map: texture,
        transparent: true
    });
    const sprite = new THREE.Sprite(spriteMaterial);
    sprite.scale.set(scale * 4, scale * 2, scale);
    return sprite;
}

// 添加新场景节点
function addNewSceneNode() {
    const sceneName = prompt('请输入新场景名称:', '新场景');
    if (sceneName && sceneName.trim() !== '') {
        if (sceneGraph[sceneName]) {
            alert('场景名称已存在!');
            return;
        }

        const newSceneData = {
            描述: '这是一个新场景',
            出口: {},
            物品: [],
            人物: {},
            特殊: '',
            脚本: ''
        };

        addSceneNodeToGraph(sceneName, newSceneData);
        selectSceneNode(sceneName);

        // 更新到服务器
        updateSceneToServer(sceneName, newSceneData);
    }
}

// 删除选中的场景节点
function deleteSelectedNode() {
    if (!selectedSceneNode) {
        alert('请先选择一个场景!');
        return;
    }

    if (confirm(`确定要删除场景 "${selectedSceneNode}" 吗?`)) {
        // 从3D场景中移除
        const sceneGroup = sceneGraph[selectedSceneNode].group;
        scene3D.remove(sceneGroup);

        // 从图形中移除
        delete sceneGraph[selectedSceneNode];

        // 从UI中移除
        const nodes = document.getElementById('sceneNodes').children;
        for (let i = 0; i < nodes.length; i++) {
            if (nodes[i].dataset.sceneName === selectedSceneNode) {
                nodes[i].remove();
                break;
            }
        }

        // 重置选择
        selectedSceneNode = null;
        document.getElementById('propertyEditor').innerHTML = '<p>选择场景元素进行编辑</p>';

        // 更新到服务器
        deleteSceneFromServer(selectedSceneNode);
    }
}

// 选择场景节点
function selectSceneNode(sceneName) {
    // 更新UI选择状态
    const nodes = document.getElementById('sceneNodes').children;
    for (let i = 0; i < nodes.length; i++) {
        nodes[i].classList.toggle('active', nodes[i].dataset.sceneName === sceneName);
    }

    // 使用场景管理器控制可见性
    sceneManager.setActive(sceneName);

    selectedSceneNode = sceneName;
    updatePropertyEditor(sceneGraph[sceneName]);

    // 显示连接线
    Object.values(sceneGraph).forEach(scene => {
        updateSceneConnections(scene.name);
    });
}

// 选择对象
function selectObject(object) {
    try {
        // 清除之前的选择高亮
        if (selectedObject && selectedObject.material) {
            // 恢复原始发光颜色
            if (selectedObject.material.emissive && selectedObject.userData.originalEmissive !== undefined) {
                selectedObject.material.emissive.setHex(selectedObject.userData.originalEmissive);
            }

            // 如果是特殊高亮材质，恢复原始材质
            if (selectedObject.userData.originalMaterial) {
                selectedObject.material = selectedObject.userData.originalMaterial;
                delete selectedObject.userData.originalMaterial;
            }
        }

        // 更新当前选择
        selectedObject = object;

        // 高亮选中的对象
        if (object && object.material) {
            // 保存原始状态
            if (object.material.emissive) {
                object.userData.originalEmissive = object.material.emissive.getHex();
                object.material.emissive.setHex(0xffff00);
            } else {
                // 如果材质没有emissive属性，创建高亮副本
                object.userData.originalMaterial = object.material;
                object.material = object.material.clone();
                object.material.emissive = new THREE.Color(0xffff00);
                object.material.needsUpdate = true;
            }
        }

        // 更新属性编辑器
        updateObjectPropertyEditor(object);
    } catch (error) {
        console.error('选择对象时出错:', error);
        selectedObject = null;
    }
}

// 更新属性编辑器
function updatePropertyEditor(sceneData) {
    const editor = document.getElementById('propertyEditor');

    editor.innerHTML = `
    <div class="property-panel">
        <h4>${sceneData.name}</h4>
        
        <div class="property-section">
            <label>场景名称</label>
            <input type="text" id="sceneNameEdit" value="${sceneData.name}" 
                   onchange="updateSceneProperty('name', this.value)">
            
            <label>场景描述</label>
            <textarea id="sceneDescEdit" onchange="updateSceneProperty('description', this.value)"
                      placeholder="输入场景描述...">${sceneData.data.描述 || ''}</textarea>
        </div>
        
        <div class="property-section">
            <h5>出口设置</h5>
            <div id="exitControls">
                ${Object.entries(sceneData.data.出口 || {}).map(([dir, target]) => `
                    <div class="exit-item">
                        <select onchange="updateExitDirection('${dir}', this.value)">
                            <option value="北" ${dir === '北' ? 'selected' : ''}>北</option>
                            <option value="南" ${dir === '南' ? 'selected' : ''}>南</option>
                            <option value="东" ${dir === '东' ? 'selected' : ''}>东</option>
                            <option value="西" ${dir === '西' ? 'selected' : ''}>西</option>
                        </select>
                        <select onchange="updateExitTarget('${dir}', this.value)">
                            ${Object.keys(sceneGraph).map(name =>
        `<option value="${name}" ${target === name ? 'selected' : ''}>${name}</option>`
    ).join('')}
                        </select>
                        <button onclick="removeExit('${dir}')">删除</button>
                    </div>
                `).join('')}
            </div>
            <button onclick="addNewExit()">添加出口</button>
        </div>
        
        <div class="property-section">
            <h5>物品管理</h5>
            <div id="itemList">
                ${(sceneData.data.物品 || []).map(item => `
                    <div class="item-tag">
                        ${item}
                        <button onclick="removeItem('${item}')">×</button>
                    </div>
                `).join('')}
            </div>
            <div class="add-item">
                <input type="text" id="newItemInput" placeholder="输入物品名称">
                <button onclick="addItem()">添加</button>
            </div>
        </div>
        
        <div class="actions">
            <button class="save-btn" onclick="saveSceneChanges('${sceneData.name}')">保存</button>
            <button class="preview-btn" onclick="previewScene('${sceneData.name}')">预览</button>
        </div>
    </div>`;
}

// 更新对象属性编辑器
function updateObjectPropertyEditor(object) {
    const editor = document.getElementById('propertyEditor');
    if (!object) {
        editor.innerHTML = '<p>选择场景元素进行编辑</p>';
        return;
    }

    let properties = '';
    if (object.name.includes('_item_')) {
        const itemName = object.name.split('_item_')[1];
        properties = `
            <h4>物品: ${itemName}</h4>
            <div class="property-section">
                <label>物品名称</label>
                <input type="text" value="${itemName}" onchange="updateObjectProperty('name', this.value)">
                
                <label>位置 X</label>
                <input type="number" value="${object.position.x.toFixed(2)}" step="0.1" 
                       onchange="updateObjectProperty('position.x', parseFloat(this.value))">
                
                <label>位置 Y</label>
                <input type="number" value="${object.position.y.toFixed(2)}" step="0.1" 
                       onchange="updateObjectProperty('position.y', parseFloat(this.value))">
                
                <label>位置 Z</label>
                <input type="number" value="${object.position.z.toFixed(2)}" step="0.1" 
                       onchange="updateObjectProperty('position.z', parseFloat(this.value))">
            </div>
            <button onclick="deleteSelectedObject()">删除物品</button>
        `;
    } else if (object.name.includes('_exit_')) {
        const parts = object.name.split('_');
        const dir = parts[2];
        properties = `
            <h4>出口: ${dir}</h4>
            <div class="property-section">
                <label>方向</label>
                <select onchange="updateExitDirection('${dir}', this.value)">
                    <option value="北" ${dir === '北' ? 'selected' : ''}>北</option>
                    <option value="南" ${dir === '南' ? 'selected' : ''}>南</option>
                    <option value="东" ${dir === '东' ? 'selected' : ''}>东</option>
                    <option value="西" ${dir === '西' ? 'selected' : ''}>西</option>
                </select>
                
                <label>目标场景</label>
                <select onchange="updateExitTarget('${dir}', this.value)">
                    ${Object.keys(sceneGraph).map(name =>
            `<option value="${name}" ${sceneGraph[selectedSceneNode].data.出口[dir] === name ? 'selected' : ''}>${name}</option>`
        ).join('')}
                </select>
            </div>
            <button onclick="removeExit('${dir}')">删除出口</button>
        `;
    }

    editor.innerHTML = `
    <div class="property-panel">
        ${properties}
    </div>`;
}

function updateSceneConnections(sceneName) {
    const sceneData = sceneGraph[sceneName];
    if (!sceneData || !sceneData.group) return;

    // 移除旧的连接线
    sceneData.group.children.forEach(child => {
        if (child.name && child.name.includes('_connection_')) {
            sceneData.group.remove(child);
        }
    });

    // 添加新的连接线
    if (sceneData.data.出口) {
        Object.entries(sceneData.data.出口).forEach(([direction, target]) => {
            if (target && sceneGraph[target] && sceneGraph[target].group) {
                const targetGroup = sceneGraph[target].group;
                const lineGeometry = new THREE.BufferGeometry().setFromPoints([
                    new THREE.Vector3(0, 0, 0),
                    targetGroup.position.clone().sub(sceneData.group.position)
                ]);
                const line = new THREE.Line(
                    lineGeometry,
                    new THREE.LineDashedMaterial({
                        color: 0xff0000,
                        dashSize: 0.5,
                        gapSize: 0.2
                    })
                );
                line.computeLineDistances();
                line.name = `${sceneName}_connection_${target}`;
                line.visible = (selectedSceneNode === sceneName);
                sceneData.group.add(line);
            }
        });
    }
}

// 更新场景属性
function updateSceneProperty(property, value) {
    if (!selectedSceneNode || !sceneGraph[selectedSceneNode]) return;

    switch (property) {
        case 'name':
            // 更新场景名称需要特殊处理
            if (value !== selectedSceneNode && !sceneGraph[value]) {
                const oldName = selectedSceneNode;
                sceneGraph[value] = sceneGraph[oldName];
                delete sceneGraph[oldName];

                // 更新3D场景中的名称
                sceneGraph[value].group.name = value;

                // 更新UI中的名称
                const nodeElement = document.querySelector(`.scene-node[data-scene-name="${oldName}"]`);
                nodeElement.textContent = value;
                nodeElement.dataset.sceneName = value;

                selectedSceneNode = value;
                sceneGraph[value].name = value;

                // 更新所有相关出口
                Object.values(sceneGraph).forEach(scene => {
                    if (scene.data.出口) {
                        Object.entries(scene.data.出口).forEach(([dir, target]) => {
                            if (target === oldName) {
                                scene.data.出口[dir] = value;
                            }
                        });
                    }
                });

                // 更新3D标签
                sceneGraph[value].group.traverse(child => {
                    if (child.name === `${oldName}_label`) {
                        child.name = `${value}_label`;
                        // 更新标签纹理
                        const newLabel = createTextLabel(value, 0x333333, 0.8);
                        newLabel.position.copy(child.position);
                        sceneGraph[value].group.remove(child);
                        sceneGraph[value].group.add(newLabel);
                    }
                });
            }
            break;
        case 'description':
            sceneGraph[selectedSceneNode].data.描述 = value;
            break;
    }

    updateSceneVisuals(selectedSceneNode);
}

// 更新出口方向
function updateExitDirection(oldDir, newDir) {
    if (!selectedSceneNode || !sceneGraph[selectedSceneNode].data.出口[oldDir]) return;

    const target = sceneGraph[selectedSceneNode].data.出口[oldDir];
    delete sceneGraph[selectedSceneNode].data.出口[oldDir];
    sceneGraph[selectedSceneNode].data.出口[newDir] = target;

    updateSceneVisuals(selectedSceneNode);
    updatePropertyEditor(sceneGraph[selectedSceneNode]);
}

// 更新出口目标
function updateExitTarget(dir, target) {
    if (!selectedSceneNode || !sceneGraph[selectedSceneNode].data.出口[dir]) return;

    sceneGraph[selectedSceneNode].data.出口[dir] = target;
    updateSceneConnections(selectedSceneNode);
}

// 添加新出口
function addNewExit() {
    if (!selectedSceneNode) return;

    const directions = ['北', '南', '东', '西'];
    const availableDirs = directions.filter(dir =>
        !sceneGraph[selectedSceneNode].data.出口?.[dir]
    );

    if (availableDirs.length === 0) {
        alert('所有方向都已设置出口!');
        return;
    }

    const newDir = availableDirs[0];
    const otherScenes = Object.keys(sceneGraph).filter(name => name !== selectedSceneNode);
    const newTarget = otherScenes.length > 0 ? otherScenes[0] : '';

    if (!sceneGraph[selectedSceneNode].data.出口) {
        sceneGraph[selectedSceneNode].data.出口 = {};
    }

    sceneGraph[selectedSceneNode].data.出口[newDir] = newTarget;
    updatePropertyEditor(sceneGraph[selectedSceneNode]);
    updateSceneVisuals(selectedSceneNode);
}

// 删除出口
function removeExit(dir) {
    if (!selectedSceneNode || !sceneGraph[selectedSceneNode].data.出口?.[dir]) return;

    delete sceneGraph[selectedSceneNode].data.出口[dir];
    updatePropertyEditor(sceneGraph[selectedSceneNode]);
    updateSceneVisuals(selectedSceneNode);
}

// 添加物品
function addItem() {
    const input = document.getElementById('newItemInput');
    const itemName = input.value.trim();

    if (itemName && selectedSceneNode) {
        if (!sceneGraph[selectedSceneNode].data.物品) {
            sceneGraph[selectedSceneNode].data.物品 = [];
        }

        if (!sceneGraph[selectedSceneNode].data.物品.includes(itemName)) {
            sceneGraph[selectedSceneNode].data.物品.push(itemName);
            updatePropertyEditor(sceneGraph[selectedSceneNode]);
            updateSceneVisuals(selectedSceneNode);
            input.value = '';
        } else {
            alert('该物品已存在!');
        }
    }
}

// 删除物品
function removeItem(itemName) {
    if (!selectedSceneNode || !sceneGraph[selectedSceneNode].data.物品) return;

    sceneGraph[selectedSceneNode].data.物品 = sceneGraph[selectedSceneNode].data.物品.filter(
        item => item !== itemName
    );
    updatePropertyEditor(sceneGraph[selectedSceneNode]);
    updateSceneVisuals(selectedSceneNode);
}

// 更新对象属性
function updateObjectProperty(property, value) {
    if (!selectedObject) return;

    const parts = property.split('.');
    if (parts.length === 2) {
        selectedObject[parts[0]][parts[1]] = value;
    }

    // 如果是物品名称更新
    if (property === 'name' && selectedObject.name.includes('_item_')) {
        const oldName = selectedObject.name.split('_item_')[1];
        const newName = value;

        // 更新场景数据中的物品名称
        const sceneName = selectedObject.parent.parent.name;
        if (sceneGraph[sceneName] && sceneGraph[sceneName].data.物品) {
            const index = sceneGraph[sceneName].data.物品.indexOf(oldName);
            if (index !== -1) {
                sceneGraph[sceneName].data.物品[index] = newName;
            }
        }

        // 更新对象名称
        selectedObject.name = `${sceneName}_item_${newName}`;

        // 更新标签
        selectedObject.parent.traverse(child => {
            if (child.name === `${sceneName}_item_label_${oldName}`) {
                const newLabel = createTextLabel(newName, 0x9932cc, 0.5);
                newLabel.position.copy(child.position);
                selectedObject.parent.remove(child);
                selectedObject.parent.add(newLabel);
            }
        });
    }
}

// 删除选中的对象
function deleteSelectedObject() {
    if (!selectedObject || !selectedSceneNode) return;

    // 如果是物品
    if (selectedObject.name.includes('_item_')) {
        const itemName = selectedObject.name.split('_item_')[1];
        removeItem(itemName);
    }
    // 如果是出口
    else if (selectedObject.name.includes('_exit_')) {
        const dir = selectedObject.name.split('_exit_')[1];
        removeExit(dir);
    }

    selectedObject = null;
    document.getElementById('propertyEditor').innerHTML = '<p>选择场景元素进行编辑</p>';
}

// 更新场景视觉效果
function updateSceneVisuals(sceneName) {
    if (!sceneGraph[sceneName] || !sceneGraph[sceneName].group) return;

    // 移除旧的场景组
    const oldGroup = sceneGraph[sceneName].group;
    scene3D.remove(oldGroup);

    // 创建新的场景组
    createSceneRepresentation(sceneName, sceneGraph[sceneName].data);

    // 恢复选择状态
    if (selectedSceneNode === sceneName) {
        selectSceneNode(sceneName);
    }
}

// 预览场景
function previewScene(sceneName) {
    if (!sceneGraph[sceneName]) return;

    const previewWindow = window.open('', '_blank');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>预览: ${sceneName}</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f8f4e9; padding: 20px; }
                .scene { background-color: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                h1 { color: #8b4513; }
                .items { margin: 15px 0; }
                .exits a { display: inline-block; margin-right: 10px; color: #5cb85c; text-decoration: none; }
                .exits a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="scene">
                <h1>${sceneName}</h1>
                <p>${sceneGraph[sceneName].data.描述 || '暂无描述'}</p>
                
                ${sceneGraph[sceneName].data.物品?.length > 0 ? `
                <div class="items">
                    <h3>物品</h3>
                    <p>${sceneGraph[sceneName].data.物品.join(', ')}</p>
                </div>
                ` : ''}
                
                ${Object.keys(sceneGraph[sceneName].data.出口 || {}).length > 0 ? `
                <div class="exits">
                    <h3>出口</h3>
                    ${Object.entries(sceneGraph[sceneName].data.出口).map(([dir, target]) => `
                        <a href="#" onclick="window.opener.selectSceneNode('${target}'); window.close();">${dir} → ${target}</a>
                    `).join('')}
                </div>
                ` : ''}
            </div>
        </body>
        </html>
    `);
    previewWindow.document.close();
}

// 保存场景更改到服务器
function saveSceneChanges(sceneName) {
    if (!sceneGraph[sceneName]) return;

    const sceneData = sceneGraph[sceneName].data;

    // 发送AJAX请求保存数据
    const xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            alert('场景保存成功!');
        } else {
            alert('保存失败: ' + xhr.statusText);
        }
    };

    const params = new URLSearchParams();
    params.append('编辑器命令', '保存场景');
    params.append('场景名称', sceneName);
    params.append('场景描述', sceneData.描述 || '');
    params.append('场景出口', JSON.stringify(sceneData.出口 || {}));
    params.append('场景物品', sceneData.物品 ? sceneData.物品.join(',') : '');
    params.append('场景人物', JSON.stringify(sceneData.人物 || {}));
    params.append('场景特殊', sceneData.特殊 || '');
    params.append('场景脚本', sceneData.脚本 || '');

    xhr.send(params.toString());
}

// 从服务器删除场景
function deleteSceneFromServer(sceneName) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            alert('场景删除成功!');
        } else {
            alert('删除失败: ' + xhr.statusText);
        }
    };

    const params = new URLSearchParams();
    params.append('编辑器命令', '删除场景');
    params.append('删除场景名称', sceneName);

    xhr.send(params.toString());
}

// 更新场景到服务器
function updateSceneToServer(sceneName, sceneData) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status !== 200) {
            console.error('更新场景失败:', xhr.statusText);
        }
    };

    const params = new URLSearchParams();
    params.append('编辑器命令', '保存场景');
    params.append('场景名称', sceneName);
    params.append('场景描述', sceneData.描述 || '');
    params.append('场景出口', JSON.stringify(sceneData.出口 || {}));
    params.append('场景物品', sceneData.物品 ? sceneData.物品.join(',') : '');
    params.append('场景人物', JSON.stringify(sceneData.人物 || {}));
    params.append('场景特殊', sceneData.特殊 || '');
    params.append('场景脚本', sceneData.脚本 || '');

    xhr.send(params.toString());
}

// 切换视图模式
function toggleViewMode() {
    const editor2D = document.getElementById('editor2D');
    const editor3D = document.getElementById('editor3D');
    const toggleBtn = document.getElementById('toggleView');

    if (editor3D.style.display === 'none') {
        editor2D.style.display = 'none';
        editor3D.style.display = 'block';
        toggleBtn.textContent = '切换到2D视图';

        // 初始化3D编辑器（如果尚未初始化）
        if (!scene3D) {
            init3DEditor();
        }
    } else {
        editor2D.style.display = 'block';
        editor3D.style.display = 'none';
        toggleBtn.textContent = '切换到3D视图';
    }
}

// 窗口大小调整时重新设置渲染器大小
window.addEventListener('resize', function () {
    if (renderer) {
        renderer.setSize(document.getElementById('scene3D').clientWidth, document.getElementById('scene3D').clientHeight);
        camera.aspect = document.getElementById('scene3D').clientWidth / document.getElementById('scene3D').clientHeight;
        camera.updateProjectionMatrix();
    }
});

// 点击场景对象选择
document.getElementById('scene3D').addEventListener('click', function (event) {
    if (!renderer || !scene3D) return;

    const mouse = new THREE.Vector2();
    const rect = this.getBoundingClientRect();
    mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

    const raycaster = new THREE.Raycaster();
    raycaster.setFromCamera(mouse, camera);

    const intersects = raycaster.intersectObjects(scene3D.children, true);

    if (intersects.length > 0) {
        let object = intersects[0].object;

        // 找到最上层的可选中父对象
        while (object.parent && object.parent !== scene3D) {
            // 跳过不可选中的辅助对象（如标签、连接线等）
            if (object.parent.userData && object.parent.userData.selectable === false) {
                break;
            }
            object = object.parent;
        }

        // 确保对象有材质才选中
        if (object.material) {
            selectObject(object);
        } else {
            selectObject(null); // 清除选择
        }
    } else {
        selectObject(null); // 点击空白处清除选择
    }
});

// 初始化拖放功能
function initDragAndDrop() {
    const scene3DContainer = document.getElementById('scene3D');

    scene3DContainer.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    scene3DContainer.addEventListener('drop', function (e) {
        e.preventDefault();

        if (!selectedSceneNode) return;

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // 处理文件拖放
            alert('文件拖放功能需要进一步实现');
        } else {
            // 处理物品拖放
            const itemName = e.dataTransfer.getData('text/plain');
            if (itemName) {
                if (!sceneGraph[selectedSceneNode].data.物品) {
                    sceneGraph[selectedSceneNode].data.物品 = [];
                }

                if (!sceneGraph[selectedSceneNode].data.物品.includes(itemName)) {
                    sceneGraph[selectedSceneNode].data.物品.push(itemName);
                    updateSceneVisuals(selectedSceneNode);
                }
            }
        }
    });
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function () {
    // 如果有3D编辑器，初始化拖放
    if (document.getElementById('editor3D')) {
        initDragAndDrop();
    }
});