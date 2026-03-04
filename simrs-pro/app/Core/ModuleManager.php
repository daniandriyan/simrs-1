<?php
declare(strict_types=1);

namespace App\Core;

class ModuleManager
{
    private array $modules = [];
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function discover(): void
    {
        $modulePath = Application::$ROOT_DIR . '/modules';
        if (!is_dir($modulePath)) return;

        $folders = array_filter(glob($modulePath . '/*'), 'is_dir');

        foreach ($folders as $folder) {
            $moduleName = basename($folder);
            $className = "Modules\{$moduleName}\Module";

            if (class_exists($className)) {
                $moduleInstance = new $className($this->app);
                $this->modules[$moduleName] = $moduleInstance;
                $moduleInstance->getRoutes($this->app->router);
            }
        }
    }

    public function getSidebarMenu(): array
    {
        $menu = [];
        foreach ($this->modules as $module) {
            $menu[] = [
                'name' => $module->getName(),
                'icon' => $module->getIcon(),
                'items' => $module->getMenu()
            ];
        }
        return $menu;
    }
}
