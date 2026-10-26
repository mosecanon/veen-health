<?php
namespace Veen\Core;

/**
 * Blade-like layouts using plain PHP templates.
 *
 * In a view:
 *   <?php $this->layout('layouts.app', ['title' => 'Patients']) ?>
 *   <?php $this->start('content') ?> ... <?php $this->stop() ?>
 * In a layout:
 *   <?= $this->slot('content') ?>
 */
class View
{
    private array $sections = [];
    private array $stack = [];
    private ?string $layout = null;
    private array $layoutData = [];
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function make(string $name, array $data = []): string
    {
        return (new self())->render($name, $data);
    }

    public static function path(string $name): string
    {
        return base_path('resources/views/' . str_replace('.', '/', $name) . '.php');
    }

    public function render(string $name, array $data = []): string
    {
        $data = array_merge(self::$shared, $data);
        $file = self::path($name);
        if (!is_file($file)) {
            throw new \RuntimeException("View [$name] not found.");
        }
        $content = $this->capture($file, $data);

        while ($this->layout !== null) {
            $layout = $this->layout;
            $this->layout = null;
            if (!isset($this->sections['content'])) {
                $this->sections['content'] = $content;
            }
            $data = array_merge($data, $this->layoutData);
            $content = $this->capture(self::path($layout), $data);
        }
        return $content;
    }

    private function capture(string $__file, array $__data): string
    {
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    public function layout(string $name, array $data = []): void
    {
        $this->layout = $name;
        $this->layoutData = $data;
    }

    public function start(string $name): void
    {
        $this->stack[] = $name;
        ob_start();
    }

    public function stop(): void
    {
        $name = array_pop($this->stack);
        $this->sections[$name] = ($this->sections[$name] ?? '') . ob_get_clean();
    }

    public function set(string $name, string $value): void
    {
        $this->sections[$name] = $value;
    }

    public function slot(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return !empty($this->sections[$name]);
    }

    public function partial(string $name, array $data = []): string
    {
        return (new self())->render($name, $data);
    }
}
