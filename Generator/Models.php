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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Class Models
 * @package MTrimech\DocumentorBundle\Generator
 */
class Models extends AbstractGenerator implements GeneratorInterface
{
    /** @var EntityManager $entityManager */
    private $entityManager;

    /**
     * Models constructor.
     * @param ContainerInterface $container
     * @param SymfonyStyle $style
     */
    public function __construct(ContainerInterface $container, SymfonyStyle $style)
    {
        parent::__construct($container, $style);
        $this->entityManager = $container->get('doctrine')->getManager();
    }

    /**
     * @return mixed|void
     * @throws \Doctrine\Common\Annotations\AnnotationException
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
                $this->writeFile($this->createDir($bundle, 'Models'), '@MTrimechDocumentor/models.html.twig', [
                    'models' => $models,
                    'alphas' => $this->alphas,
                ]);
            }
        }
    }

    /**
     * @param \ReflectionClass $class
     * @return bool
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    function isEntity(\ReflectionClass $class)
    {
        $annotationReader = new AnnotationReader();

        $classAnnotations = $annotationReader->getClassAnnotations($class);

        return isset($classAnnotations['Doctrine\ORM\Mapping\Entity']);
    }
}