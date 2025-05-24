DC=docker-compose-local.yml

.PHONY: up down build restart logs stop start \
        app-bash node-bash \
        deploy-prod deploy-dev info-dev info-prod remove-prod remove-dev \
        git-save

## Comandos docker-compose

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


## Acessar containers do docker
app-bash:
	@docker-compose -f ${DC} exec app bash

node-bash:
	@docker-compose -f ${DC} exec node bash

## Comando do serverless
deploy-prod:
	@docker-compose -f ${DC} exec node bash -c "sls deploy --stage prod"

deploy-dev:
	@docker-compose -f ${DC} exec node bash -c "sls deploy --stage dev"

info-dev:
	@docker-compose -f ${DC} exec node bash -c "sls info --stage dev"

info-prod:
	@docker-compose -f ${DC} exec node bash -c "sls info --stage prod"

remove-prod:
	@docker-compose -f ${DC} exec node bash -c "sls remove --stage prod"

remove-dev:
	@docker-compose -f ${DC} exec node bash -c "sls remove --stage dev"

# Comandos Git
git-save:
	@git add .
	@echo "\033[0;33m⊕\033[0m Arquivos adicionados ao stage!"
	@echo "\033[0;36m?\033[0m Digite a mensagem do commit:"
	@read commit_msg && \
	echo "\033[0;34m↻\033[0m Criando commit..." && \
	git commit -m "$$commit_msg" && \
	echo "\033[0;32m✓\033[0m Commit criado: '$$commit_msg'" && \
	echo "\033[0;34m↑\033[0m Enviando para o repositório remoto..." && \
	git push && \
	echo "\033[0;32m✓\033[0m Alterações enviadas com sucesso!"
