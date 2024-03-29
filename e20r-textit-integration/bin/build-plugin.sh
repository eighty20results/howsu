#!/bin/bash
# Build script for Eighty/20 Results - Text-It Messaging Service integration
#
short_name="e20r-textit-integration"
include=(images includes js languages license plugin-updates utilities e20r-textit-integration.php README.txt)
exclude=(*.yml *.phar composer.* vendor)
build=(plugin-updates/vendor/*.php)
plugin_path="${short_name}"
version=$(egrep "^Version:" ../${short_name}.php | awk '{print $2}')
metadata="../metadata.json"
src_path="../"
dst_path="../build/${plugin_path}"
kit_path="../build/kits"
kit_name="${kit_path}/${short_name}-${version}"

echo "Building kit for version ${version}"

mkdir -p ${kit_path}
mkdir -p ${dst_path}

if [[ -f  ${kit_name} ]]
then
    echo "Kit is already present. Cleaning up"
    rm -rf ${dst_path}
    rm -f ${kit_name}
fi

for p in ${include[@]}; do
	cp -R ${src_path}${p} ${dst_path}
done

for e in ${exclude[@]}; do
    find ${dst_path} -type d -iname ${e} -exec rm -rf {} \;
done

mkdir -p ${dst_path}/plugin-updates/vendor/
for b in ${build[@]}; do
    cp ${src_path}${b} ${dst_path}/plugin-updates/vendor/
done

cd ${dst_path}/..
zip -r ${kit_name}.zip ${plugin_path}
ssh siteground-e20r "cd ./www/protected-content/ ; mkdir -p \"${short_name}\""
scp ${kit_name}.zip siteground-e20r:./www/protected-content/${short_name}/
scp ${metadata} siteground-e20r:./www/protected-content/${short_name}/
ssh siteground-e20r "cd ./www/protected-content/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip"
rm -rf ${dst_path}


