DC=docker-compose-local.yml

.PHONY: up down build restart logs stop start \
        app-bash node-bash \
        deploy-prod deploy-dev info-dev info-prod remove-prod remove-dev \
        git-save

## Comandos docker-compose

up:
	@echo "\033[0;34m↻\033[0m Criando containers..."
	@docker-compose -f ${DC} up -d --remove-orphans
	@echo "\033[0;32m✓\033[0m Containers criados com sucesso!"

down:
	@echo "\033[0;34m↻\033[0m Parando containers..."
	@docker-compose -f ${DC} down
	@echo "\033[0;32m✓\033[0m Containers parados com sucesso!"

stop:
	@echo "\033[0;34m↻\033[0m Parando containers..."
	@docker-compose -f ${DC} stop
	@echo "\033[0;32m✓\033[0m Containers parados com sucesso!"

start:
	@echo "\033[0;34m↻\033[0m Iniciando containers..."
	@docker-compose -f ${DC} start
	@echo "\033[0;32m✓\033[0m Containers iniciados com sucesso!"

build:
	@echo "\033[0;34m↻\033[0m Buildando imagens..."
	@docker-compose -f ${DC} build
	@echo "\033[0;32m✓\033[0m Imagens buildadas com sucesso!"

restart:
	@echo "\033[0;34m↻\033[0m Deletando containers e volumes..."
	@docker-compose -f ${DC} down --volumes
	@echo "\033[0;32m✓\033[0m Containers e volumes deletados com sucesso!"
	@echo "\033[0;34m↻\033[0m Criando containers..."
	@docker-compose -f ${DC} up -d --build
	@echo "\033[0;32m✓\033[0m Containers criados com sucesso!"

logs:
	@echo "\033[0;34m↻\033[0m Acessando logs..."
	@docker-compose -f ${DC} logs -f


## Acessar containers do docker
app-bash:
	@echo "\033[0;34m↻\033[0m Acessando container app..."
	@docker-compose -f ${DC} exec app bash

node-bash:
	@echo "\033[0;34m↻\033[0m Acessando container node..."
	@docker-compose -f ${DC} exec node bash

## Comando do serverless
deploy-prod:
	@echo "\033[0;34m↻\033[0m Executando deploy em estágio de produção..."
	@docker-compose -f ${DC} exec node bash -c "sls deploy --stage prod"
	@echo "\033[0;32m✓\033[0m Deploy em produção realizado com sucesso!"

deploy-dev:
	@echo "\033[0;34m↻\033[0m Executando deploy em estágio de desenvolvimento..."
	@docker-compose -f ${DC} exec node bash -c "sls deploy --stage dev"
	@echo "\033[0;32m✓\033[0m Deploy em desenvolvimento realizado com sucesso!"

info-dev:
	@echo "\033[0;34m↻\033[0m Verificando informações de estágio de desenvolvimento..."
	@docker-compose -f ${DC} exec node bash -c "sls info --stage dev"

info-prod:
	@echo "\033[0;34m↻\033[0m Verificando informações de estágio de produção..."
	@docker-compose -f ${DC} exec node bash -c "sls info --stage prod"

remove-prod:
	@echo "\033[0;34m↻\033[0m Removendo estágio de produção..."
	@docker-compose -f ${DC} exec node bash -c "sls remove --stage prod"

remove-dev:
	@echo "\033[0;34m↻\033[0m Removendo estágio de desenvolvimento..."
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

# Comandos artisan
test:
	@echo "\033[0;34m↻\033[0m Executando testes..."
	@docker-compose -f ${DC} exec app bash -c "php artisan test"
	@echo "\033[0;32m✓\033[0m Testes executados com sucesso!"
