<?php

namespace Kijho\ChatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\DependencyInjection\Container;

class ConnectionFormType extends AbstractType {

    private $container;
    private $translator;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->translator = $this->container->get('translator');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('email', Type\EmailType::class, array(
                    'required' => true,
                    'label' => $this->translator->trans('client_email.email'),
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => $this->translator->trans('client_email.type_email'),
                    )
                ))
                ->add('nickname', Type\TextType::class, array(
                    'required' => true,
                    'label' => $this->translator->trans('global.nickname'),
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => $this->translator->trans('connection_form.type_nickname'),
                        'maxlength' => 14
                    )
                ))
        ;
    }

    /**
     * @return string
     */
    public function getName() {
        return 'chatbundle_connection_form_type';
    }

}
