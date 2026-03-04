<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api/alumni', name:'alumni_')]
class AlumniController extends AbstractController
{
    // 💡 Best Practice: Each method handles ONE action only
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'data' => [],
            'message' => 'Alumni list endpoint'
        ]);
    }

    #[Route('/{id}',name:'show', methods:['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'data' => ['id' => $id],
            'message' => 'Alumni retrieved successfully' 
        ]);
    }
}