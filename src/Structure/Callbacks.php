<?php

namespace Apiboard\OpenAPI\Structure;

use Apiboard\OpenAPI\Concerns\AsCountableArray;
use Apiboard\OpenAPI\Concerns\HasReferences;
use Apiboard\OpenAPI\Contents\Reference;
use ArrayAccess;
use Countable;

final class Callbacks implements ArrayAccess, Countable
{
    use AsCountableArray;
    use HasReferences;

    private array $data;

    public function __construct(array $data)
    {
        foreach ($data as $expression=>$value) {
            $data[$expression] = match ($this->isReference($value)) {
                true => new Reference($value['$ref']),
                default => new PathItem($expression, $value),
            };
        }

        $this->data = $data;
    }

    public function offsetGet(mixed $expression): PathItem|Reference|null
    {
        return $this->data[$expression] ?? null;
    }
}
