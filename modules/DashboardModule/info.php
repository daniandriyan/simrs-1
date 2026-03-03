<?php

/**
 * DashboardModule Information
 * 
 * This file contains module metadata.
 * It is loaded without instantiating the module class.
 */

return [
    'name' => 'DashboardModule',
    'description' => 'Main dashboard module with statistics and quick access',
    'author' => 'SIMRS Team',
    'version' => '1.0.0',
    'compatibility' => '1.*.*',
    'priority' => 1, // Low priority = loaded first
    'category' => 'core',
    'icon' => 'bi-speedometer2',
    'is_core' => true,
    'has_settings' => false,
];
