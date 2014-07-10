<?php
namespace Langgas\SisdikBundle\Translator;

use JMS\TranslationBundle\Model\FileSource;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;

/**
 * Mengekstrak scalar string dari entity dengan pola string terpisah titik, contoh: label.hari.senin
 */
class EntityExtractor implements FileVisitorInterface, \PHPParser_NodeVisitor
{
    private $traverser;
    private $catalogue;
    private $file;

    public function __construct()
    {
        $this->traverser = new \PHPParser_NodeTraverser();
        $this->traverser->addVisitor($this);
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
