<?php

namespace Kijho\ChatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\DependencyInjection\Container;

class ContactFormType extends AbstractType {

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
                ->add('subject', Type\TextType::class, array(
                    'required' => true,
                    'label' => $this->translator->trans('client_email.subject'),
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => $this->translator->trans('client_email.type_subject'),
                    )
                ))
                ->add('message', Type\TextareaType::class, array(
                    'required' => true,
                    'label' => $this->translator->trans('client_email.message'),
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => $this->translator->trans('global.type_your_message')
                    )
                ))
        ;
    }

    /**
     * @return string
     */
    public function getName() {
        return 'chatbundle_contact_form_type';
    }

}
