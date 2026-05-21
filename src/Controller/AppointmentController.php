<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PatientRepository;

use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/appointment')]
final class AppointmentController extends AbstractController
{
    #[Route('/', name: 'app_appointment_index', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): JsonResponse
    {
        $appointments = $appointmentRepository->findAll();

        $data = array_map(function (Appointment $appointment) {
            return $this->appointmentToArray($appointment);
        }, $appointments);

        return $this->json([
            'success' => true,
            'message' => 'Appointments retrieved successfully',
            'data' => $data,
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_appointment_show', methods: ['GET'])]
    public function show(int $id, AppointmentRepository $appointmentRepository): JsonResponse
    {
        $appointment = $appointmentRepository->find($id);

        if (!$appointment) {
            return $this->json([
                'success' => false,
                'message' => 'Appointment not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'message' => 'Appointment retrieved successfully',
            'data' => $this->appointmentToArray($appointment),
        ], Response::HTTP_OK);
    }


    #[Route('', name: 'app_appointment_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        DoctorRepository $doctorRepository,
        PatientRepository $patientRepository  // ← añadir
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['date'], $data['hourVisit'], $data['reason'], $data['observations'])) {
            return $this->json([
                'success' => false,
                'message' => 'date, hourVisit, reason, and observations are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $patientId = $data['patient_id'] ?? $data['patientId'] ?? null;
        if (empty($patientId)) {
            return $this->json([
                'success' => false,
                'message' => 'patient_id is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $patient = $patientRepository->find($patientId);
        if (!$patient) {
            return $this->json([
                'success' => false,
                'message' => 'Patient not found',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $appointment = new Appointment();
            $appointment->setDate(new \DateTime($data['date']));
            $appointment->setHourVisit(new \DateTime($data['hourVisit']));
            $appointment->setReason($data['reason']);
            $appointment->setObservations($data['observations']);
            $appointment->setPatient($patient);  // ← añadir

            $doctorId = $data['doctor_id'] ?? $data['doctorId'] ?? null;
            if (!empty($doctorId)) {
                $doctor = $doctorRepository->find($doctorId);
                if (!$doctor) {
                    return $this->json(['success' => false, 'message' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
                }
                $appointment->setDoctor($doctor);
            }

            $entityManager->persist($appointment);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Appointment created successfully',
                'data' => $this->appointmentToArray($appointment),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'app_appointment_update', methods: ['PUT'])]
    public function update(int $id, Request $request, AppointmentRepository $appointmentRepository, EntityManagerInterface $entityManager, DoctorRepository $doctorRepository): JsonResponse
    {
        $appointment = $appointmentRepository->find($id);

        if (!$appointment) {
            return $this->json([
                'success' => false,
                'message' => 'Appointment not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        try {
            if (isset($data['date'])) {
                $appointment->setDate(new \DateTime($data['date']));
            }
            if (isset($data['hourVisit'])) {
                $appointment->setHourVisit(new \DateTime($data['hourVisit']));
            }
            if (isset($data['reason'])) {
                $appointment->setReason($data['reason']);
            }
            if (isset($data['observations'])) {
                $appointment->setObservations($data['observations']);
            }

            $doctorId = $data['doctorId'] ?? $data['doctor_id'] ?? null;
            if ($doctorId) {
                $doctor = $doctorRepository->find($doctorId);
                if ($doctor) {
                    $appointment->setDoctor($doctor);
                }
            }

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Appointment updated successfully',
                'data' => $this->appointmentToArray($appointment),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating appointment: ' . $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'app_appointment_delete', methods: ['DELETE'])]
    public function delete(int $id, AppointmentRepository $appointmentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $appointment = $appointmentRepository->find($id);

        if (!$appointment) {
            return $this->json([
                'success' => false,
                'message' => 'Appointment not found',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $entityManager->remove($appointment);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Appointment deleted successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting appointment: ' . $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    private function appointmentToArray(Appointment $appointment): array
    {
        $doctor = $appointment->getDoctor();
        $patient = $appointment->getPatient();
        return [
            'id' => $appointment->getId(),
            'date' => $appointment->getDate()?->format('Y-m-d'),
            'hourVisit' => $appointment->getHourVisit()?->format('H:i:s'),
            'reason' => $appointment->getReason(),
            'observations' => $appointment->getObservations(),
            'patient' => $patient ? [
                'id'       => $patient->getId(),
                'name'     => $patient->getName(),
                'surname'  => $patient->getSurname(),
                'dni'      => $patient->getDni(),
                'disease'  => $patient->getDisease(),
                'alergias' => $patient->getAlergias(),
                'isVih'    => strtolower((string) $patient->getDisease()) === 'vih',
            ] : null,
            'doctor' => $doctor ? [
                'id' => $doctor->getId(),
                'name' => $doctor->getName(),
                'surname' => $doctor->getSurname(),
                'speciality' => $doctor->getSpeciality(),
            ] : null,
        ];
    }
}
