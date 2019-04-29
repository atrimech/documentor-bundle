<?php
/**
 * This file is part of Documentor.
 * Created by Mobelite
 * Date: 4/29/19
 * Time: 11:47 AM
 * @author: Mobelite Labs <contact@mobelite.fr>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MTrimech\DocumentorBundle\Generator;


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

    /**
     * AbstractGenerator constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->fileSystem = new Filesystem();
        $this->finder = new Finder();
        $this->container = $container;

        $this->alphas = range('A', 'Z');
        $this->twig = $container->get('twig');
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
}