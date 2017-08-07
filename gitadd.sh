#!/bin/bash
if [ ! -d "$1" ] && [ ! -f "$1" ]; then 
    echo "file $1 not exists"
    exit
fi
filename=$1

comment="add file"
if [[ $2 != "" ]]; then
    comment=$2
fi

echo "start update..."
git pull
echo "start add new file..."
git add $filename
echo "start commit..."
git commit -m "$comment" $filename
git push -u origin master
echo "git commit complete..."
