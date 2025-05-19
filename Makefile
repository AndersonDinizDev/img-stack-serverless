DC=docker-compose-local.yml

.PHONY: up down build restart logs

up:
	@docker-compose -f ${DC} up -d --remove-orphans

down:
	@docker-compose -f ${DC} down --volumes

build:
	@docker-compose -f ${DC} build

restart:
	@docker-compose -f ${DC} down
	@docker-compose -f ${DC} up -d --build

logs:
	@docker-compose -f ${DC} logs -f


## Entrar nos containers
app-bash:
	@docker-compose -f ${DC} exec app bash
