<?php

namespace NelmioApiDocGenerator\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use NelmioApiDocGenerator\Services\NelmioApiDocGenerator;

class NelmioApiDocGeneratorCommand extends ContainerAwareCommand
{
    private $apiDocGenerator;

    protected static $defaultName = 'kbunel:nelmioApiDoc:generate';

    public function __construct(NelmioApiDocGenerator $apiDocGenerator)
    {
        $this->apiDocGenerator = $apiDocGenerator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Generate doc on routes')
            ->addArgument('path', InputArgument::REQUIRED)
            ->addArgument('controllerAction', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');

        $controllerAction = $input->getArgument('controllerAction');
        if (!preg_match('/^[a-zA-Z0-9]+::[a-zA-Z0-9]$/', $controllerAction)) {
            $output->writeln('<comment>Wrong argument, controller path must be `controller::action`, eg: `SecurityController::login`</comment>');
        }

        $this->apiDocGenerator->generate($path, $controllerAction);
    }
}
