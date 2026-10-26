<?php
namespace Veen\Core;

/**
 * Rules: required, nullable, string, email, numeric, integer, min:n, max:n,
 *        date, in:a,b,c, confirmed, phone, boolean
 * min/max apply to string length, or numeric value when "numeric"/"integer" is present.
 */
class Validator
{
    public static function validate(Request $request, array $rules): array
    {
        [$data, $errors] = self::make($request->all(), $rules);
        if ($errors) {
            throw new ValidationException($errors, $request->all());
        }
        return $data;
    }

    public static function make(array $input, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $input[$field] ?? null;
            if (is_string($value)) $value = trim($value);
            $label = ucfirst(str_replace('_', ' ', $field));
            $isNumeric = in_array('numeric', $ruleList, true) || in_array('integer', $ruleList, true);
            $empty = $value === null || $value === '' || $value === [];

            if ($empty) {
                if (in_array('required', $ruleList, true)) {
                    $errors[$field][] = "$label is required.";
                } else {
                    $validated[$field] = in_array('boolean', $ruleList, true) ? 0 : null;
                }
                continue;
            }

            foreach ($ruleList as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $error = match ($name) {
                    'email'   => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$label must be a valid email.",
                    'numeric' => is_numeric($value) ? null : "$label must be a number.",
                    'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
                    'string'  => is_scalar($value) ? null : "$label must be text.",
                    'date'    => (strtotime((string) $value) !== false) ? null : "$label must be a valid date.",
                    'in'      => in_array((string) $value, explode(',', (string) $param), true) ? null : "$label is invalid.",
                    'phone'   => preg_match('/^\+?[0-9 ()-]{7,20}$/', (string) $value) ? null : "$label must be a valid phone number.",
                    'boolean' => null,
                    'confirmed' => (($input[$field . '_confirmation'] ?? null) === $value) ? null : "$label confirmation does not match.",
                    'min'     => $isNumeric
                        ? ((float) $value >= (float) $param ? null : "$label must be at least $param.")
                        : (mb_strlen((string) $value) >= (int) $param ? null : "$label must be at least $param characters."),
                    'max'     => $isNumeric
                        ? ((float) $value <= (float) $param ? null : "$label may not be greater than $param.")
                        : (mb_strlen((string) $value) <= (int) $param ? null : "$label may not exceed $param characters."),
                    default   => null,
                };
                if ($error) {
                    $errors[$field][] = $error;
                    break;
                }
            }
            if (!isset($errors[$field])) {
                $validated[$field] = in_array('boolean', $ruleList, true) ? (int) (bool) $value : $value;
            }
        }
        return [$validated, $errors];
    }
}
