<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\MCP\Logger;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class BroadcastLoggerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public $logger1;
    public $logger2;

    public function setUp(): void
    {
        $this->logger1 = Mockery::spy(LoggerInterface::class);
        $this->logger2 = Mockery::spy(LoggerInterface::class);
    }

    public function testLoggingMessageWithoutLoggersDoesntDoAnything()
    {
        $broadcaster = new BroadcastLogger;

        $broadcaster->debug('test');
        $broadcaster->alert('test 2');
    }

    public function testBroadcastingToLoggersConfiguredInConstructor()
    {
        $broadcaster = new BroadcastLogger([
            $this->logger1,
            $this->logger2
        ]);

        $broadcaster->error('test');

        $this->logger1
            ->shouldHaveReceived('log', [LogLevel::ERROR, 'test', []]);
        $this->logger2
            ->shouldHaveReceived('log', [LogLevel::ERROR, 'test', []]);
    }

    public function testBroadcastingToLoggersInSetter()
    {
        $broadcaster = new BroadcastLogger([
            $this->logger1
        ]);

        $broadcaster->addLogger($this->logger2);

        $broadcaster->warning('test');

        $this->logger1
            ->shouldHaveReceived('log', [LogLevel::WARNING, 'test', []]);
        $this->logger2
            ->shouldHaveReceived('log', [LogLevel::WARNING, 'test', []]);
    }
}
