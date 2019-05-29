<?php declare(strict_types=1);

namespace Mrself\Sync\Tests;

use Mrself\Sync\Sync;
use PHPUnit\Framework\TestCase;

class NestedTest extends TestCase
{
    public function testNestedMappingIsUsed()
    {
        $source = ['a' => ['b' => 'b']];
        $mapping = [
            'a' => [
                'b'
            ]
        ];
        $sync = Sync::make(compact(
            'source',
            'mapping'
        ));
        $sync->sync();
        $this->assertEquals('b', $sync->getTarget()['a']['b']);
    }

    public function testNestedMappingIsUsedWithRewrittenSourceKey()
    {
        $source = ['A' => ['b' => 'b']];
        $mapping = [
            'a:A' => [
                'b'
            ]
        ];
        $sync = Sync::make(compact(
            'source',
            'mapping'
        ));
        $sync->sync();
        $this->assertEquals('b', $sync->getTarget()['a']['b']);
    }
}