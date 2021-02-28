<?php declare(strict_types=1);

namespace Mrself\Sync\Tests;

use Mrself\Container\Registry\ContainerRegistry;
use Mrself\Sync\Sync;
use Mrself\Sync\SyncProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\Test\TestLogger;

class LoggingTest extends TestCase
{
    public function testItLogs()
    {
        $logger = new TestLogger();
        LoggingSync::make([
            'source' => ['a' => 1],
            'logger' => $logger
        ])->sync();

        $this->assertCount(2, $logger->records);
        $startAction = $logger->records[0];
        $this->assertEquals('a', $startAction['context']['from']);
        $this->assertEquals('b', $startAction['context']['to']);

        $completedAction = $logger->records[1];
        $this->assertEquals('a', $completedAction['context']['from']);
        $this->assertEquals('b', $completedAction['context']['to']);
    }

    protected function setUp()
    {
        parent::setUp();
        ContainerRegistry::reset();
        $provider = SyncProvider::make();
        $provider->registerAndBoot();
    }
}

class LoggingSync extends Sync
{
    protected function sourceToName(): string
    {
        return 'a';
    }

    protected function targetToName(): string
    {
        return 'b';
    }
}