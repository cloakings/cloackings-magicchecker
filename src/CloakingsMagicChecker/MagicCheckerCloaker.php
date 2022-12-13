<?php

namespace Cloakings\CloakingsMagicChecker;

use Cloakings\CloakingsCommon\CloakerInterface;
use Cloakings\CloakingsCommon\CloakerIpExtractor;
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

    public function collectParams(Request $request, array $skipKeys = []): array
    {
        $ip = (new CloakerIpExtractor())->getIp($request);
        $items = $request->server->all();
        $items['REMOTE_ADDR'] = $ip;

        foreach ($skipKeys as $key) {
            unset($items[$key]);
        }

        return (new MagicCheckerParams($items))->all();
    }

    public function handleParams(array $params): CloakerResult
    {
        $apiResponse = $this->httpClient->execute($this->campaignId, new MagicCheckerParams($params));

        return $this->createResult($apiResponse ?? new MagicCheckerApiResponse());
    }

    public function createResult(MagicCheckerApiResponse $apiResponse, CloakModeEnum $default = CloakModeEnum::Error): CloakerResult
    {
        return new CloakerResult(
            mode: match(true) {
                $apiResponse->isFake() => CloakModeEnum::Fake,
                $apiResponse->isReal() => CloakModeEnum::Real,
                default => $default,
            },
            response: new Response(),
            apiResponse: $apiResponse,
            params: $apiResponse->data,
        );
    }
}
