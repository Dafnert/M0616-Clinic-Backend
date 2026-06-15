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

final class PatientController extends AbstractController
{
    #[Route('/patient', name: 'app_patient_create', methods: ['POST'])]
    public function createPatient(Request $request, PatientRepository $patientRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['age'], $data['username'], $data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Name, age, username, and password are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar username único
        if ($patientRepository->findOneBy(['username' => $data['username']])) {
            return $this->json([
                'success' => false,
                'message' => 'Username already exists',
            ], Response::HTTP_CONFLICT);
        }

        // Validar nombre único
        if ($patientRepository->findOneBy(['name' => $data['name']])) {
            return $this->json([
                'success' => false,
                'message' => 'Patient name already exists',
            ], Response::HTTP_CONFLICT);
        }

        // Validar apellido único (si se proporciona)
        if (!empty($data['surname']) && $patientRepository->findOneBy(['surname' => $data['surname']])) {
            return $this->json([
                'success' => false,
                'message' => 'Patient surname already exists',
            ], Response::HTTP_CONFLICT);
        }

        try {
            $patient = new Patient();
            $patient->setName($data['name']);
            $patient->setSurname($data['surname'] ?? null);
            $patient->setAge($data['age']);
            $patient->setDni($data['dni'] ?? '');
            $patient->setUsername($data['username']);
            $patient->setPassword($data['password']);
            $patient->setDisease($data['disease'] ?? null);
            $patient->setAlergias($data['alergias'] ?? null);
            $patient->setObservations($data['observations'] ?? '');
            $patient->setAcceptedPrivacy($data['acceptedPrivacy'] ?? false);
            $patient->setAcceptedAnesthesia($data['acceptedAnesthesia'] ?? false);
            $entityManager->persist($patient);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Patient created successfully',
                'patient' => [
                    'id'       => $patient->getId(),
                    'name'     => $patient->getName(),
                    'age'      => $patient->getAge(),
                    'dni'      => $patient->getDni(),
                    'username' => $patient->getUsername(),
                    'disease'  => $patient->getDisease(),
                    'alergias' => $patient->getAlergias(),
                    'observations' => $patient->getObservations(),
                    'acceptedPrivacy' => $patient->getAcceptedPrivacy(),
                    'acceptedAnesthesia' => $patient->getAcceptedAnesthesia(),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage() // te mostrará el error real si algo más falla
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/patient/{id}', name: 'app_patient_read', methods: ['GET'], requirements: ['id' => '\d+'])]

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
                    'surname' => $patient->getSurname(),
                    'age' => $patient->getAge(),
                    'username' => $patient->getUsername(),
                    'dni' => $patient->getDni(),
                    'disease' => $patient->getDisease(),
                    'alergias' => $patient->getAlergias(),
                    'observations' => $patient->getObservations(),
                    'acceptedPrivacy' => $patient->getAcceptedPrivacy(),
                    'acceptedAnesthesia' => $patient->getAcceptedAnesthesia(),
                ]
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/patient/{id}', name: 'app_patient_update', methods: ['PUT'])]
    #[Route('/api/patients/{id}', name: 'api_patient_update', methods: ['PUT'])]
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

        if (isset($data['name']))     $patient->setName($data['name']);
        if (isset($data['surname']))  $patient->setSurname($data['surname']);
        if (isset($data['age']))      $patient->setAge($data['age']);
        if (isset($data['username'])) $patient->setUsername($data['username']);
        if (isset($data['password'])) $patient->setPassword($data['password']);

        $entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'message' => 'Patient updated successfully',
                'patient' => [
                    'id' => $patient->getId(),
                    'name' => $patient->getName(),
                    'surname' => $patient->getSurname(),
                    'age' => $patient->getAge(),
                    'username' => $patient->getUsername(),
                    'dni' => $patient->getDni(),
                    'disease' => $patient->getDisease(),
                ]
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/patient/{id}', name: 'app_patient_delete', methods: ['DELETE'])]
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

        // Borrar documentos del paciente
        $documents = $entityManager->getRepository(\App\Entity\Document::class)->findBy(['patient' => $patient]);
        foreach ($documents as $doc) {
            $entityManager->remove($doc);
        }

        // Borrar odontogramas del paciente
        $odontograms = $entityManager->getRepository(\App\Entity\Odontogram::class)->findBy(['patient' => $patient]);
        foreach ($odontograms as $odontogram) {
            $entityManager->remove($odontogram);
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

    #[Route('/patient/list', name: 'get_all_patients', methods: ['GET'])]
    #[Route('/api/patients', name: 'api_patients_list', methods: ['GET'])]
    public function getAllPatients(PatientRepository $patientRepository): JsonResponse
    {
        try {

            $patients = $patientRepository->findAll();

            $data = [];

            foreach ($patients as $patient) {

                $data[] = [
                    'id'       => $patient->getId(),
                    'name'     => $patient->getName(),
                    'surname'  => $patient->getSurname(),
                    'age'      => $patient->getAge(),
                    'dni'      => $patient->getDni(),
                    'disease'  => $patient->getDisease(),
                    'alergias' => $patient->getAlergias(),
                    'observations' => $patient->getObservations(),
                    'acceptedPrivacy' => $patient->getAcceptedPrivacy(),
                    'acceptedAnesthesia' => $patient->getAcceptedAnesthesia(),
                    'isVih'    => strtolower((string) $patient->getDisease()) === 'vih',
                    
                ];
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {

            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/patient/search', name: 'app_patient_search', methods: ['GET'])]
    public function searchByName(Request $request, PatientRepository $patientRepository): JsonResponse
    {
        $name = $request->query->get('name');

        if (!$name) {
            return $this->json([
                'success' => false,
                'message' => 'Name parameter is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $patients = $patientRepository->createQueryBuilder('p')
                ->where('p.name LIKE :name OR p.surname LIKE :name')
                ->setParameter('name', '%' . $name . '%')
                ->getQuery()
                ->getResult();

            if (empty($patients)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No patients found',
                ], Response::HTTP_NOT_FOUND);
            }

            $data = [];
            foreach ($patients as $patient) {
                $data[] = [
                    'id'       => $patient->getId(),
                    'name'     => $patient->getName(),
                    'surname'  => $patient->getSurname(),
                    'age'      => $patient->getAge(),
                    'dni'      => $patient->getDni(),
                    'disease'  => $patient->getDisease(),
                    'alergias' => $patient->getAlergias(),
                    'observations' => $patient->getObservations(),
                    'acceptedPrivacy' => $patient->getAcceptedPrivacy(),
                    'acceptedAnesthesia' => $patient->getAcceptedAnesthesia(),
                    'isVih'    => strtolower((string) $patient->getDisease()) === 'vih',
                ];
            }

            return $this->json([
                'success' => true,
                'count' => count($data),
                'patients' => $data,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}