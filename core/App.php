<?php
namespace Veen\Core;

class App
{
    private static Router $router;
    private static Request $request;

    public static function router(): Router
    {
        if (!isset(self::$router)) {
            self::$router = new Router(config('middleware.aliases', []), config('middleware.global', []));
            $router = self::$router;
            require base_path('routes/web.php');
        }
        return self::$router;
    }

    public static function request(): Request
    {
        if (!isset(self::$request)) {
            self::$request = Request::capture();
        }
        return self::$request;
    }

    public function run(): void
    {
        Session::start();
        self::$request = Request::capture();
        $router = self::router();

        try {
            $response = $router->dispatch(self::$request);
        } catch (ValidationException $e) {
            $response = $this->validationResponse($e);
        } catch (HttpException $e) {
            $response = $this->errorResponse($e->status, $e->getMessage());
        } catch (\Throwable $e) {
            $this->report($e);
            $msg = config('app.debug')
                ? get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
                : 'An unexpected error occurred. Our team has been notified.';
            $response = $this->errorResponse(500, $msg);
        }

        Response::from($response)->send();
    }

    private function validationResponse(ValidationException $e): Response
    {
        if (self::$request->wantsJson()) {
            return Response::json(['message' => $e->getMessage(), 'errors' => $e->errors], 422);
        }
        return back()->withErrors($e->errors)->withInput($e->input);
    }

    private function errorResponse(int $status, string $message): Response
    {
        if (self::$request->wantsJson()) {
            return Response::json(['message' => $message], $status);
        }
        try {
            return new Response(View::make('errors.error', ['code' => $status, 'message' => $message]), $status);
        } catch (\Throwable) {
            return new Response('<h1>' . $status . '</h1><p>' . e($message) . '</p>', $status);
        }
    }

    public function report(\Throwable $e): void
    {
        $dir = base_path('storage/logs');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $line = sprintf("[%s] %s: %s in %s:%d\n%s\n\n", date('c'), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
        @file_put_contents($dir . '/veen-' . date('Y-m-d') . '.log', $line, FILE_APPEND);
    }
}
