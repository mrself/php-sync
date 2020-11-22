<?php declare(strict_types=1);

namespace Mrself\Sync\Tests;

use Mrself\Sync\SyncProvider;
use Mrself\Sync\ValidationException;
use Symfony\Component\Validator\Constraints as Assert;
use Mrself\Container\Registry\ContainerRegistry;
use Mrself\Sync\Sync;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $target = [];
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = new class extends SyncMock {
            public function formatA($value)
            {
                return $value + 1;
            }
        };
        $sync->init(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(2, $sync->getTarget()['a']);
    }

    public function testOnSyncIsCalled()
    {
        $target = [];
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = new class extends SyncMock {
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
        $sync = new class extends SyncMock {
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
        $sync = new class extends SyncMock {
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

    public function testItValidates()
    {
        $this->addValidator();
        $target = new class {
            /**
             * @Assert\NotBlank()
             */
            public $b;
        };
        $source = (object) ['a' => 1];
        $mapping = ['a'];
        $validate = true;
        $sync = Sync::make(compact('target', 'source', 'mapping', 'validate'));
        $sync->sync();
        $this->assertCount(1, $sync->getErrors());
        /** @var ConstraintViolation $error */
        $error = $sync->getErrors()[0];
        $this->assertInstanceOf(Assert\NotBlank::class, $error->getConstraint());
        $this->assertEquals(1, $sync->getTarget()->a);
    }

    public function testItThrowsValidationErrors()
    {
        $this->addValidator();
        $target = new class {
            /**
             * @Assert\NotBlank()
             */
            public $b;
        };
        $source = (object) ['a' => 1];
        $mapping = ['a'];
        $validate = true;
        $validationExceptions = true;
        $sync = Sync::make(compact('target', 'source', 'mapping', 'validate',
            'validationExceptions'));
        try {
            $sync->sync();
        } catch (ValidationException $e) {
            $this->assertEquals($sync->getErrors(), $e->getErrors());
            return;
        }
        $this->assertTrue(false);
    }

    public function testItUsesGetMapping()
    {
        $target = [];
        $source = ['a' => 1];
        $sync = new class extends SyncMock {
            public function getMapping()
            {
                return ['a'];
            }
        };
        $sync->init(compact('target', 'source'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['a']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItThrowsExceptionIfGetMappingExistsAndMappingOptionExists()
    {
        $target = [];
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = new class extends SyncMock {
            public function getMapping()
            {
                return ['a'];
            }
        };
        $sync->init(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['a']);
    }

    /**
     * @expectedException \RuntimeException
     * @throws ValidationException
     * @throws \Mrself\Container\Registry\NotFoundException
     * @throws \Mrself\Options\UndefinedContainerException
     * @throws \Mrself\Property\EmptyPathException
     * @throws \Mrself\Property\InvalidSourceException
     * @throws \Mrself\Property\InvalidTargetException
     * @throws \Mrself\Property\NonValuePathException
     * @throws \Mrself\Property\NonexistentKeyException
     */
    public function testExceptionIsThrownIfMappingIsMissingAndGetMethodDoesNotExist()
    {
        $target = [];
        $source = (object) ['a' => 1];
        $sync = new class extends SyncMock {
        };
        $sync->init(compact('target', 'source'));
        $sync->sync();
    }

    public function testMappingIsMadeFromSourceKeysIfSourceIsArray()
    {
        $target = [];
        $source = ['a' => 1];
        $sync = new class extends SyncMock {
        };
        $sync->init(compact('target', 'source'));
        $this->assertEquals(1, $sync->sync()['a']);
    }

    public function testItIgnoreMissedIfSuchOptionIsSet()
    {
        $target = (object) [];
        $source = new class {
            protected $b;
            public $a = 1;
        };
        $mapping = ['a', 'b'];
        $ignoreMissed = true;
        $sync = new class extends SyncMock {};
        $sync->init(compact('target', 'source', 'mapping', 'ignoreMissed'));
        $sync->sync();
        $this->assertEquals(1, $target->a);
    }

    public function testSourceIsUsedAsMappingIfMappingOptionsIsEmpty()
    {
        $target = [];
        $source = ['a' => 1];
        $mapping = [];
        $sync = new class extends SyncMock {
            public function getMapping()
            {
                return ['a'];
            }
        };
        $sync->init(compact('target', 'source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['a']);
    }

    public function testTargetCanBeMissed()
    {
        $source = ['a' => 1];
        $mapping = ['a'];
        $sync = Sync::make(compact('source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['a']);
    }

    public function testDefaultValueIsUsedIfItSet()
    {
        $source = [];
        $mapping = ['a'];
        $sync = new class extends SyncMock {
            public function getDefaultA()
            {
                return 1;
            }
        };
        $sync = $sync->init(compact('source', 'mapping'));
        $sync->sync();
        $this->assertEquals(1, $sync->getTarget()['a']);
    }

    protected function setUp()
    {
        parent::setUp();
        ContainerRegistry::reset();
        $provider = SyncProvider::make();
        $provider->registerAndBoot();
    }

    protected function addValidator()
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
        ContainerRegistry::get('Mrself\\Sync')->set(
            ValidatorInterface::class,
            $validator
        );
    }
}

class SyncMock extends Sync {
    protected $optionsContainerNamespace = 'Mrself\\Sync';
}