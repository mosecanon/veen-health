<?php
namespace App\Controllers;

use Veen\Core\Auth;
use Veen\Core\HttpException;
use Veen\Core\Request;
use Veen\Core\Validator;

abstract class Controller
{
    protected function validate(Request $request, array $rules): array
    {
        return Validator::validate($request, $rules);
    }

    protected function authorize(string $permission): void
    {
        if (!Auth::can($permission)) {
            throw new HttpException(403);
        }
    }
}
