<?php

namespace NelmioApiDocGenerator\Services;

use App\Command\Services\FileAnalyzer;
use App\Command\Services\FileAnalyzed;
use App\Command\Services\ControllerInformations;
use App\Command\Services\Tools;
use Doctrine\Common\Annotations\AnnotationReader;
use App\Command\Services\Logger;

class NelmioApiDocGenerator
{
    private const CONTROLLER_PATH       = __DIR__ . '/../../../src';
    private const SWAG_TPL              = __DIR__.'/../Resources/views/swag.tpl.php';

    private const SWG_SERVICE           = 'Swagger\Annotations as SWG';
    private const NELMIO_MODEL_SERVICE  = 'Nelmio\ApiDocBundle\Annotation\Model';

    private $skipped = [];

    private $fileAnalyzer;
    private $tools;
    private $logger;
    private $filesystem;

    public function __construct(FileAnalyzer $fileAnalyzer, Tools $tools, Logger $logger, Filesystem $filesystem)
    {
        $this->fileAnalyzer = $fileAnalyzer;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->tools = $tools;
    }

    public function generate(string $controllerAction = null): void
    {
        $controllersFiles = array_filter($this->fileAnalyzer->analyze(self::CONTROLLER_PATH), function($file) use ($controllerAction) {
            if ($controllerAction) {
                $controller = explode('::', $controllerAction)[0];

                return $file->kind == FileAnalyzer::FILE_KIND_CONTROLLER
                    && preg_match('/\\\\' . $controller . '$/', $file->originNamespace);
            }

            return $file->kind == FileAnalyzer::FILE_KIND_CONTROLLER;
        });

        $controllersInformations = $this->getControllersInformations($controllersFiles, $controllerAction);

        $this->updateControllers($controllersInformations);
        $this->showStats($controllersInformations);
        $this->log();
    }

    /**
     * @param ControllerInformations[]
     */
    private function showStats(array $controllersInformations): void
    {
        $totalControllers = count($controllersInformations);
        $modelsFounds = 0;
        $groupsFound = 0;

        foreach ($controllersInformations as $controllerInformations) {
            $modelsFounds += $controllerInformations->model ? 1 : 0;
            $groupsFound += $controllerInformations->groups ? 1 : 0;
        }

        $this->logger->writeln('<comment>' . $modelsFounds . '/' . $totalControllers . ' models found.</comment>');
        $this->logger->writeln('<comment>' . $groupsFound . '/' . $totalControllers . ' groups found.</comment>');
    }

    private function log(): void
    {
        $this->logger->writeln('<info>' . count($this->skipped) . ' actions skipped.</info>');
        foreach ($this->skipped as $skip) {
            $this->logger->writeln($skip);
        }
    }

    /**
     * @param ControllerInformations[]
     */
    private function updateControllers(array $controllersInformations): void
    {
        $annotationReader = new AnnotationReader();

        $this->logger->writeln('<info>Adding Swag annotations for Nelmio Api Doc in controllers</info>');
        $this->logger->startProgressBar(count($controllersInformations));

        foreach ($controllersInformations as $controllerInformations) {

            $annotations = $annotationReader->getMethodAnnotations($controllerInformations->method);
            $swgs = array_filter($annotations, function($annotation) {
                return preg_match('/^Swagger/', get_class($annotation));
            });

            if ($swgs) {
                continue;
            }

            $this->addApiDocToController($controllerInformations);
            $this->logger->advanceProgressBar();
        }
        $this->logger->finishProgressBar();
    }

    private function addApiDocToController(ControllerInformations $controllerInformations): void
    {
        $class = $controllerInformations->method->class;
        $name = $controllerInformations->method->name;

        if (!$this->isASingleRoute($controllerInformations->method)) {
            $this->skipped[] = '<comment>' . $class . ':' . $name . ' skipped, is not a single route.</comment>';

            return;
        }

        $swgTemplate = $this->parseTemplate(self::SWAG_TPL, (array)$controllerInformations);
        $swgTemplateLines = explode("\n", $swgTemplate);
        foreach ($swgTemplateLines as $key => $swgTemplateLine) {
            $swgTemplateLines[$key] = "     " . $swgTemplateLine . "\n";
        }

        $fileName = $controllerInformations->method->getFileName();
        $lines = file($fileName);

        if (is_null($offset = $this->getOffset($controllerInformations->method))) {
            $this->skipped[] = '<comment>' . $class . ':' . $name . ' skipped, seems not to be a route.</comment>';

            return;
        }

        array_splice($lines, $offset, 0, $swgTemplateLines);

        $services = $this->fileAnalyzer->getServices($fileName);
        foreach ([self::NELMIO_MODEL_SERVICE, self::SWG_SERVICE] as $apiDocService) {
            if (!in_array($apiDocService, $services)) {
                $lines = $this->addServiceInLines($lines, $apiDocService);
            }
        }

        $this->filesystem->dumpFile($filePath, $content);
    }

