<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProgressionRepository;

#[ORM\Entity(repositoryClass: ProgressionRepository::class)]
class Progression
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $idProgression = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id_u", nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\ManyToOne(targetEntity: Exercice::class)]
    #[ORM\JoinColumn(name: "exercice_id", referencedColumnName: "id_ex", nullable: true, onDelete: "SET NULL")]
    private ?Exercice $exercice = null;

    #[ORM\Column(type: "integer")]
    private int $totalSessions = 0;

    #[ORM\Column(type: "integer")]
    private int $sessionsTerminees = 0;

    #[ORM\Column(type: "integer")]
    private int $tempsTotal = 0;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $moyenneScore = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $derniereActivite;

    public function __construct()
    {
        $this->derniereActivite = new \DateTime();
    }

    // Getters et Setters
    public function getIdProgression(): ?int { return $this->idProgression; }
    public function setIdProgression(?int $idProgression): self { $this->idProgression = $idProgression; return $this; }
    
    public function getUser(): ?Utilisateur { return $this->user; }
    public function setUser(?Utilisateur $user): self { $this->user = $user; return $this; }
    
    public function getExercice(): ?Exercice { return $this->exercice; }
    public function setExercice(?Exercice $exercice): self { $this->exercice = $exercice; return $this; }
    
    public function getTotalSessions(): int { return $this->totalSessions; }
    public function setTotalSessions(int $totalSessions): self { $this->totalSessions = $totalSessions; return $this; }
    
    public function getSessionsTerminees(): int { return $this->sessionsTerminees; }
    public function setSessionsTerminees(int $sessionsTerminees): self { $this->sessionsTerminees = $sessionsTerminees; return $this; }
    
    public function getTempsTotal(): int { return $this->tempsTotal; }
    public function setTempsTotal(int $tempsTotal): self { $this->tempsTotal = $tempsTotal; return $this; }
    
    public function getMoyenneScore(): ?float { return $this->moyenneScore; }
    public function setMoyenneScore(?float $moyenneScore): self { $this->moyenneScore = $moyenneScore; return $this; }
    
    public function getDerniereActivite(): \DateTimeInterface { return $this->derniereActivite; }
    public function setDerniereActivite(\DateTimeInterface $derniereActivite): self { $this->derniereActivite = $derniereActivite; return $this; }
}