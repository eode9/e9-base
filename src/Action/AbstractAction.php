<?php

namespace E9\Core\Action;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class AbstractAction
 * @package E9\Core\Action
 */
abstract class AbstractAction
{
    /**
     * @param $constraints ConstraintViolationListInterface
     * @return array
     */
    public function getValidationErrors($constraints): array
    {
        $errors = [];
        foreach ($constraints as $constraint) {
            $errors[$constraint->getPropertyPath()] = $constraint->getMessage();
        }
        return $errors;
    }

}