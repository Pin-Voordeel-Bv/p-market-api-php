<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\ParameterVariable;
use PinVandaag\PMarketAPI\Model\ParameterVariableDTO;
use PinVandaag\PMarketAPI\Model\ParameterVariableDeleteRequest;
use PinVandaag\PMarketAPI\Model\ParameterVariableSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalParameterVariableRequest;
use Psr\Http\Message\ResponseInterface;

trait TerminalVariableApiTrait
{
    public function getTerminalVariable(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $tid = null,
        ?string $serialNo = null,
        ?string $packageName = null,
        ?string $key = null,
        ?string $source = null,
    ): ParameterVariableSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if (trim((string) $tid) === '' && trim((string) $serialNo) === '') {
            throw new PMarketAPIException('Parameter tid and serialNo cannot be null at same time!');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeParameterVariableOrderBy($orderBy);
        }

        foreach ([
            'tid' => $tid,
            'serialNo' => $serialNo,
            'packageName' => $packageName,
            'key' => $key,
        ] as $name => $value) {
            if ($value !== null && $value !== '') {
                $query[$name] = $value;
            }
        }

        if ($source !== null && $source !== '') {
            $query['source'] = $this->normalizeParameterVariableSource($source);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/terminalVariables',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'get P Market terminal variables',
        );

        return $this->deserializeParameterVariableSearchResult($response, 'get P Market terminal variables');
    }

    public function createTerminalVariable(TerminalParameterVariableRequest $request): bool
    {
        $this->assertTerminalParameterVariableRequest($request);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminalVariables',
            actionDescription: 'create P Market terminal variable',
            headers: ['Content-Type' => 'application/json'],
            body: [
                'tid' => $request->tid,
                'serialNo' => $request->serialNo,
                'variableList' => array_map(
                    fn (ParameterVariable $variable): array => $this->parameterVariablePayload($variable),
                    $request->variableList,
                ),
            ],
        );

        return true;
    }

    public function updateTerminalVariable(
        int|string $terminalVariableId,
        ParameterVariable $request,
    ): bool {
        $terminalVariableId = $this->assertPositiveInteger($terminalVariableId, 'terminalVariableId');

        $errors = $this->validateParameterVariable($request, allowEmptyKey: true);
        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/terminalVariables/%s', rawurlencode((string) $terminalVariableId)),
            actionDescription: sprintf('update P Market terminal variable "%s"', $terminalVariableId),
            headers: ['Content-Type' => 'application/json'],
            body: $this->parameterVariablePayload($request),
        );

        return true;
    }

    public function deleteTerminalVariable(int|string $terminalVariableId): bool
    {
        $terminalVariableId = $this->assertPositiveInteger($terminalVariableId, 'terminalVariableId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/terminalVariables/%s', rawurlencode((string) $terminalVariableId)),
            actionDescription: sprintf('delete P Market terminal variable "%s"', $terminalVariableId),
        );

        return true;
    }

    public function batchDeletionTerminalVariable(ParameterVariableDeleteRequest $request): bool
    {
        if ($request->variableIds === []) {
            throw new PMarketAPIException('variableIds cannot be empty!');
        }

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminalVariables/batch/deletion',
            actionDescription: 'batch delete P Market terminal variables',
            headers: ['Content-Type' => 'application/json'],
            body: ['variableIds' => array_values($request->variableIds)],
        );

        return true;
    }

    private function assertTerminalParameterVariableRequest(TerminalParameterVariableRequest $request): void
    {
        $errors = [];

        if (trim((string) $request->tid) === '' && trim((string) $request->serialNo) === '') {
            $errors[] = 'The parameter serialNo and tid cannot be blank at same time!';
        }

        if ($request->variableList === []) {
            $errors[] = 'variableList can not be empty';
        }

        foreach ($request->variableList as $variable) {
            $errors = array_merge($errors, $this->validateParameterVariable($variable));
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }
}
