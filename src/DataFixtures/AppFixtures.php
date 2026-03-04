<?php

namespace App\DataFixtures;

use App\Entity\AlumniProfile;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
        )
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Create test users
        $users = [
            [
                'email' => 'admin@alumni.com',
                'password' => 'Admin123',
                'firstName' => 'Admin',
                'lastName' => 'User',
                'roles' => ['ROLE_ADMIN'],
                'company' => 'DeeepLabs',
                'jobTitle' => 'CTO',
                'graduationYear' => 2010
            ],
            [
                'email' => 'john@alumni.com',
                'password' => 'John1234',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'roles' => ['ROLE_USER'],
                'company' => 'Google',
                'jobTitle' => 'Engineer',
                'graduationYear' => 2015
            ],
            [
                'email' => 'jane@alumni.com',
                'password' => 'Jane1234',
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'roles' => ['ROLE_USER'],
                'company' => 'Meta',
                'jobTitle' => 'Designer',
                'graduationYear' => 2018
            ],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setRoles($userData['roles']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $userData['password'])
            );

            $profile = new AlumniProfile();
            $profile->setUser($user);
            $profile->setCompany($userData['company']);
            $profile->setJobTitle($userData['jobTitle']);
            $profile->setGraduationYear($userData['graduationYear']);

            $manager->persist($user);
            $manager->persist($profile);

            // 💡 Store reference so other fixtures can use these users
            $this->addReference('user_' . $userData['email'], $user);
        }

        $manager->flush();
    }
}