<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Profilpsychologique
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idP;

    #[ORM\Column(type: "integer")]
    private int $NiveauStress;

    #[ORM\Column(type: "integer")]
    private int $NiveauMotivation;

    #[ORM\Column(type: "string", length: 255)]
    private string $Description;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "profilpsychologiques")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_u', onDelete: 'CASCADE')]
    private Utilisateur $idU;

    public function getIdP()
    {
        return $this->idP;
    }

    public function setIdP($value)
    {
        $this->idP = $value;
    }

    public function getNiveauStress()
    {
        return $this->NiveauStress;
    }

    public function setNiveauStress($value)
    {
        $this->NiveauStress = $value;
    }

    public function getNiveauMotivation()
    {
        return $this->NiveauMotivation;
    }

    public function setNiveauMotivation($value)
    {
        $this->NiveauMotivation = $value;
    }

    public function getDescription()
    {
        return $this->Description;
    }

    public function setDescription($value)
    {
        $this->Description = $value;
    }

    public function getIdU()
    {
        return $this->idU;
    }

    public function setIdU($value)
    {
        $this->idU = $value;
    }
}
