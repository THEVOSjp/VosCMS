<?php

declare(strict_types=1);

namespace RzxLib\Core;

use RzxLib\Core\Container\Container;

/**
 * RezlyX Application Core
 *
 * @package RzxLib\Core
 */
class Application extends Container
{
    /**
     * The RezlyX version.
     */
    public const VERSION = '1.0.0';

    /**
     * The application instance.
     */
    protected static ?Application $instance = null;

    /**
     * The base path for the application.
     */
    protected string $basePath;

    /**
     * Indicates if the application has been bootstrapped.
     */
    protected bool $booted = false;

    /**
     * The loaded configuration.
     */
    protected array $config = [];

    /**
     * The registered service providers.
     */
    protected array $serviceProviders = [];

    /**
     * Create a new Application instance.
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();

        self::$instance = $this;
    }

    /**
     * Get the application instance.
     */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application has not been initialized.');
        }

        return self::$instance;
    }

    /**
     * Register the basic bindings into the container.
     */
    protected function registerBaseBindings(): void
    {
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
    }

    /**
     * Register the core class aliases.
     */
    protected function registerCoreContainerAliases(): void
    {
        $aliases = [
            'app' => [self::class, Container::class],
            'config' => [\RzxLib\Core\Config\Repository::class],
            'db' => [\RzxLib\Core\Database\DatabaseManager::class],
            'cache' => [\RzxLib\Core\Cache\CacheManager::class],
            'session' => [\RzxLib\Core\Session\SessionManager::class],
            'auth' => [\RzxLib\Core\Auth\AuthManager::class],
            'log' => [\Psr\Log\LoggerInterface::class],
            'router' => [\RzxLib\Core\Http\Router::class],
            'request' => [\RzxLib\Core\Http\Request::class],
            'response' => [\RzxLib\Core\Http\Response::class],
            'view' => [\RzxLib\Core\View\ViewFactory::class],
            'translator' => [\RzxLib\Modules\I18n\Translator::class],
            'validator' => [\RzxLib\Core\Validation\Factory::class],
        ];

        foreach ($aliases as $key => $aliasGroup) {
            foreach ($aliasGroup as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Bootstrap the application.
     */
    public function bootstrap(): void
    {
        if ($this->booted) {
            return;
        }

        // Load environment variables
        $this->loadEnvironment();

        // Load configuration
        $this->loadConfiguration();

        // Set error handling
        $this->setErrorHandling();

        // Register service providers
        $this->registerProviders();

        // Boot service providers
        $this->bootProviders();

        $this->booted = true;
    }

    /**
     * Load environment variables.
     */
    protected function loadEnvironment(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable($this->basePath);

        if (file_exists($this->basePath . '/.env')) {
            $dotenv->load();
        }
    }

    /**
     * Load configuration files.
     */
    protected function loadConfiguration(): void
    {
        $configPath = $this->basePath . '/config';

        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }

        // Set timezone
        date_default_timezone_set($this->config('app.timezone', 'UTC'));

        // Set locale
        setlocale(LC_ALL, $this->config('app.locale', 'ko_KR.UTF-8'));
    }

    /**
     * Set error handling based on configuration.
     */
    protected function setErrorHandling(): void
    {
        $debug = $this->config('app.debug', false);

        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', '0');
        }

        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
    }

    /**
     * Register all configured providers.
     */
    protected function registerProviders(): void
    {
        $providers = $this->config('app.providers', []);

        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Register a service provider.
     */
    public function register(string $provider): void
    {
        if (isset($this->serviceProviders[$provider])) {
            return;
        }

        $instance = new $provider($this);
        $instance->register();

        $this->serviceProviders[$provider] = $instance;
    }

    /**
     * Boot all service providers.
     */
    protected function bootProviders(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    /**
     * Get a configuration value.
     */
    public function config(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Get the base path of the application.
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the version number of the application.
     */
    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * Determine if the application is in debug mode.
     */
    public function isDebug(): bool
    {
        return (bool) $this->config('app.debug', false);
    }

    /**
     * Get the current application environment.
     */
    public function environment(): string
    {
        return $this->config('app.env', 'production');
    }

    /**
     * Determine if we are running in the console.
     */
    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * Handle an uncaught exception.
     */
    public function handleException(\Throwable $e): void
    {
        $handler = $this->make(\RzxLib\Core\Exceptions\Handler::class);
        $handler->report($e);
        $handler->render($e);
    }

    /**
     * Convert a PHP error to an ErrorException.
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }

        return false;
    }

    /**
     * Run the application.
     */
    public function run(): void
    {
        $this->bootstrap();

        $request = $this->make('request');
        $router = $this->make('router');

        $response = $router->dispatch($request);
        $response->send();
    }
}
