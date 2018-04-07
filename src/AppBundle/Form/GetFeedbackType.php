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
                    'label' => 'Mon adresse email :',
                    'required' => false,
                ]
            )
            ->add(
                'feedback_team',
                CheckboxType::class,
                [
                    'label' => 'Je souhaite recevoir le bilan consolidé des résultats de mon équipe. (Merci de vous assurer que votre adresse email est bien renseignée en haut de ce formulaire)',
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
                    'label' => 'Je partage avec mes collaborateurs',
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
                    'label' => 'Je partage avec mon manager',
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
                    'label' => 'Je partage avec mes collègues ou autres relations hors de ma société :',
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
