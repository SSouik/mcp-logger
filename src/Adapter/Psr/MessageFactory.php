<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace MCP\Logger\Adapter\Psr;

use MCP\Logger\MessageInterface;
use MCP\Logger\Message\MessageFactory as BaseMessageFactory;

/**
 * This factory builds a message that can be sent through the logger service.
 *
 * @see BaseMessageFactory
 * @internal
 */
class MessageFactory extends BaseMessageFactory implements LevelAwareInterface
{
    use LevelAwareTrait;

    /**
     * Sanitize and instantiate a Message
     *
     * @param mixed $level A valid psr-3 log level
     * @param string $message
     * @param array $context
     * @return MessageInterface
     */
    public function buildMessage($level, $message, array $context = array())
    {
        $level = $this->convertPsr3LogLevel($level);
        if (!$level) {
            $level = static::ERROR;
        }

        return parent::buildMessage($level, $message, $context);
    }
}