<?php
namespace Veen\Core;

class HttpException extends \RuntimeException
{
    public function __construct(public int $status, string $message = '')
    {
        parent::__construct($message ?: self::defaultMessage($status), $status);
    }

    public static function defaultMessage(int $status): string
    {
        return match ($status) {
            401 => 'Unauthenticated.',
            403 => 'You do not have permission to perform this action.',
            404 => 'The page you are looking for could not be found.',
            405 => 'Method not allowed.',
            419 => 'Your session has expired. Please refresh and try again.',
            429 => 'Too many requests. Please slow down.',
            default => 'Something went wrong.',
        };
    }
}
