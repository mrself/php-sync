<?php declare(strict_types=1);

namespace Mrself\Sync;

use Mrself\Container\Container;
use Mrself\Container\ServiceProvider;
use Mrself\DataTransformers\DataTransformersProvider;
use Mrself\Property\PropertyProvider;

class SyncProvider extends ServiceProvider
{
    protected function getContainer(): Container
    {
        return Container::make();
    }

    protected function getNamespace(): string
    {
        return 'Mrself\\Sync';
    }

    protected function getDependentProviders(): array
    {
        return [
            PropertyProvider::class,
            DataTransformersProvider::class
        ];
    }

    protected function getFallbackContainers(): array
    {
        return [
            'Mrself\Property',
            'Mrself\DataTransformers',
        ];
    }
}