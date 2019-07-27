<?php declare(strict_types=1);

namespace Mrself\Sync;

use ICanBoogie\Inflector;
use Mrself\Container\Registry\ContainerRegistry;
use Mrself\DataTransformers\DataTransformers;
use Mrself\Options\Annotation\Option;
use Mrself\Options\WithOptionsTrait;
use Mrself\Property\Property;
use function PHPSTORM_META\map;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Sync
{
    use WithOptionsTrait;

    /**
     * @Option()
     * @var mixed
     */
	protected $source;

    /**
     * @Option()
     * @var mixed
     */
	protected $target = [];

    /**
     * @var array|string[]
     */
	protected $mapping;

    /**
     * @var array
     */
	protected $transformers;

    /**
     * @Option()
     * @var bool
     */
	protected $validate = false;

    /**
     * @Option()
     * @var bool
     */
	protected $validationExceptions = false;

    /**
     * @var ValidatorInterface
     */
	protected $validator;

    /**
     * @var Inflector
     */
	protected $inflector;

    /**
     * @var Property
     */
	protected $property;

    /**
     * @var ConstraintViolationListInterface
     */
	protected $errors;

    /**
     * @var boolean
     */
	protected $ignoreMissed;

    /**
     * @var DataTransformers
     */
	protected $dataTransformers;

    /**
     * @var array
     */
	protected $meta = [];

    /**
     * @var mixed
     */
	protected $originalSource;

    public function __construct()
    {
        $this->property = Property::make();
        $this->inflector = Inflector::get();
	}

	protected function getOptionsSchema()
    {
        return [
            'allowedTypes' => [
                'mapping' => 'array',
                'source'=> ['array', 'object'],
                'target'=> ['array', 'object'],
                'transformers' => 'array',
                'ignoreMissed' => ['bool']
            ],
            'defaults' => [
                'transformers' => [],
                'validator' => null,
                'mapping' => [],
                'ignoreMissed' => false
            ]
        ];
    }

    /**
     * @throws \Mrself\Property\EmptyPathException
     * @throws \Mrself\Property\InvalidSourceException
     * @throws \Mrself\Property\InvalidTargetException
     * @throws \Mrself\Property\NonValuePathException
     * @throws \Mrself\Property\NonexistentKeyException
     * @throws ValidationException
     * @throws \Mrself\Container\Registry\NotFoundException
     */
    public function sync()
    {
        $this->beforeSync();
        $this->defineMapping();
        $this->parseMapping();
        $this->defineSource();
        $this->runMapping();
        $this->onSync();
        $this->validate();
        $this->onValidate();
        return $this->target;
	}

    protected function beforeSync()
    {
	}

    protected function runMapping()
    {
        if (!$this->mapping) {
            foreach ($this->source as $key => $value) {
                $this->syncField($key, $key);
            }
            return;
        }

        foreach ($this->mapping as $keyTo => $keyFrom) {
            $this->syncField($keyFrom, $keyTo);
        }
	}

    protected function parseMapping()
    {
        if (!array_key_exists('mapping', $this->mapping)) {
            return;
        }

        if (array_key_exists('meta', $this->mapping)) {
            $this->meta = $this->mapping['meta'];
        }

        $this->mapping = $this->mapping['mapping'];
	}

    protected function defineSource()
    {
        $this->originalSource = $this->source;

        if (!array_key_exists('path', $this->meta)) {
            return;
        }
        $this->source = $this->property->get($this->source, $this->meta['path']);
	}

    protected function formatKeys(&$keyTo, &$keyFrom)
    {
        if (is_int($keyTo)) {
            $keyTo = $keyFrom;
        } else {
            $parts = explode(':', $keyTo);
            $keyTo = $parts[0];
        }
	}

    /**
     * @param string $keyFrom
     * @param string $keyTo
     * @throws \Mrself\Property\EmptyPathException
     * @throws \Mrself\Property\InvalidSourceException
     * @throws \Mrself\Property\InvalidTargetException
     * @throws \Mrself\Property\NonValuePathException
     * @throws \Mrself\Property\NonexistentKeyException
     */
    protected function syncField($keyFrom, $keyTo)
    {
        $targetKey = $keyTo;
        $this->formatKeys($keyTo, $keyFrom);
        if (!$this->property->canGet($this->source, $keyFrom)) {
            $defaultMethod = 'getDefault' . $this->inflector->camelize($keyTo);
            if (method_exists($this, $defaultMethod)) {
                $value = $this->$defaultMethod();
            } elseif ($this->ignoreMissed) {
                return;
            } else {
                $value = $this->get($keyFrom, $targetKey);
            }
        } else {
            $value = $this->get($keyFrom, $targetKey);
        }

        $keyTo = $this->formatEachKey($keyTo);
        $this->formatEach($keyTo, $value);
        $formatMethod = 'format' . $this->inflector->camelize($keyTo);
        if (method_exists($this, $formatMethod)) {
            $value = $this->$formatMethod($value);
        }
        $value = $this->transformValue($keyTo, $value);
        $this->property->setByKey($this->target, $keyTo, $value);
    }

    protected function get($keyFrom, $keyTo)
    {
        if (is_array($keyFrom)) {
            $key = $this->defineSourceKey($keyTo);
            $sync = static::make([
                'source' => $this->property->get($this->source, $key),
                'mapping' => $keyFrom
            ]);
            $sync->sync();
            return $sync->getTarget();
        }

        return $this->property->get($this->source, $keyFrom);
    }

    protected function defineSourceKey(string $key)
    {
        $parts = explode(':', $key);
        if (count($parts) === 2) {
            return $parts[1];
        }
        return $parts[0];
    }

    protected function formatEach(string &$key, &$value)
    {
    }

    protected function formatEachKey(string $key)
    {
        return $key;
    }

    protected function transformValue($key, $value)
    {
        if (!array_key_exists($key, $this->transformers)) {
            return $value;
        }

        return $this->dataTransformers
            ->applyTransformers($value, $this->transformers[$key]);
    }

    protected function onSync()
    {
    }

    /**
     * @throws ValidationException
     * @throws \Mrself\Container\Registry\NotFoundException
     */
    protected function validate()
    {
        if (!$this->validate) {
            return;
        }
        $container = ContainerRegistry::get('Mrself\\Sync');
        if (!$container->has(ValidatorInterface::class)) {
            throw new \RuntimeException('Validator service is not specified for the container');
        }
        /** @var ValidatorInterface $validator */
        $validator = $container->get(ValidatorInterface::class);
        $this->errors = $validator->validate($this->target);
        if ($this->validationExceptions) {
            throw new ValidationException($this->errors);
        }
    }

    protected function onValidate()
    {
    }

    protected function defineMapping()
    {
        if (method_exists($this, 'getMapping')) {
            if (empty($this->mapping)) {
                $this->mapping = $this->getMapping();
            } else {
                throw new \RuntimeException('#getMapping() exists but mapping was also given through options. Do not use both ways.');
            }
        } elseif (!$this->mapping) {
            if (is_array($this->source)) {
                $this->mapping = array_keys($this->source);
            } else {
                throw new \RuntimeException('Can not define mapping. Use "mapping" option or define #getMapping');
            }
        }
    }

    protected function onInit()
    {
        $this->dataTransformers = DataTransformers::make();
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function getErrors(): ?ConstraintViolationListInterface
    {
        return $this->errors;
    }
}