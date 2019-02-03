<?php declare(strict_types=1);

namespace Mrself\Sync;

use Mrself\Container\Container;
use Mrself\Container\Registry\ContainerRegistry;

class SyncProvider
{
    public function boot()
    {
        $container = Container::make();
        ContainerRegistry::add('Mrself\\Sync', $container);
    }
}