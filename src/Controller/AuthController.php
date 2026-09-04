<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CsrfTokens;
use App\Service\UserStore;
use PDOException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Управляет регистрацией, входом и выходом пользователя. */
final class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserStore $users, CsrfTokens $csrf): Response
    {
        if ($request->getSession()->has('user')) {
            return $this->redirectToRoute('app_home');
        }

        $errors = [];
        $email = trim((string) $request->request->get('email'));

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password');
            $confirmation = (string) $request->request->get('password_confirmation');

            if (!$csrf->isValid($request, 'register', (string) $request->request->get('_token'))) {
                $errors[] = 'Сессия формы устарела. Обновите страницу и попробуйте снова.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Введите корректный email.';
            }
            if (mb_strlen($password) < 8) {
                $errors[] = 'Пароль должен содержать не менее 8 символов.';
            }
            if ($password !== $confirmation) {
                $errors[] = 'Пароли не совпадают.';
            }

            if ($errors === []) {
                try {
                    $id = $users->create($email, $password);
                    $this->authenticate($request, $id, $email);

                    return $this->redirectToRoute('app_home');
                } catch (PDOException $exception) {
                    if (str_contains($exception->getMessage(), 'UNIQUE')) {
                        $errors[] = 'Пользователь с таким email уже зарегистрирован.';
                    } else {
                        throw $exception;
                    }
                }
            }
        }

        return $this->render('auth/register.html.twig', ['errors' => $errors, 'email' => $email, 'csrf_token' => $csrf->get($request, 'register')]);
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, UserStore $users, CsrfTokens $csrf): Response
    {
        if ($request->getSession()->has('user')) {
            return $this->redirectToRoute('app_home');
        }

        $error = null;
        $email = trim((string) $request->request->get('email'));

        if ($request->isMethod('POST')) {
            if (!$csrf->isValid($request, 'login', (string) $request->request->get('_token'))) {
                $error = 'Сессия формы устарела. Обновите страницу и попробуйте снова.';
            } else {
                $user = $users->findByEmail($email);
                if ($user === null || !password_verify((string) $request->request->get('password'), $user['password_hash'])) {
                    $error = 'Неверный email или пароль.';
                } else {
                    $this->authenticate($request, (int) $user['id'], (string) $user['email']);

                    return $this->redirectToRoute('app_home');
                }
            }
        }

        return $this->render('auth/login.html.twig', ['error' => $error, 'email' => $email, 'csrf_token' => $csrf->get($request, 'login')]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(Request $request, CsrfTokens $csrf): RedirectResponse
    {
        if ($csrf->isValid($request, 'logout', (string) $request->request->get('_token'))) {
            $request->getSession()->invalidate();
        }

        return $this->redirectToRoute('app_home');
    }

    private function authenticate(Request $request, int $id, string $email): void
    {
        $session = $request->getSession();
        $session->migrate(true);
        $session->set('user', ['id' => $id, 'email' => mb_strtolower($email)]);
    }
}
