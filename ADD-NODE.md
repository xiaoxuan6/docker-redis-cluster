# 扩容

将原来的 `三主三从` 扩容到 `四主四从`

## 1、首先启动 `redis` 节点 `node-7` 和节点 `node-8`

```bash
docker-compose -f docker-compose-add-node.yml up -d 
```

## 2、进入节点 `node-7` 加入 redis 集群

<details>
<summary><b>Add Node</b></summary>

> 格式：redis-cli --cluster add-node 新节点ip:port 集群ip:port

```bash
docker exec -it node-7 sh
redis-cli --cluster add-node 127.0.0.1:6385 127.0.0.1:6379
>>> Adding node 127.0.0.1:6385 to cluster 127.0.0.1:6379
>>> Performing Cluster Check (using node 127.0.0.1:6379)
M: 7460b8066d02612d7028db2d8cecd368febd4e77 127.0.0.1:6379
   slots:[0-5460] (5461 slots) master
   1 additional replica(s)
M: d221716225e355966aa300efb8ca5bef496fd4b9 127.0.0.1:6380
   slots:[5461-10922] (5462 slots) master
   1 additional replica(s)
M: 5d8ede04727c1581cd040f51da79f9f076e0c4fd 127.0.0.1:6381
   slots:[10923-16383] (5461 slots) master
   1 additional replica(s)
S: edfa7fe812375524b6c586897887033382864392 127.0.0.1:6382
   slots: (0 slots) slave
   replicates d221716225e355966aa300efb8ca5bef496fd4b9
S: 357a2c306a0349e12ea2434c92655c6f0d5b207d 127.0.0.1:6384
   slots: (0 slots) slave
   replicates 7460b8066d02612d7028db2d8cecd368febd4e77
S: c0d025968fdf0070dbecf5085f287e324264309e 127.0.0.1:6383
   slots: (0 slots) slave
   replicates 5d8ede04727c1581cd040f51da79f9f076e0c4fd
[OK] All nodes agree about slots configuration.
>>> Check for open slots...
>>> Check slots coverage...
[OK] All 16384 slots covered.
>>> Send CLUSTER MEET to node 127.0.0.1:6385 to make it join the cluster.
[OK] New node added correctly.
```

</details>

## 3、查看节点（check）是否加入成功

<details>
<summary><b>Check</b></summary>

出现下面的表示节点加入成功，但是没有从节点（slaves）和槽位（slots）

> 127.0.0.1:6385 (6888de05...) -> 0 keys | 0 slots | 0 slaves.

```bash
docker exec -it node-1 sh
redis-cli --cluster check 127.0.0.1:6379
127.0.0.1:6379 (7460b806...) -> 0 keys | 5461 slots | 1 slaves.
127.0.0.1:6385 (6888de05...) -> 0 keys | 0 slots | 0 slaves.
127.0.0.1:6380 (d2217162...) -> 1 keys | 5462 slots | 1 slaves.
127.0.0.1:6381 (5d8ede04...) -> 0 keys | 5461 slots | 1 slaves.
[OK] 1 keys in 4 masters.
0.00 keys per slot on average.
>>> Performing Cluster Check (using node 127.0.0.1:6379)
M: 7460b8066d02612d7028db2d8cecd368febd4e77 127.0.0.1:6379
   slots:[0-5460] (5461 slots) master
   1 additional replica(s)
M: 6888de053c6c35fbdfc62d503e2464f6db970dff 127.0.0.1:6385
   slots: (0 slots) master
M: d221716225e355966aa300efb8ca5bef496fd4b9 127.0.0.1:6380
   slots:[5461-10922] (5462 slots) master
   1 additional replica(s)
M: 5d8ede04727c1581cd040f51da79f9f076e0c4fd 127.0.0.1:6381
   slots:[10923-16383] (5461 slots) master
   1 additional replica(s)
S: edfa7fe812375524b6c586897887033382864392 127.0.0.1:6382
   slots: (0 slots) slave
   replicates d221716225e355966aa300efb8ca5bef496fd4b9
S: 357a2c306a0349e12ea2434c92655c6f0d5b207d 127.0.0.1:6384
   slots: (0 slots) slave
   replicates 7460b8066d02612d7028db2d8cecd368febd4e77
S: c0d025968fdf0070dbecf5085f287e324264309e 127.0.0.1:6383
   slots: (0 slots) slave
   replicates 5d8ede04727c1581cd040f51da79f9f076e0c4fd
[OK] All nodes agree about slots configuration.
>>> Check for open slots...
>>> Check slots coverage...
[OK] All 16384 slots covered.
```

</details>

## 4、重新分配槽号（reshard）

<details>
<summary><b>Reshard</b></summary>

> 格式：redis-cli --cluster reshard 集群ip:port

```bash
docker exec -it node-1 sh
redis-cli --cluster reshard 127.0.0.1:6379
```

> How many slots do you want to move (from 1 to 16384)？4096

这里的槽位根据总槽位（16384）/总节点（master）,这里是四个 master 节点，所以是：4096

> What is the receiving node ID？6888de053c6c35fbdfc62d503e2464f6db970dff

这里分配给那个节点，M: 6888de053c6c35fbdfc62d503e2464f6db970dff 127.0.0.1:6385

