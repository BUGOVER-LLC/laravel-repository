<?php

declare(strict_types=1);

namespace Nucleus\Repository\Generators;

/**
 * Class RepositoryEloquentGenerator
 * @package Nucleus\Repository\Generators
 */
class RepositoryEloquentGenerator extends Generator
{

    /**
     * Get stub name.
     *
     * @var string
     */
    protected string $stub = 'repository/eloquent';

    /**
     * Get root namespace.
     *
     * @return string
     */
    public function getRootNamespace(): string
    {
        return parent::getRootNamespace() . $this->getConfigGeneratorClassPath($this->getPathConfigNode());
    }

    /**
     * Get generator path config node.
     *
     * @return string
     */
    public function getPathConfigNode(): string
    {
        return 'repositories';
    }

    /**
     * Get destination path for generated file.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->getBasePath() . '/' . $this->getConfigGeneratorClassPath($this->getPathConfigNode(), true) . '/' . $this->getName() . 'repository.php';
    }

    /**
     * Get base path of destination file.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return config('repository.generator.basePath', app()->path());
    }

    /**
     * Get array replacements.
     *
     * @return array
     */
    public function getReplacements(): array
    {
        $repository = parent::getRootNamespace() . $this->getConfigGeneratorClassPath('interfaces') . '\\' . $this->name . 'Repository;';
        $repository = str_replace([
            "\\",
            '/',
        ], '\\', $repository);

        return array_merge(parent::getReplacements(), [
            'repository' => $repository,
            'model' => $this->options['model'] ?? '',
        ]);
    }
}
