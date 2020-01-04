# Swoft Release CLI

Swoft core and ext components release CLI tool package.

- Github: https://github.com/swoftlabs/swoft-releasecli

## Install

```bash
git clone https://github.com/swoftlabs/swoft-releasecli
cd swoft-releasecli
ln -s $PWD/bin/releasecli /usr/local/bin/releasecli
chmod a+x bin/releasecli
```

## Update

```bash
git pull
chmod a+x bin/releasecli
```

## Usage

goto swoft-components dir:

```bash
# 1. add remote for all components
releasecli git:addrmt --all
# 2. force push all change to every github repo
releasecli git:fpush --all
# 3. release new version for all components
releasecli git:release --all -y -t v2.0.8
```

## Build Phar

> Required the `swoftcli`

```bash
php -d phar.readonly=0 ~/.composer/vendor/bin/swoftcli phar:pack -o=releasecli.phar
```

