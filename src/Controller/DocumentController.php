<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/documents')] 


final class DocumentController extends AbstractController
{
    private function documentToArray(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'title' => $document->getTitle(), 
            'content' => $document->getContent(),
            'createdAt' => $document->getCreatedAt()?->format(DATE_ATOM),
        ];
    }

    // GET /api/documents
    #[Route('', name: 'documents_list', methods: ['GET'])]
    public function list(DocumentRepository $repository): JsonResponse
    {
        $documents = $repository->findAll();


        $result = array_map(
            fn (Document $d) => $this->documentToArray($d),
            $documents
        );

        return $this->json($result);
    }

    // GET /api/documents/1
    #[Route('/{id}', name: 'documents_get', methods: ['GET'])]
    public function getOne(int $id, DocumentRepository $repository): JsonResponse
    {
        $document = $repository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document no trobat'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->documentToArray($document));
    }

    // POST /api/documents
    #[Route('', name: 'documents_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invàlid'], Response::HTTP_BAD_REQUEST);
        }

        $title = trim((string)($data['title'] ?? ''));
        $content = trim((string)($data['content'] ?? ''));

        if ($title === '' || $content === '') {
            return $this->json(
                ['error' => 'Falten camps obligatoris: title, content'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $document = new Document();
        $document->setTitle($title);
        $document->setContent($content);

        // Si tu entidad usa DateTimeImmutable (recomendado)
        $document->setCreatedAt(new \DateTimeImmutable());

        $em->persist($document);
        $em->flush();

        return $this->json($this->documentToArray($document), Response::HTTP_CREATED);
    }

    // PUT /api/documents/1
    #[Route('/{id}', name: 'documents_update', methods: ['PUT'])]
    public function update(int $id, Request $request, DocumentRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $document = $repository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document no trobat'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invàlid'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('title', $data)) {
            $title = trim((string)$data['title']);
            if ($title === '') {
                return $this->json(['error' => 'title no pot estar buit'], Response::HTTP_BAD_REQUEST);
            }   
            $document->setTitle($title);
        }

        if (array_key_exists('content', $data)) {
            $content = trim((string)$data['content']);
            if ($content === '') {
                return $this->json(['error' => 'content no pot estar buit'], Response::HTTP_BAD_REQUEST);
            }
            $document->setContent($content);
        }

        $em->flush();

        return $this->json($this->documentToArray($document));
    }

    // DELETE /api/documents/1
    #[Route('/{id}', name: 'documents_delete', methods: ['DELETE'])]
    public function delete(int $id, DocumentRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $document = $repository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document no trobat'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($document);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    



}