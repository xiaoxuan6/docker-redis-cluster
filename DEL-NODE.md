# 缩容

## 分析

删除 node-7 和 node-8

## 1、检查集群情况并获取 `node-8` 节点的ID

```bash
redis-cli --cluster check 127.0.0.1:6379 # 127.0.0.1:6379 集群ip:port
```

## 2、删除集群中 `node-7` 的从节点 `node-8`

<details>
<summary><b>删除节点 node-8</b></summary>

```bash
redis-cli --cluster del-node 127.0.0.1:6386 1ff420897e6021901b99f9ac073f0e428ef45c62
>>> Removing node 1ff420897e6021901b99f9ac073f0e428ef45c62 from cluster 127.0.0.1:6386
>>> Sending CLUSTER FORGET messages to the cluster...
>>> Sending CLUSTER RESET SOFT to the deleted node.

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

> redis-cli --cluster check 127.0.0.1:6379
> 
> 127.0.0.1:6379 (7460b806...) -> 0 keys | 4096 slots | 1 slaves.
> 
> 127.0.0.1:6385 (6888de05...) -> 1 keys | 4096 slots | 0 slaves. # 这里可以看到 slaves 已被删除
> 
> 127.0.0.1:6380 (d2217162...) -> 0 keys | 4096 slots | 1 slaves.
> 
> 127.0.0.1:6381 (5d8ede04...) -> 0 keys | 4096 slots | 1 slaves.

</details>

## 3、将 `node-7` 的槽号清空，重新分配（将 `node-7` 的槽号全部给 `node-1`）

<details>
<summary><b>清空槽号</b></summary>

```bash
 redis-cli --cluster reshard 127.0.0.1:6385
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
How many slots do you want to move (from 1 to 16384)? 4096
What is the receiving node ID? 7460b8066d02612d7028db2d8cecd368febd4e77
Please enter all the source node IDs.
  Type 'all' to use all the nodes as source nodes for the hash slots.
  Type 'done' once you entered all the source nodes IDs.
Source node #1: 6888de053c6c35fbdfc62d503e2464f6db970dff
Source node #2: done
```

> How many slots do you want to move (from 1 to 16384)? 4096

这里将 `node-7` 的所有槽位 `4096` 重新分配

> What is the receiving node ID? 7460b8066d02612d7028db2d8cecd368febd4e77

使用 `node-1` 6379 的节点编号接受 `M: 7460b8066d02612d7028db2d8cecd368febd4e77 127.0.0.1:6379`

> Source node #1: 6888de053c6c35fbdfc62d503e2464f6db970dff

这里选择 `node-7` 的编码 `M: 6888de053c6c35fbdfc62d503e2464f6db970dff 127.0.0.1:6385`

> Please enter all the source node IDs.
> 
> Type 'all' to use all the nodes as source nodes for the hash slots.
> 
> Type 'done' once you entered all the source nodes IDs.

后面选择 `done` 继续执行

</details>

## 4、删除集群中的 `node-7` 节点

<details>
<summary><b>查看集群</b></summary>

### 查看集群

```bash
 redis-cli --cluster check 127.0.0.1:6379
127.0.0.1:6379 (7460b806...) -> 1 keys | 8192 slots | 1 slaves.
127.0.0.1:6385 (6888de05...) -> 0 keys | 0 slots | 0 slaves.
127.0.0.1:6380 (d2217162...) -> 0 keys | 4096 slots | 1 slaves.
127.0.0.1:6381 (5d8ede04...) -> 0 keys | 4096 slots | 1 slaves.
[OK] 1 keys in 4 masters.
0.00 keys per slot on average.
>>> Performing Cluster Check (using node 127.0.0.1:6379)
M: 7460b8066d02612d7028db2d8cecd368febd4e77 127.0.0.1:6379
   slots:[0-6826],[10923-12287] (8192 slots) master
   1 additional replica(s)
M: 6888de053c6c35fbdfc62d503e2464f6db970dff 127.0.0.1:6385
   slots: (0 slots) master
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

> 127.0.0.1:6385 (6888de05...) -> 0 keys | 0 slots | 0 slaves.

这里可以看到节点 `node-7` `127.0.0.1:6385` 的槽位已经没有从节点（slaves）和槽位（slots）

### 删除节点

```bash
redis-cli --cluster del-node 127.0.0.1:6385 6888de053c6c35fbdfc62d503e2464f6db970dff
>>> Removing node 6888de053c6c35fbdfc62d503e2464f6db970dff from cluster 127.0.0.1:6385
>>> Sending CLUSTER FORGET messages to the cluster...
>>> Sending CLUSTER RESET SOFT to the deleted node.
```

### 重新查看节点

```bash
redis-cli --cluster check 127.0.0.1:6379
127.0.0.1:6379 (7460b806...) -> 1 keys | 8192 slots | 1 slaves.
127.0.0.1:6380 (d2217162...) -> 0 keys | 4096 slots | 1 slaves.
127.0.0.1:6381 (5d8ede04...) -> 0 keys | 4096 slots | 1 slaves.
[OK] 1 keys in 3 masters.
0.00 keys per slot on average.
>>> Performing Cluster Check (using node 127.0.0.1:6379)
M: 7460b8066d02612d7028db2d8cecd368febd4e77 127.0.0.1:6379
   slots:[0-6826],[10923-12287] (8192 slots) master
   1 additional replica(s)
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

从结果中可以看出已经从 `四主四从` 缩容到 `三主三从`。

</details>
