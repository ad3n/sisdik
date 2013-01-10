<?php
namespace Fast\SisdikBundle\Controller;
use FOS\UserBundle\Controller\ProfileController as FOSProfileController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * 
 * @author Ihsan Faisal
 *
 */
class ProfileController extends FOSProfileController
{
    /**
     * Show the user
     */
    public function showAction() {
        $user = $this->container->get('security.context')->getToken()->getUser();

        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        foreach ($user->getRoles() as $keys => $values) {
            $roles[] = str_replace('_', ' ', $values);
        }

        return $this->container->get('templating')
                ->renderResponse('FastSisdikBundle:Profile:show.html.twig',
                        array(
                            'user' => $user, 'roles' => $roles, 'name' => $user->getName()
                        ));
    }

    /**
     * Edit the user
     */
    //     public function editAction() {
    //         $user = $this->container->get('security.context')->getToken()->getUser();
    //         if (!is_object($user) || !$user instanceof UserInterface) {
    //             throw new AccessDeniedException('This user does not have access to this section.');
    //         }

    //         $form = $this->container->get('fos_user.profile.form'); // $this->createForm(new UserFormType($this->container), $user);
    //         $formHandler = $this->container->get('fos_user.profile.form.handler');

    //         $process = $formHandler->process($user);
    //         if ($process) {
    //             $this->setFlash('success', 'flash.profile.updated');

    //             return new RedirectResponse($this->getRedirectionUrl($user));
    //         }

    //         return $this->container->get('templating')
    //                 ->renderResponse(
    //                         'FOSUserBundle:Profile:edit.html.'
    //                                 . $this->container->getParameter('fos_user.template.engine'),
    //                         array(
    //                             'form' => $form->createView()
    //                         ));
    //     }
}
