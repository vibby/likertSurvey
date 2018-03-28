<?php

namespace AppBundle\Form;

use AppBundle\Entity\Respondent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class RespondentType extends AbstractType
{
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class)
            ->add('source', HiddenType::class)
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function(FormEvent $event) use ($options) {
                    $data = $event->getData();
                    $data->setSource(
                        isset($options['attr']['source'])
                        ? $options['attr']['source']
                        : 'unknown'
                    );
                    $data->setDomain($this->request->getBaseUrl());
                    $event->setData($data);
                }
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Respondent::class,
        ));
    }
}
