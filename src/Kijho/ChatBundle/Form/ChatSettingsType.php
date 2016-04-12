<?php

namespace Kijho\ChatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\DependencyInjection\Container;

class ChatSettingsType extends AbstractType {

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
                ->add('emailOfflineMessages', Type\EmailType::class, array(
                    'required' => false,
                    'label' => $this->translator->trans('admin_settings.email_offline_messages'),
                    'attr' => array('placeholder' => 'info@yoursite.com')
                ))
                ->add('automaticMessage', Type\TextareaType::class, array(
                    'required' => false,
                    'label' => $this->translator->trans('admin_settings.automatic_welcome_message'),
                    'attr' => array('placeholder' => 'Type here your welcome message')
                ))
                ->add('enableCustomResponses', Type\CheckboxType::class, array(
                    'required' => false,
                    'label' => $this->translator->trans('admin_settings.enable_custom_messages'),
                    'attr' => array()
                ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Kijho\ChatBundle\Entity\ChatSettings'
        ));
    }
    
    /**
     * @return string
     */
    public function getName() {
        return 'chatbundle_chat_settings_type';
    }

}
