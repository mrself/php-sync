<?php declare(strict_types=1);

namespace Mrself\Sync\Tests;

use PHPUnit\Framework\TestCase;

class ToArrayTest extends TestCase
{
    public function testWithNonAssociativeArray()
    {
        $object = new class {
            use \Mrself\Sync\SyncTrait;

            protected $field;

            public function getField()
            {
                return 'value';
            }
        };
        $fields = $object->toArray(['field']);
        $this->assertEquals('value', $fields['field']);
    }

    public function testWithAssociativeArrayKeys()
    {
        $object = new class {
            use \Mrself\Sync\SyncTrait;

            protected $field;

            public function getField()
            {
                return 'value';
            }
        };
        $fields = $object->toArray(['field1' => 'field']);
        $this->assertEquals('value', $fields['field1']);
    }

    public function testWithoutArgument()
    {
        $object = new class {
            use \Mrself\Sync\SyncTrait;

            protected $field;

            public function getField()
            {
                return 'value';
            }
        };
        $fields = $object->toArray();
        $this->assertEquals('value', $fields['field']);
    }

    public function testItIgnoresAttributes()
    {
        $object = new class {
            use \Mrself\Sync\SyncTrait;

            protected $field;
            public $field1;

            public function getField()
            {
                return 'value';
            }

            protected function getIgnoredExportKeys(): array
            {
                return ['field1'];
            }
        };
        $fields = $object->toArray();
        $this->assertEquals(['field' => 'value'], $fields);
    }
}