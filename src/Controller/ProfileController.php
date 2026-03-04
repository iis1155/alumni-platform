<?php

namespace App\Controller;

use App\DTO\UpdateProfileRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AlumniService;
use App\Traits\ApiResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/profile', name: 'profile_')]
class ProfileController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private AlumniService $alumniService,   // ← only service needed
        private UserRepository $userRepository,
        private ValidatorInterface $validator
    ) {}

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->successResponse(
            $this->alumniService->formatProfile($user, $user->getProfile()),
            'Profile retrieved'
        );
    }

    #[Route('/me', name: 'update_me', methods: ['PUT'])]
    public function updateMe(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->errorResponse('Invalid JSON body');
        }

        $dto = $this->mapToDTO($data, new UpdateProfileRequest());
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        /** @var User $user */
        $user = $this->getUser();

        // 💡 One line — all logic in service
        $this->alumniService->updateProfile($user, $dto, $data);

        return $this->successResponse(
            $this->alumniService->formatProfile($user, $user->getProfile()),
            'Profile updated successfully'
        );
    }

    #[Route('/alumni', name: 'alumni_list', methods: ['GET'])]
    public function alumniList(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        $search = $request->query->get('search');

        $result = $this->alumniService->getPaginatedAlumni($page, $limit, $search);

        $data = array_map(
            fn(User $user) => $this->alumniService->formatProfile($user, $user->getProfile()),
            $result['data']
        );

        return $this->json([
            'status' => 'success',
            'message' => 'Alumni list retrieved',
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => $result['pages'],
                'search' => $search
            ]
        ]);
    }

    #[Route('/alumni/{id}', name: 'alumni_show', methods: ['GET'])]
    public function alumniShow(int $id): JsonResponse
    {
        $user = $this->userRepository->findActiveById($id);

        if (!$user) {
            return $this->errorResponse('Alumni not found', 404);
        }

        return $this->successResponse(
            $this->alumniService->formatProfile($user, $user->getProfile()),
            'Alumni retrieved'
        );
    }
}