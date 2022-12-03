<?php

/**
 * 错误一、Couldn't map cluster keyspace using any provided seed
 * 原因：
 * 1、客户端无法连接到集群（超时）
 * 2、实例未处于集群模式
 * 3、客户端连接成功，但身份验证失败。假设集群在配置中指定了密码，但您正在尝试连接不发送密码的客户端。
 *
 * 错误二、Can't communicate with any node in the cluster
 * 原因：
 * 1、使用的 network: host
 * 2、将创建集群中的 ip 换成 docker 容器中的 ip
 * redis-cli --cluster create 127.0.0.1:6379 127.0.0.1:6380 127.0.0.1:6381 127.0.0.1:6382 127.0.0.1:6383 127.0.0.1:6384 --cluster-replicas 1
 * 换成
 * redis-cli --cluster create 172.17.47.59:6379 172.17.47.59:6380 172.17.47.59:6381 172.17.47.59:6382 172.17.47.59:6383 172.17.47.59:6384 --cluster-replicas 1
 *
 */

// 1、redis 单机 报错：Fatal error: Uncaught RedisException: MOVED 5798 172.17.47.59:6380 in /var/www/html/index.php:23 Stack trace: #0 /var/www/html/index.php(23): Redis->get('name') #1 {main} thrown in /var/www/html/index.php on line 23
//$client = new Redis();
//$client->connect('172.17.47.59', 6379);
//var_export($client->get('name'));

// 2、redis 集群
/**
 * 这里的 ip 使用的系统本地中的 ip,使用 127.0.0.1 报错错误一
 * 原因：redis 集群中使用的 network_mode: "host"
 */
$seeds = ['172.31.141.10:6379', '172.31.141.10:6380', '172.31.141.10:6381'];

// 使用容器名
//$seeds = ['node-1:6379', 'node-2:6380', 'node-3:6381'];
// Warning: RedisCluster::__construct(): php_network_getaddresses: getaddrinfo failed: Try again in /var/www/html/index.php on line 35

try {

    /**
     * RedisCluster redis集群环境，说phpredis 2.x版本的不支持集群环境，要3.X的才支持
     *
     * 第一个参数传NULL 别问我，我也不知道为啥。反正文档没找到，这篇也没看懂。
     * 第二个参数是我们需要连接的redis cluster的master服务器列表。我们有3个master，就填3个, 填一个主节点也行, 甚至填一个从节点也行, 但是性能有差异
     *
     * 参考链接：
     * @see https://www.jianshu.com/p/d786a1c8d2be
     *
     */
    $redis_cluster = new RedisCluster(NULL, $seeds);
    $redis_cluster->set('cluster_test', '***ok***');
    $value = $redis_cluster->get('cluster_test');
    var_dump($value);

} catch (RedisClusterException $e) {
    var_dump($e->getMessage());
}
