<?php

namespace Productsup\Guzzle;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

class RetryHandler
{
    public const RETRIES_MAX = 3;

    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function delay()
    {
        return function ($numberOfRetries) {
            return 1000 * $numberOfRetries;
        };
    }

    public function decide()
    {
        return function (
            $retries,
            Request $request,
            Response $response = null,
            \Exception $exception = null
        ) {

            // Only for read requests
            if (!\in_array($request->getMethod(), ['GET', 'HEAD'])) {
                return false;
            }

            // Don't retry if we have run out of retries.
            if ($retries >= self::RETRIES_MAX) {
                return false;
            }

            $shouldRetry = false;
            // Retry connection exceptions.
            if ($exception instanceof ConnectException) {
                $shouldRetry = true;
            }
            // Retry on server errors.
            if ($response && $response->getStatusCode() >= 500) {
                $shouldRetry = true;
            }
            // Log if we are retrying
            if ($shouldRetry) {
                $this->logger->notice(
                    sprintf(
                        'Retrying %s %s %s/5, %s',
                        $request->getMethod(),
                        $request->getUri(),
                        $retries + 1,
                        $response ? 'status code: ' . $response->getStatusCode() :
                            $exception->getMessage()
                    )
                );
            }

            return $shouldRetry;
        };
    }
}
