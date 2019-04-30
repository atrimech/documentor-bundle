<?php
/**
 * This file is part of Documentor.
 * Created by MTrimech
 * Date: 4/29/19
 * Time: 11:43 AM
 * @author: Mahdi Trimech Labs <trimechmehdi11@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MTrimech\DocumentorBundle\Generator;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class Routers
 * @package MTrimech\DocumentorBundle\Generator
 */
class Routers extends AbstractGenerator implements GeneratorInterface
{
    /** @var RouterInterface $router */
    private $router;

    /**
     * Routers constructor.
     * @param ContainerInterface $container
     * @param SymfonyStyle $style
     */
    public function __construct(ContainerInterface $container, SymfonyStyle $style)
    {
        parent::__construct($container, $style);
        $this->router = $container->get('router');
    }

    /**
     * @return mixed|void
     * @throws \ReflectionException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function generate()
    {
        $i18nInstalled = $this->checkBundleInstalled($this->bundles, 'JMSI18nRoutingBundle');
        /** @var array $routers */
        $routers = [];
        /** @var array $undefined */
        $undefined = [];

        /**
         * @var string $routeName
         * @var Route $route
         */
        foreach ($this->router->getRouteCollection() as $routeName => $route) {
            $controllerParams = explode('::', $route->getDefault('_controller'));
            if (count($controllerParams) === 1) {
                $controllerParams = explode(':', $route->getDefault('_controller'));
            }
            $controller = $controllerParams[0];
            $action = $controllerParams[1];
            if (strpos($controller, '.')) {
                $controller = get_class($this->container->get(explode(':', $controller)[0]));
            }

            $bundleNameSpace = strstr($controller, '\Controller', true);
            if (class_exists($controller)) {
                $reflectionClass = new \ReflectionClass($controller);
                if (!$reflectionClass->hasMethod($action)) {
                    $undefined[$bundleNameSpace][$action] = $controller;
                    continue;
                }
            }

            $routeName = $i18nInstalled ? strstr($routeName, 'RG__') : $routeName;
            $routeName = str_replace('RG__', '', $routeName);

            $routers[$bundleNameSpace][$routeName] = [
                'path' => str_replace(['{', '}'], [':', ':'], $route->getPath()),
                'methods' => implode(', ', array_merge($route->getMethods(), ['GET'])),
                'controller' => explode('\\', $controller),
                'controllerNameSpace' => $controller,
                'action' => $action,
                'exception' => $this->checkThrowException($controller, $action)
            ];
        }
        if (count($routers)) {
            /** @var BundleInterface $bundle */
            foreach ($this->bundles as $bundle) {
                if (!array_key_exists($bundle->getNamespace(), $undefined)) {
                    $undefined[$bundle->getNamespace()] = [];
                }

                /** If the Bundle contain 0 routers, dont need to create directory and generate documents */
                if (!array_key_exists($bundle->getNamespace(), $routers)) {
                    continue;
                }

                $this->writeFile($bundle, $this->createDir($bundle, 'Routers'), '@MTrimechDocumentor/routers.html.twig', [
                    'routers' => $routers[$bundle->getNamespace()],
                    'undefined' => $undefined[$bundle->getNamespace()],
                    'bundle' => $bundle,
                ]);
            }
        }
    }
}