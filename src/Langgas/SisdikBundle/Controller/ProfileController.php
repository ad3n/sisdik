<?php

namespace Langgas\SisdikBundle\Controller;

use FOS\UserBundle\Controller\ProfileController as FOSProfileController;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProfileController extends FOSProfileController
{
    public function showAction()
    {
        $user = $this->container
            ->get('security.context')
            ->getToken()
            ->getUser()
        ;

        if (! is_object($user) || ! $user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $roles = [];
        foreach ($user->getRoles() as $keys => $values) {
            $values = str_replace('ROLE_', '', $values);
            $roles[] = str_replace('_', ' ', $values);
        }

        return $this->container
            ->get('templating')
            ->renderResponse('LanggasSisdikBundle:Profile:show.html.twig', [
                'user' => $user,
                'roles' => $roles,
                'name' => $user->getName(),
            ])
        ;
    }

    public function editAction()
    {
        $user = $this->container
            ->get('security.context')
            ->getToken()
            ->getUser()
        ;
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
                        $message = $this->container->get('translator')->trans('alert.username.numeric.forstudent');

                        $form->get('username')->addError(new FormError($message));
                    }
                }

                $userManager->updateUser($user);

                $this->setFlash('fos_user_success', 'profile.flash.updated');

                $userManager->reloadUser($user);

                return new RedirectResponse($this->getRedirectionUrl($user));
            }
        }

        return $this->container
            ->get('templating')
            ->renderResponse('FOSUserBundle:Profile:edit.html.'.$this->container->getParameter('fos_user.template.engine'), [
                'form' => $form->createView(),
            ])
        ;
    }
}
