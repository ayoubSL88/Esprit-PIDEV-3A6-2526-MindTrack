<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Objectif;

#[ORM\Entity]
class Planificateurintelligent
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idPlanificateur;

        #[ORM\ManyToOne(targetEntity: Objectif::class, inversedBy: "planificateurintelligents")]
    #[ORM\JoinColumn(name: 'idObj', referencedColumnName: 'id_obj', onDelete: 'CASCADE')]
    private Objectif $idObj;

    #[ORM\Column(type: "string", length: 30)]
    private string $modeOrganisation;

    #[ORM\Column(type: "integer")]
    private int $capaciteQuotidienne;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $derniereGeneration;

    public function getIdPlanificateur()
    {
        return $this->idPlanificateur;
    }

    public function setIdPlanificateur($value)
    {
        $this->idPlanificateur = $value;
    }

    public function getIdObj()
    {
        return $this->idObj;
    }

    public function setIdObj($value)
    {
        $this->idObj = $value;
    }

    public function getModeOrganisation()
    {
        return $this->modeOrganisation;
    }

    public function setModeOrganisation($value)
    {
        $this->modeOrganisation = $value;
    }

    public function getCapaciteQuotidienne()
    {
        return $this->capaciteQuotidienne;
    }

    public function setCapaciteQuotidienne($value)
    {
        $this->capaciteQuotidienne = $value;
    }

    public function getDerniereGeneration()
    {
        return $this->derniereGeneration;
    }

    public function setDerniereGeneration($value)
    {
        $this->derniereGeneration = $value;
    }
}
