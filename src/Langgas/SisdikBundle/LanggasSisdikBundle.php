<?php

namespace Langgas\SisdikBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Langgas\SisdikBundle\DependencyInjection\Compiler\TranslatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class LanggasSisdikBundle extends Bundle
{
    private $kernel;

    public function __construct(KernelInterface $kernel) {
        $this->kernel = $kernel;
    }

    public function boot() {
        $em = $this->container->get('doctrine')->getManager();
        $platform = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
    }

    public function build(ContainerBuilder $container) {
        parent::build($container);

        $container->addCompilerPass(new TranslatorCompilerPass($this->kernel));
    }

    public function getParent() {
        return 'FOSUserBundle';
    }
}
