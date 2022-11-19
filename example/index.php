<?php

$seeds = ['node1:6379', 'node2:6380', 'node3:6381', 'node4:6382', 'node5:6383', 'node6:6384'];

try {

    /**
     * RedisCluster redis集群环境，说phpredis 2.x版本的不支持集群环境，要3.X的才支持
     */
    $redis_cluster = new RedisCluster(NULL, $seeds);
    $redis_cluster->set('cluster_test', '***ok***');
    $value = $redis_cluster->get('cluster_test');
    var_dump($value);

} catch (RedisClusterException $e) {
    var_dump($e->getMessage());
}
