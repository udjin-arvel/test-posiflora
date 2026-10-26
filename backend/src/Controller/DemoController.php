<?php

namespace App\Controller;

use App\Service\Order\DemoResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemoController extends AbstractController
{
    public function __construct(
        private readonly DemoResetService $demoReset,
    ) {
    }

    #[Route('/api/demo/reset', name: 'api_demo_reset', methods: ['POST'])]
    public function reset(): JsonResponse
    {
        $this->demoReset->reset();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
