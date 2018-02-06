<?php

namespace AppBundle\Form;

use AppBundle\Entity\Respondent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class GetFeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'email_feedback',
                EmailType::class,
                [
                    'label' => 'Votre email',
                ]
            )
            ->add(
                'feedback_myself',
                CheckboxType::class,
                [
                    'label' => 'Inclure des comparaisons entre mes résultats et les moyennes',
                    'required' => false,
                ]
            )
            ->add(
                'feedback_team',
                CheckboxType::class,
                [
                    'label' => 'Inclure une synthèse de mon équipe',
                    'required' => false,
                ]
            )
            ->add('ok', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Respondent::class,
        ));
    }
}
