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
    public function createItem(string $table, array $data): bool
    {
        try {
            $this->dynamodb->putItem([
                'TableName' => $table,
                'Item' => $this->marshaler->marshalItem($data)
            ]);
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
     * @param string $table
     * @param string $key
     * @param string $idName
     * @param array $data
     * @return bool
     * @throws AwsServiceFailureException|DynamoDBFailureException
     */
    public function updateItem(string $table, string $key, string $idName, array $data): bool
    {
        try {
            $updateExpression = 'SET ';
            $expressionAttributeNames = [];
            $expressionAttributeValues = [];
            $count = 0;

            foreach ($data as $field => $value) {
                if ($count > 0) {
                    $updateExpression .= ', ';
                }
                $updateExpression .= "#{$field} = :{$field}";
                $expressionAttributeNames["#{$field}"] = $field;
                $expressionAttributeValues[":{$field}"] = $value;
                $count++;
            }

            $this->dynamodb->updateItem([
                'TableName' => $table,
                'Key' => $this->marshaler->marshalItem([$idName => $key]),
                'UpdateExpression' => $updateExpression,
                'ExpressionAttributeNames' => $expressionAttributeNames,
                'ExpressionAttributeValues' => $this->marshaler->marshalItem($expressionAttributeValues)
            ]);
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
    public function getItem(string $tableName, string $key, string $value, string $index = null, int $limit = 1): array
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
                    ':key' => $value
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

        if (!empty($result['Items'])) {
            return $this->marshaler->unmarshalItem($result['Items'][0]);
        }

        return [];
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
