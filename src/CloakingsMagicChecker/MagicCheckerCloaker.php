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
        private readonly array $hideServerKeys = [],
    ) {
    }

    public function handle(Request $request): CloakerResult
    {
        return $this->handleParams($this->collectParams($request));
    }

    public function collectParams(Request $request): array
    {
        $ip = (new CloakerIpExtractor())->getIp($request);
        $items = $request->server->all();
        $items['REMOTE_ADDR'] = $ip;

        $items = $this->hideKeys($items);

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

    private function hideKeys(array $items): array
    {
        foreach ($this->hideServerKeys as $key) {
            unset($items[$key]);
        }
        if (isset($items['SYMFONY_DOTENV_VARS'])) {
            $skipKeys = explode(',', $items['SYMFONY_DOTENV_VARS']);
            foreach ($skipKeys as $key) {
                unset($items[$key]);
            }
            unset($items['SYMFONY_DOTENV_VARS']);
        }

        return $items;
    }
}
