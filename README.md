# PHP文字游戏项目

## 简介
本项目是一个基于PHP的文字游戏，玩家可以通过输入命令与游戏世界进行交互。同时，项目还提供了一个地图制作器，允许用户创建和编辑自己的游戏地图。

## 功能特性
1. **文字游戏核心**：玩家可以上传自定义的地图文件（JSON格式）或使用默认地图开始游戏。在游戏中，玩家可以查看当前位置的描述、物品、人物等信息，并通过输入命令与游戏世界进行交互。
2. **地图制作器**：支持创建、编辑和导入导出地图。提供代码编辑器，允许用户编写全局脚本和场景脚本，以实现更复杂的游戏逻辑。
3. **3D编辑器**：可视化的场景管理工具，帮助用户更直观地设计游戏场景。

## 项目结构
```
text/
├── data/
│   └── common/
│       ├── footer.php
│       └── rice-paper-2.png
├── engine/
│   ├── edit.php
│   └── game.php
├── edit.php
├── editScript.js
├── index.php
└── LICENSE
```

### 文件说明
- `index.php`：游戏主页面，用户可以在这里开始游戏、输入命令和查看游戏输出。
- `edit.php`：地图制作器页面，用户可以在这里创建、编辑和管理游戏地图。
- `editScript.js`：地图制作器的脚本文件，负责初始化代码编辑器和3D编辑器，以及处理脚本测试等功能。
- `engine/`：包含游戏和地图制作器的核心逻辑。
  - `game.php`：游戏引擎，处理游戏逻辑和命令响应。
  - `edit.php`：地图编辑引擎，处理地图的创建、编辑和导入导出。
- `data/common/`：包含项目的公共资源，如页脚文件和背景图片。
  - `footer.php`：页脚文件，显示项目的版权信息和链接。
  - `rice-paper-2.png`：背景图片，用于美化页面。
- `LICENSE`：项目使用的许可证文件，本项目采用Apache License 2.0。

## 安装与部署
### 环境要求
- PHP 7.0 或更高版本
- 支持 JavaScript 的现代浏览器

### 步骤
1. **克隆项目**：
```bash
git clone https://github.com/yourusername/php-text-game.git
```
2. **配置服务器**：将项目文件部署到支持 PHP 的服务器上，如 Apache 或 Nginx。
3. **访问项目**：在浏览器中访问项目的根目录，即可开始游戏或使用地图制作器。

## 使用方法
### 游戏玩法
1. 打开 `index.php` 页面。
2. 上传自定义的地图文件（JSON格式）或选择使用默认地图开始游戏。
3. 在输入框中输入命令，如 `前进`、`查看背包` 等，然后点击 `执行` 按钮。
4. 查看游戏输出，了解当前位置的信息和命令的响应。

### 地图制作
1. 打开 `edit.php` 页面。
2. 在左侧面板中，点击 `添加场景` 按钮创建新的场景节点。
3. 在右侧面板中，编辑场景的基本信息，如描述、出口、物品等。
4. 使用代码编辑器编写全局脚本和场景脚本，以实现更复杂的游戏逻辑。
5. 点击 `测试脚本` 按钮，测试脚本的正确性。
6. 点击 `导入地图` 或 `导出地图` 按钮，进行地图的导入和导出操作。

## 脚本编写指南
### 可用变量
- `$玩家`：表示当前玩家的信息。
- `$场景`：表示当前场景的信息。
- `$_SESSION`：用于存储会话数据。

### 常用函数
- `物品在背包中()`：检查物品是否在玩家的背包中。
- `添加物品()`：向玩家的背包中添加物品。
- `移除物品()`：从玩家的背包中移除物品。

### 安全提示
请勿编写危险代码，如文件操作、数据库查询等，以确保游戏的安全性。

## 开源协议
本项目采用 [Apache License 2.0](LICENSE) 开源协议。

## 贡献
欢迎贡献代码、提出问题和建议。请在 GitHub 上提交 issue 或 pull request。
