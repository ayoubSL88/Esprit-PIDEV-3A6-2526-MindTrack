<?php

namespace App\Entity;

use App\Repository\SuivihabitudeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SuivihabitudeRepository::class)]
class Suivihabitude
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id_suivi')]
    private int $idSuivi;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'boolean')]
    private bool $etat = false;

    #[ORM\ManyToOne(targetEntity: Habitude::class, inversedBy: 'suivihabitudes')]
    #[ORM\JoinColumn(name: 'idHabitude', referencedColumnName: 'id_habitude', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Selectionnez une habitude.')]
    private ?Habitude $idHabitude = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'La valeur est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La valeur doit etre positive ou egale a zero.')]
    private int $valeur = 0;

    public function getIdSuivi(): int
    {
        return $this->idSuivi;
    }

    public function setIdSuivi(int $value): self
    {
        $this->idSuivi = $value;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $value): self
    {
        $this->date = $value;

        return $this;
    }

    public function getEtat(): bool
    {
        return $this->etat;
    }

    public function setEtat(bool $value): self
    {
        $this->etat = $value;

        return $this;
    }

    public function getIdHabitude(): ?Habitude
    {
        return $this->idHabitude;
    }

    public function setIdHabitude(?Habitude $value): self
    {
        $this->idHabitude = $value;

        return $this;
    }

    public function getValeur(): int
    {
        return $this->valeur;
    }

    public function setValeur(int $value): self
    {
        $this->valeur = $value;

        return $this;
    }
}
