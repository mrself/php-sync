<?php declare(strict_types=1);

namespace Mrself\Sync\Tests;

use Mrself\Sync\Sync;
use PHPUnit\Framework\TestCase;

class SyncTest extends TestCase
{
    /**
     * @var Sync
     */
    protected $sync;

    public function testSync()
    {
        $target = [];
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = Sync::make(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['a']);
    }

    public function testFormatMethodIsUsed()
    {
        $target = new class {
            public $a;

            public function formatA($value)
            {
                return $value + 1;
            }
        };
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = Sync::make(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(2, $target->a);
    }

    public function testOnSyncIsCalled()
    {
        $target = [];
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = new class extends Sync {
            public $a;

            protected function onSync()
            {
                return $this->a = 3;
            }
        };
        $sync->init(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(3, $sync->a);
    }

    public function testFormatEachIsCalled()
    {
        $target = [];
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = new class extends Sync {
            protected function formatEach(string &$key, &$value)
            {
                $key = 'b';
                $value = 2;
            }
        };
        $sync->init(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(2, $sync->getTarget()['b']);
    }

    public function testFormatEachKeyIsCalled()
    {
        $target = [];
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = new class extends Sync {
            protected function formatEachKey(string $key)
            {
                return 'b';
            }
        };
        $sync->init(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['b']);
    }

    public function testAssociativeArrayCanBeUsedForMapping()
    {
        $target = [];
        $source = ['a_b' => 1];
        $mapping = ['aB' => 'a_b'];
        $sync = Sync::make(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['aB']);
    }

    public function testTargetCanBeObject()
    {
        $target = (object) [];
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = Sync::make(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()->a);
    }

    public function testSourceCanBeObject()
    {
        $target = [];
        $source = (object) ['a' => 1];
        $mapping = ['a'];
        $sync = Sync::make(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['a']);
    }
}