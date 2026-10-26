<?php
namespace Veen\Core;

class Route
{
    public ?string $name = null;
    private string $regex;
    private array $paramNames = [];

    public function __construct(
        public string $method,
        public string $uri,
        public mixed $action,
        public array $middleware,
        private Router $router,
        private string $namePrefix = '',
    ) {
        preg_match_all('/\{(\w+)\}/', $uri, $m);
        $this->paramNames = $m[1];
        $pattern = preg_replace('/\\\\\{(\w+)\\\\\}/', '(?P<$1>[^/]+)', preg_quote($uri, '#'));
        $this->regex = '#^' . $pattern . '$#';
    }

    public function name(string $name): self
    {
        $this->name = $this->namePrefix . $name;
        $this->router->registerName($this->name, $this);
        return $this;
    }

    public function middleware(string|array $mw): self
    {
        $this->middleware = array_merge($this->middleware, (array) $mw);
        return $this;
    }

    public function match(string $path): ?array
    {
        if (!preg_match($this->regex, $path, $m)) {
            return null;
        }
        $params = [];
        foreach ($this->paramNames as $p) {
            $params[$p] = $m[$p];
        }
        return $params;
    }

    public function paramNames(): array
    {
        return $this->paramNames;
    }
}
