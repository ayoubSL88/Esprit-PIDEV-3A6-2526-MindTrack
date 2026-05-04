<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Jalonprogression;

#[ORM\Entity]
class Objectif
{

    #[ORM\Id]
    #[ORM\Column(type: "integer", name: "id_obj")]
    private int $idObj;

    #[ORM\Column(type: "string", length: 255)]
    private string $titre;

    #[ORM\Column(type: "string", length: 255)]
    private string $descriprion;

    #[ORM\Column(type: "date", name: "date_debut")]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type: "date", name: "date_fin")]
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

    #[ORM\OneToMany(mappedBy: "idObj", targetEntity: Jalonprogression::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $jalonprogressions;

    #[ORM\OneToMany(mappedBy: "idObj", targetEntity: Planaction::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $planactions;

    #[ORM\OneToMany(mappedBy: "idObj", targetEntity: Planificateurintelligent::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $planificateurintelligents;

    public function __construct()
    {
        $this->jalonprogressions = new ArrayCollection();
        $this->planactions = new ArrayCollection();
        $this->planificateurintelligents = new ArrayCollection();
    }

    public function getJalonprogressions(): Collection
    {
        return $this->jalonprogressions;
    }

    public function getPlanactions(): Collection
    {
        return $this->planactions;
    }

    public function getPlanificateurintelligents(): Collection
    {
        return $this->planificateurintelligents;
    }
}
