<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Todo;

#[ORM\Entity]
class Exercice
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idEx;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(type: "string", length: 255)]
    private string $type;

    #[ORM\Column(type: "integer")]
    private int $duree;

    #[ORM\Column(type: "string", length: 50)]
    private string $difficulte;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "text")]
    private string $demarche;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_creation;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_modification;

    public function getIdEx()
    {
        return $this->idEx;
    }

    public function setIdEx($value)
    {
        $this->idEx = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getDuree()
    {
        return $this->duree;
    }

    public function setDuree($value)
    {
        $this->duree = $value;
    }

    public function getDifficulte()
    {
        return $this->difficulte;
    }

    public function setDifficulte($value)
    {
        $this->difficulte = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getDemarche()
    {
        return $this->demarche;
    }

    public function setDemarche($value)
    {
        $this->demarche = $value;
    }

    public function getDate_creation()
    {
        return $this->date_creation;
    }

    public function setDate_creation($value)
    {
        $this->date_creation = $value;
    }

    public function getDate_modification()
    {
        return $this->date_modification;
    }

    public function setDate_modification($value)
    {
        $this->date_modification = $value;
    }

    #[ORM\OneToMany(mappedBy: "idEx", targetEntity: Session::class)]
    private Collection $sessions;

        public function getSessions(): Collection
        {
            return $this->sessions;
        }
    
        public function addSession(Session $session): self
        {
            if (!$this->sessions->contains($session)) {
                $this->sessions[] = $session;
                $session->setIdEx($this);
            }
    
            return $this;
        }
    
        public function removeSession(Session $session): self
        {
            if ($this->sessions->removeElement($session)) {
                // set the owning side to null (unless already changed)
                if ($session->getIdEx() === $this) {
                    $session->setIdEx(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "idExercice", targetEntity: Todo::class)]
    private Collection $todos;

        public function getTodos(): Collection
        {
            return $this->todos;
        }
    
        public function addTodo(Todo $todo): self
        {
            if (!$this->todos->contains($todo)) {
                $this->todos[] = $todo;
                $todo->setIdExercice($this);
            }
    
            return $this;
        }
    
        public function removeTodo(Todo $todo): self
        {
            if ($this->todos->removeElement($todo)) {
                // set the owning side to null (unless already changed)
                if ($todo->getIdExercice() === $this) {
                    $todo->setIdExercice(null);
                }
            }
    
            return $this;
        }
}
