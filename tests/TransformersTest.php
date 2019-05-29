<?php declare(strict_types=1);

namespace Mrself\Sync\Tests;

use Mrself\Container\Container;
use Mrself\Container\Registry\ContainerRegistry;
use Mrself\Sync\Sync;
use PHPUnit\Framework\TestCase;

class TransformersTest extends TestCase
{
    public function testTransformersAreApplied()
    {
        $target = [];
        $source = ['a' => 'ab'];
        $mapping = ['a'];
        $transformers = ['a' => 'first'];
        $sync = Sync::make(compact(
            'target',
            'source',
            'mapping',
            'transformers'
        ));
        $sync->sync();
        $this->assertEquals('a', $sync->getTarget()['a']);
    }

    public function testTransformersAreAppliedInSourceString()
    {
        $source = ['a' => 'ab'];
        $mapping = ['a' => 'a|first'];
        $sync = Sync::make(compact(
            'source',
            'mapping'
        ));
        $sync->sync();
        $this->assertEquals('a', $sync->getTarget()['a']);
    }

    protected function setUp()
    {
        parent::setUp();
        ContainerRegistry::reset();
        $container = Container::make();
        ContainerRegistry::add('Mrself\\Sync', $container);
    }
}