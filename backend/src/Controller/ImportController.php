<?php

namespace App\Controller;

use App\Service\Import\ImportService;
use App\Service\Import\InvalidImportPayloadException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImportController extends AbstractController
{
    public function __construct(
        private readonly ImportService $importService,
    ) {
    }

    #[Route('/api/imports', name: 'api_imports', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['message' => 'Файл должен содержать массив orders'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($this->importService->import($payload));
        } catch (InvalidImportPayloadException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
