<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;


#[Route(path: '/user')]
final class UserController extends AbstractController
{
    #[Route('', name: 'user_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        $data = array_map(fn($u) => [
            'id'       => $u->getId(),
            'name'     => $u->getName(),
            'username' => $u->getUsername(),
            'role'     => $u->getRole(),
        ], $users);

        return $this->json($data);
    }

    #[Route('/login', name: 'app_patient_login', methods: ['POST'])]

    public function login(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        // Validación básica
        if (empty($username) || empty($password)) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Username and password are required',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Buscar usuario
        $user = $userRepository->findOneBy(['username' => $username]);

        // Verificar credenciales
        if ($user && $user->getPassword() === $password) {
            return $this->json(
                [
                    'success' => true,
                    'message' => 'Success',
                    'user' => [
                        'id' => $user->getId(),
                        'name' => $user->getName(),
                        'age' => $user->getAge(),
                        'username' => $user->getUsername(),
                        'role' => $user->getRole(),
                    ]
                ],
                Response::HTTP_OK
            );
        }

        return $this->json(
            [
                'success' => false,
                'message' => 'Invalid credentials',
            ],
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route('/{id}', name: 'app_user_get', methods: ['GET'])]
    public function getUserById(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'age' => $user->getAge(),
                'speciality' => $user->getSpeciality(),
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_user_update', methods: ['PUT'])]
    public function updateUser(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name']))       $user->setName($data['name']);
        if (isset($data['surname']))    $user->setSurname($data['surname']);
        if (isset($data['age']))        $user->setAge($data['age']);
        if (isset($data['speciality'])) $user->setSpeciality($data['speciality']);
        if (isset($data['username']))   $user->setUsername($data['username']);
        if (isset($data['password']) && !empty($data['password'])) {
            $user->setPassword($data['password']);
        }
        if (isset($data['role']) && in_array($data['role'], ['ADMIN', 'DOCTOR'])) {
            $user->setRole($data['role']);
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'age' => $user->getAge(),
                'speciality' => $user->getSpeciality(),
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
            ]
        ], Response::HTTP_OK);
    }

    #[Route('', name: 'app_user_create', methods: ['POST'])]
    public function createuser(Request $request, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validar que los campos requeridos estén presentes
        if (empty($data['name']) || empty($data['username']) || empty($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Name, username and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar username único
        if ($userRepository->findOneBy(['username' => $data['username']])) {
            return $this->json([
                'success' => false,
                'message' => 'Username already exists'
            ], Response::HTTP_CONFLICT);
        }

        // Validar nombre único
        if ($userRepository->findOneBy(['name' => $data['name']])) {
            return $this->json([
                'success' => false,
                'message' => 'Name already exists'
            ], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setName($data['name']);
        $user->setSurname($data['surname'] ?? '');
        $user->setAge($data['age'] ?? 0);
        $user->setSpeciality($data['speciality'] ?? '');
        $user->setUsername($data['username']);
        $user->setPassword($data['password']);
        $user->setRole($data['role'] ?? 'DOCTOR');

        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id'       => $user->getId(),
                'name'     => $user->getName(),
                'username' => $user->getUsername(),
                'role'     => $user->getRole(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    public function deleteUser(int $id, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) return new JsonResponse(['error' => 'Not found'], 404);

        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