    private function isASingleRoute(\ReflectionMethod $method): bool
    {
        $lines = file($method->getFileName());
        foreach ($lines as $key => $line) {
            $line = trim($line);

            if (preg_match('/^public function ' . $method->name . '/', $line)) {
                $route = 0;
                while(!empty(trim($lines[$key]))) {
                    $key--;
                    if (preg_match('/(@get|@post|@put|@delete|@path|@fos\\\\get|@fos\\\\put|@fos\\\\post|@fos\\\\delete|@fos\\\\patch)/', strtolower($lines[$key - 1]))) {
                        $route++;
                    }
                }

                return $route == 1;
            }
        }
    }

    private function getOffset(\ReflectionMethod $method): ?int
    {
        $lines = file($method->getFileName());
        foreach ($lines as $key => $line) {
            $line = trim($line);

            if (preg_match('/^public function ' . $method->name . '/', $line)) {
                while(!empty(trim($lines[$key]))) {
                    $key--;
                    if (preg_match('/\*/', $lines[$key])) {
                        break;
                    }
                }

                return !empty($lines[$key]) ? $key - 1 : null;
            }
        }
    }

    private function addServiceInLines(array $lines, string $service): array
    {
        $lastUsed = 0;
        foreach ($lines as $key => $line) {
            $line = trim($line);

            if (preg_match('/^use/', $line)) {
                $lastUsed = $key;
            }

            if (preg_match('/^class/', $line)) {
                break;
            }
        }

        array_splice($lines, $lastUsed + 1, 0, "use " . $service . ";\n");

        return $lines;
    }

    /**
     * @param FileAnalyzed[]
     *
     * @return ControllerInformations[]
     */
    private function getControllersInformations(array $controllersFiles, ?string $controllerAction): array
    {
        $controllersInformations = [];
        foreach ($controllersFiles as $controllersFile) {
            $controllerClass = new \ReflectionClass($controllersFile->originNamespace);

            $methods = array_filter($controllerClass->getMethods(), function($method) use ($controllersFile, $controllerAction) {
                if ($controllerAction) {
                    $action = explode('::', $controllerAction)[1];

                    return $method->isPublic()
                        && $method->class == $controllersFile->originNamespace
                        && $action == $method->name;
                }

                return $method->isPublic()
                    && $method->class == $controllersFile->originNamespace;
            });

            foreach ($methods as $method) {
                $controllersInformations[] = $this->getControllerInformations($method);
            }
        }

        return $controllersInformations;
    }

    private function getControllerInformations(\ReflectionMethod $method): ControllerInformations
    {
        $ci = new ControllerInformations();
        $ci->method = $method;

        $ci->description = ucfirst($this->tools->camelCaseToSpace(str_replace('Action', '', $method->name)));

        $ar = explode('\\', $method->class);
        $ci->tag = str_replace('Controller', '', $ar[count($ar) - 1]);

        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $length = $endLine - $startLine;
        $source = file($filename);
        $lines = array_slice($source, $startLine, $length);

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/[a-zA-Z0-9]+Type::class/', $line, $match)) {
                if (!preg_match('/\\\\+/', $match[0])) {
                    $service = $this->getServicesAssociated(explode('::', $match[0])[0], $method->getFileName());
                    $ci->model = $service . '::class';
                } else {
                    $ci->model = $match[0];
                }
            }

            if (preg_match('/(getSerializedView|setSerializationContext)/', $line)) {
                if (preg_match('/\[[\', a-zA-Z0-9\-]+\]/', $line, $match)) {
                    $ci->groups = str_replace('\'', '"', str_replace(['[', ']'], '', $match[0]));
                }
            }

            if (preg_match('/(^return |getSerializedView|\$this->view)/', $line) and preg_match('/[0-9]+/', $line, $match)) {
                $httpResponseCode = intval($match[0]);
                if ($httpResponseCode >= 200 && $httpResponseCode < 300) {
                    $ci->httpResponseCode = $httpResponseCode;
                }
            }
        }

        if (!$ci->model) {
            $ci->model = $this->getModelFromVariableReturned($lines, $method);
        }

        return $ci;
    }

    private function getModelFromVariableReturned(array $lines, \ReflectionMethod $method): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/getSerializedView/', $line)) {
                $varName = explode(', ', preg_replace('/\).+$/', '', explode('(', $line)[1]))[0];

                break;
            }
        }

        if (!isset($varName)) {
            foreach ($lines as $line) {
                if (preg_match('/\$this->view\(([a-zA-Z0-9 ,]+)\)/', $line, $match)) {
                    $varName = trim(explode(',', $match[1])[0]);

                    break;
                }
            }
        }

        if (!isset($varName) || $varName == 'null') {
            return null;
        }

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() == str_replace('$', '', $varName)) {
                return $parameter->getType()->getName() . '::class';
            }
        }

        return null;
    }

    private function getServicesAssociated(string $serviceName, $filePath): string
    {
        $services = $this->fileAnalyzer->getServices($filePath);
        foreach ($services as $service) {
            if (preg_match('/' . $serviceName . '$/', $service)) {
                return $service;
            }
        }
    }

    public function parseTemplate(string $templatePath, array $parameters): string
    {
        ob_start();
        extract($parameters, EXTR_SKIP);
        include $templatePath;

        return ob_get_clean();
    }
}
