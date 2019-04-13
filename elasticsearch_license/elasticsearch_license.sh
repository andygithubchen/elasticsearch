#!/bin/bash

# 自动激活Elasticsearch 脚本

install_path=/usr/local/ElasticSearch
group=elastic
user=elastic
elasticsearch=${install_path}/elasticsearch-6.3.1
logstash=${install_path}/logstash-6.3.2
DIR=$(cd "$(dirname "$0")";pwd)
symbol=';'

yum install java-devel
apt install openjdk-8-jdk-headless -y

#1. 备份x-pack-core-6.3.1.jar
cp ${elasticsearch}/modules/x-pack/x-pack-core/x-pack-core-6.3.1.jar ${elasticsearch}/modules/x-pack/x-pack-core/x-pack-core-6.3.1.jar_old

#2. 用./x-pack-core-6.3.1.jar 覆盖 x-pack-core-6.3.1.jar
cp ./x-pack-core-6.3.1.jar ${elasticsearch}/modules/x-pack/x-pack-core/x-pack-core-6.3.1.jar


#2. 编译 LicenseVerifier.java和XPackBuild.java
options="${elasticsearch}/modules/x-pack/x-pack-core/x-pack-core-6.3.1.jar${symbol}${elasticsearch}/lib/lucene-core-7.3.1.jar${symbol}${elasticsearch}/lib/elasticsearch-6.3.1.jar${symbol}${elasticsearch}/lib/elasticsearch-core-6.3.1.jar"
#javac -cp "${options}" org/elasticsearch/license/LicenseVerifier.java
javac -cp "${options}" ${DIR}/org/elasticsearch/xpack/core/XPackBuild.java

exit 1

#3. 替换x-pack-core-6.3.1.jar的class文件
lv_class=./org/elasticsearch/license/LicenseVerifier.class
xb_class=./org/elasticsearch/xpack/core/XPackBuild.class
if [ -f "${lv_class}" ]; then
  rm -fr ${lv_class}
fi
if [ -f "${xb_class}" ]; then
  rm -fr ${xb_class}
fi

jar uvf ${elasticsearch}/modules/x-pack/x-pack-core/x-pack-core-6.3.1.jar ${lv_class}

jar uvf ${elasticsearch}/modules/x-pack/x-pack-core/x-pack-core-6.3.1.jar ${xb_class}

#4. 打入 license 证书
host='http://127.0.0.1'
curl -H "Content-Type: application/json" -XPUT "${host}:9200/_xpack/license?acknowledge=true" -d @./license.json






