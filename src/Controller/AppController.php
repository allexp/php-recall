<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CsrfTokens;
use App\Service\ConceptCatalog;
use App\Service\FunctionCatalog;
use App\Service\ManualService;
use App\Service\MaterialStore;
use App\Service\SandboxClient;
use App\Service\UserStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Управляет главной страницей и публичными API тренажёра. */
final class AppController extends AbstractController
{
    /** Отображает интерфейс изучения функций PHP. */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(Request $request, CsrfTokens $csrf): Response
    {
        return $this->render('app/index.html.twig', [
            'logout_token' => $csrf->get($request, 'logout'),
            'knowledge_token' => $csrf->get($request, 'knowledge'),
            'sandbox_token' => $csrf->get($request, 'sandbox'),
        ]);
    }

    /** Выполняет PHP-код в отдельном одноразовом контейнере. */
    #[Route('/api/sandbox/run', name: 'api_sandbox_run', methods: ['POST'])]
    public function runSandbox(Request $request, SandboxClient $sandbox, CsrfTokens $csrf): JsonResponse
    {
        if (!$csrf->isValid($request, 'sandbox', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Сессия устарела. Обновите страницу.'], Response::HTTP_FORBIDDEN);
        }

        $runs = array_values(array_filter(
            (array) $request->getSession()->get('_sandbox_runs', []),
            static fn (mixed $time): bool => is_int($time) && $time > time() - 60,
        ));
        if (count($runs) >= 10) {
            return $this->json(['error' => 'Слишком много запусков. Повторите через минуту.'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        $runs[] = time();
        $request->getSession()->set('_sandbox_runs', $runs);

        try {
            $payload = $request->toArray();
            $result = $sandbox->run(is_string($payload['code'] ?? null) ? $payload['code'] : '');

            return $this->json($result);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return $this->json(['error' => 'Не удалось выполнить код в песочнице.'], Response::HTTP_BAD_GATEWAY);
        }
    }

    /** Возвращает доступные категории и названия функций. */
    #[Route('/api/catalog', name: 'api_catalog', methods: ['GET'])]
    public function catalog(FunctionCatalog $catalog): JsonResponse
    {
        return $this->json($catalog->all());
    }

    /** Возвращает категории и карточки концепций разработки. */
    #[Route('/api/concepts', name: 'api_concepts', methods: ['GET'])]
    public function concepts(ConceptCatalog $catalog): JsonResponse
    {
        return $this->json($catalog->all());
    }

    /** Возвращает полное описание концепции с раскрываемыми подсекциями. */
    #[Route('/api/concepts/{slug}', name: 'api_concept', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function concept(string $slug, ConceptCatalog $catalog): JsonResponse
    {
        $material = $catalog->get($slug);
        if ($material === null) {
            return $this->json(['error' => 'Концепция не найдена.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'title' => $material['title'],
            'definition' => $material['definition'],
            'short_description' => $material['short_description'],
            'full_description' => $material['full_description'],
            'source_url' => $material['source_url'],
            'code_examples' => $material['code_examples'],
            'sections' => $material['sections'],
        ]);
    }

    /**
     * Возвращает краткое и полное описание функции из PHP Manual.
     *
     * @param string $function Имя функции без круглых скобок
     */
    #[Route('/api/manual/{function}', name: 'api_manual', requirements: ['function' => '[a-z0-9_]+'], methods: ['GET'])]
    public function manual(
        string $function,
        FunctionCatalog $catalog,
        MaterialStore $materials,
        ManualService $manual,
    ): JsonResponse
    {
        if (!$catalog->contains($function)) {
            return $this->json(['error' => 'Функция не найдена в каталоге.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $documentation = $materials->functionDocumentation($function);
            if ($documentation === null) {
                $loaded = $manual->fetch($function);
                $materials->saveFunctionDocumentation(
                    $function,
                    $loaded['definition'],
                    $loaded['short_description'],
                    $loaded['full_description'],
                    $loaded['source_url'],
                );
                $documentation = $materials->functionDocumentation($function);
            }

            if ($documentation === null) {
                throw new \RuntimeException('Не удалось сохранить документацию функции.');
            }

            $summary = '<p>'.htmlspecialchars(
                (string) $documentation['short_description'],
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8',
            ).'</p>';
            $summary .= '<pre><code>'.htmlspecialchars(
                (string) $documentation['definition'],
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8',
            ).'</code></pre>';

            return $this->json([
                'definition' => $documentation['definition'],
                'short_description' => $documentation['short_description'],
                'summary' => $summary,
                'full' => $documentation['full_description'],
                'source' => $documentation['source_url'],
            ]);
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    /** Возвращает сохранённый прогресс авторизованного пользователя. */
    #[Route('/api/knowledge', name: 'api_knowledge', methods: ['GET'])]
    public function knowledge(Request $request, UserStore $users): JsonResponse
    {
        $user = $request->getSession()->get('user');
        if (!is_array($user) || !isset($user['id'])) {
            return $this->json(['error' => 'Необходима авторизация.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'functions' => $users->knownFunctions((int) $user['id']),
            'concepts' => $users->knownConcepts((int) $user['id']),
        ]);
    }

    /** Изменяет отметку «знаю» для функции. */
    #[Route('/api/knowledge/{function}', name: 'api_knowledge_update', requirements: ['function' => '[a-z0-9_]+'], methods: ['PUT', 'DELETE'])]
    public function updateKnowledge(
        string $function,
        Request $request,
        FunctionCatalog $catalog,
        UserStore $users,
        CsrfTokens $csrf,
    ): JsonResponse {
        $user = $request->getSession()->get('user');
        if (!is_array($user) || !isset($user['id'])) {
            return $this->json(['error' => 'Необходима авторизация.'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$csrf->isValid($request, 'knowledge', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Сессия устарела. Обновите страницу.'], Response::HTTP_FORBIDDEN);
        }
        if (!$catalog->contains($function)) {
            return $this->json(['error' => 'Функция не найдена в каталоге.'], Response::HTTP_NOT_FOUND);
        }

        $users->setFunctionKnown((int) $user['id'], $function, $request->isMethod('PUT'));

        return $this->json(['functions' => $users->knownFunctions((int) $user['id'])]);
    }

    /** Изменяет отметку «знаю» для концепции. */
    #[Route('/api/knowledge/concept/{slug}', name: 'api_concept_knowledge_update', requirements: ['slug' => '[a-z0-9-]+'], methods: ['PUT', 'DELETE'])]
    public function updateConceptKnowledge(
        string $slug,
        Request $request,
        ConceptCatalog $catalog,
        UserStore $users,
        CsrfTokens $csrf,
    ): JsonResponse {
        $user = $request->getSession()->get('user');
        if (!is_array($user) || !isset($user['id'])) {
            return $this->json(['error' => 'Необходима авторизация.'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$csrf->isValid($request, 'knowledge', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Сессия устарела. Обновите страницу.'], Response::HTTP_FORBIDDEN);
        }
        if ($catalog->get($slug) === null) {
            return $this->json(['error' => 'Концепция не найдена.'], Response::HTTP_NOT_FOUND);
        }

        $users->setConceptKnown((int) $user['id'], $slug, $request->isMethod('PUT'));

        return $this->json(['concepts' => $users->knownConcepts((int) $user['id'])]);
    }
}
