<?php

namespace App\Entity;

use App\Repository\HabitudeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: HabitudeRepository::class)]
class Habitude
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id_habitude')]
    private int $idHabitude;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le nom ne doit pas depasser 255 caracteres.')]
    private string $nom = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'La frequence est obligatoire.')]
    #[Assert\Choice(choices: ['QUOTIDIEN', 'HEBDOMADAIRE', 'MENSUEL'], message: 'Choisissez une frequence valide.')]
    private string $frequence = 'QUOTIDIEN';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'L objectif est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'L objectif ne doit pas depasser 255 caracteres.')]
    private string $objectif = '';

    #[ORM\Column(type: 'string', length: 10)]
    #[Assert\NotBlank(message: 'Le type d habitude est obligatoire.')]
    #[Assert\Choice(choices: ['BOOLEAN', 'NUMERIC'], message: 'Choisissez un type valide.')]
    private string $habitType = 'BOOLEAN';

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'La valeur cible est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La valeur cible doit etre positive ou egale a zero.')]
    private int $targetValue = 0;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Length(max: 20, maxMessage: 'L unite ne doit pas depasser 20 caracteres.')]
    private string $unit = '';

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'habitudes')]
    #[ORM\JoinColumn(name: 'idU', referencedColumnName: 'id_u', nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $idU = null;

    /** @var Collection<int, Rappel_habitude> */
    #[ORM\OneToMany(mappedBy: 'idHabitude', targetEntity: Rappel_habitude::class)]
    private Collection $rappel_habitudes;

    /** @var Collection<int, Suivihabitude> */
    #[ORM\OneToMany(mappedBy: 'idHabitude', targetEntity: Suivihabitude::class)]
    private Collection $suivihabitudes;

    public function __construct()
    {
        $this->rappel_habitudes = new ArrayCollection();
        $this->suivihabitudes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nom !== '' ? $this->nom : 'Habitude';
    }

    public function getIdHabitude(): int
    {
        return $this->idHabitude;
    }

    public function setIdHabitude(int $value): self
    {
        $this->idHabitude = $value;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $value): self
    {
        $this->nom = $value;

        return $this;
    }

    public function getFrequence(): string
    {
        return $this->frequence;
    }

    public function setFrequence(string $value): self
    {
        $this->frequence = $value;

        return $this;
    }

    public function getObjectif(): string
    {
        return $this->objectif;
    }

    public function setObjectif(string $value): self
    {
        $this->objectif = $value;

        return $this;
    }

    public function getHabitType(): string
    {
        return $this->habitType;
    }

    public function setHabitType(string $value): self
    {
        $this->habitType = $value;

        return $this;
    }

    public function getTargetValue(): int
    {
        return $this->targetValue;
    }

    public function setTargetValue(int $value): self
    {
        $this->targetValue = $value;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(?string $value): self
    {
        $this->unit = $value ?? '';

        return $this;
    }

    public function getIdU(): ?Utilisateur
    {
        return $this->idU;
    }

    public function setIdU(?Utilisateur $value): self
    {
        $this->idU = $value;

        return $this;
    }

    /**
     * @return Collection<int, Rappel_habitude>
     */
    public function getRappel_habitudes(): Collection
    {
        return $this->rappel_habitudes;
    }

    public function addRappel_habitude(Rappel_habitude $rappel_habitude): self
    {
        if (!$this->rappel_habitudes->contains($rappel_habitude)) {
            $this->rappel_habitudes->add($rappel_habitude);
            $rappel_habitude->setIdHabitude($this);
        }

        return $this;
    }

    public function removeRappel_habitude(Rappel_habitude $rappel_habitude): self
    {
        if ($this->rappel_habitudes->removeElement($rappel_habitude) && $rappel_habitude->getIdHabitude() === $this) {
            $rappel_habitude->setIdHabitude(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Suivihabitude>
     */
    public function getSuivihabitudes(): Collection
    {
        return $this->suivihabitudes;
    }

    public function addSuivihabitude(Suivihabitude $suivihabitude): self
    {
        if (!$this->suivihabitudes->contains($suivihabitude)) {
            $this->suivihabitudes->add($suivihabitude);
            $suivihabitude->setIdHabitude($this);
        }

        return $this;
    }

    public function removeSuivihabitude(Suivihabitude $suivihabitude): self
    {
        if ($this->suivihabitudes->removeElement($suivihabitude) && $suivihabitude->getIdHabitude() === $this) {
            $suivihabitude->setIdHabitude(null);
        }

        return $this;
    }

    #[Assert\Callback]
    public function validateTargetValue(ExecutionContextInterface $context): void
    {
        if ($this->habitType === 'BOOLEAN' && !in_array($this->targetValue, [0, 1], true)) {
            $context
                ->buildViolation('Pour une habitude BOOLEAN, la valeur cible doit etre True ou False.')
                ->atPath('targetValue')
                ->addViolation();
        }

        if ($this->habitType === 'NUMERIC' && trim($this->unit) === '') {
            $context
                ->buildViolation('L unite est obligatoire pour une habitude numerique.')
                ->atPath('unit')
                ->addViolation();
        }
    }
}
