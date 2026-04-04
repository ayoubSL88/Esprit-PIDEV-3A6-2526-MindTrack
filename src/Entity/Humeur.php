<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Humeur
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idH;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "string", length: 255)]
    private string $TypeHumeur;

    #[ORM\Column(type: "integer")]
    private int $intensite;

    public function getIdH()
    {
        return $this->idH;
    }

    public function setIdH($value)
    {
        $this->idH = $value;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($value)
    {
        $this->date = $value;
    }

    public function getTypeHumeur()
    {
        return $this->TypeHumeur;
    }

    public function setTypeHumeur($value)
    {
        $this->TypeHumeur = $value;
    }

    public function getIntensite()
    {
        return $this->intensite;
    }

    public function setIntensite($value)
    {
        $this->intensite = $value;
    }
}
