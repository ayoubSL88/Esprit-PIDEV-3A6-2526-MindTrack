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
    #[Assert\NotNull(message: 'The date is required.')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: 'The mood type is required.')]
    #[Assert\Choice(
        choices: ['sad', 'anxious', 'happy', 'neutural', 'tired'],
        message: 'Choose a valid mood type.'
    )]
    private ?string $TypeHumeur = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotNull(message: 'The intensity is required.')]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: 'The intensity must be between {{ min }} and {{ max }}.'
    )]
    private ?int $intensite = null;

    public function getIdH()
    {
        return $this->idH;
    }

    public function setIdH($value)
    {
        $this->idH = $value;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $value): void
    {
        $this->date = $value;
    }

    public function getTypeHumeur(): ?string
    {
        return $this->TypeHumeur;
    }

    public function setTypeHumeur(?string $value): void
    {
        $this->TypeHumeur = $value;
    }

    public function getIntensite(): ?int
    {
        return $this->intensite;
    }

    public function setIntensite(?int $value): void
    {
        $this->intensite = $value;
    }

    public function getMoodLabel(): string
    {
        return match (strtolower(trim((string) ($this->TypeHumeur ?? '')))) {
            'sad' => 'Sad',
            'anxious', 'stress', 'stressed' => 'Stressed',
            'happy' => 'Happy',
            'tired', 'fatigue', 'fatigued', 'exhausted' => 'Tired',
            'neutral', 'neutural', 'neutre' => 'Neutral',
            default => $this->TypeHumeur === null || trim($this->TypeHumeur) === '' ? 'Not set' : ucfirst($this->TypeHumeur),
        };
    }
}
