# docker-compose 搭建 redis 集群

# 1、启动容器

```bash
docker-compose up -d
```

# 2、开启集群

随便找一个容器进入，这里我选择 `node-1` 进入。 在进入容器后，输入如下命令开启集群 .
> 因为使用的是 Host 网络，因此直接 localhost + port 指定就行，--cluster-replicas 设置每个 Master 复制的数量

```bash
# 注意将 ip 换成自己服务的 ip
# redis-cli --cluster create 127.0.0.1:6379 127.0.0.1:6380 127.0.0.1:6381 127.0.0.1:6382 127.0.0.1:6383 127.0.0.1:6384 --cluster-replicas 1

redis-cli --cluster create 172.17.47.59:6379 172.17.47.59:6380 172.17.47.59:6381 172.17.47.59:6382 172.17.47.59:6383 172.17.47.59:6384 --cluster-replicas 1
```

> 这里有个坑，ip 如何使用 127.0.0.1 程序代码执行会报错，必须使用本地中的 ip（ip address | grep eth0）
> 
> 报错：Can't communicate with any node in the cluster

# 3、测试

## 3.1、查看节点属性

```bash
127.0.0.1:6379> cluster info
cluster_state:ok
cluster_slots_assigned:16384
cluster_slots_ok:16384
cluster_slots_pfail:0
cluster_slots_fail:0
cluster_known_nodes:6
cluster_size:3
cluster_current_epoch:6
cluster_my_epoch:1
cluster_stats_messages_ping_sent:34
cluster_stats_messages_pong_sent:37
cluster_stats_messages_sent:71
cluster_stats_messages_ping_received:32
cluster_stats_messages_pong_received:34
cluster_stats_messages_meet_received:5
cluster_stats_messages_received:71
```

## 3.2、查看节点信息

```bash
127.0.0.1:6379> cluster nodes
d221716225e355966aa300efb8ca5bef496fd4b9 127.0.0.1:6380@16380 master - 0 1668232069546 2 connected 5461-10922
357a2c306a0349e12ea2434c92655c6f0d5b207d 127.0.0.1:6384@16384 slave 7460b8066d02612d7028db2d8cecd368febd4e77 0 1668232069000 1 connected
c0d025968fdf0070dbecf5085f287e324264309e 127.0.0.1:6383@16383 slave 5d8ede04727c1581cd040f51da79f9f076e0c4fd 0 1668232070709 3 connected
7460b8066d02612d7028db2d8cecd368febd4e77 127.0.0.1:6379@16379 myself,master - 0 1668232069000 1 connected 0-5460
edfa7fe812375524b6c586897887033382864392 127.0.0.1:6382@16382 slave d221716225e355966aa300efb8ca5bef496fd4b9 0 1668232070000 2 connected
5d8ede04727c1581cd040f51da79f9f076e0c4fd 127.0.0.1:6381@16381 master - 0 1668232069647 3 connected 10923-16383
```

### slave,master,myself 区别

|关键字|说明| 
|:-|:-| 
|slave|该节点为备份节点| 
|master|该节点为主节点|
|myself|该节点为当前链接的节点|

## 3.3、插入一个值

```bash
$ docker exec -it node-1 sh
# redis-cli
127.0.0.1:6379> get name
(error) MOVED 5798 127.0.0.1:6380
```

> 报错：(error) MOVED 5798 127.0.0.1:6380
> 报错原因：是因为启动 `redis-cli` 时没有设置集群模式所导致，启动的时候使用-c参数来启动集群模式，命令如下：
> redis-cli -c

#### 正确用法：

进入容器 `node1`

```bash
$ docker exec -it node-1 sh
redis-cli -c
127.0.0.1:6379> set name eto
-> Redirected to slot [5798] located at 127.0.0.1:6380
OK
127.0.0.1:6380>
```
> 注意：这里根据切片自动切换到了该数据分片所在的节点上，所以下面可以看到连接的节点变为了 127.0.0.1:6380

然后退出，重启进入容器 `node2`

```bash
$ docker exec -it node-2 sh
redis-cli -c -p 6380
127.0.0.1:6380> get name
"eto"
127.0.0.1:6380>
```

# 4、更多操作

## check cluster

```bash
redis-cli --cluster check ip:port
```

## 容错机制

nodes master `6381` 宕机 nodes slave `6383` 顶替 nodes master `6381` 变成 nodes master `6383`, 
当 nodes fail `6381` 重启之后变成 nodes slave `6381`， nodes master `6383` 不变还是 master  

> 如果想把 nodes slave `6381` 变成 master，需要 stop nodes master `6383`， 然后重启 nodes fail `6383`
> 注意：这里 master 和 slave 切换会耗时几秒，不能停止立刻重启

## 扩容

[Add-Node](./ADD-NODE.md)

## 缩容

[Del-Node](./DEL-NODE.md)

# 注意事项

1、按照 `Redis` 官网：[https://redis.io/topics/cluster-tutorial](https://redis.io/topics/cluster-tutorial) 的提示，为了使 `Docker` 与 `Redis Cluster` 兼容，您需要使用 `Docker` 的 `host` 网络模式。

> host 网络模式可以让容器共享宿主机网络栈，容器将不会虚拟出自己的网卡，配置自己的 IP 等，而是使用宿主机的 IP 和端口。

2、使用了数据卷 `./node6/redis.conf:/usr/local/etc/redis/redis.conf`, 必须覆盖容器启动后默认执行的命令。

3、集群至少使用 6 各节点

[redis集群为什么最少需要6个节点？](https://www.php.cn/redis/434214.html)

[为什么redis集群最少需要6个节点？](https://www.cnblogs.com/tracydzf/p/14340667.html)