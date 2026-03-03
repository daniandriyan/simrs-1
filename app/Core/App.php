<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

/**
 * Main Application Class
 * 
 * Core application singleton that bootstraps the framework,
 * manages services, and handles the request lifecycle.
 */
class App
{
    /**
     * @var self|null Application instance (Singleton)
     */
    private static ?self $instance = null;

    /**
     * @var Router Router instance
     */
    private Router $router;

    /**
     * @var Database Database instance
     */
    private Database $database;

    /**
     * @var Container Dependency injection container
     */
    private Container $container;

    /**
     * @var ModuleManager Module manager instance
     */
    private ModuleManager $moduleManager;

    /**
     * @var array Application configuration
     */
    private array $config = [];

    /**
     * @var bool Application initialized flag
     */
    private bool $initialized = false;

    /**
     * @var string Application version
     */
    private const VERSION = '1.0.0';

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    /**
     * Get application instance (Singleton)
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Bootstrap the application
     *
     * @return self
     */
    public function bootstrap(): self
    {
        if ($this->initialized) {
            return $this;
        }

        // Load environment variables
        $this->loadEnvironment();

        // Set error reporting based on environment
        $this->configureErrorHandling();

        // Set default timezone
        $this->configureTimezone();

        // Start session
        $this->startSession();

        // Initialize core services
        $this->initializeServices();

        // Load configuration
        $this->loadConfig();

        // Load modules
        $this->loadModules();

        // Register module services
        $this->moduleManager->registerServices();

        // Register routes
        $this->registerRoutes();

        $this->initialized = true;

        return $this;
    }

    /**
     * Load environment variables from .env file
     *
     * @return void
     */
    private function loadEnvironment(): void
    {
        $envPath = base_path('.env');
        
        if (file_exists($envPath)) {
            $dotenv = Dotenv::createImmutable(dirname($envPath));
            $dotenv->safeLoad();
        }
    }

    /**
     * Configure error handling based on environment
     *
     * @return void
     */
    private function configureErrorHandling(): void
    {
        $isDebug = env('APP_DEBUG', false);

        if ($isDebug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        // Set custom error handler
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
        });

