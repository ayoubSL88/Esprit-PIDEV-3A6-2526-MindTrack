<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Todo;
use App\Entity\Session;
use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Exercice
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private int $idEx;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Minimum 3 caractères")]
    private string $nom;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le type est obligatoire")]
    private string $type;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank(message: "La durée est obligatoire")]
    #[Assert\Positive(message: "La durée doit être un nombre positif")]
    private int $duree;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: "La difficulté est obligatoire")]
    #[Assert\Choice(choices: ["FACILE", "MOYEN", "DIFFICILE"], message: "Difficulté invalide")]
    private string $difficulte;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    private ?string $description = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\NotBlank(message: "La démarche est obligatoire")]
    private ?string $demarche = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $date_modification = null;

    #[ORM\OneToMany(mappedBy: "idEx", targetEntity: Session::class)]
    private Collection $sessions;

    #[ORM\OneToMany(mappedBy: "idExercice", targetEntity: Todo::class)]
    private Collection $todos;

    // Constructeur pour initialiser les collections
    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->todos = new ArrayCollection();
        $this->date_creation = new \DateTime(); // Date automatique à la création
        // $this->date_modification = null; // Pas de date de modification à la création
    }
    //Appelé automatiquement avant la persistance (création)
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->date_creation = new \DateTime();
        // $this->date_modification = null; // Pas de date de modification à la création
    }

    // Met à jour automatiquement la date de modification
    #[ORM\PreUpdate]
    public function updateDateModification(): void
    {
        $this->date_modification = new \DateTime();
    }
    // Getters et setters
    public function getIdEx()
    {
        return $this->idEx;
    }

    /*public function setIdEx($value)
    {
        $this->idEx = $value;
        return $this;
    }*/

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    public function getDuree()
    {
        return $this->duree;
    }

    public function setDuree($value)
    {
        $this->duree = $value;
        return $this;
    }

    public function getDifficulte()
    {
        return $this->difficulte;
    }

    public function setDifficulte($value)
    {
        $this->difficulte = $value;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }

    public function getDemarche()
    {
        return $this->demarche;
    }

    public function setDemarche($value)
    {
        $this->demarche = $value;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    // Méthodes pour les sessions
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
            if ($session->getIdEx() === $this) {
                $session->setIdEx(null);
            }
        }
        return $this;
    }

    // Méthodes pour les todos
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
            if ($todo->getIdExercice() === $this) {
                $todo->setIdExercice(null);
            }
        }
        return $this;
    }
    
}
