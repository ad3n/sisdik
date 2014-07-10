<?php

namespace Langgas\SisdikBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class TranslatorCompilerPass implements CompilerPassInterface
{
    private $kernel;

    public function __construct(KernelInterface $kernel) {
        $this->kernel = $kernel;
    }

    public function process(ContainerBuilder $container) {
        if ('test' === $this->kernel->getEnvironment()) {
            $definition = $container->getDefinition('translator.default');
            $definition->setClass('Langgas\SisdikBundle\Translator\NoTranslator');
        }
    }
}