        // Set custom exception handler
        set_exception_handler(function ($e) {
            $this->handleException($e);
        });
    }

    /**
     * Configure application timezone
     *
     * @return void
     */
    private function configureTimezone(): void
    {
        $timezone = env('APP_TIMEZONE', 'UTC');
        date_default_timezone_set($timezone);
    }

    /**
     * Start secure session
     *
     * @return void
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionName = env('SESSION_NAME', 'SIMRSSESSID');
            $sessionLifetime = (int) env('SESSION_LIFETIME', 120);
            $sessionSecure = env('SESSION_SECURE', false);
            $sessionHttpOnly = env('SESSION_HTTP_ONLY', true);

            session_name($sessionName);
            ini_set('session.gc_maxlifetime', (string) ($sessionLifetime * 60));
            ini_set('session.cookie_lifetime', (string) ($sessionLifetime * 60));
            ini_set('session.cookie_httponly', $sessionHttpOnly ? '1' : '0');
            ini_set('session.cookie_secure', $sessionSecure ? '1' : '0');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            session_start();

            // Regenerate session ID periodically for security
            if (!isset($_SESSION['_created'])) {
                $_SESSION['_created'] = time();
            } elseif (time() - $_SESSION['_created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['_created'] = time();
            }
        }
    }

    /**
     * Initialize core services
     *
     * @return void
     */
    private function initializeServices(): void
    {
        $this->router = new Router();
        $this->database = new Database();
        
        // Register core services in container
        $this->container->singleton('router', $this->router);
        $this->container->singleton('database', $this->database);
        $this->container->singleton(Container::class, $this->container);
        
        // Initialize Module Manager
        $this->moduleManager = new ModuleManager(modules_path(), $this->container);
        $this->container->singleton('module_manager', $this->moduleManager);
    }

    /**
     * Load application configuration
     *
     * @return void
     */
    private function loadConfig(): void
    {
        $configPath = app_path('Config');

        // Load main config
        $mainConfig = $this->loadConfigFile($configPath . '/config.php');
        $this->config = $mainConfig;

        // Load database config
        $dbConfig = $this->loadConfigFile($configPath . '/database.php');
        $this->config['database'] = $dbConfig;

        // Load routes config
        $routesConfig = $this->loadConfigFile($configPath . '/routes.php');
        $this->config['routes'] = $routesConfig;
    }

    /**
     * Load configuration file
     *
     * @param string $path Config file path
     * @return array
     */
    private function loadConfigFile(string $path): array
    {
        if (file_exists($path)) {
            return require $path;
        }
        return [];
    }

    /**
     * Load all modules using ModuleManager
     *
     * @return void
     */
    private function loadModules(): void
    {
        // ModuleManager handles discovery and loading
        $this->moduleManager->discover()->load();
    }

    /**
     * Register application routes
     *
     * @return void
     */
    private function registerRoutes(): void
    {
        // Load routes from config
        $routesConfig = $this->config['routes'] ?? [];

        if (isset($routesConfig['register']) && is_callable($routesConfig['register'])) {
            ($routesConfig['register'])($this->router);
        }

        // Let modules register their routes via ModuleManager
        $this->moduleManager->registerRoutes($this->router);
    }

    /**
     * Run the application
     *
     * @return void
     */
    public function run(): void
    {
        if (!$this->initialized) {
            $this->bootstrap();
        }

        try {
            // Boot all modules
            $this->moduleManager->boot();
            
            // Init all modules
            $this->moduleManager->init();

            // Dispatch router
            $response = $this->router->dispatch();

            // Handle response
            if (is_string($response)) {
                echo $response;
            } elseif (is_array($response)) {
                header('Content-Type: application/json');
                echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } elseif ($response !== null) {
                echo (string) $response;
            }
        } catch (\Throwable $e) {
            $this->handleException($e);
        }

        // Shutdown modules
        $this->moduleManager->shutdown();
    }

    /**
     * Handle uncaught exceptions
     *
     * @param \Throwable $e Exception
     * @return void
     */
    private function handleException(\Throwable $e): void
    {
        // Log error
        log_message('error', sprintf(
            'Uncaught %s: %s in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ), 'exceptions');

        // Don't display detailed errors in production
        $isDebug = env('APP_DEBUG', false);

        if ($isDebug) {
            // Show detailed error in debug mode
            echo $this->renderDebugException($e);
        } else {
            // Show generic error page
            http_response_code(500);
            
            $errorView = views_path('errors/500.html');
            if (file_exists($errorView)) {
                echo file_get_contents($errorView);
            } else {
                echo '<h1>500 - Internal Server Error</h1>';
                echo '<p>An unexpected error occurred. Please try again later.</p>';
            }
        }
    }

    /**
     * Render detailed exception for debugging
     *
     * @param \Throwable $e Exception
     * @return string
     */
    private function renderDebugException(\Throwable $e): string
    {
        $html = '<style>
            body { font-family: monospace; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; }
            .error-title { color: #dc3545; font-size: 18px; font-weight: bold; margin-bottom: 10px; }
            .error-message { background: #f8d7da; padding: 10px; margin: 10px 0; }
            .trace { background: #f8f9fa; padding: 10px; overflow-x: auto; }
            .trace-line { border-bottom: 1px solid #eee; padding: 5px 0; }
        </style>';
        
        $html .= '<div class="error-box">';
        $html .= '<div class="error-title">' . htmlspecialchars(get_class($e)) . '</div>';
        $html .= '<div class="error-message">' . htmlspecialchars($e->getMessage()) . '</div>';
        $html .= '<div><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</div>';
        
        $html .= '<div class="trace"><strong>Stack Trace:</strong><br>';
        foreach ($e->getTrace() as $i => $trace) {
            $file = $trace['file'] ?? '[internal function]';
            $line = $trace['line'] ?? '';
            $func = $trace['function'] ?? '';
            $class = $trace['class'] ?? '';
            $type = $trace['type'] ?? '';
            
            $html .= '<div class="trace-line">';
            $html .= '#' . $i . ' ';
            $html .= htmlspecialchars($file) . ':' . $line . ' ';
            $html .= htmlspecialchars($class . $type . $func . '()');
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get router instance
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get database instance
     *
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Get container instance
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get module manager instance
     *
     * @return ModuleManager
     */
    public function getModuleManager(): ModuleManager
    {
        return $this->moduleManager;
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key (dot notation supported)
     * @param mixed $default Default value
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Get all configuration
     *
     * @return array
     */
    public function getAllConfig(): array
    {
        return $this->config;
    }

    /**
     * Get application version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Check if application is initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Register a service in the container
     *
     * @param string $name Service name
     * @param mixed $service Service instance
     * @return self
     */
    public function register(string $name, mixed $service): self
    {
        $this->config['services'][$name] = $service;
        return $this;
    }

    /**
     * Get a service from the container
     *
     * @param string $name Service name
     * @param mixed $default Default value
     * @return mixed
     */
    public function service(string $name, mixed $default = null): mixed
    {
        return $this->config['services'][$name] ?? $default;
    }
}
