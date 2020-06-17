```php

// From array to array

$sync = Sync::make([
    'source' => ['a' => 1],
    'target' => [],
    'mapping' => ['a']
]);
$sync->getTarget()['a'] === 1;

// =======

// From array to object

$targetObject = (object) [];
$sync = Sync::make([
    'source' => ['a' => 1],
    'target' => $targetObject,
    'mapping' => ['a']
]);
$targetObject->a === 1;

// =======

// Extending from Sync

// Formatting values

$target = [];
$source = ['a' => 1];
$mapping = ['a'];
$sync = new class extends Sync {
    public function formatA($value)
    {
        return $value + 1;
    }
};
$sync->init(compact('target', 'source', 'mapping'));
$sync->sync();
