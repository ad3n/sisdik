<?php

namespace Fast\SisdikBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    
    /**
     * @Template()
     */
    public function indexAction() {
        // check school id. if not defined, disabled all?
        return array();
    }
}
