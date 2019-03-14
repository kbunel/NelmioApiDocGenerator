<?php

namespace NelmioApiDocGenerator\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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
            ->addArgument('path', InputArgument::OPTIONAL)
            ->addOption('route', null, InputOption::VALUE_OPTIONAL, 'Generate a route specifying Controller::Action')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path') ?? 'src';

        $controllerAction = $input->getOption('route');
        if ($controllerAction && !preg_match('/^[a-zA-Z0-9\\\\]+::[a-zA-Z0-9]+$/', $controllerAction)) {
            $output->writeln('<comment>Wrong route, The route must be formated `namespace::action`, eg: `App\Controller\SecurityController::login`</comment>');

            return;
        }

        $this->apiDocGenerator->generate($path, $controllerAction);
    }
}
