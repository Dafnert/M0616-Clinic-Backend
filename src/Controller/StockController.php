<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stock')]
final class StockController extends AbstractController
{
    private function stockToArray(Stock $stock): array
    {
        return [
            'id' => $stock->getId(),
            'name' => $stock->getName(),
            'description' => $stock->getDescription(),
            'quantity' => $stock->getQuantity(),
            'minimumQuantity' => $stock->getMinimumQuantity(),
            'unit' => $stock->getUnit(),
            'createdAt' => $stock->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $stock->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }

    // GET /api/stock
    #[Route('', name: 'stock_list', methods: ['GET'])]
    public function list(StockRepository $repository): JsonResponse
    {
        $stocks = $repository->findAll();

        $result = array_map(
            fn(Stock $s) => $this->stockToArray($s),
            $stocks
        );

        return $this->json($result);
    }

    // GET /api/stock/{id}
    #[Route('/{id}', name: 'stock_get', methods: ['GET'])]
    public function getOne(int $id, StockRepository $repository): JsonResponse
    {
        $stock = $repository->find($id);

        if (!$stock) {
            return $this->json(['error' => 'Stock item not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->stockToArray($stock));
    }

    // POST /api/stock
    #[Route('', name: 'stock_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string)($data['name'] ?? ''));
        $unit = trim((string)($data['unit'] ?? ''));

        if ($name === '' || $unit === '' || !isset($data['quantity']) || !isset($data['minimumQuantity'])) {
            return $this->json(
                ['error' => 'Missing required fields: name, quantity, minimumQuantity, unit'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $quantity = (int)$data['quantity'];
        $minimumQuantity = (int)$data['minimumQuantity'];

        if ($quantity < 0) {
            return $this->json(['error' => 'quantity cannot be negative'], Response::HTTP_BAD_REQUEST);
        }

        if ($minimumQuantity <= 0) {
            return $this->json(['error' => 'minimumQuantity must be greater than 0'], Response::HTTP_BAD_REQUEST);
        }

        $stock = new Stock();
        $stock->setName($name);
        $stock->setDescription($data['description'] ?? null);
        $stock->setQuantity($quantity);
        $stock->setMinimumQuantity($minimumQuantity);
        $stock->setUnit($unit);
        $stock->setCreatedAt(new \DateTimeImmutable());

        $em->persist($stock);
        $em->flush();

        return $this->json($this->stockToArray($stock), Response::HTTP_CREATED);
    }

    // PUT /api/stock/{id}
    #[Route('/{id}', name: 'stock_update', methods: ['PUT'])]
    public function update(int $id, Request $request, StockRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $stock = $repository->find($id);

        if (!$stock) {
            return $this->json(['error' => 'Stock item not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('name', $data)) {
            $name = trim((string)$data['name']);
            if ($name === '') {
                return $this->json(['error' => 'name cannot be empty'], Response::HTTP_BAD_REQUEST);
            }
            $stock->setName($name);
        }

        if (array_key_exists('description', $data)) {
            $stock->setDescription($data['description']);
        }

        if (array_key_exists('quantity', $data)) {
            $quantity = (int)$data['quantity'];
            if ($quantity < 0) {
                return $this->json(['error' => 'quantity cannot be negative'], Response::HTTP_BAD_REQUEST);
            }
            $stock->setQuantity($quantity);
        }

        if (array_key_exists('minimumQuantity', $data)) {
            $minimumQuantity = (int)$data['minimumQuantity'];
            if ($minimumQuantity <= 0) {
                return $this->json(['error' => 'minimumQuantity must be greater than 0'], Response::HTTP_BAD_REQUEST);
            }
            $stock->setMinimumQuantity($minimumQuantity);
        }

        if (array_key_exists('unit', $data)) {
            $unit = trim((string)$data['unit']);
            if ($unit === '') {
                return $this->json(['error' => 'unit cannot be empty'], Response::HTTP_BAD_REQUEST);
            }
            $stock->setUnit($unit);
        }

        $stock->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json($this->stockToArray($stock));
    }

    // DELETE /api/stock/{id}
    #[Route('/{id}', name: 'stock_delete', methods: ['DELETE'])]
    public function delete(int $id, StockRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $stock = $repository->find($id);

        if (!$stock) {
            return $this->json(['error' => 'Stock item not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($stock);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
