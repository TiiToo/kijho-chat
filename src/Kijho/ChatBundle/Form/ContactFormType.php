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
                    //'label' => $this->translator->trans('backend.user_role.name')
                    'label' => 'Email',
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => 'Type your email here..'
                    )
                ))
                ->add('subject', Type\TextType::class, array(
                    'required' => true,
                    'label' => 'Subject',
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => 'Type the subject here..'
                    )
                ))
                ->add('message', Type\TextareaType::class, array(
                    'required' => true,
                    'label' => 'Message',
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => 'Type your message here..'
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
