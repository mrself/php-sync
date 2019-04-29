<?php declare(strict_types=1);

namespace Mrself\Sync;

trait SyncTrait
{
    /**
     * @param array|null $keys
     * @return mixed
     * @throws ValidationException
     * @throws \Mrself\Container\Registry\NotFoundException
     * @throws \Mrself\Property\EmptyPathException
     * @throws \Mrself\Property\InvalidSourceException
     * @throws \Mrself\Property\InvalidTargetException
     * @throws \Mrself\Property\NonValuePathException
     * @throws \Mrself\Property\NonexistentKeyException
     */
    public function toArray(array $keys = null)
    {
        $sync = Sync::make([
            'source' => $this,
            'target' => [],
            'mapping' => $this->getExportKeys($keys)
        ]);
        $sync->sync();
        return $sync->getTarget();
    }

    protected function getExportKeys($keys): array
    {
        if ($keys) {
            return $keys;
        }

        $properties = get_object_vars($this);
        $keys = array_keys($properties);
        return array_diff($keys, $this->getIgnoredExportKeys());
    }

    protected function getIgnoredExportKeys(): array
    {
        return [];
    }
}