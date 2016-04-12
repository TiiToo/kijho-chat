<?php

namespace Kijho\ChatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\DependencyInjection\Container;

class UserChatSettingsType extends AbstractType {

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
                ->add('notificationSound', Type\ChoiceType::class, array(
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
                ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
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

}
