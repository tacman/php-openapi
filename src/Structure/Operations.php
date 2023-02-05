<?php

namespace Apiboard\OpenAPI\Structure;

use Apiboard\OpenAPI\Concerns\CanBeUsedAsArray;
use ArrayAccess;
use Countable;
use Iterator;

final class Operations extends Structure implements ArrayAccess, Countable, Iterator
{
    use CanBeUsedAsArray;

    public function __construct(array $data)
    {
        foreach ($data as $method => $value) {
            $data[$method] = new Operation($method, $value);
        }

        $this->data = $data;
    }

    public function offsetGet(mixed $method): ?Operation
    {
        return $this->data[$method] ?? null;
    }

    public function current(): Operation
    {
        return $this->iterator->current();
    }
}
