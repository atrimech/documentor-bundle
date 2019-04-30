<?php
/**
 * This file is part of Documentor.
 * Created by MTrimech
 * Date: 4/29/19
 * Time: 11:47 AM
 * @author: Mahdi Trimech Labs <trimechmehdi11@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MTrimech\DocumentorBundle\Generator;


use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Class AbstractGenerator
 * @package MTrimech\DocumentorBundle\Generator
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var object|\Twig\Environment
     */
    protected $twig;

    /**
     * @var Finder
     */
    protected $finder;
    /**
     * @var ContainerInterface
     */
    protected $container;

    /** @var string */
    protected $targetDir = '/Resources/docs';

    /** @var array $alphas */
    protected $alphas = [];

    /** @var array $bundles */
    protected $bundles = [];
    /** @var SymfonyStyle $style */
    protected $style;

    /**
     * AbstractGenerator constructor.
     * @param ContainerInterface $container
     * @param SymfonyStyle $style
     */
    public function __construct(ContainerInterface $container, SymfonyStyle $style)
    {
        $this->bundles = $container->get('kernel')->getBundles();
        $this->fileSystem = new Filesystem();
        $this->finder = new Finder();
        $this->container = $container;

        $this->alphas = range('A', 'Z');
        $this->twig = $container->get('twig');
        $this->style = $style;
    }

    /**
     * @param $fileName
     * @param $method
     * @param string $checker
     * @return string
     * @throws \ReflectionException
     */
    protected function getFunctionContent($fileName, $method, $checker = 'return')
    {
        if (!class_exists($fileName)) {
            return '';
        }

        $reflectionClass = new \ReflectionClass($fileName);
        if (!$reflectionClass->hasMethod($method)) {
            return '';
        }

        $func = new \ReflectionMethod($fileName, $method);

        $f = $func->getFileName();
        $start_line = $func->getStartLine() - 1;
        $end_line = $func->getEndLine();

        $source = file($f);
        $source = implode('', array_slice($source, 0, count($source)));
        $source = preg_split("/(\n|\r\n|\r)/", $source);

        $body = '';
        for ($i = $start_line; $i < $end_line; $i++) {
            if (strpos($source[$i], $checker)) {
                $body .= preg_replace('#\n|\t|\r#', '', $source[$i]);
            }
        }

        return $body;
    }

    /**
     * @param $controllerClassName
     * @param $actionName
     * @return array
     * @throws \ReflectionException
     */
    protected function checkThrowException($controllerClassName, $actionName)
    {
        foreach (['throw new', '$this->createNotFoundException', 'createAccessDeniedException'] as $checker) {
            $content = $this->getFunctionContent($controllerClassName, $actionName, $checker);
            if (!empty($content)) {
                return ['exist' => true, 'line' => strstr(str_replace('$this->', '', $content), '(', true)];
            }
        }

        return ['exist' => false];
    }

    /**
     * @param BundleInterface $bundle
     * @param string $dir
     * @return string
     */
    protected function createDir(BundleInterface $bundle, $dir = 'Models')
    {
        $target = $bundle->getPath() . $this->targetDir . '/' . $dir;
        if (!$this->fileSystem->exists($target)) {
            mkdir($target, 0777, true);
        }

        return $target;
    }

    /**
     * @param $bundles
     * @param $bundle
     * @return bool
     */
    protected function checkBundleInstalled($bundles, $bundle)
    {
        return array_key_exists($bundle, $bundles);
    }

    /**
     * @param BundleInterface $bundle
     * @param string $directory
     * @param string $view
     * @param array $parameters
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function writeFile(BundleInterface $bundle, $directory, $view, array $parameters = [])
    {
        $dirs = explode('/', $directory);
        if (!$this->style->confirm(sprintf('Generating docs for %s. Do you confirm?', $bundle->getNamespace() . $dirs[count($dirs) - 1]), true)) {
            return;
        }

        $fileName = $this->checkDestinationFile($directory);
        file_put_contents($fileName, $this->twig->render($view, $parameters));
    }

    /**
     * @param $directory
     * @return array|bool|mixed|null|string
     */
    private function checkDestinationFile($directory)
    {
        $fileName = $directory . '/README.md';

        if ($this->fileSystem->exists($fileName)) {
            $answer = $directory . '/' . $this->style->askQuestion(new Question('The output file README.md already exist. Please enter another name', 'README.md'));

            if (!strpos($answer, '.md')) {
                $answer .= '.md';
            }

            if ($this->fileSystem->exists($answer)) {
                return $this->checkDestinationFile($directory);
            }

            return $answer;
        }

        return $fileName;
    }
}