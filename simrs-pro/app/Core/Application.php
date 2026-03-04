<?php
declare(strict_types=1);

namespace App\Core;

class Application
{
    public static string $ROOT_DIR;
    public Router $router;
    public Request $request;
    public Response $response;
    public ModuleManager $moduleManager;

    public function __construct(string $rootDir)
    {
        self::$ROOT_DIR = $rootDir;
        Config::load(self::$ROOT_DIR . '/.env');
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        $this->moduleManager = new ModuleManager($this);
        $this->moduleManager->discover();
    }

    public function run(): void
    {
        echo $this->router->resolve();
    }
}
