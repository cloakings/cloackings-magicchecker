<?php

namespace Cloakings\CloakingsMagicChecker;

use Cloakings\CloakingsCommon\CloakerInterface;
use Cloakings\CloakingsCommon\CloakerResult;
use Cloakings\CloakingsCommon\CloakModeEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MagicCheckerCloaker implements CloakerInterface
{
    public function __construct(
        private readonly string $campaignId,
        private readonly MagicCheckerHttpClient $httpClient = new MagicCheckerHttpClient(),
    ) {
    }

    public function handle(Request $request): CloakerResult
    {
        return $this->handleParams($this->collectParams($request));
    }

    public function collectParams(Request $request): array
    {
        return (new MagicCheckerParams($request->server->all()))->all();
    }

    public function handleParams(array $params): CloakerResult
    {
        $apiResponse = $this->httpClient->execute($this->campaignId, new MagicCheckerParams($params));

        return $this->createResult($apiResponse);
    }

    /** @noinspection PhpDuplicateMatchArmBodyInspection */
    public function createResult(MagicCheckerApiResponse $apiResponse): CloakerResult
    {
        return new CloakerResult(
            mode: match(true) {
                $apiResponse->isFake() => CloakModeEnum::Fake,
                $apiResponse->isReal() => CloakModeEnum::Real,
                default => CloakModeEnum::Error,
            },
            response: new Response(),
            apiResponse: $apiResponse,
            params: $apiResponse->data,
        );
    }
}
