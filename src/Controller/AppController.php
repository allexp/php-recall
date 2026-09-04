<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CsrfTokens;
use App\Service\FunctionCatalog;
use App\Service\ManualService;
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
        return $this->render('app/index.html.twig', ['logout_token' => $csrf->get($request, 'logout')]);
    }

    /** Возвращает доступные категории и названия функций. */
    #[Route('/api/catalog', name: 'api_catalog', methods: ['GET'])]
    public function catalog(FunctionCatalog $catalog): JsonResponse
    {
        return $this->json($catalog->all());
    }

    /**
     * Возвращает краткое и полное описание функции из PHP Manual.
     *
     * @param string $function Имя функции без круглых скобок
     */
    #[Route('/api/manual/{function}', name: 'api_manual', requirements: ['function' => '[a-z0-9_]+'], methods: ['GET'])]
    public function manual(string $function, FunctionCatalog $catalog, ManualService $manual): JsonResponse
    {
        if (!$catalog->contains($function)) {
            return $this->json(['error' => 'Функция не найдена в каталоге.'], Response::HTTP_NOT_FOUND);
        }

        try {
            return $this->json($manual->get($function));
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
