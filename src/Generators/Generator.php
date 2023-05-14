<?php

declare(strict_types=1);

namespace Nucleus\Repository\Generators;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Nucleus\Repository\Exceptions\FileAlreadyExistsException;

use function array_key_exists;
use function dirname;

/**
 * Class Generator
 * @package Nucleus\Repository\Generators
 */
abstract class Generator
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $filesystem;
    /**
     * The array of options.
     *
     * @var array
     */
    protected array $options;
    /**
     * The shortname of stub.
     *
     * @var string
     */
    protected string $stub;


    /**
     * Create new instance of this class.
     *
     * @param  array  $options
     */
    public function __construct(array $options = [])
    {
        $this->filesystem = new Filesystem();
        $this->options = $options;
    }


    /**
     * Get the filesystem instance.
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Set the filesystem instance.
     *
     * @param  Filesystem  $filesystem
     *
     * @return $this
     */
    public function setFilesystem(Filesystem $filesystem): Generator
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get class-specific output paths.
     *
     * @param $class
     *
     * @return string
     */
    public function getConfigGeneratorClassPath($class, bool $directoryPath = false): string
    {
        switch ($class) {
            case ('models' === $class):
                $path = config('repository.generator.paths.models', 'Entities');
                break;
            case ('repositories' === $class):
                $path = config('repository.generator.paths.repositories', 'Repositories');
                break;
            case ('interfaces' === $class):
                $path = config('repository.generator.paths.interfaces', 'Repositories');
                break;
            case ('presenters' === $class):
                $path = config('repository.generator.paths.presenters', 'Presenters');
                break;
            case ('transformers' === $class):
                $path = config('repository.generator.paths.transformers', 'Transformers');
                break;
            case ('validators' === $class):
                $path = config('repository.generator.paths.validators', 'Validators');
                break;
            case ('controllers' === $class):
                $path = config('repository.generator.paths.controllers', 'Http\Controllers');
                break;
            case ('provider' === $class):
                $path = config('repository.generator.paths.provider', 'RepositoryModelProvider');
                break;
            case ('criteria' === $class):
                $path = config('repository.generator.paths.criteria', 'Criteria');
                break;
            default:
                $path = '';
        }

        if ($directoryPath) {
            $path = str_replace('\\', '/', $path);
        } else {
            $path = str_replace('/', '\\', $path);
        }


        return $path;
    }

    abstract public function getPathConfigNode();

    /**
     * Run the generator.
     *
     * @return int
     * @throws FileAlreadyExistsException
     */
    public function run(): int
    {
        $this->setUp();
        if ($this->filesystem->exists($path = $this->getPath()) && !$this->force) {
            throw new FileAlreadyExistsException($path);
        }
        if (!$this->filesystem->isDirectory($dir = dirname($path))) {
            $this->filesystem->makeDirectory($dir, 0777, true, true);
        }

        return $this->filesystem->put($path, $this->getStub());
    }

    /**
     * Setup some hook.
     *
     * @return void
     */
    public function setUp(): void
    {
        //
    }

    /**
     * Get destination path for generated file.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->getBasePath().'/'.$this->getName().'.php';
    }

    /**
     * Get base path of destination file.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return base_path();
    }

    /**
     * Get name input.
     *
     * @return string
     */
    public function getName(): string
    {
        $name = $this->name;
        if (Str::contains($this->name, '\\')) {
            $name = str_replace('\\', '/', $this->name);
        }
        if (Str::contains($this->name, '/')) {
            $name = str_replace('/', '/', $this->name);
        }

        return Str::studly(str_replace(' ', '/', ucwords(str_replace('/', ' ', $name))));
    }

    /**
     * Get stub template for generated file.
     *
     * @return string
     */
    public function getStub()
    {
        $path = config('repository.generator.stubsOverridePath', __DIR__);

        if (!file_exists($path.'/Stubs/'.$this->stub.'.stub')) {
            $path = __DIR__;
        }

        return (new Stub($path.'/Stubs/'.$this->stub.'.stub', $this->getReplacements()))->render();
    }

    /**
     * Get template replacements.
     *
     * @return array
     */
    public function getReplacements(): array
    {
        return [
            'class' => $this->getClass(),
            'namespace' => $this->getNamespace(),
            'root_namespace' => $this->getRootNamespace()
        ];
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass(): string
    {
        return Str::studly(class_basename($this->getName()));
    }

    /**
     * Get class namespace.
     *
     * @return string
     */
    public function getNamespace(): ?string
    {
        $segments = $this->getSegments();
        array_pop($segments);
        $rootNamespace = $this->getRootNamespace();
        if (false == $rootNamespace) {
            return null;
        }

        return 'namespace '.rtrim($rootNamespace.'\\'.implode('\\', $segments), '\\').';';
    }

    /**
     * Get paths of namespace.
     *
     * @return array
     */
    public function getSegments(): array
    {
        return explode('/', $this->getName());
    }

    /**
     * Get root namespace.
     *
     * @return string
     */
    public function getRootNamespace(): string
    {
        return config('repository.generator.rootNamespace', $this->getAppNamespace());
    }

    /**
     * Get application namespace
     *
     * @return string
     */
    public function getAppNamespace(): string
    {
        return Container::getInstance()->getNamespace();
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Handle call to __get method.
     *
     * @param  string  $key
     *
     * @return string|mixed
     */
    public function __get(string $key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        return $this->option($key);
    }

    /**
     * Helper method for "getOption".
     *
     * @param  string  $key
     * @param  string|null  $default
     *
     * @return string
     */
    public function option($key, $default = null): ?string
    {
        return $this->getOption($key, $default);
    }

    /**
     * Get value from options by given key.
     *
     * @param  string  $key
     * @param  string|null  $default
     *
     * @return string
     */
    public function getOption($key, $default = null): ?string
    {
        if (!$this->hasOption($key)) {
            return $default;
        }

        return $this->options[$key] ?: $default;
    }

    /**
     * Determinte whether the given key exist in options array.
     *
     * @param  string  $key
     *
     * @return bool
     */
    public function hasOption($key): bool
    {
        return array_key_exists($key, $this->options);
    }
}
