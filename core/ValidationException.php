<?php
namespace Veen\Core;

class ValidationException extends \RuntimeException
{
    public function __construct(public array $errors, public array $input = [])
    {
        parent::__construct('The given data was invalid.', 422);
    }
}
