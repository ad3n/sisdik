<?php

namespace Fast\SisdikBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FastSisdikBundle extends Bundle
{
    public function boot() {
        $em = $this->container->get('doctrine')->getManager();
        $platform = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
    }

    public function getParent() {
        return 'FOSUserBundle';
    }
}
