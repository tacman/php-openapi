<?php

namespace Apiboard\OpenAPI\References;

use Apiboard\OpenAPI\Contents\Json;
use Apiboard\OpenAPI\Contents\Retriever;
use Apiboard\OpenAPI\Contents\Yaml;

final class Resolver
{
    private ?Retriever $retriever;

    private array $retrievedReferences = [];

    private array $replacedKeys = [];

    public function __construct(Retriever $retriever = null)
    {
        $this->retriever = $retriever;
    }

    public function resolve(Json|Yaml $contents): Json|Yaml
    {
        if ($this->retriever === null) {
            return $contents;
        }

        $this->retrieveReferences($contents->references(), $this->retriever->basePath());

        $resolved = $this->replaceReferences([], $contents->toArray());

        return match ($contents::class) {
            Json::class => new Json(json_encode($resolved, JSON_UNESCAPED_SLASHES)),
            Yaml::class => new Yaml(\Symfony\Component\Yaml\Yaml::dump($resolved)),
        };
    }

    /**
     * @param  array<array-key,Reference>  $references
     */
    private function retrieveReferences(array $references, string $basePath): void
    {
        foreach ($references as $reference) {
            if ($reference->isInternal()) {
                continue;
            }

            $absoluteReference = $reference->withBase($basePath);

            $previouslyRetrieved = array_key_exists($absoluteReference->path(), $this->retrievedReferences);

            if ($previouslyRetrieved === false) {
                $contents = $this->retriever->retrieve($absoluteReference->path());

                $resolvedContents = $contents->toArray();

                $this->retrievedReferences[$absoluteReference->path()] = $resolvedContents;

                foreach ($reference->properties() as $property) {
                    $resolvedContents = $resolvedContents[$property];
                }

                $this->retrievedReferences[$reference->value()] = $resolvedContents;
                $this->retrievedReferences[$reference->value()] = $resolvedContents;

                $this->retrieveReferences($contents->references(), $absoluteReference->basePath());
            }

            if ($previouslyRetrieved === true) {
                $resolvedContents = $this->retrievedReferences[$absoluteReference->path()];

                foreach ($reference->properties() as $property) {
                    $resolvedContents = $resolvedContents[$property];
                }

                $this->retrievedReferences[$reference->value()] = $resolvedContents;
            }
        }
    }

    private function replaceReferences(array|string $resolved, array|string $contents): array|string
    {
        if (is_string($resolved)) {
            return $resolved;
        }

        foreach ($contents as $key => $value) {
            if ($key !== '$ref') {
                $this->replacedKeys[] = $key;

                $resolved[$key] = $value;

                if (is_array($value)) {
                    $resolved[$key] = $this->replaceReferences([], $value);
                }
            }

            if ($key === '$ref') {
                $resolvedValue = $this->retrievedReferences[$value] ?? null;

                // This is an internal reference, don't replace this.
                if ($resolvedValue === null) {
                    $resolved[$key] = $value;

                    continue;
                }

                // Prevent entering an infinite reference replacement loop.
                // Use a JSON pointer reference instead here when needed.
                $resolvedKey = is_array($resolvedValue) ? array_key_first($resolvedValue) : null;
                if ($resolvedKey && ($resolvedValue[$resolvedKey] === $contents)) {
                    $resolved = [
                        $resolvedKey => [
                            '$ref' => '#/'.implode('/', $this->replacedKeys).'/'.array_key_first($resolvedValue),
                        ],
                    ];

                    continue;
                }

                // Replace any references that are inside the resolved value
                $resolved = $this->replaceReferences($resolvedValue, $resolvedValue);
            }
        }

        $this->replacedKeys = [];

        return $resolved;
    }
}
