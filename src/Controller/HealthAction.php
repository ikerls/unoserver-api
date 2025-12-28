<?php

declare(strict_types = 1);

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health', name: 'api_health', methods: ['GET'])]
#[OA\Get(
    description: 'Returns the health status of the API.',
    summary: 'Health check endpoint',
    tags: ['Health'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Service is healthy',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'ok'),
                ],
            ),
        ),
    ],
)]
final class HealthAction extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
        ]);
    }
}
