<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Suivihabitude;

#[ORM\Entity]
class Habitude
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idHabitude;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(type: "string", length: 255)]
    private string $frequence;

    #[ORM\Column(type: "string", length: 255)]
    private string $objectif;

    #[ORM\Column(type: "string", length: 10)]
    private string $habitType;

    #[ORM\Column(type: "integer")]
    private int $targetValue;

    #[ORM\Column(type: "string", length: 20)]
    private string $unit;

    public function getIdHabitude()
    {
        return $this->idHabitude;
    }

    public function setIdHabitude($value)
    {
        $this->idHabitude = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getFrequence()
    {
        return $this->frequence;
    }

    public function setFrequence($value)
    {
        $this->frequence = $value;
    }

    public function getObjectif()
    {
        return $this->objectif;
    }

    public function setObjectif($value)
    {
        $this->objectif = $value;
    }

    public function getHabitType()
    {
        return $this->habitType;
    }

    public function setHabitType($value)
    {
        $this->habitType = $value;
    }

    public function getTargetValue()
    {
        return $this->targetValue;
    }

    public function setTargetValue($value)
    {
        $this->targetValue = $value;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function setUnit($value)
    {
        $this->unit = $value;
    }

    #[ORM\OneToMany(mappedBy: "idHabitude", targetEntity: Rappel_habitude::class)]
    private Collection $rappel_habitudes;

        public function getRappel_habitudes(): Collection
        {
            return $this->rappel_habitudes;
        }
    
        public function addRappel_habitude(Rappel_habitude $rappel_habitude): self
        {
            if (!$this->rappel_habitudes->contains($rappel_habitude)) {
                $this->rappel_habitudes[] = $rappel_habitude;
                $rappel_habitude->setIdHabitude($this);
            }
    
            return $this;
        }
    
        public function removeRappel_habitude(Rappel_habitude $rappel_habitude): self
        {
            if ($this->rappel_habitudes->removeElement($rappel_habitude)) {
                // set the owning side to null (unless already changed)
                if ($rappel_habitude->getIdHabitude() === $this) {
                    $rappel_habitude->setIdHabitude(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "idHabitude", targetEntity: Suivihabitude::class)]
    private Collection $suivihabitudes;
}
