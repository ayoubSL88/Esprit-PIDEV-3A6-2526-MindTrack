<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProgressionRepository::class)]
class Progression
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $idProgression = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "idU", nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\ManyToOne(targetEntity: Exercice::class)]
    #[ORM\JoinColumn(name: "idEx", referencedColumnName: "idEx", nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\Column(type: "integer")]
    private int $idJalon;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateRealisation;

    #[ORM\Column(type: "integer")]
    private int $scoreObtenu;

    #[ORM\Column(type: "integer")]
    private int $ressentiUtilisateur;

    #[ORM\Column(type: "text")]
    private string $notesPersonnelles;

    #[ORM\Column(type: "integer")]
    private int $tempsPasse;

    #[ORM\Column(type: "integer")]
    private int $idEx;

    #[ORM\Column(type: "integer")]
    private int $idSession;

    #[ORM\Column(type: "boolean")]
    private bool $atteint;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateAtteinte;

    #[ORM\Column(type: "integer")]
    private int $pourcentageProgression;

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

    public function getIdProgression()
    {
        return $this->idProgression;
    }

    public function setIdProgression($value)
    {
        $this->idProgression = $value;
    }

    public function getIdJalon()
    {
        return $this->idJalon;
    }

    public function setIdJalon($value)
    {
        $this->idJalon = $value;
    }

    public function getDateRealisation()
    {
        return $this->dateRealisation;
    }

    public function setDateRealisation($value)
    {
        $this->dateRealisation = $value;
    }

    public function getScoreObtenu()
    {
        return $this->scoreObtenu;
    }

    public function setScoreObtenu($value)
    {
        $this->scoreObtenu = $value;
    }

    public function getRessentiUtilisateur()
    {
        return $this->ressentiUtilisateur;
    }

    public function setRessentiUtilisateur($value)
    {
        $this->ressentiUtilisateur = $value;
    }

    public function getNotesPersonnelles()
    {
        return $this->notesPersonnelles;
    }

    public function setNotesPersonnelles($value)
    {
        $this->notesPersonnelles = $value;
    }

    public function getTempsPasse()
    {
        return $this->tempsPasse;
    }

    public function setTempsPasse($value)
    {
        $this->tempsPasse = $value;
    }


    public function getIdSession()
    {
        return $this->idSession;
    }

    public function setIdSession($value)
    {
        $this->idSession = $value;
    }

    public function getAtteint()
    {
        return $this->atteint;
    }

    public function setAtteint($value)
    {
        $this->atteint = $value;
    }

    public function getDateAtteinte()
    {
        return $this->dateAtteinte;
    }

    public function setDateAtteinte($value)
    {
        $this->dateAtteinte = $value;
    }

    public function getPourcentageProgression()
    {
        return $this->pourcentageProgression;
    }

    public function setPourcentageProgression($value)
    {
        $this->pourcentageProgression = $value;
    }

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
