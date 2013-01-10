<?php

namespace Acme\BlogBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Acme\BlogBundle\Entity\Author;

/**
 * 
 * @Route("/blog")
 *
 */
class DefaultController extends Controller
{
    /**
     * @Route("/author")
     * @Template()
     */
    public function indexAction() {
        $this->get('session')->setLocale($this->container->getParameter('locale'));
        //         $t = $this->get('translator')->trans('author.name.not_blank');
        return array(
            'locale' => $this->get('session')->getLocale()
        );
        //         return new Response($this->get('session')->getLocale() . ': ' . $t);

        //         $author = new Author();
        //         $author->setName('ihsan');

        //         $validator = $this->get('validator');
        //         $errors = $validator->validate($author);
        //         if (count($errors) > 0) {
        //             return new Response(print_r($errors, true));
        //         } else {
        //             return new Response($author->getName() . ' The author is valid! Yes!');
        //         }
    }
}
