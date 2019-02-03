<?php declare(strict_types=1);

namespace Mrself\Sync;

use ICanBoogie\Inflector;
use Mrself\Options\Annotation\Option;
use Mrself\Options\WithOptionsTrait;
use Mrself\Property\Property;

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
     * @Option()
     * @var array|string[]
     */
	protected $mapping;

    /**
     * @var Inflector
     */
	protected $inflector;

    /**
     * @var Property
     */
	protected $property;

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
                'target'=> ['array', 'object']
            ]
        ];
    }

    /**
     * @throws \Mrself\Property\EmptyPathException
     * @throws \Mrself\Property\InvalidSourceException
     * @throws \Mrself\Property\InvalidTargetException
     * @throws \Mrself\Property\NonValuePathException
     * @throws \Mrself\Property\NonexistentKeyException
     */
    public function sync()
    {
        foreach ($this->mapping as $keyTo => $keyFrom) {
            $this->formatKeys($keyTo, $keyFrom);
            $this->syncField($keyFrom, $keyTo);
		}
        $this->onSync();
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
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }
}