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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Class Models
 * @package MTrimech\DocumentorBundle\Generator
 */
class Models extends AbstractGenerator implements GeneratorInterface
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
            $path = $bundle->getPath() . '/Entity';

            if (!$this->fileSystem->exists($path)) {
                continue;
            }

            $target = $this->createDir($bundle, 'Models');

            /** @var array $models */
            $models = [];

            /** @var SplFileInfo $file */
            foreach ($this->finder->files()->in($path) as $file) {
                $fileName = str_replace('.php', '', $file->getFilename());
                $classNameSpace = $bundle->getNamespace() . '\Entity\\' . $fileName;

                if (!class_exists($classNameSpace)) {
                    continue;
                }

                $reflection = new \ReflectionClass($classNameSpace);

                if (!$this->isEntity($reflection)) {
                    continue;
                }

                /** @var \Doctrine\ORM\Mapping\ClassMetadata $classMetaData */
                $classMetaData = $this->entityManager->getClassMetadata($reflection->getName());

                $models[] = [
                    'classMetaData' => $classMetaData,
                    'model' => $fileName
                ];
            }

            if (count($models)) {
                file_put_contents($target . '/README.md', $this->twig->render('@MTrimechDocumentor/models.html.twig', [
                    'models' => $models,
                    'alphas' => $this->alphas,
                ]));
            }
        }
    }

    /**
     * @param \ReflectionClass $class
     * @return mixed
     */
    function isEntity(\ReflectionClass $class)
    {
        $annotationReader = new AnnotationReader();

        $classAnnotations = $annotationReader->getClassAnnotations($class);

        return isset($classAnnotations['Doctrine\ORM\Mapping\Entity']);
    }
}