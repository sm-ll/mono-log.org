<?php

use Respect\Validation\Validator as Validator;

class Tasks_member extends Tasks
{
    /**
     * Validates a given submission
     * 
     * @param array  $submission  Submission to validate
     * @return array
     */
    public function validate($submission)
    {
        return Form::validate($submission, array_get($this->loadConfigFile('fields'), 'fields', array()));
    }
}