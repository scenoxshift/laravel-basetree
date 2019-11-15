<?php

namespace BaseTree\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

class ApplicationNamespace extends Command
{
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'basetree:namespace';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Set the application namespace';

    /**
     * The Composer class instance.
     * @var Composer
     */
    protected $composer;

    /**
     * The filesystem instance.
     * @var Filesystem
     */
    protected $files;

    /**
     * Current root application namespace.
     * @var string
     */
    protected $currentRoot;

    /**
     * Create a new key generator command.
     * @param Composer   $composer
     * @param Filesystem $files
     * @return void
     */
    public function __construct(Composer $composer, Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->currentRoot = trim($this->laravel->getNamespace(), '\\');
        $this->setAppDirectoryNamespace();
        $this->setBootstrapNamespaces();
        $this->setConfigNamespaces();
        $this->setComposerNamespace();
        $this->setDatabaseFactoryNamespaces();
        $this->info('Application namespace set!');
        $this->composer->dumpAutoloads();
        $this->call('optimize:clear');
    }

    /**
     * Set the namespace on the files in the app directory.
     * @return void
     */
    protected function setAppDirectoryNamespace()
    {
        $files = Finder::create()
                       ->in($this->laravel['path'])
                       ->contains($this->currentRoot)
                       ->name('*.php');
        foreach ($files as $file) {
            $this->replaceNamespace($file->getRealPath());
        }
    }

    /**
     * Replace the App namespace at the given path.
     * @param string $path
     * @return void
     */
    protected function replaceNamespace($path)
    {
        $search = [
            'namespace ' . $this->currentRoot . ';',
            $this->currentRoot . '\\',
        ];
        $replace = [
            'namespace ' . $this->argument('name') . ';',
            $this->argument('name') . '\\',
        ];
        $this->replaceIn($path, $search, $replace);
    }

    /**
     * Set the bootstrap namespaces.
     * @return void
     */
    protected function setBootstrapNamespaces()
    {
        $search = [
            $this->currentRoot . '\\Http',
            $this->currentRoot . '\\Console',
            $this->currentRoot . '\\Exceptions',
        ];
        $replace = [
            $this->argument('name') . '\\Http',
            $this->argument('name') . '\\Console',
            $this->argument('name') . '\\Exceptions',
        ];
        $this->replaceIn($this->getBootstrapPath(), $search, $replace);
    }

    /**
     * Set the namespace in the appropriate configuration files.
     * @return void
     */
    protected function setConfigNamespaces(): void
    {
        $this->setAppConfigNamespaces();
        $this->setAuthConfigNamespace();
        $this->setServicesConfigNamespace();
    }

    /**
     * Set the application provider namespaces.
     * @return void
     */
    protected function setAppConfigNamespaces(): void
    {
        $search = [
            $this->currentRoot . '\\Providers',
            $this->currentRoot . '\\Http\\Controllers\\',
        ];
        $replace = [
            $this->argument('name') . '\\Providers',
            $this->argument('name') . '\\Http\\Controllers\\',
        ];
        $this->replaceIn($this->getConfigPath('app'), $search, $replace);
    }

    /**
     * Set the authentication User namespace.
     * @return void
     */
    protected function setAuthConfigNamespace(): void
    {
        $this->replaceIn(
            $this->getConfigPath('auth'),
            $this->currentRoot . '\\User',
            $this->argument('name') . '\\User'
        );
    }

    /**
     * Set the services User namespace.
     * @return void
     */
    protected function setServicesConfigNamespace(): void
    {
        $this->replaceIn(
            $this->getConfigPath('services'),
            $this->currentRoot . '\\User',
            $this->argument('name') . '\\User'
        );
    }

    /**
     * Set the PSR-4 namespace in the Composer file.
     * @return void
     */
    protected function setComposerNamespace(): void
    {
        $this->replaceIn(
            $this->getComposerPath(),
            str_replace('\\', '\\\\', $this->currentRoot) . '\\\\',
            str_replace('\\', '\\\\', $this->argument('name')) . '\\\\'
        );
    }

    /**
     * Set the namespace in database factory files.
     * @return void
     */
    protected function setDatabaseFactoryNamespaces(): void
    {
        $files = Finder::create()
                       ->in(database_path('factories'))
                       ->contains($this->currentRoot)
                       ->name('*.php');
        foreach ($files as $file) {
            $this->replaceIn(
                $file->getRealPath(),
                $this->currentRoot, $this->argument('name')
            );
        }
    }

    /**
     * Replace the given string in the given file.
     * @param string       $path
     * @param string|array $search
     * @param string|array $replace
     * @return void
     * @throws FileNotFoundException
     */
    protected function replaceIn($path, $search, $replace): void
    {
        if ($this->files->exists($path)) {
            $this->files->put($path, str_replace($search, $replace, $this->files->get($path)));
        }
    }

    /**
     * Get the path to the bootstrap/app.php file.
     * @return string
     */
    protected function getBootstrapPath(): string
    {
        return $this->laravel->bootstrapPath() . '/app.php';
    }

    /**
     * Get the path to the Composer.json file.
     * @return string
     */
    protected function getComposerPath(): string
    {
        return base_path('composer.json');
    }

    /**
     * Get the path to the given configuration file.
     * @param string $name
     * @return string
     */
    protected function getConfigPath($name): string
    {
        return $this->laravel['path.config'] . '/' . $name . '.php';
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The desired namespace'],
        ];
    }
}