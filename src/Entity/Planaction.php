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

        #[ORM\ManyToOne(targetEntity: Objectif::class, inversedBy: "planactions")]
    #[ORM\JoinColumn(name: 'idObj', referencedColumnName: 'idObj', onDelete: 'CASCADE')]
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
}
