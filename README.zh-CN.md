# Swoft Release CLI

[![GitHub tag (latest SemVer)](https://img.shields.io/github/tag/swoftlabs/swoft-releasecli)](https://github.com/swoftlabs/swoft-releasecli)

Swoft 核心和扩展组件的版本发布工具.

- 同步最新改动到各个子仓库
- 批量的发布新版本

> Github https://github.com/swoftlabs/swoft-releasecli

**环境依赖:**

- git
- php
- swoole
- composer

**工具预览:**

![all-commands](all-commands.png)

## [English](README.md)

## 安装

### 脚本安装

```bash
curl https://raw.githubusercontent.com/swoftlabs/swoft-releasecli/master/install.sh | bash
```

### 手动安装

内容来自于 [install.sh](install.sh) 脚本

```bash
cd ~
git clone https://github.com/swoftlabs/swoft-releasecli
cd swoft-releasecli
composer install
ln -s $PWD/bin/releasecli /usr/local/bin/releasecli
chmod a+x bin/releasecli
```

## 使用

先用git拉取最新的 swoft-components 或 swoft-ext 到本地，跳转到仓库目录，执行：

```bash
# 1. add remote for all components
releasecli git:addrmt --all

# 2. force push all change to every github repo
releasecli git:fpush --all

# 3. release new version for all components
releasecli git:release --all -y -t v2.0.8
```

## 更新工具

### 内置命令更新

Use builtin command

```bash
releasecli upself
```

### 手动更新

```bash
cd ~/swoft-releasecli
git pull
chmod a+x bin/releasecli
```

## 构建Phar

> Required the `swoftcli`

```bash
php -d phar.readonly=0 ~/.composer/vendor/bin/swoftcli phar:pack -o=releasecli.phar
```

## 删除工具

```bash
rm -f /usr/local/bin/releasecli
rm -rf ~/swoft-releasecli
```

## 依赖包

- https://github.com/php-toolkit/cli-utils
- https://github.com/swoft-cloud/swoft-console
- https://github.com/swoft-cloud/swoft-stdlib
