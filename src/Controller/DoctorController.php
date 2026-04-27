<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DoctorRepository;
#[Route(path: '/doctor')]
final class DoctorController extends AbstractController
{

    #[Route('/random', methods: ['GET'])]
    public function getRandomDoctors(DoctorRepository $doctorRepository): JsonResponse
    {
        $doctors = $doctorRepository->findAll();

        shuffle($doctors); // mezcla aleatoriamente
        $doctors = array_slice($doctors, 0, 4); // coger 4

        $data = array_map(function ($doctor) {
            return [
                'id' => $doctor->getId(),
                'name' => $doctor->getName(),
                'speciality' => $doctor->getSpeciality()
            ];
        }, $doctors);

        return $this->json($data);
    }
}