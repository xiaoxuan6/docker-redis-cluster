container=node-1
path=$(shell pwd)
files=node1 node2 node3 node4 node5 node6

define unlink
	rm -f $(path)/$(1)/data/*.aof $(path)/$(1)/data/*.conf $(path)/$(1)/data/*.rdb
endef

clean:
	@$(foreach file,${files},$(call unlink,${file}))

up:
	@docker-compose up -d

down:
	@docker-compose down
	@$(foreach file,${files},$(call unlink,${file}))

retry: down up
	# retry container successful

exec:
	@docker exec -it ${container} sh

run-web:
	@docker-compose -f docker-compose.yml -f docker-compose-web.yml up -d

down-web:
	@docker-compose -f docker-compose.yml -f docker-compose-web.yml down
	@$(foreach file,${files},$(call unlink,${file}))

retry-web: down-web run-web
	# retry web container successful

up-node:
	@docker-compose -f docker-compose-add-node.yml up -d

ip:
	@ip address | grep eth0