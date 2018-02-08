<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidator;

class EmailsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof \Traversable) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
        $emailValidator = new EmailValidator();
        $emailValidator->initialize($this->context);
        $emailConstraint = new Email();
        foreach ($value as $email) {
            $emailValidator->validate($email, $emailConstraint);
        }
    }
}
