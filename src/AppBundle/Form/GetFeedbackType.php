<?php

namespace AppBundle\Form;

use AppBundle\Entity\Respondent;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
                    'required' => false,
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
            ->add(
                'subordinates',
                CollectionType::class,
                array(
                    'entry_type' => RespondentType::class,
                    'entry_options' => [
                        'attr' => [
                            'source' => Respondent::SOURCE_AFTER_SUBORD,
                        ]
                    ],
                    'required'  => false,
                    'label' => 'Mes collaborateurs',
                    'allow_add' => true,
                    'by_reference' => false,
                    'mapped' => false,
                )
            )
            ->add(
                'manager',
                RespondentType::class,
                array(
                    'required'  => false,
                    'label' => 'Mon manager',
                    'attr' => [
                        'source' => Respondent::SOURCE_AFTER_MANAGER
                    ]
                )
            )
            ->add(
                'colleagues',
                CollectionType::class,
                array(
                    'entry_type' => RespondentType::class,
                    'entry_options' => [
                        'attr' => [
                            'source' => Respondent::SOURCE_AFTER_COLLEAGUE,
                        ]
                    ],
                    'required'  => false,
                    'label' => 'Mes collègues',
                    'allow_add' => true,
                    'by_reference' => false,
                    'mapped' => false,
                )
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
