<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reenvía una petición entrante hacia el microservicio destino y transmite
 * la respuesta de vuelta al cliente, preservando código de estado, cuerpo
 * y cabeceras relevantes.
 */
final class ProxyService
{
    /**
     * @param string[] $hopByHopHeaders
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly array $hopByHopHeaders,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function forward(Request $request, ResolvedRoute $route): StreamedResponse
    {
        $targetUrl = $route->url($request->getQueryString() ?? '');

        $options = [
            'headers' => $this->buildOutboundHeaders($request),
            'body' => $request->getContent(),
        ];

        $this->logger->info('Gateway: reenviando petición', [
            'method' => $request->getMethod(),
            'target' => $targetUrl,
        ]);

        try {
            $upstreamResponse = $this->httpClient->request($request->getMethod(), $targetUrl, $options);
            $statusCode = $upstreamResponse->getStatusCode();
            $responseHeaders = $this->buildInboundHeaders($upstreamResponse->getHeaders(false));
        } catch (TransportException $e) {
            $this->logger->error('Gateway: microservicio no disponible', [
                'target' => $targetUrl,
                'error' => $e->getMessage(),
            ]);

            return new StreamedResponse(
                function () {
                    echo json_encode(['error' => 'Servicio no disponible', 'code' => 'BAD_GATEWAY']);
                },
                502,
                ['Content-Type' => 'application/json'],
            );
        }

        $streamedResponse = new StreamedResponse(
            function () use ($upstreamResponse): void {
                try {
                    foreach ($this->httpClient->stream($upstreamResponse) as $chunk) {
                        echo $chunk->getContent();
                        flush();
                    }
                } catch (ExceptionInterface $e) {
                    $this->logger->error('Gateway: error transmitiendo respuesta', ['error' => $e->getMessage()]);
                }
            },
            $statusCode,
            $responseHeaders,
        );

        return $streamedResponse;
    }

    /**
     * @return array<string, string>
     */
    private function buildOutboundHeaders(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            if (in_array(strtolower($name), $this->hopByHopHeaders, true)) {
                continue;
            }
            $headers[$name] = implode(', ', $values);
        }

        // Cabeceras estándar de proxy para que el microservicio conozca el origen real
        $headers['X-Forwarded-For'] = $request->getClientIp() ?? 'unknown';
        $headers['X-Forwarded-Host'] = $request->getHost();
        $headers['X-Forwarded-Proto'] = $request->getScheme();
        $headers['X-Request-Id'] = $request->headers->get('X-Request-Id') ?? bin2hex(random_bytes(8));

        return $headers;
    }

    /**
     * @param array<string, string[]> $upstreamHeaders
     * @return array<string, string>
     */
    private function buildInboundHeaders(array $upstreamHeaders): array
    {
        $headers = [];

        foreach ($upstreamHeaders as $name => $values) {
            if (in_array(strtolower($name), $this->hopByHopHeaders, true)) {
                continue;
            }
            $headers[$name] = implode(', ', $values);
        }

        return $headers;
    }
}
