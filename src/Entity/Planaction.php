<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Objectif;

#[ORM\Entity]
class Planaction
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idPlan;

    #[ORM\Column(type: "string", length: 255)]
    private string $etape;

    #[ORM\Column(type: "integer")]
    private int $priorite;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $statut = null;

        #[ORM\ManyToOne(targetEntity: Objectif::class, inversedBy: "planactions")]
    #[ORM\JoinColumn(name: 'objectif_id', referencedColumnName: 'id_obj', nullable: false, onDelete: 'CASCADE')]
    private Objectif $idObj;

    public function getIdPlan()
    {
        return $this->idPlan;
    }

    public function setIdPlan($value)
    {
        $this->idPlan = $value;
    }

    public function getEtape()
    {
        return $this->etape;
    }

    public function setEtape($value)
    {
        $this->etape = $value;
    }

    public function getPriorite()
    {
        return $this->priorite;
    }

    public function setPriorite($value)
    {
        $this->priorite = $value;
    }

    public function getIdObj()
    {
        return $this->idObj;
    }

    public function setIdObj($value)
    {
        $this->idObj = $value;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $value): self
    {
        $this->titre = $value;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $value): self
    {
        $this->description = $value;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $value): self
    {
        $this->dateDebut = $value;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $value): self
    {
        $this->dateFin = $value;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $value): self
    {
        $this->statut = $value;
        return $this;
    }
}
