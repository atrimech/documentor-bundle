<?php
/**
 * This file is part of Documentor.
 * Created by Mobelite
 * Date: 4/29/19
 * Time: 11:43 AM
 * @author: Mobelite Labs <contact@mobelite.fr>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MTrimech\DocumentorBundle\Generator;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Class Commands
 * @package MTrimech\DocumentorBundle\Generator
 */
class Commands extends AbstractGenerator implements GeneratorInterface
{
    /** @var array $bundles */
    private $bundles = [];

    /** @var EntityManager $entityManager */
    private $entityManager;

    /**
     * Models constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->entityManager = $container->get('doctrine')->getManager();
        $this->bundles = $container->get('kernel')->getBundles();
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
        /** @var BundleInterface $bundle */
        foreach ($this->bundles as $bundle) {
            $directory = $bundle->getPath() . '/Command';

            if (!$this->fileSystem->exists($directory)) {
                continue;
            }
            /** @var array $commands */
            $commands = [];

            $prefix = $bundle->getNamespace() . '\\Command';
            /** @var SplFileInfo $file */
            foreach ($this->finder->in($bundle->getPath() . '/Command')->files()->name('*Command.php') as $file) {
                $ns = $prefix;
                if ($relativePath = $file->getRelativePath()) {
                    $ns .= '\\' . str_replace('/', '\\', $relativePath);
                }
                $class = $ns . '\\' . $file->getBasename('.php');

                if ($this->container) {
                    $commandIds = $this->container->hasParameter('console.command.ids') ? $this->container->getParameter('console.command.ids') : array();
                    $alias = 'console.command.'.strtolower(str_replace('\\', '_', $class));
                    if (isset($commandIds[$alias]) || $this->container->has($alias)) {
                        continue;
                    }
                }

                if (!class_exists($class)) {
                    continue;
                }
                $r = new \ReflectionClass($class);

                if ($r->isSubclassOf('Symfony\\Component\\Console\\Command\\Command') && !$r->isAbstract() && !$r->getConstructor()->getNumberOfRequiredParameters()) {
                    @trigger_error(sprintf('Auto-registration of the command "%s" is deprecated since Symfony 3.4 and won\'t be supported in 4.0. Use PSR-4 based service discovery instead.', $class), E_USER_DEPRECATED);

                    /** @var Command $command */
                    $command = $r->newInstance();

                    $commands[] = $command;
                }
            }

            if (count($commands)) {
                $target = $this->createDir($bundle, 'Command');
                file_put_contents($target . '/README.md', $this->twig->render('@MTrimechDocumentor/commands.html.twig', [
                    'commands' => $commands,
                    'bundle' => $bundle
                ]));
            }

        }
    }
}