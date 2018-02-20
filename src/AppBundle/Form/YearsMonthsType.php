<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type;

class YearsMonthsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('annees', Type\ChoiceType::class, array(
                'label' => ' ',
                'required' => true,
                'placeholder' => '-années-',
                'choices' => range(0, 45)
            ))
            ->add('mois', Type\ChoiceType::class, array(
                'label' => ' ',
                'required' => true,
                'placeholder' => '-mois-',
                'choices' => range(0, 11)
            ))
        ;
    }
}
