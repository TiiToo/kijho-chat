<?php

namespace Kijho\ChatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectionFormType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $this->translator = $options['translator'];

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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setRequired('translator');
    }

    /**
     * @return string
     */
    public function getName() {
        return 'chatbundle_connection_form_type';
    }

    public function getBlockPrefix() {
        return $this->getName();
    }

}
