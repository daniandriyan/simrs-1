<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller Class
 * 
 * All controllers should extend this class.
 * Provides common functionality for request handling, validation, and response.
 */
abstract class BaseController
{
    /**
     * @var Database Database instance
     */
    protected Database $db;

    /**
     * @var Router Router instance
     */
    protected Router $router;

    /**
     * @var array Request data
     */
    protected array $request = [];

    /**
     * @var array Validation errors
     */
    protected array $errors = [];

    /**
     * @var string Module name
     */
    protected string $module = '';

    /**
     * @var string View path
     */
    protected string $viewPath = '';

    /**
     * @var array Data to pass to views
     */
    protected array $data = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = new Database();
        $this->router = App::getInstance()->getRouter();
        $this->request = array_merge($_GET, $_POST);
        
        // Set module name from class name
        $className = static::class;
        $parts = explode('\\', $className);
        $this->module = end($parts);
        
        // Set default view path
        $this->viewPath = $this->module;
    }

    /**
     * Get database instance
     *
     * @param string|null $table Table name (optional)
     * @return Database|Database
     */
    protected function db(?string $table = null): Database
    {
        $db = new Database();
        if ($table !== null) {
            $db->table($table);
        }
        return $db;
    }

    /**
     * Get request input
     *
     * @param string $key Input key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    /**
     * Get all request inputs
     *
     * @return array
     */
    protected function all(): array
    {
        return $this->request;
    }

    /**
     * Get only specified inputs
     *
     * @param array $keys Keys to get
     * @return array
     */
    protected function only(array $keys): array
    {
        return array_intersect_key($this->request, array_flip($keys));
    }

    /**
     * Get all inputs except specified keys
     *
     * @param array $keys Keys to exclude
     * @return array
     */
    protected function except(array $keys): array
    {
        return array_diff_key($this->request, array_flip($keys));
    }

    /**
     * Check if input exists
     *
     * @param string $key Input key
     * @return bool
     */
    protected function hasInput(string $key): bool
    {
        return isset($this->request[$key]);
    }

    /**
     * Validate request data
     *
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @return bool
     */
    protected function validate(array $rules, array $messages = []): bool
    {
        $validator = new Validator($this->request, $rules, $messages);
        
        if ($validator->fails()) {
            $this->errors = $validator->errors();
            return false;
        }
        
        return true;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first validation error
     *
     * @return string|null
     */
    public function firstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Set data for view
     *
     * @param string $key Variable name
     * @param mixed $value Variable value
     * @return self
     */
    protected function with(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Set multiple data for view
     *
     * @param array $data Data array
     * @return self
     */
    protected function withArray(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Render a view
     *
     * @param string $view View name (relative to module view path)
     * @param array $data Data to pass to view
     * @return string Rendered HTML
     */
    protected function view(string $view, array $data = []): string
    {
        $data = array_merge($this->data, $data);
        
        $template = new \App\Libraries\Template();
        
        // Build view path: modules/{module}/view/{view}
        $viewPath = modules_path("{$this->viewPath}/view/{$view}");
        
        return $template->render($viewPath, $data);
    }

    /**
     * Render admin view
     *
     * @param string $view View name
     * @param array $data Data to pass to view
     * @return string Rendered HTML
     */
    protected function adminView(string $view, array $data = []): string
    {
        $data = array_merge($this->data, $data);
        
        $template = new \App\Libraries\Template();
        $viewPath = views_path("admin/{$view}");
        
        return $template->render($viewPath, $data);
    }

    /**
     * Return JSON response
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @return string JSON string
     */
    protected function json(mixed $data, int $statusCode = 200): string
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Return success JSON response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @return string JSON string
     */
    protected function success(mixed $data = null, string $message = 'Success'): string
    {
        return $this->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Return error JSON response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param mixed $errors Error details
     * @return string JSON string
     */
    protected function error(string $message, int $statusCode = 400, mixed $errors = null): string
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $statusCode);
    }

    /**
     * Redirect to URL
     *
     * @param string $url URL to redirect to
     * @return void
     */
    protected function redirect(string $url): void
    {
        redirect($url);
    }

    /**
     * Redirect back to previous page
     *
     * @return void
     */
    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? base_url();
        $this->redirect($referer);
    }

    /**
     * Set flash message
     *
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message content
     * @return void
     */
    protected function flash(string $type, string $message): void
    {
        flash($type, $message);
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    protected function isAjax(): bool
    {
        return is_ajax();
    }

    /**
     * Get request method
     *
     * @return string
     */
    protected function method(): string
    {
        return request_method();
    }

    /**
     * Check if request method is POST
     *
     * @return bool
     */
    protected function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Check if request method is GET
     *
     * @return bool
     */
    protected function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Get CSRF token field
     *
     * @return string
     */
    protected function csrfField(): string
    {
        return csrf_field();
    }

    /**
     * Verify CSRF token
     *
     * @return bool
     */
    protected function verifyCsrf(): bool
    {
        return csrf_verify();
    }

    /**
     * Require authentication
     *
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!is_authenticated()) {
            if ($this->isAjax()) {
                $this->error('Unauthorized', 401);
                exit;
            }
            flash('error', 'Silakan login untuk melanjutkan');
            redirect(base_url('login'));
        }
    }

    /**
     * Get current user ID
     *
     * @return int|null
     */
    protected function userId(): ?int
    {
        return auth_id();
    }

    /**
     * Get current user data
     *
     * @return array|null
     */
    protected function user(): ?array
    {
        return auth_user();
    }

    /**
     * Check if user has permission
     *
     * @param string $permission Permission name
     * @return bool
     */
    protected function can(string $permission): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        $permissions = $user['permissions'] ?? [];
        return in_array($permission, $permissions) || $user['role'] === 'admin';
    }

    /**
     * Authorize user action
     *
     * @param bool $condition Authorization condition
     * @param string $message Error message if unauthorized
     * @return void
     */
    protected function authorize(bool $condition, string $message = 'Unauthorized'): void
    {
        if (!$condition) {
            if ($this->isAjax()) {
                $this->error($message, 403);
                exit;
            }
            flash('error', $message);
            $this->back();
        }
    }

    /**
     * Paginate query results
     *
     * @param int $perPage Items per page
     * @param int|null $page Current page number
     * @return array Paginated data
     */
    protected function paginate(int $perPage = 10, ?int $page = null): array
    {
        $page = $page ?? (int) $this->input('page', 1);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        
        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset
        ];
    }

    /**
     * Render pagination links
     *
     * @param int $total Total items
     * @param int $perPage Items per page
     * @param int $page Current page
     * @param string $baseUrl Base URL for pagination
     * @return string HTML pagination links
     */
    protected function renderPagination(int $total, int $perPage, int $page, string $baseUrl): string
    {
        $totalPages = (int) ceil($total / $perPage);
        
        if ($totalPages <= 1) {
            return '';
        }
        
        $html = '<nav><ul class="pagination">';
        
        // Previous button
        if ($page > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($page - 1) . '">Previous</a></li>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = $i === $page ? 'active' : '';
            $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
        }
        
        // Next button
        if ($page < $totalPages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($page + 1) . '">Next</a></li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }
}
