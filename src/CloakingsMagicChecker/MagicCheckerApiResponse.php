<?php

namespace Cloakings\CloakingsMagicChecker;

use Cloakings\CloakingsCommon\CloakerApiResponseInterface;
use Cloakings\CloakingsCommon\CloakerHelper;

class MagicCheckerApiResponse implements CloakerApiResponseInterface
{
    public function __construct(
        public readonly bool $hasResponse = false,
        public readonly bool $isBan = false,
        public readonly bool $isBlocked = false,
        public readonly string $errorMessage = '',
        public readonly bool $success = false,
        public readonly string $urlType = '', // inline, redirect
        public readonly string $url = '',
        public readonly int $sendParams = 0,
        public readonly array $data = [],
        public readonly int $responseStatus = 0,
        public readonly array $responseHeaders = [],
        public readonly string $responseBody = '',
        public readonly float $responseTime = 0.0,
    ) {
    }

    public static function create(array $a): self
    {
        if (!$a) {
            return new self();
        }

        $isBan = (int)($a['ban'] ?? 0) === 1;
        unset($a['ban']);

        $isBlocked = !empty($a['isBlocked']);
        unset($a['isBlocked']);

        $isSuccess = (int)$a['success'] === 1;
        unset($a['success']);

        $errorMessage = $a['errorMessage'] ?? '';
        unset($a['errorMessage']);

        $urlType = $a['urlType'] ?? '';
        $url = $a['url'] ?? '';
        $sendParams = (int)($a['send_params'] ?? 0);
        $responseStatus = (int)($a['response_status'] ?? 0);
        $responseHeaders = $a['response_headers'] ?? [];
        $responseBody = $a['response_body'] ?? '';
        $responseTime = (float)($a['response_time'] ?? 0.0);
        unset($a['urlType'], $a['url'], $a['send_params'], $a['response_status'], $a['response_headers'], $a['response_body'], $a['response_time']);

        return new self(
            hasResponse: true,
            isBan: $isBan,
            isBlocked: $isBlocked,
            errorMessage: $errorMessage,
            success: $isSuccess,
            urlType: $urlType,
            url: $url,
            sendParams: $sendParams,
            data: $a,
            responseStatus: $responseStatus,
            responseHeaders: $responseHeaders,
            responseBody: $responseBody,
            responseTime: $responseTime,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'has_response' => $this->hasResponse,
            'is_ban' => $this->isBan,
            'is_blocked' => $this->isBlocked,
            'error_message' => $this->errorMessage,
            'success' => $this->success,
            'url_type' => $this->urlType,
            'url' => $this->url,
            'send_params' => $this->sendParams,
            'response_status' => $this->responseStatus,
            'response_headers' => CloakerHelper::flattenHeaders($this->responseHeaders),
            'response_body' => $this->responseBody,
            'response_time' => $this->responseTime,
            'data' => $this->data,
        ];
    }

    public function isReal(): bool
    {
        return (
            !$this->isBan &&
            !$this->isBlocked &&
            (
                str_contains($this->url, 'real') ||
                str_contains($this->url, 'money')
            )
        );
    }

    public function isFake(): bool
    {
        return (
            $this->isBan ||
            $this->isBlocked ||
            (
                str_contains($this->url, 'fake') ||
                str_contains($this->url, 'safe')
            )
        );
    }

    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getResponseTime(): float
    {
        return $this->responseTime;
    }
}
