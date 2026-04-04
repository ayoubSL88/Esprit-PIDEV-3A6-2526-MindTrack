<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Objectif;

#[ORM\Entity]
class Jalonprogression
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idJalon;

        #[ORM\ManyToOne(targetEntity: Objectif::class, inversedBy: "jalonprogressions")]
    #[ORM\JoinColumn(name: 'idObj', referencedColumnName: 'id_obj', onDelete: 'CASCADE')]
    private Objectif $idObj;

    #[ORM\Column(type: "string", length: 150)]
    private string $titre;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateCible;

    #[ORM\Column(type: "boolean")]
    private bool $atteint;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateAtteinte;

    #[ORM\Column(type: "integer")]
    private int $pourcentageProgression;

    public function getIdJalon()
    {
        return $this->idJalon;
    }

    public function setIdJalon($value)
    {
        $this->idJalon = $value;
    }

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

    public function getDateCible()
    {
        return $this->dateCible;
    }

    public function setDateCible($value)
    {
        $this->dateCible = $value;
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
