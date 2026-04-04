<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Habitude;

#[ORM\Entity]
class Suivihabitude
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idSuivi;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "boolean")]
    private bool $etat;

        #[ORM\ManyToOne(targetEntity: Habitude::class, inversedBy: "suivihabitudes")]
    #[ORM\JoinColumn(name: 'idHabitude', referencedColumnName: 'id_habitude', onDelete: 'CASCADE')]
    private Habitude $idHabitude;

    #[ORM\Column(type: "integer")]
    private int $valeur;

    public function getIdSuivi()
    {
        return $this->idSuivi;
    }

    public function setIdSuivi($value)
    {
        $this->idSuivi = $value;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($value)
    {
        $this->date = $value;
    }

    public function getEtat()
    {
        return $this->etat;
    }

    public function setEtat($value)
    {
        $this->etat = $value;
    }

    public function getIdHabitude()
    {
        return $this->idHabitude;
    }

    public function setIdHabitude($value)
    {
        $this->idHabitude = $value;
    }

    public function getValeur()
    {
        return $this->valeur;
    }

    public function setValeur($value)
    {
        $this->valeur = $value;
    }
}
