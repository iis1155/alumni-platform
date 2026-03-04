<?php

namespace App\Controller;

use App\DTO\RegisterRequest;
use App\Service\AuthService;
use App\Traits\ApiResponseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\User;
use App\Event\AuditEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/api/auth', name: 'auth_')]
class AuthController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private AuthService $authService,       // ← only service needed now
        private ValidatorInterface $validator,
        private EventDispatcherInterface $dispatcher,
        //private RateLimiterFactory $loginLimiterFactory  
        private RateLimiterFactory $loginLimiterLimiter
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->errorResponse('Invalid JSON body');
        }

        $dto = $this->mapToDTO($data, new RegisterRequest());
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        // 🔒 Check duplicate email
        if ($this->authService->isEmailTaken($dto->email)) {
            return $this->errorResponse('Email already registered', 409);
        }

        // 💡 One line — all logic in service
        $user = $this->authService->register($dto);

        return $this->successResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName()
        ], 'Registration successful', 201);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $limiter = $this->loginLimiterLimiter->create($request->getClientIp());
        $limit   = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            return $this->errorResponse('Too many login attempts. Try again in 1 minute.', 429);
        }
        
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'], $data['password'])) {
            return $this->errorResponse('Missing email or password');
        }

        // 💡 One line — all logic in service
        $user = $this->authService->verifyCredentials($data['email'], $data['password']);

        if (!$user) {
            $this->dispatcher->dispatch(
                new AuditEvent(
                    AuditEvent::LOGIN_FAILED,
                    null,
                    $data['email'],
                    $request->getClientIp(),
                    'Invalid credentials'
                ),
                AuditEvent::LOGIN_FAILED
            );
            return $this->errorResponse('Invalid credentials', 401);
        }

        if (!$user->isActive()) {
            return $this->errorResponse('Account is disabled', 403);
        }

        $token = $jwtManager->create($user);

        // 🔥 Fire success login event
        $this->dispatcher->dispatch(
            new AuditEvent(
                AuditEvent::LOGIN_SUCCESS,
                $user->getId(),
                $user->getEmail(),
                $request->getClientIp()
            ),
            AuditEvent::LOGIN_SUCCESS
        );
    
        return $this->successResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles()
            ]
        ], 'Login successful');
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->successResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s')
        ], 'Authenticated user retrieved');
    }
}