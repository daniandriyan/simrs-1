<?php
declare(strict_types=1);

namespace App\Core;

abstract class BaseModule
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    abstract public function getName(): string;
    abstract public function getIcon(): string;
    abstract public function getMenu(): array;
    abstract public function getRoutes(Router $router): void;
}
