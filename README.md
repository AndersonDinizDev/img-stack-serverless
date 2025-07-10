# IMG-STACK

## Descrição

IMG-STACK é um serviço serverless inteligente para processamento de imagens em tempo real totalmente em serverless.
Oferecendo redimensionamento, cortes, filtros, analise com IA e otimização automática sem necessidade de
pré-processamento.

### Características Principais

- **Processamento Assincrono**: Processamento feito totalmente via AWS SQS
- **Cache Inteligente**: Sistema de cache baseado em conteúdo com TTL automático
- **Arquitetura Serverless**: Escalabilidade automática usando AWS Lambda
- **URLs Assinadas**: Segurança através do CloudFront com chaves privadas

## Arquitetura

### Componentes Principais

- **ImageProcessingService**: Orquestrador que decide estratégia de processamento
- **WorkerService**: Gerencia jobs assíncronos via SQS e DynamoDB
- **ProcessImageJob**: Worker Lambda para processamento pesado
- **StorageService**: Abstração para S3 e CloudFront
- **DynamoDBService**: Responsável por gerenciar a tabela de jobs
- **RekognitionService**: Utiliza do serviço para análise de imagens com IA

### Fluxo de Processamento

```
Requisição → Verificar Cache na S3 → Adiciona à Fila → Retorna Informação `Retry-After`
         ↓
SQS Queue → Lambda Worker → Processa a Imagem → Salva Cache na S3 → Atualiza o Status
         ↓
Verifica se Finalizou → Retorna a Imagem Processada
```

## Índice

- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Deploy](#deploy)
- [Utilizando Aplicação](#utilizando-aplicação)
- [Infraestrutura AWS](#infraestrutura-aws)
- [Comandos Disponíveis](#comandos-disponíveis)
- [Desenvolvimento](#desenvolvimento)
- [Monitoramento](#monitoramento)
- [Troubleshooting](#troubleshooting)
- [Contribuição](#contribuição)

## Requisitos

- **Docker** - versão 20.10 ou superior
- **Docker Compose** - versão 2.0 ou superior
- **Conta AWS** com as seguintes permissões:
    - Lambda (criação e execução)
    - S3 (buckets e objetos)
    - CloudFront (distribuições e chaves)
    - DynamoDB (tabelas e índices)
    - SQS (filas)
    - IAM (roles e políticas)
- **Make** (opcional, mas recomendado)

## Instalação

### 1. Clone do repositório

```bash
git clone https://github.com/AndersonDinizDev/img-stack-serverless.git
cd img-stack-serverless
```

### 2. Configuração do ambiente

```bash
cp .env.example .env
```

### 3. Inicialização dos serviços de desenvolvimento

```bash
make up
```

## Configuração

### Variáveis de Ambiente

Edite o arquivo `.env` com as seguintes configurações:

```env
# AWS Credentials
AWS_ACCESS_KEY_ID=sua_access_key_aqui
AWS_SECRET_ACCESS_KEY=sua_secret_key_aqui
AWS_DEFAULT_REGION=us-east-1

# Application
APP_NAME=img-stack
APP_ENV=production
APP_DEBUG=false

```

### CloudFront - Configuração de Chaves Privadas

Para URLs assinadas, configure no console AWS:

1. **Acesse CloudFront** → [Console AWS](https://console.aws.amazon.com/cloudfront/)
2. **Crie um Key Group**:
    - Gere um par de chaves RSA-2048
    - Adicione a chave pública ao Key Group
    - Configure a chave privada no ambiente
3. **Configure a Distribuição**:
    - Associe o Key Group à distribuição
    - Configure comportamentos de cache
4. **Adicione as chaves na AWS SSM para uso no serverless.yml**:
    ```
   aws ssm put-parameter \
    --name "/img-stack/cloudfront-key-group-id" \
    --value "Sua key group aqui" \
    --type "String" \
    --description "Sua descrição aqui." \
    --overwrite
   
    aws ssm put-parameter \
    --name "/img-stack/cloudfront-key-pair-id" \
    --value "Sua key pair aqui" \
    --type "String" \
    --description "Sua descrição aqui." \
    --overwrite
   
    aws ssm put-parameter \
    --name "/img-stack/cloudfront-private-key" \
    --value "Sua private key aqui" \
    --type "String" \
    --description "Sua descrição aqui." \
    --overwrite
   ```

**Documentação oficial**:
[Trusted Signers](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-trusted-signers.html)

## Deploy

### Ambiente de Desenvolvimento

```bash
# Deploy inicial
make deploy-dev

# Deploy apenas de código
serverless deploy function --function web --stage dev
```

### Ambiente de Produção

```bash
# Deploy completo
make deploy-prod
```

### Verificação do Deploy

```bash
# Status dos recursos
serverless info --stage prod

# Logs em tempo real
serverless logs --function web --stage prod --tail
```

## Utilizando Aplicação

### Parâmetros da Requisição

| Parâmetros | Descrição                                                       | Exemplo                      |
|------------|-----------------------------------------------------------------|------------------------------|
| `r_w`      | Largura da imagem                                               | 100                          |
| `w_h`      | Altura da imagem                                                | 100                          |
| `i_f`      | Formato da imagem                                               | webp                         |
| `i_q`      | Qualidade da imagem                                             | 100                          |
| `image`    | URL da imagem que deseja utilizar                               | http://example.com/teste.png |
| `ai`       | Utilizar analise de IA para verificar imagens de teor impróprio | ['safe', 'faces']            |            

- **resources/views/teste.blade.php**: Arquivo com um simples exemplo de uso
- **faces**: Utiliza a aws rekogtion para decteção de rosto na imagem e embaça caso encontrado
- **safe**: Utiliza a aws rekognition para decteção de conteúdo impróprio, indesejado ou ofensivo e embaçada toda a
  imagem caso encontrado

## Infraestrutura AWS

### Recursos Criados Automaticamente

#### Lambda Functions

- **web**: API HTTP principal (timeout: 28s)
- **worker**: Processamento assíncrono (timeout: 300s, RAM: 1GB)

#### Armazenamento

- **S3 Bucket**: Cache de imagens com lifecycle policy (90 dias)
- **DynamoDB**:
    - Tabela para controle de jobs
    - GSI para queries por status
    - TTL automático

#### Rede e Distribuição

- **CloudFront**: CDN global com cache inteligente
- **SQS**: Fila principal
- **IAM Roles**: Permissões mínimas necessárias

### Otimizações de Performance

- **Bref Layer**: Runtime PHP otimizado para Lambda
- **ImageMagick Layer**: Processamento nativo de imagens
- **CloudFront Cache**: Headers inteligentes baseados em conteúdo
- **DynamoDB GSI**: Queries eficientes por status e timestamp

## Comandos Disponíveis

### Desenvolvimento

| Comando        | Descrição                          |
|----------------|------------------------------------|
| `make up`      | Inicia ambiente de desenvolvimento |
| `make down`    | Para serviços e remove volumes     |
| `make build`   | Reconstrói imagens Docker          |
| `make logs`    | Logs em tempo real                 |
| `make restart` | Reinicia todos os serviços         |

### Containers

| Comando          | Descrição                |
|------------------|--------------------------|
| `make app-bash`  | Acessa container Laravel |
| `make node-bash` | Acessa container Node.js |

### Deploy

| Comando            | Descrição                       |
|--------------------|---------------------------------|
| `make deploy-dev`  | Deploy para desenvolvimento     |
| `make deploy-prod` | Deploy para produção            |
| `make remove-dev`  | Remove stack de desenvolvimento |
| `make remove-prod` | Remove stack de produção        |

### Outros

| Comando         | Descrição                    |
|-----------------|------------------------------|
| `make git-save` | Atalho para commit no github |

### AWS

```bash
# Listar funções Lambda
aws lambda list-functions --region us-east-1

# Verificar DynamoDB
aws dynamodb describe-table --table-name img-stack-prod-images

# Status da fila SQS
aws sqs get-queue-attributes --queue-url $SQS_QUEUE_URL --attribute-names All
```

## Desenvolvimento

### Debugging

```bash
# Logs da aplicação
make logs

# Logs específicos do worker
docker-compose logs worker

# Debug de job específico
php artisan queue:retry [job-id]
```

### Testes

```bash
# Testes de Feature/Unit
make test

# Teste de processamento
curl "http://dominio.cloudfront.com/v1/image?url=https://example.com/image.jpg&w=300&h=200&f=webp&t=resize"
```

### Problemas Comuns

#### Deploy falha

```bash
# Verificar permissões IAM
aws iam get-user

# Verificar configuração Serverless
serverless config credentials --provider aws --key KEY --secret SECRET

# Deploy com verbose
serverless deploy --verbose --stage dev
```

#### Processamento lento

```bash
# Verificar worker Lambda
aws lambda get-function --function-name img-stack-prod-worker

# Verificar fila SQS
aws sqs receive-message --queue-url $SQS_QUEUE_URL

# Reprocessar jobs com falha
php artisan queue:retry all
```

#### Cache não funciona

```bash
# Verificar S3
aws s3 ls s3://img-stack-cache/

# Invalidar CloudFront
aws cloudfront create-invalidation --distribution-id E123456 --paths "/*"

# Verificar DynamoDB
aws dynamodb scan --table-name img-stack-prod-images --limit 10
```

### Comandos de Diagnóstico

```bash
# Status completo da infraestrutura
serverless info --stage prod --verbose

# Métricas de performance
aws logs filter-log-events --log-group-name /aws/lambda/img-stack-prod-web

```

### Logs e Monitoramento

```bash
# Logs em tempo real por função
serverless logs --function web --stage prod --tail

# Logs por período
serverless logs --function worker --stage prod --startTime 1h

# Métricas DynamoDB
aws dynamodb describe-table --table-name img-stack-prod-images
```

---

**Mantido por**: [Anderson Diniz](https://github.com/AndersonDinizDev)
