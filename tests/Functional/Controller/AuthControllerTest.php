<?php

namespace App\Tests\Functional\Controller;

use App\Controller\AuthController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


#[CoversClass(AuthController::class)]
class AuthControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        // 💡 Creates a test HTTP client
        $this->client = static::createClient();

        // Load fresh fixtures before each test
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        // Clean database before each test
        $em->createQuery('DELETE FROM App\Entity\AlumniProfile')->execute();
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    // 💡 Helper to make JSON requests
    private function jsonRequest(string $method, string $url, array $data = [], string $token = ''): array
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($token) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request(
            $method,
            $url,
        [],
        [],
            $headers,
            json_encode($data)
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testRegisterSuccess(): void
    {
        $response = $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'newuser@alumni.com',
            'password' => 'Password123',
            'firstName' => 'New',
            'lastName' => 'User'
        ]);

        // Assert HTTP status 201
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Registration successful', $response['message']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertEquals('newuser@alumni.com', $response['data']['email']);
    }

    public function testRegisterFailsWithInvalidEmail(): void
    {
        $response = $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'notanemail',
            'password' => 'Password123',
            'firstName' => 'New',
            'lastName' => 'User'
        ]);

        $this->assertEquals(422, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('email', $response['data']['errors']);
    }

    public function testRegisterFailsWithDuplicateEmail(): void
    {
        // Register first time
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'duplicate@alumni.com',
            'password' => 'Password123',
            'firstName' => 'Test',
            'lastName' => 'User'
        ]);

        // Register second time with same email
        $response = $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'duplicate@alumni.com',
            'password' => 'Password123',
            'firstName' => 'Test',
            'lastName' => 'User'
        ]);

        $this->assertEquals(409, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Email already registered', $response['message']);
    }

    public function testLoginSuccess(): void
    {
        // Register first
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'login@alumni.com',
            'password' => 'Password123',
            'firstName' => 'Login',
            'lastName' => 'User'
        ]);

        // Then login
        $response = $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'login@alumni.com',
            'password' => 'Password123'
        ]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('token', $response['data']); // ← JWT token exists
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        // Register first
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'test@alumni.com',
            'password' => 'Password123',
            'firstName' => 'Test',
            'lastName' => 'User'
        ]);

        // Login with wrong password
        $response = $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'test@alumni.com',
            'password' => 'WrongPassword123'
        ]);

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Invalid credentials', $response['message']);
    }

    public function testMeRequiresToken(): void
    {
        // Hit /me without token
        $this->client->request('GET', '/api/auth/me');

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
    }

    public function testMeReturnsUserWithValidToken(): void
    {
        // Register and login to get token
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'me@alumni.com',
            'password' => 'Password123',
            'firstName' => 'Me',
            'lastName' => 'User'
        ]);

        $loginResponse = $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'me@alumni.com',
            'password' => 'Password123'
        ]);

        $token = $loginResponse['data']['token'];

        // Hit /me with token
        $response = $this->jsonRequest('GET', '/api/auth/me', [], $token);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('me@alumni.com', $response['data']['email']);
    }
}// temp