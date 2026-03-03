<?php

declare(strict_types=1);

/**
 * Route Configuration
 * 
 * Define application routes here.
 * Routes are registered using the Router instance.
 */

use App\Core\Router;

return [
    /*
    |--------------------------------------------------------------------------
    | Route Registration Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called when the application bootstraps.
    | Use the Router instance to register your routes.
    |
    */
    'register' => function (Router $router) {

        // ============================================
        // PUBLIC ROUTES
        // ============================================
        
        // Home page
        $router->get('/', function () {
            if (is_authenticated()) {
                redirect(base_url('admin/dashboard'));
            }
            redirect(base_url('login'));
        });

        // Login routes with Guest middleware
        $router->get('login', function () {
            // Check if already logged in
            if (is_authenticated()) {
                redirect(base_url('admin/dashboard'));
            }
            
            $controller = new \Modules\Users\AuthController();
            echo $controller->showLogin();
        });

        $router->post('login', function () {
            $controller = new \Modules\Users\AuthController();
            echo $controller->login();
        });

        // Logout route
        $router->get('logout', function () {
            $controller = new \Modules\Users\AuthController();
            $controller->logout();
        });

        // ============================================
        // ADMIN ROUTES (Protected with Auth Middleware)
        // ============================================
        
        $router->group('admin', function (Router $router) {
            
            // Dashboard (accessible by all authenticated users)
            $router->get('', function () {
                // Auth check is done in controller
                $controller = new \Modules\Dashboard\Admin();
                echo $controller->index();
            });

            $router->get('dashboard', function () {
                $controller = new \Modules\Dashboard\Admin();
                echo $controller->index();
            });

            // Users module (Admin only)
            $router->group('users', function (Router $router) {
                $router->get('', function () {
                    $controller = new \Modules\Users\Admin();
                    echo $controller->manage();
                });
                $router->get('manage', function () {
                    $controller = new \Modules\Users\Admin();
                    echo $controller->manage();
                });
                $router->get('form', function () {
                    $controller = new \Modules\Users\Admin();
                    echo $controller->form();
                });
                $router->get('form/:int', function ($id) {
                    $controller = new \Modules\Users\Admin();
                    echo $controller->form($id);
                });
                $router->post('save', function () {
                    $controller = new \Modules\Users\Admin();
                    echo $controller->save();
                });
                $router->post('delete', function () {
                    $controller = new \Modules\Users\Admin();
                    echo $controller->delete();
                });
                $router->post('toggleStatus', function () {
                    $controller = new \Modules\Users\Admin();
                    echo $controller->toggleStatus();
                });
                $router->post('resetPassword', function () {
                    $controller = new \Modules\Users\Admin();
                    echo $controller->resetPassword();
                });
            });

            // Settings module
            $router->group('settings', function (Router $router) {
                $router->get('', function () {
                    $controller = new \Modules\Settings\Admin();
                    echo $controller->index();
                });
                $router->get('general', function () {
                    $controller = new \Modules\Settings\Admin();
                    echo $controller->general();
                });
                $router->post('save', function () {
                    $controller = new \Modules\Settings\Admin();
                    echo $controller->save();
                });
            });

            // Master data module
            $router->group('master', function (Router $router) {
                $router->get('', function () {
                    $controller = new \Modules\Master\Admin();
                    echo $controller->index();
                });
                
                // Poliklinik
                $router->get('poliklinik', function () {
                    $controller = new \Modules\Master\Admin();
                    echo $controller->poliklinik();
                });
                $router->post('poliklinik/save', function () {
                    $controller = new \Modules\Master\Admin();
                    echo $controller->savePoliklinik();
                });
                
                // Dokter
                $router->get('dokter', function () {
                    $controller = new \Modules\Master\Admin();
                    echo $controller->dokter();
                });
                $router->post('dokter/save', function () {
                    $controller = new \Modules\Master\Admin();
                    echo $controller->saveDokter();
                });
                
                // Penjab (Insurance)
                $router->get('penjab', function () {
                    $controller = new \Modules\Master\Admin();
                    echo $controller->penjab();
                });
                $router->post('penjab/save', function () {
                    $controller = new \Modules\Master\Admin();
                    echo $controller->savePenjab();
                });
            });

        });

        // ============================================
        // API ROUTES (if enabled)
        // ============================================
        
        if (env('API_ENABLED', false)) {
            $router->group('api/v1', function (Router $router) {
                // API routes will be defined by modules
            });
        }

    },

    /*
    |--------------------------------------------------------------------------
    | Route Groups
    |--------------------------------------------------------------------------
    */
    'groups' => [
        'web' => [
            'middleware' => ['web'],
        ],
        'api' => [
            'middleware' => ['api'],
        ],
        'admin' => [
            'middleware' => ['auth'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'web' => \App\Middleware\WebMiddleware::class,
        'api' => \App\Middleware\ApiMiddleware::class,
        'auth' => \App\Middleware\AuthMiddleware::class,
        'admin' => \App\Middleware\AdminMiddleware::class,
        'guest' => \App\Middleware\GuestMiddleware::class,
    ],

];
