<?php

namespace Kijho\ChatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Type;

class UserChatSettingsType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $version = "3.0.0";
        $symfonyVersion = \Symfony\Component\HttpKernel\Kernel::VERSION;

        $this->translator = $options['translator'];
        /**
         * symfonyVersion  < version for symfony 2.8
         */
        if (version_compare($symfonyVersion, $version) == '-1') {
            $builder->add('notificationSound', Type\ChoiceType::class, array(
                'required' => false,
                'label' => $this->translator->trans('admin_settings.notification_sound'),
                'choices' => array(
                    'sounds-capisci.mp3' => 'Capisci',
                    'sounds-come-to-daddy.mp3' => 'Come to Daddy',
                    'sounds-communication-channel.mp3' => 'Communication Channel',
                    'sounds-credulous.mp3' => 'Credulous',
                    'sounds-et-voila.mp3' => 'Et Voila',
                    'sounds-gets-in-the-way.mp3' => 'Gets in the way',
                    'sounds-isnt-it.mp3' => "Isn't it",
                    'sounds-no-way.mp3' => 'No way',
                    'sounds-obey.mp3' => 'Obey',
                    'sounds-pedantic.mp3' => 'Pedantic',
                    'sounds-served.mp3' => 'Served',
                    'sounds-surprise-on-a-spring.mp3' => 'Surprise',
                    'sounds-worthwhile.mp3' => 'Worthwhile',
                    'sounds-you-know.mp3' => 'You know',
                    'sounds-your-turn.mp3' => 'Your turn',
                )
            ));
        } else {
            $builder->add('notificationSound', Type\ChoiceType::class, array(
                'required' => false,
                'label' => $this->translator->trans('admin_settings.notification_sound'),
                'choices' => array(
                    'Capisci' => 'sounds-capisci.mp3',
                    'Come to Daddy' => 'sounds-come-to-daddy.mp3',
                    'Communication Channel' => 'sounds-communication-channel.mp3',
                    'Credulous' => 'sounds-credulous.mp3',
                    'Et Voila' => 'sounds-et-voila.mp3',
                    'Gets in the way' => 'sounds-gets-in-the-way.mp3',
                    "Isn't it" => 'sounds-isnt-it.mp3',
                    'No way' => 'sounds-no-way.mp3',
                    'Obey' => 'sounds-obey.mp3',
                    'Pedantic' => 'sounds-pedantic.mp3',
                    'Served' => 'sounds-served.mp3',
                    'Surprise' => 'sounds-surprise-on-a-spring.mp3',
                    'Worthwhile' => 'sounds-worthwhile.mp3',
                    'You know' => 'sounds-you-know.mp3',
                    'Your turn' => 'sounds-your-turn.mp3',
                )
            ));
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setRequired('translator');

        $resolver->setDefaults(array(
            'data_class' => 'Kijho\ChatBundle\Entity\UserChatSettings'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'chatbundle_user_chat_settings_type';
    }

    public function getBlockPrefix() {
        return $this->getName();
    }

}
