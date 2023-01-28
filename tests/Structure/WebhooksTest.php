<?php

use Apiboard\OpenAPI\References\Reference;
use Apiboard\OpenAPI\Structure\PathItem;
use Apiboard\OpenAPI\Structure\Webhooks;

test('it can retrieve webhooks by their uri', function () {
    $webhooks = new Webhooks([
        '/my-path' => [],
    ]);

    $result = $webhooks['/my-path'];

    expect($result)->toBeInstanceOf(PathItem::class);
});

test('it can retrieve referenced webhooks by their uri', function () {
    $webhooks = new Webhooks([
        '/my-path' => [
            '$ref' => '#/some/ref',
        ],
    ]);

    $result = $webhooks['/my-path'];

    expect($result)->toBeInstanceOf(Reference::class);
});

test('it returns null for unknown webhooks', function () {
    $webhooks = new Webhooks([]);

    $result = $webhooks['/my-path'];

    expect($result)->toBeNull();
});

test('webhooks can be counted', function () {
    $webhooks = new Webhooks([
        '/my-path' => [],
        '/my-other-path' => [],
    ]);

    $result = count($webhooks);

    expect($result)->toBe(2);
});
