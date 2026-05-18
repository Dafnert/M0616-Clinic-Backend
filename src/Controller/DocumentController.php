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
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $fileData = file_get_contents($file->getPathname());

        $document = new Document();
        $document->setFilename($filename);
        $document->setOriginalName($originalName);
        $document->setMimeType($mimeType);
        $document->setFileData($fileData);
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

        $fileData = $document->getFileData();
        if (!$fileData) {
            return $this->json(['success' => false, 'message' => 'File not found'], Response::HTTP_NOT_FOUND);
        }

        $response = new Response($fileData);
        $response->headers->set('Content-Type', $document->getMimeType());
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $document->getOriginalName() . '"');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition, Content-Type');

        return $response;
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

        $em->remove($document);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Document deleted successfully']);
    }
}