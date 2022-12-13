<?php /** @noinspection SpellCheckingInspection */

/** @noinspection HttpUrlsUsage */

namespace Cloakings\CloakingsMagicChecker;

use Gupalo\Json\Json;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MagicCheckerHttpClient
{
    private const SERVICE_NAME = 'magicchecker';

    public function __construct(
        private readonly string $apiUrl = 'http://check.magicchecker.com/v2.2/{action}.php',
        private readonly HttpClientInterface $httpClient = new CurlHttpClient(),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function execute(string $campaignId, MagicCheckerParams $params, string $action = 'index'): ?MagicCheckerApiResponse
    {
        $url = (string)str_replace('{action}', $action, $this->apiUrl);

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->request(Request::METHOD_POST, $url, [
                'headers' => ['adapi' => '2.2'],
                'body' => http_build_query([
                    'cmp' => $campaignId,
                    'headers' => $params->all(),
                    'adapi' => '2.2',
                    'sv' => '13997.3',
                ]),
                'verify_peer' => false,
                'verify_host' => false,
                'max_duration' => 4000, // ms
            ]);
            $time = microtime(true) - $startTime;

            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
            $content = $response->getContent();
            $data = array_merge(
                Json::toArray(trim($content, " \t\n\r\0\x0B\"")),
                [
                    'response_status' => $status,
                    'response_headers' => $headers,
                    'response_body' => $content,
                    'response_time' => $time,
                ],
            );
        } catch (\Throwable $e) {
            $this->logger->error('cloaking_request_error', ['service' => self::SERVICE_NAME, 'action' => $action, 'params' => $params->all(), 'status' => $status ?? 0, 'headers' => $headers ?? [], 'content' => $content ?? '', 'exception' => $e]);

            return ($action === 'index') ? MagicCheckerApiResponse::create([]) : null;
        }

        $this->logger->info('cloaking_request', ['service' => self::SERVICE_NAME, 'action' => $action, 'params' => $params->all(), 'status' => $status ?? 0, 'headers' => $headers ?? [], 'content' => $content ?? '', 'time' => $time ?? 0]);

        return ($action === 'index') ? MagicCheckerApiResponse::create($data ?? []) : null;
    }
}
