<?php

namespace Kijho\ChatBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArgvInput;

class PidFileEventListener {

    public function onConsoleCommand(ConsoleCommandEvent $event) {

        $definition = $event->getCommand()->getDefinition();
        // add the option to the application's input definition
        $definition->addOption(new InputOption('pidfile', null, InputOption::VALUE_OPTIONAL, 'The location of the PID file that should be created for this process', null));
        // merge the application's input definition
        $event->getCommand()->mergeApplicationDefinition();
        // the input object will read the actual arguments from $_SERVER['argv']
        $input = new ArgvInput();
        // bind the application's input definition to it

        $option = array('--pidfile');

        if (true === $input->hasParameterOption($option)) {
            $input->bind($definition);
            
            $pidFile = $input->getParameterOption($option);
            if ($pidFile !== null) {
                file_put_contents($pidFile, getmypid());
            }
        }
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event) {
        $option = array('--pidfile');
        $input = $event->getInput();
//        \Symfony\Component\VarDumper\VarDumper::dump($input);
        if (true === $input->hasParameterOption($option)) {
            $pidFile = $input->getParameterOption($option);

            if ($pidFile !== null) {
                unlink($pidFile);
            }
        }
    }

}
