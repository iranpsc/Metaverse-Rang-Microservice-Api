<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ConvertPersianNumbersToLatin
{
    /**
     * Convert Persian numbers in the request to Latin numbers.
     *
     * @param Request $request The request object.
     * @param Closure $next The next middleware in the pipeline.
     * @return mixed The response from the next middleware.
     */
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        $converted = $this->convertPersianNumbersToLatin($input);
        $request->merge($converted);

        return $next($request);
    }

    /**
     * Convert Persian numbers in the given array to Latin numbers.
     *
     * @param array $array The array to convert.
     * @return array The converted array.
     */
    private function convertPersianNumbersToLatin(array $array): array
    {
        $converted = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $converted[$key] = $this->convertPersianNumbersToLatin($value);
            } else {
                $converted[$key] = $this->convertPersianNumbersToLatinInString($value);
            }
        }

        return $converted;
    }

    /**
     * Convert Persian numbers in the given string to Latin numbers.
     *
     * @param string $string The string to convert.
     * @return string The converted string.
     */
    private function convertPersianNumbersToLatinInString(string $string): string
    {
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $latinNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($persianNumbers, $latinNumbers, $string);
    }
}
