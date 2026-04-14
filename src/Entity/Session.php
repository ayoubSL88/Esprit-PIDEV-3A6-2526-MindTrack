<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Exercice;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private ?int $idSession = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "idU", nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateSession;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $Resultat;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $commentaires;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $dureeReelle = null;

    #[ORM\Column(type: "boolean")]
    private bool $terminee = false;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $progress = 0;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $steps = null;

    #[ORM\ManyToOne(targetEntity: Exercice::class, inversedBy: "sessions")]
    #[ORM\JoinColumn(name: 'idEx', referencedColumnName: 'id_ex', nullable: false, onDelete: 'CASCADE')]
    private ?Exercice $exercice = null;

    // Constructeur
    public function __construct()
    {
        $this->dateSession = new \DateTime();
        $this->dateDebut = new \DateTime();
        $this->steps = [];
        $this->progress = 0;
    }

    // Getters et Setters
    public function getIdSession(): ?int
    {
        return $this->idSession;
    }

    public function setIdSession(int $idSession): self
    {
        $this->idSession = $idSession;
        return $this;
    }

    public function getDateSession(): \DateTimeInterface
    {
        return $this->dateSession;
    }

    public function setDateSession(\DateTimeInterface $dateSession): self
    {
        $this->dateSession = $dateSession;
        return $this;
    }

    public function getDateDebut(): \DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getResultat(): ?string
    {
        return $this->Resultat;
    }

    public function setResultat(?string $Resultat): self
    {
        $this->Resultat = $Resultat;
        return $this;
    }

    public function getCommentaires(): ?string
    {
        return $this->commentaires;
    }

    public function setCommentaires(?string $commentaires): self
    {
        $this->commentaires = $commentaires;
        return $this;
    }

    public function getDureeReelle(): ?int
    {
        return $this->dureeReelle;
    }

    public function setDureeReelle(?int $dureeReelle): self
    {
        $this->dureeReelle = $dureeReelle;
        return $this;
    }

    public function getTerminee(): bool
    {
        return $this->terminee;
    }

    public function setTerminee(bool $terminee): self
    {
        $this->terminee = $terminee;
        return $this;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(?int $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    public function getSteps(): ?array
    {
        return $this->steps;
    }

    public function setSteps(?array $steps): self
    {
        $this->steps = $steps;
        return $this;
    }

    public function getExercice(): ?Exercice
{
    return $this->exercice;
}

public function setExercice(?Exercice $exercice): self
{
    $this->exercice = $exercice;
    return $this;
}

public function getUser(): ?Utilisateur 
{ 
    return $this->user; 
}

public function setUser(?Utilisateur $user): self 
{ 
    $this->user = $user; 
    return $this; 
}
}