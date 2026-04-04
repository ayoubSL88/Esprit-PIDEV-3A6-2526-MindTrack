<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Jalonprogression;

#[ORM\Entity]
class Objectif
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idObj;

    #[ORM\Column(type: "string", length: 255)]
    private string $titre;

    #[ORM\Column(type: "string", length: 255)]
    private string $descriprion;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateFin;

    #[ORM\Column(type: "string", length: 255)]
    private string $statut;

    public function getIdObj()
    {
        return $this->idObj;
    }

    public function setIdObj($value)
    {
        $this->idObj = $value;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($value)
    {
        $this->titre = $value;
    }

    public function getDescriprion()
    {
        return $this->descriprion;
    }

    public function setDescriprion($value)
    {
        $this->descriprion = $value;
    }

    public function getDateDebut()
    {
        return $this->dateDebut;
    }

    public function setDateDebut($value)
    {
        $this->dateDebut = $value;
    }

    public function getDateFin()
    {
        return $this->dateFin;
    }

    public function setDateFin($value)
    {
        $this->dateFin = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    #[ORM\OneToMany(mappedBy: "idObj", targetEntity: Jalonprogression::class)]
    private Collection $jalonprogressions;

    #[ORM\OneToMany(mappedBy: "idObj", targetEntity: Planaction::class)]
    private Collection $planactions;

    #[ORM\OneToMany(mappedBy: "idObj", targetEntity: Planificateurintelligent::class)]
    private Collection $planificateurintelligents;
}
