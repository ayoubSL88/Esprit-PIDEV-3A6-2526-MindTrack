<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity]
class Humeur
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idH;

    #[ORM\Column(type: "date")]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: 'Le type d\'humeur est obligatoire.')]
    #[Assert\Choice(
        choices: ['sad', 'anxious', 'happy', 'neutural'],
        message: 'Choisissez une humeur valide.'
    )]
    private string $TypeHumeur;

    #[ORM\Column(type: "integer")]
    #[Assert\NotNull(message: 'L\'intensite est obligatoire.')]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: 'L\'intensite doit etre comprise entre {{ min }} et {{ max }}.'
    )]
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
