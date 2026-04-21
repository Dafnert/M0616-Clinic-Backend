<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\userRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\user;


#[Route(path: '/user')]
final class userController extends AbstractController
{
    #[Route('/', name: 'app_user_create', methods: ['POST'])]
    public function createuser(Request $request, userRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            !isset($data['name']) ||
            !isset($data['surname']) ||
            !isset($data['age']) ||
            !isset($data['speciality']) ||
            !isset($data['username']) ||
            !isset($data['password'])
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required fields'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new user();
        $user->setName($data['name']);
        $user->setSurname($data['surname']);
        $user->setAge($data['age']);
        $user->setSpeciality($data['speciality']);
        $user->setUsername($data['username']);
        $user->setPassword($data['password']);

        $userRepository->save($user, true);

        return $this->json([
            'success' => true,
            'message' => "user '{$user->getName()}' created successfully",
            'data' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'age' => $user->getAge(),
                'speciality' => $user->getSpeciality(),
                'username' => $user->getUsername(),
            ]
        ], Response::HTTP_CREATED);
    }
}