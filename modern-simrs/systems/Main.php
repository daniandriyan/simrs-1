<?php
namespace Systems;

use PDO;
use PDOException;

class Main
{
    protected $db;
    protected $route;

    public function __construct()
    {
        $this->connectDB();
    }

    private function connectDB()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->db = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function run()
    {
        // Simple Segment-based Router
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = str_replace(URL_PATH, '', $uri);
        $segments = explode('/', trim($uri, '/'));

        $plugin = !empty($segments[0]) ? $segments[0] : 'dashboard';
        $method = !empty($segments[1]) ? $segments[1] : 'index';

        $this->loadPlugin($plugin, $method, array_slice($segments, 2));
    }

    private function loadPlugin($plugin, $method, $params)
    {
        $className = "Plugins" . ucfirst($plugin) . "\Controller";
        $file = BASE_DIR . "/plugins/" . $plugin . "/Controller.php";

        if (file_exists($file)) {
            require_once $file;
            if (class_exists($className)) {
                $controller = new $className($this);
                if (method_exists($controller, $method)) {
                    call_user_func_array([$controller, $method], $params);
                } else {
                    echo "Method $method not found in plugin $plugin";
                }
            } else {
                echo "Class $className not found";
            }
        } else {
            // Default to Dashboard if not found
            if ($plugin !== 'dashboard') {
                header("Location: " . URL_PATH . "/dashboard");
                exit;
            }
            echo "Dashboard plugin not found.";
        }
    }
}
