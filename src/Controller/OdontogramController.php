<?php

namespace App\Controller;

use App\Entity\Odontogram;
use App\Repository\OdontogramRepository;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/odontogram')]
final class OdontogramController extends AbstractController
{

    #[Route('/patient/{patientId}', name: 'odontogram_by_patient', methods: ['GET'])]
    public function getByPatient(
        int $patientId,
        PatientRepository $patientRepository,
        OdontogramRepository $odontogramRepository
    ): JsonResponse {
        $patient = $patientRepository->find($patientId);

        if (!$patient) {
            return $this->json([
                'success' => false,
                'message' => 'Patient not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $records = $odontogramRepository->findByPatientId($patientId);

        $data = array_map(fn(Odontogram $o) => $this->odontogramToArray($o), $records);

        return $this->json([
            'success' => true,
            'message' => 'Odontogram retrieved successfully',
            'data'    => $data,
        ], Response::HTTP_OK);
    }

   
    #[Route('/{id}', name: 'odontogram_show', methods: ['GET'])]
    public function show(int $id, OdontogramRepository $odontogramRepository): JsonResponse
    {
        $odontogram = $odontogramRepository->find($id);

        if (!$odontogram) {
            return $this->json([
                'success' => false,
                'message' => 'Odontogram record not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'message' => 'Odontogram record retrieved successfully',
            'data'    => $this->odontogramToArray($odontogram),
        ], Response::HTTP_OK);
    }

 
    #[Route('', name: 'odontogram_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        PatientRepository $patientRepository,
        OdontogramRepository $odontogramRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON body',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Required fields
        if (!isset($data['patientId'], $data['toothNumber'], $data['status'])) {
            return $this->json([
                'success' => false,
                'message' => 'patientId, toothNumber and status are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate patient
        $patient = $patientRepository->find((int) $data['patientId']);
        if (!$patient) {
            return $this->json([
                'success' => false,
                'message' => 'Patient not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Validate tooth number (FDI notation)
        $toothNumber = (int) $data['toothNumber'];
        if (!$this->isValidToothNumber($toothNumber)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid tooth number. Use FDI notation: 11-18, 21-28, 31-38, 41-48',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate status
        if (!in_array($data['status'], Odontogram::VALID_STATUSES, true)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid status. Allowed values: ' . implode(', ', Odontogram::VALID_STATUSES),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Prevent duplicates: one record per tooth per patient
        $existing = $odontogramRepository->findOneByPatientAndTooth($patient->getId(), $toothNumber);
        if ($existing) {
            return $this->json([
                'success' => false,
                'message' => sprintf('Tooth %d already has a record for this patient. Use PUT to update it.', $toothNumber),
            ], Response::HTTP_CONFLICT);
        }

        try {
            $odontogram = new Odontogram();
            $odontogram->setPatient($patient);
            $odontogram->setToothNumber($toothNumber);
            $odontogram->setStatus($data['status']);
            $odontogram->setNotes($data['notes'] ?? null);

            $entityManager->persist($odontogram);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Odontogram record created successfully',
                'data'    => $this->odontogramToArray($odontogram),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error creating odontogram record: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'odontogram_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        OdontogramRepository $odontogramRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $odontogram = $odontogramRepository->find($id);

        if (!$odontogram) {
            return $this->json([
                'success' => false,
                'message' => 'Odontogram record not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON body',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], Odontogram::VALID_STATUSES, true)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid status. Allowed values: ' . implode(', ', Odontogram::VALID_STATUSES),
                ], Response::HTTP_BAD_REQUEST);
            }
            $odontogram->setStatus($data['status']);
        }

        if (array_key_exists('notes', $data)) {
            $odontogram->setNotes($data['notes']);
        }

        try {
            $odontogram->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Odontogram record updated successfully',
                'data'    => $this->odontogramToArray($odontogram),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating odontogram record: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

 
    #[Route('/{id}', name: 'odontogram_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        OdontogramRepository $odontogramRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $odontogram = $odontogramRepository->find($id);

        if (!$odontogram) {
            return $this->json([
                'success' => false,
                'message' => 'Odontogram record not found',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $entityManager->remove($odontogram);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Odontogram record deleted successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting odontogram record: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

   

   
    private function odontogramToArray(Odontogram $odontogram): array
    {
        return [
            'id'          => $odontogram->getId(),
            'toothNumber' => $odontogram->getToothNumber(),
            'status'      => $odontogram->getStatus(),
            'notes'       => $odontogram->getNotes(),
            'createdAt'   => $odontogram->getCreatedAt()->format(DATE_ATOM),
            'updatedAt'   => $odontogram->getUpdatedAt()?->format(DATE_ATOM),
            'patient'     => [
                'id'   => $odontogram->getPatient()?->getId(),
                'name' => $odontogram->getPatient()?->getName(),
            ],
        ];
    }

 
    private function isValidToothNumber(int $toothNumber): bool
    {
        $quadrant = (int) ($toothNumber / 10);
        $position = $toothNumber % 10;

        return in_array($quadrant, [1, 2, 3, 4], true) && $position >= 1 && $position <= 8;
    }
}