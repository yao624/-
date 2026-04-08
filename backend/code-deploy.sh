#!/bin/bash

# 定义源代码目录和目标目录
SOURCE_DIR="$(dirname "$0")"
TARGET_DIR="/var/www/gemini-main-be"

# 使用rsync命令将源代码目录下的内容拷贝到目标目录
rsync -avz $SOURCE_DIR/ $TARGET_DIR

# 输出操作完成信息
echo "Code has been successfully copied to $TARGET_DIR"

# 切换到目标目录
cd $TARGET_DIR

# 拷贝.env.prod文件到.env
cp .env.prod .env

# 切换到docker prod目录
cd docker/prod/

# 构建并启动docker
docker-compose build
docker-compose up -d

# 输出操作完成信息
echo "Docker has been successfully built and started"
