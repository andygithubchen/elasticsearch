#ElasticSearch:

###一. 破解步骤：
```shell
1. 公用文件
cp ./x-pack-core-6.3.1.jar ./elasticsearch-6.3.1/modules/x-pack/x-pack-core/
cp -r ./certs ./elasticsearch-6.3.1/config/

2. bin/elasticsearch-keystore add xpack.security.transport.ssl.keystore.secure_password （需要输入密码，和elastic-stack-ca.p12一样的密码: 123456）
3. bin/elasticsearch-keystore add xpack.security.transport.ssl.truststore.secure_password （需要输入密码，和elastic-stack-ca.p12一样的密码: 123456）
4. chown elastic:elastic ./elasticsearch-6.3.1 -R

(elastic-stack-ca.p12文件 用master ES 的）)
(./license.json 用于破解)
(elastic-stack-ca.p12 是用 bin/elasticsearch-certutil ca 生成的，需要输入密码，测试用：123456)
```

###二. xpack生成密码(记住)：
bin/elasticsearch-setup-passwords auto

###三. elasticsearch-head 组合 xpack:
```shell
#在elasticsearch.yml里配置
http.cors.enabled: true
http.cors.allow-origin: "*"
http.cors.allow-headers: Authorization,X-Requested-With,Content-Length,Content-Type

xpack.security.enabled: true
xpack.security.transport.ssl.enabled: true
xpack.security.transport.ssl.verification_mode: certificate
xpack.security.transport.ssl.keystore.path: certs/elastic-stack-ca.p12
xpack.security.transport.ssl.truststore.path: certs/elastic-stack-ca.p12
```

###doc
```shell
https://www.elastic.co/guide/en/elasticsearch/reference/6.3/configuring-tls.html#node-certificates
https://blog.csdn.net/xiaoyu_BD/article/details/81698882
```


###四. 常见问题：
```shell
failed to load plugin class [org.elasticsearch.xpack.core.XPackPlugin]
```
用下面的步骤设置密码：
 bin/elasticsearch-keystore add xpack.security.transport.ssl.keystore.secure_password
 bin/elasticsearch-keystore add xpack.security.transport.ssl.truststore.secure_password 


