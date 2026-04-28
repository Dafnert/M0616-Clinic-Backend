<?php

namespace App\Entity;

use App\Repository\OdontogramRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OdontogramRepository::class)]
class Odontogram
{
    public const STATUS_HEALTHY    = 'healthy';
    public const STATUS_CARIES     = 'caries';
    public const STATUS_FILLED     = 'filled';
    public const STATUS_CROWN      = 'crown';
    public const STATUS_MISSING    = 'missing';
    public const STATUS_IMPLANT    = 'implant';
    public const STATUS_ROOT_CANAL = 'root_canal';
    public const STATUS_EXTRACTED  = 'extracted';

    public const VALID_STATUSES = [
        self::STATUS_HEALTHY,
        self::STATUS_CARIES,
        self::STATUS_FILLED,
        self::STATUS_CROWN,
        self::STATUS_MISSING,
        self::STATUS_IMPLANT,
        self::STATUS_ROOT_CANAL,
        self::STATUS_EXTRACTED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $toothNumber = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_HEALTHY;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToothNumber(): ?int
    {
        return $this->toothNumber;
    }

    public function setToothNumber(int $toothNumber): static
    {
        $this->toothNumber = $toothNumber;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;

        return $this;
    }
}