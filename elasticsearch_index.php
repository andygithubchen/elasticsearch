<?php

$shards = 20;
$host   = '192.168.2.250';
$elasticsearch_connect = 'http://'.$host.':9200/';
$elasticsearch_hosts   = '["'.$host.':9200"]';

function mysqlConf(){
    return [
        'hostname' => '',
        'username' => 'datauser',
        'password' => '',
        'database' => '',
    ];
}

function _curl($elasticsearch_connect, $action, $fields = '{}'){
    $username = 'elastic';
    $password = '';
    $ch = curl_init();
    $header[] = "Content-type: application/json";
    curl_setopt($ch, CURLOPT_URL, $elasticsearch_connect);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $action);
    curl_setopt($ch, CURLOPT_HEADER,0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function _getTables(){
    $mysql = mysqlConf();
    $tables = [];
    $link = new mysqli($mysql['hostname'], $mysql['username'], $mysql['password'], $mysql['database']);
    $result = $link->query('show tables');
    if(!$result)
        die("mysqli error\n");

    while($row = $result->fetch_object()){
        $table = implode('', array_values((array)$row));
        $columns = $link->query("select COLUMN_NAME from information_schema.COLUMNS where table_schema = DATABASE() AND table_name = '{$table}'")->fetch_all();
        $columns = array_column($columns, 0);
        $tables[] = [
            'table'   => $table,
            'primary' => 'id',
            'columns' => $columns,
        ];
    }
    mysqli_close($link);
    return $tables;
}

function createIndex($elasticsearch_connect, $shards){
    if($elasticsearch_connect == '' || $shards <= 0)
        return false;

    //从日志数据库中取出表来
    $tables = _getTables();

    if(empty($tables))
        die("no mysql table in ". $mysql['database'] . "\n");

    $settings = [
        'settings' => [
            "number_of_shards" => $shards,
            "number_of_replicas" => 0,
        ],
    ];

    foreach($tables as $index){
        echo $index['table'];

        $properties = [];
        foreach($index['columns'] as $name){
            if(in_array($name, ['account_name', 'role_name', 'account_name', 'imei', 'uid'])) //需要在ES里用到group的字符串字段
                $properties[$name] = ['type' => 'keyword'];
        }
        if(!empty($properties)){
            $settings['mappings'] = [
                '_doc' => [
                    'properties' => $properties,
                ]
            ];
        }

        $result = _curl($elasticsearch_connect . $index['table'], 'PUT', json_encode($settings));
        if(isset($result['index']) && $result['index'] == $index['table']){
            echo ": Success \n";
        }else{
            echo ": Fail \n";
        }
    }

}

function createOne($elasticsearch_connect, $name, $shards){
    $settings = json_encode([
        "settings" => [
            "number_of_shards" => $shards,
            "number_of_replicas" => 0,
        ],
    ]);
    $result = _curl($elasticsearch_connect . $name, 'PUT', $settings);

    echo $name;
    if(isset($result['index']) && $result['index'] == $name){
        echo ": Success \n";
    }else{
        echo ": Fail \n";
    }
}



function delAll($elasticsearch_connect){
    $all_index = _curl($elasticsearch_connect.'_aliases', 'GET');
    if(empty($all_index))
        return true;

    $all_index = array_keys($all_index);
    $all_index = array_map(function($index){
        if(strpos($index, 'security') === false)
            return $index;
    }, $all_index);
    $all_index = array_filter($all_index);
    if(empty($all_index))
        return true;

    foreach($all_index as $index){
        $result = _curl($elasticsearch_connect . $index, 'DELETE');
        print_r($result);
    }

    return true;
}


function makeJdbcConf($target_host = '', $database = '', $elasticsearch_hosts = ''){
    if($target_host == '' || $database == '')
        die("database empty \n");

    $conf_dir = '/etc/logstash/';
    if(!is_dir($conf_dir))
        shell_exec("mkdir -p {$conf_dir}");

    $file = $conf_dir . "{$target_host}_{$database}.conf";
    if(!is_file($file))
        shell_exec("touch {$file}");
    if(!is_file($file))
        die($file. "is not file \n");

    $jdbc = $type = '';
    $tables = _getTables();
    $mysql = mysqlConf();

    //clean_run => "true"
    foreach($tables as $value){
        $t = $value['table'];
        $key = in_array($t,['log_role_status', 'log_role_nurture']) ? 'role_id' : $value['primary'];
        $sql_last_value = in_array($t,['log_role_status']) ? 'time' : $value['primary'];
        $jdbc .= 'jdbc {
              jdbc_connection_string => "jdbc:mysql://'.$target_host.':3306/'.$database.'"
              jdbc_user              => "'.$mysql['username'].'"
              jdbc_password          => "'.$mysql['password'].'"
              jdbc_driver_library    => "/usr/local/ElasticSearch/logstash-6.3.2/tools/mysql-connector-java-5.1.36.jar"
              jdbc_driver_class      => "com.mysql.jdbc.Driver"

              record_last_run  => "true"
              use_column_value => "true"
              tracking_column  => "'.$key.'"

              statement => "select * from '.$t.' where '.$sql_last_value.' > :sql_last_value limit 10000"
              schedule  => "* * * * *"
              type      => "'.$t.'"
          }';
        $type .= 'if[type] == "'.$t.'" {
            elasticsearch {
                hosts         => '.$elasticsearch_hosts.'
                user          => ""
                password      => ""
                index         => '.$t.'
                document_type => "_doc"
                document_id   => "%{'.$key.'}"
            }
          }';
    }

    $conent = <<<EOF
input {
  stdin {
  }
  {$jdbc}
}

filter {
  json {
    source => "message"
      remove_field => ["message"]
  }
}

output {
    {$type}
}
EOF;

    file_put_contents($file, $conent);
}


delAll($elasticsearch_connect); //delete all elasticsearch index
////createIndex($elasticsearch_connect, $shards); //create elasticsearch index from mysql database
createOne($elasticsearch_connect, 'log_item', $shards); //create one elasticsearch index
createOne($elasticsearch_connect, 'log_silver', $shards); //create one elasticsearch index
//makeJdbcConf('192.168.2.250', 'admin_local_1', $elasticsearch_hosts);
//makeJdbcConf('192.168.2.250', 'admin_local_51', $elasticsearch_hosts);

//makeJdbcConf('game-local.sl-xyjgx.com', 'admin_local_1', $elasticsearch_hosts);
//makeJdbcConf('game-local.sl-xyjgx.com', 'admin_local_2', $elasticsearch_hosts);
//makeJdbcConf('game-local.sl-xyjgx.com', 'admin_local_11', $elasticsearch_hosts);
//makeJdbcConf('game-local.sl-xyjgx.com', 'admin_local_111', $elasticsearch_hosts);

echo "\n";
