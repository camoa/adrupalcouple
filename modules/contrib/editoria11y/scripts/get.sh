#!/bin/bash

# This is a simple script to pull down the specified version of editoria11y from github

GIT_REF="main"

mkdir -p tmp/
cd tmp/
git clone git@github.com:itmaybejj/editoria11y.git .
git checkout $GIT_REF
rm -rf ../library/js
mv js ../library/js
mv dist ../library/js/dist
cd ../
rm -rf tmp
