#!/bin/bash

mkdir -p tmp/content-sectioner

cp -r src/* tmp/content-sectioner

cd tmp
zip -r content-sectioner content-sectioner
mv content-sectioner.zip ../
cd ../
rm -rf tmp



