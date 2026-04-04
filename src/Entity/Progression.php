<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Progression
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idProgression;

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

    public function getIdEx()
    {
        return $this->idEx;
    }

    public function setIdEx($value)
    {
        $this->idEx = $value;
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
}
