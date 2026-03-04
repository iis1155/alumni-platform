<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AuditEvent extends Event
{
    public const LOGIN_SUCCESS = 'audit.login_success';
    public const LOGIN_FAILED = 'audit.login_failed';
    public const PROFILE_UPDATED = 'audit.profile_updated';
    public const ROLE_CHANGED = 'audit.role_changed';
    public const USER_TOGGLED = 'audit.user_toggled';

    public function __construct(
        private string $action,
        private ?int $userId,
        private ?string $userEmail,
        private ?string $ipAddress,
        private ?string $details = null,
        )
    {
    }

    public function getAction(): string
    {
        return $this->action;
    }
    public function getUserId(): ?int
    {
        return $this->userId;
    }
    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }
    public function getDetails(): ?string
    {
        return $this->details;
    }
}