<?php
/**
 * @copyright (c) 2015 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace MCP\Logger\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Pool;
use MCP\Logger\Exception;
use MCP\Logger\MessageInterface;
use Exception as BaseException;

/**
 * @internal
 */
trait GuzzleTrait
{
    /**
     * @var ClientInterface
     */
    private $guzzle;

    /**
     * @param MessageInterface $message
     *
     * @return RequestInterface
     */
    protected function createRequest(MessageInterface $message)
    {
        $options = [
            'body' => call_user_func($this->serializer, $message),
            'headers' => ['Content-Type' => $this->serializer->contentType()],
            'exceptions' => true
        ];

        return $this->guzzle->createRequest('POST', $this->uri->expand([]), $options);
    }

    /**
     * Requires Guzzle 5
     *
     * @param RequestInterface[] $requests
     *
     * @return void
     */
    protected function handleBatch(array $requests)
    {
        $errors = [];

        Pool::send($this->guzzle, $requests, [
            'error' => function (ErrorEvent $event) use (&$errors) {
                $errors[] = $event->getException();
            }
        ]);

        $this->handleErrors($requests, $errors);
    }

    /**
     * Requires Guzzle 5
     *
     * @param RequestInterface[] $requests
     * @param ErrorEvent[] $errors
     *
     * @throws Exception
     *
     * @return void
     */
    protected function handleErrors(array $requests, array $errors)
    {
        if (!$errors) {
            return;
        }

        $batchSize = count($requests);

        $template = defined('static::ERR_BATCH') ? static::ERR_BATCH : '%d errors occured while sending %d messages with mcp-logger';
        $msg = sprintf($template, count($errors), $batchSize);

        // Silent handling
        if (property_exists($this, 'isSilent') && $this->isSilent) {
            error_log($msg);
            return;
        }

        // Send a more specific message if only one error
        if ($batchSize === 1) {
            $e = reset($errors);
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        throw new Exception($msg);
    }
}
