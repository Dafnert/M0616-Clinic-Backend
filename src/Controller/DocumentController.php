<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DocumentRepository;
use App\Repository\PatientRepository;
use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/document')]
final class DocumentController extends AbstractController
{
    #[Route('/upload/{patientId}', name: 'document_upload', methods: ['POST'])]
    public function upload(
        int $patientId,
        Request $request,
        PatientRepository $patientRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $patient = $patientRepository->find($patientId);
        if (!$patient) {
            return $this->json(['success' => false, 'message' => 'Patient not found'], Response::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['success' => false, 'message' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        $allowedMimeTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return $this->json(
                ['success' => false, 'message' => 'Invalid file type. Only PDF and images (PNG, JPG) are allowed'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $maxFileSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxFileSize) {
            return $this->json(
                ['success' => false, 'message' => 'File size exceeds maximum limit of 5MB'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        $file->move($uploadDir, $filename);

        $document = new Document();
        $document->setFilename($filename);
        $document->setOriginalName($originalName);
        $document->setMimeType($mimeType);
        $document->setUploadedAt(new \DateTimeImmutable());
        $document->setPatient($patient);

        $em->persist($document);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => [
                'id' => $document->getId(),
                'originalName' => $document->getOriginalName(),
                'mimeType' => $document->getMimeType(),
                'uploadedAt' => $document->getUploadedAt()->format('d/m/Y H:i'),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/patient/{patientId}', name: 'document_list', methods: ['GET'])]
    public function listByPatient(
        int $patientId,
        DocumentRepository $documentRepository
    ): JsonResponse {
        $documents = $documentRepository->findBy(['patient' => $patientId]);

        $data = array_map(fn($d) => [
            'id' => $d->getId(),
            'originalName' => $d->getOriginalName(),
            'mimeType' => $d->getMimeType(),
            'uploadedAt' => $d->getUploadedAt()->format('d/m/Y H:i'),
        ], $documents);

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/download/{id}', name: 'document_download', methods: ['GET'])]
    public function download(
        int $id,
        DocumentRepository $documentRepository
    ): Response {
        $document = $documentRepository->find($id);
        if (!$document) {
            return $this->json(['success' => false, 'message' => 'Document not found'], Response::HTTP_NOT_FOUND);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $document->getFilename();
        if (!file_exists($filePath)) {
            return $this->json(['success' => false, 'message' => 'File not found on server'], Response::HTTP_NOT_FOUND);
        }

        return $this->file($filePath, $document->getOriginalName());
    }

    #[Route('/{id}', name: 'document_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        DocumentRepository $documentRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $document = $documentRepository->find($id);
        if (!$document) {
            return $this->json(['success' => false, 'message' => 'Document not found'], Response::HTTP_NOT_FOUND);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $document->getFilename();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $em->remove($document);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Document deleted successfully']);
    }
}