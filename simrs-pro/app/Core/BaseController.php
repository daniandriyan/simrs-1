<?php
declare(strict_types=1);

namespace App\Core;

abstract class BaseController
{
    protected \PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    protected function render(string $module, string $view, array $data = []): string
    {
        extract($data);
        $viewPath = Application::$ROOT_DIR . "/modules/{$module}/views/{$view}.php";
        
        if (!file_exists($viewPath)) {
            $viewPath = Application::$ROOT_DIR . "/app/Views/{$view}.php";
        }

        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        ob_start();
        include Application::$ROOT_DIR . "/app/Views/layouts/admin.php";
        return ob_get_clean();
    }
}
