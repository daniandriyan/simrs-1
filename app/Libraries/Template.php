<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Template Engine Class
 * 
 * Simple and secure template rendering system.
 * Supports template inheritance, partials, and basic templating syntax.
 * 
 * Syntax:
 *   {$variable}           - Output variable
 *   {loop: $items as $item}...{/loop}  - Loop through array
 *   {if $condition}...{/if}  - Conditional
 *   {if $condition}...{else}...{/if}  - If-else
 *   {include='file.html'}  - Include partial
 *   {raw $variable}        - Output without escaping
 *   {url()}                - Base URL
 *   {url('path')}          - URL with path
 *   {e($string)}           - Escape string
 */
class Template
{
    /**
     * @var array Template variables
     */
    private array $data = [];

    /**
     * @var string Layout file path
     */
    private ?string $layout = null;

    /**
     * @var array Sections content
     */
    private array $sections = [];

    /**
     * @var string Current section being captured
     */
    private ?string $currentSection = null;

    /**
     * @var array Stack for nested sections
     */
    private array $sectionStack = [];

    /**
     * @var array Template cache
     */
    private static array $cache = [];

    /**
     * @var array Sections that exist
     */
    private array $existingSections = [];

    /**
     * Set template variable
     *
     * @param string $key Variable name
     * @param mixed $value Variable value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Set multiple template variables
     *
     * @param array $data Variables array
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Get template variable
     *
     * @param string $key Variable name
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set layout template
     *
     * @param string $layout Layout file path
     * @return self
     */
    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Start section capture
     *
     * @param string $name Section name
     * @return void
     */
    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        $this->sectionStack[] = $name;
        ob_start();
    }

    /**
     * End section capture
     *
     * @return void
     */
    public function endSection(): void
    {
        $content = ob_get_clean();
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = $content;
        $this->existingSections[] = $name;
        $this->currentSection = end($this->sectionStack) ?: null;
    }

    /**
     * Check if section exists
     *
     * @param string $name Section name
     * @return bool
     */
    public function sectionExists(string $name): bool
    {
        return in_array($name, $this->existingSections);
    }

    /**
     * Get section content
     *
     * @param string $name Section name
     * @param string $default Default content
     * @return string
     */
    public function getSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Yield section content (for layouts)
     *
     * @param string $name Section name
     * @param string $default Default content
     * @return void
     */
    public function yieldSection(string $name, string $default = ''): void
    {
        echo $this->getSection($name, $default);
    }

    /**
     * Render template file
     *
     * @param string $template Template file path (without extension)
     * @param array $data Template variables
     * @param bool $return Return as string or output directly
     * @return string
     */
    public function render(string $template, array $data = [], bool $return = true): string
    {
        // Add .html extension if not present
        if (!str_ends_with($template, '.html') && !str_ends_with($template, '.php')) {
            $template .= '.html';
        }

        // Merge data
        $templateData = array_merge($this->data, $data);

        // Render template
        $content = $this->renderFile($template, $templateData);

        // Apply layout if set
        if ($this->layout !== null) {
            $templateData['content'] = $content;
            $templateData['sections'] = $this->sections;
            $content = $this->renderFile($this->layout, $templateData);
        }

        // Reset state
        $this->data = [];
        $this->sections = [];
        $this->layout = null;

        if ($return) {
            return $content;
        }

        echo $content;
        return '';
    }

    /**
     * Render a partial template
     *
     * @param string $template Template file path
     * @param array $data Template variables
     * @return string Rendered content
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->renderFile($template, array_merge($this->data, $data));
    }

    /**
     * Render template file
     *
     * @param string $template Template file path
     * @param array $data Template variables
     * @return string Rendered content
     */
    private function renderFile(string $template, array $data): string
    {
        // Check cache
        $cacheKey = $template;
        if (isset(self::$cache[$cacheKey])) {
            $compiled = self::$cache[$cacheKey];
        } else {
            // Find template file
            $filePath = $this->findTemplate($template);
            
            if (!file_exists($filePath)) {
                // Try with .html extension
                $filePath .= '.html';
                if (!file_exists($filePath)) {
                    throw new \RuntimeException("Template not found: {$template}");
                }
            }

            // Compile template
            $compiled = $this->compile(file_get_contents($filePath));
            self::$cache[$cacheKey] = $compiled;
        }

        // Extract variables for template
        extract($data, EXTR_SKIP);

        // Start output buffering
        ob_start();

        // Execute compiled template
        try {
            eval('?>' . $compiled);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("Template error in {$template}: " . $e->getMessage());
        }

        return ob_get_clean();
    }

    /**
     * Find template file
     *
     * @param string $template Template path
     * @return string Full file path
     */
    private function findTemplate(string $template): string
    {
        // Absolute path
        if (str_starts_with($template, '/')) {
            return $template;
        }

        // Check common locations
        $paths = [
            $template,
            views_path($template),
            views_path('layouts/' . $template),
            views_path('admin/' . $template),
            views_path('modules/' . $template),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
            if (file_exists($path . '.html')) {
                return $path . '.html';
            }
        }

        return $template;
    }

    /**
     * Compile template syntax to PHP
     *
     * @param string $content Template content
     * @return string Compiled PHP code
     */
    private function compile(string $content): string
    {
        // Compile template directives
        $content = $this->compileComments($content);
        $content = $this->compileEcho($content);
        $content = $this->compileRaw($content);
        $content = $this->compileIf($content);
        $content = $this->compileElse($content);
        $content = $this->compileEndIf($content);
        $content = $this->compileLoop($content);
        $content = $this->compileEndLoop($content);
        $content = $this->compileInclude($content);
        $content = $this->compileSection($content);
        $content = $this->compileEndSection($content);
        $content = $this->compileYield($content);
        $content = $this->compileExtends($content);
        $content = $this->compileHelpers($content);

        return $content;
    }

    /**
     * Compile comments
     */
    private function compileComments(string $content): string
    {
        return preg_replace('/\{\#(.+?)\#\}/s', '<?php /*$1*/ ?>', $content);
    }

    /**
     * Compile echo statements
     */
    private function compileEcho(string $content): string
    {
        // {$variable} - escaped output
        return preg_replace('/\{\$(\w+(?:\.\w+)*)\}/', '<?php echo e($1); ?>', $content);
    }

    /**
     * Compile raw output (no escaping)
     */
    private function compileRaw(string $content): string
    {
        return preg_replace('/\{raw\s+\$(\w+(?:\.\w+)*)\}/', '<?php echo $1; ?>', $content);
    }

    /**
     * Compile if statements
     */
    private function compileIf(string $content): string
    {
        return preg_replace('/\{if\s+(.+?)\}/', '<?php if ($1): ?>', $content);
    }

    /**
     * Compile else statements
     */
    private function compileElse(string $content): string
    {
        return preg_replace('/\{else\}/', '<?php else: ?>', $content);
    }

    /**
     * Compile endif statements
     */
    private function compileEndIf(string $content): string
    {
        return preg_replace('/\{\/if\}/', '<?php endif; ?>', $content);
    }

    /**
     * Compile loop statements
     */
    private function compileLoop(string $content): string
    {
        // {loop: $items as $item}
        $pattern = '/\{loop:\s+(\$\w+)\s+as\s+(\$\w+)(?:\s+=>\s+(\$\w+))?\}/';
        return preg_replace_callback($pattern, function ($matches) {
            $array = $matches[1];
            $value = $matches[2];
            $key = $matches[3] ?? null;
            
            if ($key) {
                return "<?php foreach ({$array} as {$key} => {$value}): ?>";
            }
            return "<?php foreach ({$array} as {$value}): ?>";
        }, $content);
    }

    /**
     * Compile endloop statements
     */
    private function compileEndLoop(string $content): string
    {
        return preg_replace('/\{\/loop\}/', '<?php endforeach; ?>', $content);
    }

    /**
     * Compile include statements
     */
    private function compileInclude(string $content): string
    {
        return preg_replace_callback('/\{include=[\'"](.+?)[\'"]\}/', function ($matches) {
            $file = $matches[1];
            return "<?php echo \$this->partial('{$file}', get_defined_vars()); ?>";
        }, $content);
    }

    /**
     * Compile section statements
     */
    private function compileSection(string $content): string
    {
        return preg_replace_callback('/\{section=[\'"](.+?)[\'"]\}/', function ($matches) {
            $name = $matches[1];
            return "<?php \$this->startSection('{$name}'); ?>";
        }, $content);
    }

    /**
     * Compile endsection statements
     */
    private function compileEndSection(string $content): string
    {
        return preg_replace('/\{\/section\}/', '<?php $this->endSection(); ?>', $content);
    }

    /**
     * Compile yield statements
     */
    private function compileYield(string $content): string
    {
        return preg_replace_callback('/\{yield=[\'"](.+?)[\'"]\}/', function ($matches) {
            $name = $matches[1];
            return "<?php \$this->yieldSection('{$name}'); ?>";
        }, $content);
    }

    /**
     * Compile extends statements
     */
    private function compileExtends(string $content): string
    {
        return preg_replace_callback('/\{extends=[\'"](.+?)[\'"]\}/', function ($matches) {
            $layout = $matches[1];
            return "<?php \$this->setLayout('{$layout}'); ?>";
        }, $content);
    }

    /**
     * Compile helper functions
     */
    private function compileHelpers(string $content): string
    {
        // {url()} or {url('path')}
        $content = preg_replace_callback('/\{url(?:=[\'"](.+?)[\'"])?\}/', function ($matches) {
            $path = $matches[1] ?? '';
            return "<?php echo base_url('{$path}'); ?>";
        }, $content);

        // {e($string)}
        $content = preg_replace('/\{e\((.+?)\)\}/', '<?php echo e($1); ?>', $content);

        // {csrf_field()}
        $content = preg_replace('/\{csrf_field\}/', '<?php echo csrf_field(); ?>', $content);

        // {csrf_token()}
        $content = preg_replace('/\{csrf_token\}/', '<?php echo csrf_token(); ?>', $content);

        // {asset('path')}
        $content = preg_replace_callback('/\{asset=[\'"](.+?)[\'"]\}/', function ($matches) {
            $path = $matches[1];
            return "<?php echo asset('{$path}'); ?>";
        }, $content);

        // {config('key')}
        $content = preg_replace_callback('/\{config=[\'"](.+?)[\'"]\}/', function ($matches) {
            $key = $matches[1];
            return "<?php echo config('{$key}'); ?>";
        }, $content);

        // {env('key')}
        $content = preg_replace_callback('/\{env=[\'"](.+?)[\'"]\}/', function ($matches) {
            $key = $matches[1];
            return "<?php echo env('{$key}'); ?>";
        }, $content);

        // {date('format')}
        $content = preg_replace_callback('/\{date(?:=[\'"](.+?)[\'"])?\}/', function ($matches) {
            $format = $matches[1] ?? 'Y-m-d H:i:s';
            return "<?php echo date('{$format}'); ?>";
        }, $content);

        // {flash type="success"}
        $content = preg_replace_callback('/\{flash\s+type=[\'"](\w+)[\'"]\}/', function ($matches) {
            $type = $matches[1];
            return "<?php echo flash_get('{$type}') ?? ''; ?>";
        }, $content);

        // {yield_exists('section')}
        $content = preg_replace_callback('/\{yield_exists=[\'"](.+?)[\'"]\}/', function ($matches) {
            $name = $matches[1];
            return "<?php echo \$this->sectionExists('{$name}') ? '1' : ''; ?>";
        }, $content);

        return $content;
    }

    /**
     * Clear template cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Get cache size
     *
     * @return int
     */
    public function getCacheSize(): int
    {
        return count(self::$cache);
    }
}
