<?php

namespace AppBundle\Form;

use AppBundle\Entity\Respondent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function(FormEvent $event) use ($options) {
                    /** @var Respondent $data */
                    $respondent = $event->getData();
                    if ($respondent && $respondent->getEmail()) {
                        $respondent->setSource(
                            isset($options['attr']['source'])
                            ? $options['attr']['source']
                            : 'unknown'
                        );
                        $respondent->setDomain($this->request->server->get('SERVER_NAME'));
                        $event->setData($respondent);
                    }
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
