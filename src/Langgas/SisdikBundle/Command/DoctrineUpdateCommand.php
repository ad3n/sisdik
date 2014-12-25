<?php

namespace Langgas\SisdikBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineUpdateCommand extends UpdateSchemaDoctrineCommand
{
    protected $ignoredEntities = [
        'Langgas\SisdikBundle\Entity\TransaksiPembayaran',
    ];

    protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas)
    {
        /** @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $newMetadatas = [];
        foreach ($metadatas as $metadata) {
            if (!in_array($metadata->getName(), $this->ignoredEntities)) {
                array_push($newMetadatas, $metadata);
            }
        }

        parent::executeSchemaCommand($input, $output, $schemaTool, $newMetadatas);
    }
}
