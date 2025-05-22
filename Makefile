DC=docker-compose-local.yml

.PHONY: up down build restart logs

## Comandos base

up:
	@docker-compose -f ${DC} up -d --remove-orphans

down:
	@docker-compose -f ${DC} down --volumes

stop:
	@docker-compose -f ${DC} stop

start:
	@docker-compose -f ${DC} start

build:
	@docker-compose -f ${DC} build

restart:
	@docker-compose -f ${DC} down
	@docker-compose -f ${DC} up -d --build

logs:
	@docker-compose -f ${DC} logs -f


## Acessar containers
app-bash:
	@docker-compose -f ${DC} exec app bash

node-bash:
	@docker-compose -f ${DC} exec node bash

## Comando do serverless
deploy-prod:
	@docker-compose -f ${DC} exec node bash -c "sls deploy --stage prod"
