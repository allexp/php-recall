<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

/** Создаёт и проверяет CSRF-токены, привязанные к пользовательской сессии. */
final class CsrfTokens
{
    public function get(Request $request, string $action): string
    {
        $session = $request->getSession();
        $tokens = $session->get('_csrf_tokens', []);

        if (!isset($tokens[$action])) {
            $tokens[$action] = bin2hex(random_bytes(32));
            $session->set('_csrf_tokens', $tokens);
        }

        return $tokens[$action];
    }

    public function isValid(Request $request, string $action, string $token): bool
    {
        return $token !== '' && hash_equals($this->get($request, $action), $token);
    }
}
