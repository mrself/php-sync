<?php declare(strict_types=1);

namespace Mrself\Sync;

use ICanBoogie\Inflector;
use Mrself\Container\Registry\ContainerRegistry;
use Mrself\Options\Annotation\Option;
use Mrself\Options\WithOptionsTrait;
use Mrself\Property\Property;
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
	protected $target;

    /**
     * @var array|string[]
     */
	protected $mapping;

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
            ],
            'defaults' => [
                'validator' => null,
                'mapping' => []
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
        $this->defineMapping();
        foreach ($this->mapping as $keyTo => $keyFrom) {
            $this->formatKeys($keyTo, $keyFrom);
            $this->syncField($keyFrom, $keyTo);
		}
        $this->onSync();
        $this->validate();
        $this->onValidate();
	}

    protected function formatKeys(&$keyTo, string &$keyFrom)
    {
        if (is_int($keyTo)) {
            $keyTo = $keyFrom;
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
    protected function syncField(string $keyFrom, string $keyTo)
    {
        $value = $this->property->get($this->source, $keyFrom);
        $keyTo = $this->formatEachKey($keyTo);
        $this->formatEach($keyTo, $value);
        $formatMethod = 'format' . $this->inflector->camelize($keyTo);
        if (method_exists($this->target, $formatMethod)) {
            $value = $this->target->$formatMethod($value);
        }
        $this->property->setByKey($this->target, $keyTo, $value);
    }

    protected function formatEach(string &$key, &$value)
    {
    }

    protected function formatEachKey(string $key)
    {
        return $key;
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
        }
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