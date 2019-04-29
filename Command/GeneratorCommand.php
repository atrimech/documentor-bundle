<?php

namespace MTrimech\DocumentorBundle\Command;

use MTrimech\DocumentorBundle\Generator\AbstractGenerator;
use MTrimech\DocumentorBundle\Generator\Commands;
use MTrimech\DocumentorBundle\Generator\Models;
use MTrimech\DocumentorBundle\Generator\Routers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GeneratorCommand
 * @package MTrimech\DocumentorBundle\Command
 */
class GeneratorCommand extends ContainerAwareCommand
{
    /**
     * Configure Command
     */
    protected function configure()
    {
        $this
            ->setName('mtrimech:documentor:generate')
            ->setDescription(
                'Wwrite some README files under each parsed bundle that contains a simple 
                documentation of routing, models and commands.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generators = [
            Models::class,
            Routers::class,
            Commands::class
        ];

        $container = $this->getContainer();

        /** @var AbstractGenerator $generator */
        foreach ($generators as $generator) {
            (new $generator($container))->generate();
        }
    }
}
