<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Позволяет авторизованному администратору изменить свой пароль. */
final class ChangePasswordController extends AbstractController
{
    #[AdminRoute(path: '/change-password', name: 'change_password', options: ['methods' => ['GET', 'POST']])]
    public function __invoke(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $admin = $this->getUser();
        if (!$admin instanceof AdminUser) {
            throw $this->createAccessDeniedException();
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $currentPassword = (string) $request->request->get('current_password');
            $newPassword = (string) $request->request->get('new_password');
            $passwordConfirmation = (string) $request->request->get('password_confirmation');

            if (!$this->isCsrfTokenValid('admin_change_password', (string) $request->request->get('_token'))) {
                $errors[] = 'Не удалось проверить форму. Обновите страницу и попробуйте снова.';
            }

            if (!$passwordHasher->isPasswordValid($admin, $currentPassword)) {
                $errors[] = 'Текущий пароль указан неверно.';
            }

            if (mb_strlen($newPassword) < 12) {
                $errors[] = 'Новый пароль должен содержать не менее 12 символов.';
            }

            if ($newPassword !== $passwordConfirmation) {
                $errors[] = 'Новый пароль и его подтверждение не совпадают.';
            }

            if ($errors === []) {
                $admin->setPassword($passwordHasher->hashPassword($admin, $newPassword));
                $entityManager->flush();

                $this->addFlash('success', 'Пароль успешно изменён.');

                return $this->redirectToRoute('admin_change_password');
            }
        }

        return $this->render('admin/change_password.html.twig', [
            'errors' => $errors,
        ]);
    }
}