后面执行 `all` 给所有的 `master` 重新分配槽位 `yes`

</details>

## 5、重新查看分配槽位

<details>
<summary><b>Check Reshard</b></summary>

```bash
redis-cli --cluster check 127.0.0.1:6379
127.0.0.1:6379 (7460b806...) -> 0 keys | 4096 slots | 1 slaves.
127.0.0.1:6385 (6888de05...) -> 1 keys | 4096 slots | 0 slaves.
127.0.0.1:6380 (d2217162...) -> 0 keys | 4096 slots | 1 slaves.
127.0.0.1:6381 (5d8ede04...) -> 0 keys | 4096 slots | 1 slaves.
[OK] 1 keys in 4 masters.
0.00 keys per slot on average.
>>> Performing Cluster Check (using node 127.0.0.1:6379)
M: 7460b8066d02612d7028db2d8cecd368febd4e77 127.0.0.1:6379
   slots:[1365-5460] (4096 slots) master
   1 additional replica(s)
M: 6888de053c6c35fbdfc62d503e2464f6db970dff 127.0.0.1:6385
   slots:[0-1364],[5461-6826],[10923-12287] (4096 slots) master
M: d221716225e355966aa300efb8ca5bef496fd4b9 127.0.0.1:6380
   slots:[6827-10922] (4096 slots) master
   1 additional replica(s)
M: 5d8ede04727c1581cd040f51da79f9f076e0c4fd 127.0.0.1:6381
   slots:[12288-16383] (4096 slots) master
   1 additional replica(s)
S: edfa7fe812375524b6c586897887033382864392 127.0.0.1:6382
   slots: (0 slots) slave
   replicates d221716225e355966aa300efb8ca5bef496fd4b9
S: 357a2c306a0349e12ea2434c92655c6f0d5b207d 127.0.0.1:6384
   slots: (0 slots) slave
   replicates 7460b8066d02612d7028db2d8cecd368febd4e77
S: c0d025968fdf0070dbecf5085f287e324264309e 127.0.0.1:6383
   slots: (0 slots) slave
   replicates 5d8ede04727c1581cd040f51da79f9f076e0c4fd
[OK] All nodes agree about slots configuration.
>>> Check for open slots...
>>> Check slots coverage...
[OK] All 16384 slots covered.
```

> M: 6888de053c6c35fbdfc62d503e2464f6db970dff 127.0.0.1:6385
> slots:[0-1364],[5461-6826],[10923-12287] (4096 slots) master

从上面可以看到 nodes master `node-7` 槽位分别从其他三个 `master` 节点凑齐 `4096`

</details>

## 6、给 `node-7` 分配 `slave` `node-8`节点

<details>
<summary><b>Cluster-slave</b></summary>

```bash
redis-cli --cluster add-node 127.0.0.1:6386 127.0.0.1:6385 --cluster-slave --cluster-master-id 6888de053c6c35fbdfc62d503e2464f6db970dff
>>> Adding node 127.0.0.1:6386 to cluster 127.0.0.1:6385
>>> Performing Cluster Check (using node 127.0.0.1:6385)
M: 6888de053c6c35fbdfc62d503e2464f6db970dff 127.0.0.1:6385
   slots:[0-1364],[5461-6826],[10923-12287] (4096 slots) master
M: 5d8ede04727c1581cd040f51da79f9f076e0c4fd 127.0.0.1:6381
   slots:[12288-16383] (4096 slots) master
   1 additional replica(s)
M: d221716225e355966aa300efb8ca5bef496fd4b9 127.0.0.1:6380
   slots:[6827-10922] (4096 slots) master
   1 additional replica(s)
M: 7460b8066d02612d7028db2d8cecd368febd4e77 127.0.0.1:6379
   slots:[1365-5460] (4096 slots) master
   1 additional replica(s)
S: edfa7fe812375524b6c586897887033382864392 127.0.0.1:6382
   slots: (0 slots) slave
   replicates d221716225e355966aa300efb8ca5bef496fd4b9
S: 357a2c306a0349e12ea2434c92655c6f0d5b207d 127.0.0.1:6384
   slots: (0 slots) slave
   replicates 7460b8066d02612d7028db2d8cecd368febd4e77
S: c0d025968fdf0070dbecf5085f287e324264309e 127.0.0.1:6383
   slots: (0 slots) slave
   replicates 5d8ede04727c1581cd040f51da79f9f076e0c4fd
[OK] All nodes agree about slots configuration.
>>> Check for open slots...
>>> Check slots coverage...
[OK] All 16384 slots covered.
>>> Send CLUSTER MEET to node 127.0.0.1:6386 to make it join the cluster.
Waiting for the cluster to join
.
>>> Configure node as replica of 127.0.0.1:6385.
[OK] New node added correctly.
```

> 127.0.0.1:6386 节点 node-8、127.0.0.1:6385 节点 node-7
> 6888de053c6c35fbdfc62d503e2464f6db970dff 表示 node-7 的编号
>
> 6386 必须在 6385 前面，表示 6386 挂载到 6385 上面

</details>

## 7、查看扩容后的集群

```bash
redis-cli --cluster check 127.0.0.1:6379
```
