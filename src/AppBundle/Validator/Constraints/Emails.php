<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Emails extends Constraint
{
    public $message = 'La liste ne contient une liste d’adresses de courriels valide';

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
