<?php

namespace Fast\SisdikBundle\Translator;

use JMS\TranslationBundle\Exception\RuntimeException;
use JMS\TranslationBundle\Model\FileSource;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Annotation\Meaning;
use JMS\TranslationBundle\Annotation\Desc;
use JMS\TranslationBundle\Annotation\Ignore;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Logger\LoggerAwareInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class EntityExtractor implements LoggerAwareInterface, FileVisitorInterface, \PHPParser_NodeVisitor
{
    private $traverser;
    private $catalogue;
    private $file;
    private $logger;

    public function __construct()
    {
        $this->traverser = new \PHPParser_NodeTraverser();
        $this->traverser->addVisitor($this);
    }

    /**
     * @param \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function enterNode(\PHPParser_Node $node)
    {
        if (!$node instanceof \PHPParser_Node_Scalar_String) {
            return;
        }

        $id = $node->value;

        if (preg_match('/(\.\.|\.\.\.)/', $id)) {
            return;
        }

        if (preg_match('/.*\./', $id)) {
            $domain = 'messages';
            $message = new Message($id, $domain);
            $message->addSource(new FileSource((string) $this->file, $node->getLine()));

            $this->catalogue->add($message);
        }
    }

    public function visitPhpFile(\SplFileInfo $file, MessageCatalogue $catalogue, array $ast)
    {
        $this->file = $file;
        $this->catalogue = $catalogue;
        if ($this->file->getPathInfo()->getFilename() == 'Entity') {
            $this->traverser->traverse($ast);
        }
    }

    public function beforeTraverse(array $nodes) { }
    public function leaveNode(\PHPParser_Node $node) { }
    public function afterTraverse(array $nodes) { }
    public function visitFile(\SplFileInfo $file, MessageCatalogue $catalogue) { }
    public function visitTwigFile(\SplFileInfo $file, MessageCatalogue $catalogue, \Twig_Node $ast) { }
}
