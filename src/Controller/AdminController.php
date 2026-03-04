<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\AuditEvent;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use App\Service\AlumniService;
use App\Traits\ApiResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private AlumniService $alumniService,
        private EventDispatcherInterface $dispatcher  // 👈 ADD THIS
    ) {}

    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));

        $result = $this->userRepository->findPaginatedAll($page, $limit);

        $data = array_map(function (User $user) {
            return [
                ...$this->alumniService->formatProfile($user, $user->getProfile()),
                'isActive'  => $user->isActive(),
                'roles'     => $user->getRoles(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $result['data']);

        return $this->json([
            'status'  => 'success',
            'message' => 'Users retrieved',
            'data'    => $data,
            'meta'    => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => $result['pages']
            ]
        ]);
    }

    #[Route('/users/{id}/toggle', name: 'users_toggle', methods: ['PATCH'])]
    public function toggleUser(int $id, Request $request): JsonResponse  // 👈 ADD Request
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        if ($user === $this->getUser()) {
            return $this->errorResponse('You cannot deactivate your own account', 403);
        }

        $user->setIsActive(!$user->isActive());
        $this->em->flush();

        $status = $user->isActive() ? 'activated' : 'deactivated';

        // 🔥 Fire event
        $this->dispatcher->dispatch(
            new AuditEvent(
                AuditEvent::USER_TOGGLED,
                $user->getId(),
                $user->getEmail(),
                $request->getClientIp(),
                "User {$status}"
            ),
            AuditEvent::USER_TOGGLED
        );

        return $this->successResponse([
            'id'       => $user->getId(),
            'email'    => $user->getEmail(),
            'isActive' => $user->isActive()
        ], "User {$status} successfully");
    }

    #[Route('/users/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        if ($user === $this->getUser()) {
            return $this->errorResponse('You cannot delete your own account', 403);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->successResponse(null, 'User deleted successfully');
    }

    #[Route('/users/{id}/role', name: 'users_role', methods: ['PATCH'])]
    public function changeRole(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['role'])) {
            return $this->errorResponse('Role is required');
        }

        $allowedRoles = ['ROLE_USER', 'ROLE_ADMIN'];
        if (!in_array($data['role'], $allowedRoles)) {
            return $this->errorResponse('Invalid role. Allowed: ROLE_USER, ROLE_ADMIN', 422);
        }

        if ($user === $this->getUser()) {
            return $this->errorResponse('You cannot change your own role', 403);
        }

        $user->setRoles([$data['role']]);
        $this->em->flush();

        // 🔥 Fire event
        $this->dispatcher->dispatch(
            new AuditEvent(
                AuditEvent::ROLE_CHANGED,
                $user->getId(),
                $user->getEmail(),
                $request->getClientIp(),
                'Role changed to ' . $data['role']
            ),
            AuditEvent::ROLE_CHANGED
        );

        return $this->successResponse([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ], 'Role updated successfully');
    }

    #[Route('/logs', name: 'admin_logs', methods: ['GET'])]
    public function logs(Request $request, AuditLogRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page   = max(1, $request->query->getInt('page', 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $logs  = $repo->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $total = $repo->count([]);

        $data = array_map(fn($log) => [
            'id'        => $log->getId(),
            'action'    => $log->getAction(),
            'userId'    => $log->getUserId(),
            'userEmail' => $log->getUserEmail(),
            'ipAddress' => $log->getIpAddress(),
            'details'   => $log->getDetails(),
            'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $logs);

        return $this->json([
            'status'  => 'success',
            'message' => 'Audit logs retrieved',
            'data'    => $data,
            'meta'    => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
            ]
        ]);
    }
}