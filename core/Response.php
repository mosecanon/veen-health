<?php
namespace Veen\Core;

class Response
{
    public function __construct(
        public string $content = '',
        public int $status = 200,
        public array $headers = [],
    ) {
    }

    public function status(): int
    {
        return $this->status;
    }

    public static function from(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }
        if (is_array($value) || $value instanceof \JsonSerializable) {
            return self::json($value);
        }
        return new self((string) $value);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public function with(string $key, mixed $value): self
    {
        Session::flash($key, $value);
        return $this;
    }

    public function withErrors(array $errors): self
    {
        Session::flash('_errors', $errors);
        return $this;
    }

    public function withInput(array $input): self
    {
        foreach (array_keys($input) as $k) {
            if (str_contains(strtolower((string) $k), 'password') || $k === '_token') {
                unset($input[$k]);
            }
        }
        Session::flash('_old', $input);
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            $defaults = [
                'Content-Type' => 'text/html; charset=utf-8',
                'X-Frame-Options' => 'SAMEORIGIN',
                'X-Content-Type-Options' => 'nosniff',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
            ];
            foreach (array_merge($defaults, $this->headers) as $name => $value) {
                header("$name: $value");
            }
        }
        echo $this->content;
    }
}
