<?php

namespace AppBundle\Form;

use AppBundle\Validator\Constraints\Emails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ManyRespondentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'emails',
                TextareaType::class,
                [
                    'constraints' => [
                        new Emails(),
                    ],
                ]
            )
        ;
        $builder->get('emails')
            ->addModelTransformer(new CallbackTransformer(
                function ($emailsAsArray) {
                    return $emailsAsArray ? implode("\r\n", $emailsAsArray) : '';
                },
                function ($emailsAsString) {
                    return $emailsAsString ? array_filter(preg_split("/\\n\\r|\\r\\n|\\r|\\n/", $emailsAsString)) : [];
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
