<?php

namespace App\Controller;

use App\Service\Order\OrderListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersController extends AbstractController
{
    public function __construct(
        private readonly OrderListService $orderList,
    ) {
    }

    #[Route('/api/orders', name: 'api_orders', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $status = $request->query->get('status');
            $statusParam = is_string($status) ? $status : null;

            return $this->json($this->orderList->list($statusParam));
        } catch (\InvalidArgumentException) {
            return $this->json(['message' => 'Неизвестный статус'], Response::HTTP_BAD_REQUEST);
        }
    }
}
