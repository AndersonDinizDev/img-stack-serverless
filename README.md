# IMG-STACK

## Descrição

Este projeto é uma API serverless que permite processar e transformar imagens sob demanda através de URLs ou chamadas de API. Em vez de pré-processar todas as variações de imagens (que consome tempo e espaço), este serviço gera e armazena transformações apenas quando solicitadas.

## Requisitos
- Docker
- Docker Compose


## Instalação
1. Clone o repositório:
```bash
git clone https://github.com/AndersonDinizDev/img-stack-serverless.git
```
2. Navegue até o diretório do projeto:
```bash
cd img-stack-serverless
```
3. Crie um arquivo `.env` com as variáveis de ambiente necessárias. Você pode usar o arquivo `.env.example` como referência.
```bash
cp .env.example .env
```
4. Inicie os serviços com utilizando o Makefile:
```bash
make up
```
5. Acesse a aplicação em `http://localhost:80`.

## Comandos disponíveis
- `make up`: Inicia os serviços.
- `make down`: Para os serviços junto com os volumes.
- `make build`: Constrói as imagens Docker.
- `make logs`: Exibe os logs dos serviços.
- `make restart`: Para e recria os serviços.
- `make app-bash`: Acessa o container da aplicação laravel.
