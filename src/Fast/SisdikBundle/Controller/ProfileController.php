<?php
namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Form\FormError;
use FOS\UserBundle\Controller\ProfileController as FOSProfileController;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserManagerInterface;

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
    public function editAction() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $userManager = $this->container->get('fos_user.user_manager');

        $form = $this->container->get('fos_user.profile.form');
        $form->setData($user);

        $formHandler = $this->container->get('fos_user.profile.form.handler');

        if ('POST' === $this->container->get('request')->getMethod()) {
            $form->submit($this->container->get('request'));

            if ($form->isValid()) {
                $data = $form->getData();
                if (!in_array('ROLE_SISWA', $data->getRoles(), true)) {
                    if (is_numeric($data->getUsername())) {
                        $message = $this->container->get('translator')
                                ->trans('alert.username.numeric.forstudent');
                        $form->get('username')->addError(new FormError($message));
                    }
                }

                $userManager->updateUser($user);

                $this->setFlash('fos_user_success', 'profile.flash.updated');
            }

            $userManager->reloadUser($user);

            return new RedirectResponse($this->getRedirectionUrl($user));
        }

        return $this->container->get('templating')
                ->renderResponse(
                        'FOSUserBundle:Profile:edit.html.'
                                . $this->container->getParameter('fos_user.template.engine'),
                        array(
                            'form' => $form->createView()
                        ));

    }
}
