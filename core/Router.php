<?php
namespace Veen\Core;

use Closure;

/**
 * Laravel-style router:
 *   $router->get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');
 *   $router->group(['prefix' => 'pharmacy', 'middleware' => ['auth'], 'as' => 'pharmacy.'], fn($r) => ...);
 */
class Router
{
    /** @var Route[] */
    private array $routes = [];
    private array $named = [];
    private array $groupStack = [];

    public function __construct(private array $aliases = [], private array $global = [])
    {
    }

    public function get(string $uri, mixed $action): Route { return $this->add('GET', $uri, $action); }
    public function post(string $uri, mixed $action): Route { return $this->add('POST', $uri, $action); }
    public function put(string $uri, mixed $action): Route { return $this->add('PUT', $uri, $action); }
    public function patch(string $uri, mixed $action): Route { return $this->add('PATCH', $uri, $action); }
    public function delete(string $uri, mixed $action): Route { return $this->add('DELETE', $uri, $action); }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $uri, mixed $action): Route
    {
        $prefix = '';
        $middleware = [];
        $as = '';
        foreach ($this->groupStack as $g) {
            if (!empty($g['prefix'])) {
                $prefix .= '/' . trim($g['prefix'], '/');
            }
            $middleware = array_merge($middleware, (array) ($g['middleware'] ?? []));
            $as .= $g['as'] ?? '';
        }
        $full = '/' . trim($prefix . '/' . trim($uri, '/'), '/');
        $route = new Route($method, $full, $action, $middleware, $this, $as);
        $this->routes[] = $route;
        return $route;
    }

    public function registerName(string $name, Route $route): void
    {
        $this->named[$name] = $route;
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->named[$name])) {
            throw new \InvalidArgumentException("Route [$name] not defined.");
        }
        $route = $this->named[$name];
        $uri = $route->uri;
        foreach ($route->paramNames() as $p) {
            if (!array_key_exists($p, $params)) {
                throw new \InvalidArgumentException("Missing parameter [$p] for route [$name].");
            }
            $uri = str_replace('{' . $p . '}', rawurlencode((string) $params[$p]), $uri);
            unset($params[$p]);
        }
        $qs = $params ? '?' . http_build_query($params) : '';
        return Request::basePath() . ($uri === '/' ? '/' : $uri) . $qs;
    }

    public function dispatch(Request $request): mixed
    {
        $path = $request->path();
        $method = $request->method();
        $allowed = false;

        foreach ($this->routes as $route) {
            $params = $route->match($path);
            if ($params === null) {
                continue;
            }
            $routeMethod = $route->method;
            if ($routeMethod !== $method && !($method === 'HEAD' && $routeMethod === 'GET')) {
                $allowed = true;
                continue;
            }
            $request->attributes['route'] = $route;
            return $this->runPipeline($route, $request, $params);
        }

        throw new HttpException($allowed ? 405 : 404);
    }

    private function runPipeline(Route $route, Request $request, array $params): mixed
    {
        $core = fn (Request $req) => $this->callAction($route->action, $req, $params);

        $stack = array_reverse(array_merge($this->global, $route->middleware));
        foreach ($stack as $definition) {
            [$name, $argString] = array_pad(explode(':', $definition, 2), 2, null);
            $args = $argString !== null ? explode(',', $argString) : [];
            $class = $this->aliases[$name] ?? $name;
            $next = $core;
            $core = fn (Request $req) => (new $class())->handle($req, $next, ...$args);
        }

        return $core($request);
    }

    private function callAction(mixed $action, Request $request, array $params): mixed
    {
        $args = array_values($params);
        if ($action instanceof Closure) {
            return $action($request, ...$args);
        }
        if (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $controller = new $class();
            return $controller->$method($request, ...$args);
        }
        throw new \RuntimeException('Invalid route action.');
    }
}
