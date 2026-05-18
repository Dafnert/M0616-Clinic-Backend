<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private mixed $fileData = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    public function getId(): ?int { return $this->id; }

    public function getFilename(): ?string { return $this->filename; }
    public function setFilename(string $filename): static { $this->filename = $filename; return $this; }

    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(string $o): static { $this->originalName = $o; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(string $m): static { $this->mimeType = $m; return $this; }

    public function getFileData(): string|null
    {
        if (is_resource($this->fileData)) {
            return stream_get_contents($this->fileData);
        }
        return $this->fileData;
    }
    public function setFileData(string $fileData): static { $this->fileData = $fileData; return $this; }

    public function getUploadedAt(): ?\DateTimeImmutable { return $this->uploadedAt; }
    public function setUploadedAt(\DateTimeImmutable $d): static { $this->uploadedAt = $d; return $this; }

    public function getPatient(): ?Patient { return $this->patient; }
    public function setPatient(?Patient $p): static { $this->patient = $p; return $this; }
}