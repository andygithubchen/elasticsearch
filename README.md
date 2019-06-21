
**生产配置**
```shell
一、配置index模板为 20个分片，0个副本，最大查询结果数1000000
curl --user elastic:sTV213z -XPUT "http://192.168.2.250:9200/_template/all" -H 'Content-Type: application/json' -d'
{
  "template": "*",
  "settings": {
    "number_of_shards": "20",
    "number_of_replicas": "0",
    "index": {
      "max_result_window": "1000000"
    }
  }
}'

二、激活Elasticsearch
host='http://127.0.0.1'
curl -H "Content-Type: application/json" -XPUT "${host}:9200/_xpack/license?acknowledge=true" -d @./elasticsearch_license/license.json

三、要重启测试，写入测试数据，不然不要上线

```


**ES 的批量操作**
```shell
一、 批量创建：
curl --user elastic:Sstd4 -XPOST "http://192.168.2.250:9200/_bulk" -H 'Content-Type: application/json' -d'
{ "create": {"_type":"_doc", "_index":"log_test", "_id":1001} }
{ "test_field":"test12"}
{ "create": {"_type":"_doc", "_index":"log_test", "_id":3001} }
{ "test_field_1":"test12", "key":"value"}
'

1. post方式
2. 192.168.2.250 会有多个来做负载
3. log_test是表名
4. --user 是账号密码
5. 数据格式：
{ "create": {"_id":ID值}}\n
{ "字段名":"字段值" }\n
{ "create": {"_id":ID值}}\n
{ "字段名1":"字段值", "字段名2":"字段值", ... }\n
......
6. 返回成功实例：
{"took":225,"errors":false,"items":[{"create":{"_index":"log_test","_type":"_doc","_id":"11","_version":1,"result":"created","_shards":{"total":1,"successful":1,"failed":0},"_seq_no":0,"_primary_term":1,"status":201}},{"create":{"_index":"log_test","_type":"_doc","_id":"31","_version":1,"result":"created","_shards":{"total":1,"successful":1,"failed":0},"_seq_no":0,"_primary_term":1,"status":201}}]}
只判断"errors":false就可以了。


----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
二、 创建/更新：
curl --user elastic:Sstd4r -XPUT "http://192.168.2.250:9200/log_test/_doc/10" -H 'Content-Type: application/json' -d'
{
  "test_field1" : 1,
    "test_field2" : "的说法是"
}
'

1. put方式
2. 192.168.2.250 会有多个来做负载
3. log_test是表名
4. --user 是账号密码
5. ....../10 10是ID
6. 数据格式：
{
  "字段名1" : "字段值",
    "字段名2" : "字段值",
    .........
}
7. 返回成功实例：
{"_index":"log_test1","_type":"_doc","_id":"12","_version":1,"result":"created","_shards":{"total":2,"successful":1,"failed":0},"_seq_no":0,"_primary_term":1}
只判断"result":"created"就可以了。


----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
三、错误时调用的接口：
http://192.168.2.250:9200/index/index/errorLog
提交的json数据格式：
[
{"table":"log_item","id":1,"agent_id":2,"server_id":3},
{"table":"log_item","id":2,"agent_id":2,"server_id":3}
]
```

**ES 最简单的批量操作 (存在就替换，否则新增)**

```shell
curl --user elastic:Sstd4rp -XPOST "http://192.168.2.250:9200/_bulk" -H 'Content-Type: application/json' -d'
{ "index": {"_type":"_doc", "_index":"log_test", "_id":1001} }
{ "test_field":"test12"}
{ "index": {"_type":"_doc", "_index":"log_test", "_id":3001} }
{ "test_field_1":"test12", "key":"value"}
'
```

**常用**
```shell
#查看证书时间
curl -XGET http://127.0.0.1:9200/_license

# 修改密码：
curl --user elastic:Sstd4r -XPOST 'http://192.168.2.250:9200/_xpack/security/user/elastic/_password' -H 'Content-Type: application/json' -d '{
  "password" : "4RShsdf3M9"
}'

```

**查看所有节点信息**
```shell
curl -XGET http://127.0.0.1:9200/_nodes/stats?pretty
```


**从集群中移除一个节点**
```shell
curl --user elastic:12332sdkf3 -XPUT "http://192.168.2.250:9200/_cluster/settings" -H 'Content-Type: application/json' -d'{
  "transient" :{
    "cluster.routing.allocation.exclude._ip" : "192.168.2.183"
  }
}'
cluster.routing.allocation.exclude._ip 是要移除节点的IP，
在执行上面的操作时不是一下子就完成的，不能马上关闭这个节点，因为ElasticSearch会将这个节点数据迁移到集群的其他节点，需要一定的时间
```


**修改字段属性**
```shell
curl --user elastic:ddsfs3df -XPUT "http://192.168.2.250:9200/log_treasure?pretty" -H 'Content-Type: application/json' -d'
{
  "mappings": {
    "_doc": {
      "properties": {
        "role_id": {
          "type": "long"
        }
      }
    }
  }
}'
```


**用脚本去新增一个字段，这个字段的值等于某两个字段值的拼接字符**
```shell
curl --user elastic:pwdd -XPOST "http://192.168.2.250:9200/special_test/_update_by_query" -H 'Content-Type: application/json' -d'{
  "script": {
    "inline": "if (ctx._source.agent_id==null || ctx._source.server_id==null){ ctx._source.agent_server=null }else{ctx._source.agent_server = ctx._source.agent_id +\"\"+ ctx._source.server_id}"
  }
}'
```




