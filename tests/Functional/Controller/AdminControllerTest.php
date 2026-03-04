<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Controller\AdminController;

#[CoversClass(AdminController::class)]
class AdminControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\AlumniProfile')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    private function createUser(string $email, string $password, array $roles = ['ROLE_USER']): User
    {
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setIsActive(true);
        // REMOVE: $user->setCreatedAt(new \DateTime());
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function getToken(string $email, string $password): string
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['email' => $email, 'password' => $password]));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['data']['token'];
    }

    // ✅ Regular user cannot access admin routes
    public function testRegularUserCannotAccessAdminRoutes(): void
    {
        $this->createUser('user@test.com', 'Password1');
        $token = $this->getToken('user@test.com', 'Password1');

        $this->client->request('GET', '/api/admin/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('error', $data['status']);
    }

    // ✅ Admin can list all users
    public function testAdminCanListUsers(): void
    {
        $this->createUser('admin@test.com', 'Password1', ['ROLE_ADMIN']);
        $this->createUser('user@test.com', 'Password1', ['ROLE_USER']);
        $token = $this->getToken('admin@test.com', 'Password1');

        $this->client->request('GET', '/api/admin/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertCount(2, $data['data']);
    }

    // ✅ Admin can toggle user active status
    public function testAdminCanToggleUser(): void
    {
        $this->createUser('admin@test.com', 'Password1', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.com', 'Password1', ['ROLE_USER']);
        $token = $this->getToken('admin@test.com', 'Password1');

        $this->client->request('PATCH', '/api/admin/users/' . $user->getId() . '/toggle', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['data']['isActive']); // was active, now deactivated
    }

    // ✅ Admin can change user role
    public function testAdminCanChangeUserRole(): void
    {
        $this->createUser('admin@test.com', 'Password1', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.com', 'Password1', ['ROLE_USER']);
        $token = $this->getToken('admin@test.com', 'Password1');

        $this->client->request('PATCH', '/api/admin/users/' . $user->getId() . '/role', [], [], [
            'CONTENT_TYPE'      => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode(['role' => 'ROLE_ADMIN']));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertContains('ROLE_ADMIN', $data['data']['roles']);
    }

    // ✅ Admin cannot deactivate themselves
    public function testAdminCannotDeactivateSelf(): void
    {
        $admin = $this->createUser('admin@test.com', 'Password1', ['ROLE_ADMIN']);
        $token = $this->getToken('admin@test.com', 'Password1');

        $this->client->request('PATCH', '/api/admin/users/' . $admin->getId() . '/toggle', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    }

    // ✅ Returns 404 for non-existent user
    public function testReturns404ForMissingUser(): void
    {
        $this->createUser('admin@test.com', 'Password1', ['ROLE_ADMIN']);
        $token = $this->getToken('admin@test.com', 'Password1');

        $this->client->request('PATCH', '/api/admin/users/9999/toggle', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }
}