<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route(path: '/patient')]
final class PatientController extends AbstractController
{
    #[Route('/login', name: 'app_patient_login', methods: ['POST'])]

public function login(Request $request, PatientRepository $patientRepository): JsonResponse
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
    $patient = $patientRepository->findOneBy(['username' => $username]);

    // Verificar credenciales
    if ($patient && $patient->getPassword() === $password) {
        return $this->json(
        [
            'success' => true,
            'message' => 'Success',
            'patient' => [
                'id' => $patient->getId(),
                'name' => $patient->getName(),
                'age' => $patient->getAge(),
                'username' => $patient->getUsername(),
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

    #[Route('', name: 'app_patient_create', methods: ['POST'])]
    public function createPatient(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['age'], $data['username'], $data['password'])) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Name, age, username, and password are required',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $patient = new Patient();
        $patient->setName($data['name']);
        $patient->setAge($data['age']);
        $patient->setUsername($data['username']);
        $patient->setPassword($data['password']);

        $entityManager->persist($patient);
        $entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'message' => 'Patient created successfully',
                'patient' => [
                    'id' => $patient->getId(),
                    'name' => $patient->getName(),
                    'age' => $patient->getAge(),
                    'username' => $patient->getUsername(),
                ]
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'app_patient_read', methods: ['GET'])]
    public function readById(int $id, PatientRepository $patientRepository): JsonResponse
    {
        $patient = $patientRepository->find($id);

        if (!$patient) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Patient not found',
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json(
            [
                'success' => true,
                'patient' => [
                    'id' => $patient->getId(),
                    'name' => $patient->getName(),
                    'age' => $patient->getAge(),
                    'username' => $patient->getUsername(),
                ]
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'app_patient_update', methods: ['PUT'])]
    public function updatePatient(int $id, Request $request, PatientRepository $patientRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $patient = $patientRepository->find($id);

        if (!$patient) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Patient not found',
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $patient->setName($data['name']);
        }
        if (isset($data['age'])) {
            $patient->setAge($data['age']);
        }
        if (isset($data['username'])) {
            $patient->setUsername($data['username']);
        }
        if (isset($data['password'])) {
            $patient->setPassword($data['password']);
        }

        $entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'message' => 'Patient updated successfully',
                'patient' => [
                    'id' => $patient->getId(),
                    'name' => $patient->getName(),
                    'age' => $patient->getAge(),
                    'username' => $patient->getUsername(),
                ]
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'app_patient_delete', methods: ['DELETE'])]
    public function deleteById(int $id, PatientRepository $patientRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $patient = $patientRepository->find($id);

        if (!$patient) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Patient not found',
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $entityManager->remove($patient);
        $entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'message' => 'Patient deleted successfully',
            ],
            Response::HTTP_OK
        );
    }
}