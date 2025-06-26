<?php

namespace App\Http\Services;

use App\Exceptions\AwsServiceFailureException;
use App\Exceptions\DynamoDBFailureException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class DynamoDBService
{
    public function __construct(private DynamoDbClient $dynamodb, private Marshaler $marshaler)
    {
    }

    /**
     * Cria um item na tabela informada
     *
     * @param array $data
     * @return bool
     * @throws DynamoDBFailureException|AwsServiceFailureException
     */
    public function createItem(array $data): bool
    {
        try {
            $this->dynamodb->putItem($data);
        } catch (DynamoDbException $e) {
            self::handleException($e);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return true;
    }

    /**
     * Atualiza um item na tabela informada
     *
     * @param array $data
     * @return bool
     * @throws AwsServiceFailureException|DynamoDBFailureException
     */
    public function updateItem(array $data): bool
    {
        try {
            $this->dynamodb->updateItem($data);
        } catch (DynamoDbException $e) {
            self::handleException($e);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return true;
    }

    /**
     * Busca por um item na tabela informada
     *
     * @param string $tableName
     * @param string $key
     * @param string|null $index
     * @param int $limit
     * @return array
     * @throws AwsServiceFailureException|DynamoDBFailureException
     */
    public function getItem(string $tableName, string $key, string $index = null, int $limit = 1): array
    {
        try {
            $result = $this->dynamodb->query([
                'TableName' => $tableName,
                'IndexName' => $index,
                'KeyConditionExpression' => '#key = :key',
                'ExpressionAttributeNames' => [
                    '#key' => $key
                ],
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':key' => $key
                ]),
                'ScanIndexForward' => false,
                'Limit' => $limit
            ]);
        } catch (DynamoDbException $e) {
            self::handleException($e);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return $result['Item'] ?? [];
    }


    /**
     * @param DynamoDbException $e
     * @return void
     * @throws DynamoDBFailureException
     */
    private function handleException(DynamoDbException $e): void
    {
        match ($e->getAwsErrorCode()) {
            'AccessDeniedException' => throw new DynamoDBFailureException("Acesso negado ao DynamoDB"),
            'ConditionalCheckFailedException' => throw new DynamoDBFailureException("Falha na solicitação condicional"),
            'ItemCollectionSizeLimitExceededException' => throw new DynamoDBFailureException("Tamanho da coleção excedido"),
            'ProvisionedThroughputExceededException' => throw new DynamoDBFailureException("Cota de provisionamento excedida"),
            'LimitExceededException' => throw new DynamoDBFailureException("Limite excedido"),
            'RequestLimitExceeded' => throw new DynamoDBFailureException("A taxa de transferência excedeu o limite"),
            default => throw new DynamoDBFailureException("Erro ao executar ação no DynamoDB.")
        };
    }
}
