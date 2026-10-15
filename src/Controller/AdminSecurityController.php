<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/** Управляет отдельным входом в административную часть. */
final class AdminSecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof AdminUser) {
            return $this->redirect('/admin');
        }

        return $this->render('@EasyAdmin/page/login.html.twig', [
            'page_title' => 'PHP Recall — администрирование',
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'csrf_token_intention' => 'authenticate',
            'username_label' => 'Email',
            'password_label' => 'Пароль',
            'sign_in_label' => 'Войти',
            'target_path' => '/admin',
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Выход обрабатывается firewall административной части.');
    }
}
