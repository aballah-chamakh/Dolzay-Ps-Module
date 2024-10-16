<?php

namespace Dolzay\CustomClasses\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;



class IsIntegerAndGreaterThanZero extends Constraint
{
    public $message = 'this field must be an integer greater than 0.';
}


class IsIntegerAndGreaterThanZeroValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if($value == null){
            return;
        }
        
        if (!is_numeric($value) || (int)$value <= 0) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}