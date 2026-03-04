<?php

namespace App\EventListener;

use App\Entity\AuditLog;
use App\Event\AuditEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: AuditEvent::LOGIN_SUCCESS)]
#[AsEventListener(event: AuditEvent::LOGIN_FAILED)]
#[AsEventListener(event: AuditEvent::PROFILE_UPDATED)]
#[AsEventListener(event: AuditEvent::ROLE_CHANGED)]
#[AsEventListener(event: AuditEvent::USER_TOGGLED)]
class AuditListener
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function __invoke(AuditEvent $event): void
    {
        $log = new AuditLog();
        $log->setAction($event->getAction());
        $log->setUserId($event->getUserId());
        $log->setUserEmail($event->getUserEmail());
        $log->setIpAddress($event->getIpAddress());
        $log->setDetails($event->getDetails());
        $log->setCreatedAt(new \DateTime());

        $this->em->persist($log);
        $this->em->flush();
    }
}