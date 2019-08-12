<?php

use Respect\Validation\Validator as Validator;

class Form
{
    /**
     * Validate a form $submission against a given $config
     * 
     * @param $submission  array  Array of submitted fields
     * @param $config  array  Configuration to validate against
     * @return array
     */
    public static function validate($submission, $config)
    {
        // list of invalid fields
        $invalid = array();
        $missing = array();

        // rules
        $rules           = self::getValidationRules($config);
        $error_messages  = self::getErrorMessages($config);

        // check for required
        foreach ($rules['required'] as $field => $is_required) {
            if (!$is_required) {
                continue;
            }

            if (!isset($submission[$field]) || $submission[$field] == '') {
                array_push($missing, $field);
            }
        }

        // check for validation
        foreach ($rules['validate'] as $field => $rule) {
            if (!isset($submission[$field])) {
                continue;
            }

            if (!self::validateField($submission[$field], $rule)) {
                array_push($invalid, $field);
            }
        }

        // errors, required take priority over validation
        $errors = array();
        $error_fields = array_unique(array_merge($invalid, $missing));

        foreach ($error_fields as $field) {
            $type = null;

            // required
            if (in_array($field, $missing)) {
                $type = 'required';
            } elseif (in_array($field, $invalid)) {
                $type = 'validate';
            }

            // field not found, skip it, this shouldn't happen
            if (!$type) {
                continue;
            }

            // parse out error message
            if (!is_array($error_messages[$field])) {
                $errors[$field] = $error_messages[$field];
            } else {
                if (!isset($error_messages[$field][$type])) {
                    $errors[$field] = "";
                } else {
                    $errors[$field] = $error_messages[$field][$type];
                }
            }
        }

        return $errors;
    }


    /**
     * Validate a field's value with a given $rule
     * 
     * @param $value  mixed  Value to validate
     * @param $rule  mixed  Rule(s) to use for validation
     * @return bool
     */
    public static function validateField($value, $rule)
    {
        // only validate non-empty fields
        if ($value == "") {
            return true;
        }

        // validate
        $validator = new Validator();

        // create rules
        if (!is_array($rule)) {
            $validator->addRule(Validator::buildRule($rule));
        } else {
            foreach ($rule as $rule_key => $params) {
                $params = Helper::ensureArray($params);
                $validator->addRule(Validator::buildRule($rule_key, $params));
            }
        }

        return $validator->validate($value);
    }


    /**
     * Parse the validation rules out of a fieldset config
     * 
     * @param $fields  array  List of fields with their configuraitions
     * @return array
     */
    public static function getValidationRules($fields)
    {
        $fields = Helper::ensureArray($fields);
        $rules  = array(
            'required' => array(),
            'validate' => array()
        );

        foreach ($fields as $field => $options) {
            $validation_rules = array_get($options, 'validate', false);
            $is_required = array_get($options, 'required', false);

            if ($is_required) {
                $rules['required'][$field] = true;
            }

            if ($validation_rules) {
                $rules['validate'][$field] = $validation_rules;
            }
        }

        return $rules;
    }


    /**
     * Parse the error messages out of a fieldset config
     * 
     * @param $fields  array  List of fields with their configurations
     * @return array
     */
    public static function getErrorMessages($fields)
    {
        $fields = Helper::ensureArray($fields);
        $rules = array();

        foreach ($fields as $field => $options) {
            $rules[$field] = $messages = array_get($options, 'error_messages', null);
        }

        return $rules;
    }
}