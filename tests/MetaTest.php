<?php declare(strict_types=1);

namespace Mrself\Sync\Tests;

use Mrself\Container\Registry\ContainerRegistry;
use Mrself\Sync\Sync;
use Mrself\Sync\SyncProvider;
use PHPUnit\Framework\TestCase;

class MetaTest extends TestCase
{
    public function testContainerIsUsed()
    {
        $target = [];
        $source = ['a' => ['b' => 'b']];
        $mapping = [
            'mapping' => ['b'],
            'meta' => [
                'path' => 'a'
            ]
        ];
        $sync = Sync::make(compact(
            'target',
            'source',
            'mapping'
        ));
        $sync->sync();
        $this->assertEquals('b', $sync->getTarget()['b']);
    }

    protected function setUp()
    {
        parent::setUp();
        ContainerRegistry::reset();
        SyncProvider::make()->register();
    }
}