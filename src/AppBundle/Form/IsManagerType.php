<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class IsManagerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'is_manager',
                ChoiceType::class,
                [
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0,
                    ],
                    'label'=>'Avez-vous des fonctions d’encadrement / de management ?',
                    'expanded' => true,
                ]
            )
            ->add('ok', SubmitType::class)
        ;
    }
}
