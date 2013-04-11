<?php
namespace Fast\SisdikBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 *
 * @author Ihsan Faisal
 *
 */
class SettingsController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction() {
        return $this->redirect($this->generateUrl('settings_user'));
    }
}
