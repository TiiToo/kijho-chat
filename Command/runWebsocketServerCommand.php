<?php

namespace Kijho\ChatBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Process\Process;
use Kijho\ChatBundle\Entity\ChatSettings;

/**
 * 
 * @author LUIS FERNNADO GRANADOS 
 * @since 1.0 04/10/2016
 */
class runWebsocketServerCommand extends ContainerAwareCommand {

    const startHour = "23:00:00";
    const endHour = "23:03:59";

    /**
     * Esta funcion permite establecer el nombre del comando
     * @author LUIS FERNNADO GRANADOS 
     * @since 1.0 04/10/2016
     */
    protected function configure() {
        $this->setName('run:websocket:server')->setDescription('Allow verify start or stop the chat app');
    }

    /**
     * funcion que verifica si el chat debe iniciar o detenerse
     * @author LUIS FERNNADO GRANADOS 
     * @since 1.0 04/10/2016
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        set_time_limit(0);
        ini_set('memory_limit  ', '-1');

        $date = new \DateTime('now');
        $isNight = $date->format('H:i:s');

        $container = $this->getContainer();

        $em = $container->get('doctrine')->getManager();

        $chatSettings = $em->getRepository('ChatBundle:ChatSettings')->
                findOneBy(array(), array());

        if ($chatSettings->getIsRunCommand() == ChatSettings::CHAT_START) {
            $this->startAndStop($container, $em, $output, $chatSettings
                    , ChatSettings::CHAT_START);
        } elseif ($chatSettings->getIsRunCommand() == ChatSettings::CHAT_STOP) {
            $this->startAndStop($container, $em, $output, $chatSettings
                    , ChatSettings::CHAT_STOP);
        } elseif ($isNight >= static::startHour &&
                $isNight <= static::endHour &&
                $chatSettings->getIsRunCommand() ==
                ChatSettings::CHAT_RUNNING) {
            $this->startAndStop($container, $em, $output, $chatSettings
                    , ChatSettings::CHAT_STOP);
        } elseif ($chatSettings->getIsRunCommand() == ChatSettings::CHAT_RUNNING) {
            $output->writeln("Server chat is running");
        } else {
            $output->writeln("Waiting");
        }
    }

    function startAndStop($container, $em, $output, $chatSettings, $req) {

        $rootPath = $container->getParameter('kernel.root_dir') . '/../';
        switch ($req) {
            case ChatSettings::CHAT_START:
                $consolePath = $container->getParameter('kernel.root_dir')
                        . '/console';
                // Detect if console binary is in new symfony 3 structure folder
                if (file_exists($container->getParameter('kernel.root_dir')
                                . '/../bin/console')) {
                    $consolePath = $container->getParameter('kernel.root_dir')
                            . '/../bin/console';
                }

                $commandline = "php " . $consolePath . " gos:websocket:server --env=prod  --pidfile="
                        . $rootPath . "chat.pid";
                $output->writeln("Starting server chat");

                shell_exec($commandline . ' > /dev/null & echo $!');

                $file = $rootPath . '/chat.pid';

                while (!file_exists($file)) {
                    sleep(3);
                    $pid = file_get_contents($file);
                    $chatSettings->setPid($pid);
                    $output->writeln("PID: " . $pid);
                }

                $chatSettings->setIsRunCommand(ChatSettings::CHAT_RUNNING);
                $em->persist($chatSettings);
                $em->flush();
                break;
            case ChatSettings::CHAT_STOP:
                $output->writeln("Stoping server chat");
                $pid = $chatSettings->getPid();
                $cmd = 'kill ' . $pid;
                $output->writeln($cmd);

                $chatSettings->setIsRunCommand(0);
                $chatSettings->setPid(null);

                $process = new Process($cmd);
                $process->start();

                $em->persist($chatSettings);
                $em->flush();
                break;
        }
    }

}
