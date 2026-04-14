<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Todo;
use App\Entity\Session;
use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity(repositoryClass: ExerciceRepository::class)]
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

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    private string $description;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La démarche est obligatoire")]
    private string $demarche;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_creation;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_modification;

    #[ORM\OneToMany(mappedBy: "exercice", targetEntity: Session::class, cascade: ["remove"])]
    private Collection $sessions;

    #[ORM\OneToMany(mappedBy: "idExercice", targetEntity: Todo::class)]
    private Collection $todos;

    // Constructeur pour initialiser les collections
    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->todos = new ArrayCollection();
        $now = new \DateTime();
        $this->date_creation = $now;
        $this->date_modification = $now;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $now = new \DateTime();
        $this->date_creation = $now;
        $this->date_modification = $now;
    }

    #[ORM\PreUpdate]
    public function updateDateModification(): void
    {
        $this->date_modification = new \DateTime();
    }

    public function getIdEx()
    {
        return $this->idEx;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value): self
    {
        $this->nom = $value;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value): self
    {
        $this->type = $value;
        return $this;
    }

    public function getDuree()
    {
        return $this->duree;
    }

    public function setDuree($value):self
    {
        $this->duree = $value;
        return $this;
    }

    public function getDifficulte()
    {
        return $this->difficulte;
    }

    public function setDifficulte($value): self
    {
        $this->difficulte = $value;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value): self
    {
        $this->description = $value;
        return $this;
    }

    public function getDemarche()
    {
        return $this->demarche;
    }

    public function setDemarche($value): self
    {
        $this->demarche = $value;
        return $this;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $value): self
    {
        $this->date_creation = $value;

        return $this;
    }

    public function setDate_creation(\DateTimeInterface $value): self
    {
        return $this->setDateCreation($value);
    }

    public function getDateModification(): \DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(\DateTimeInterface $value): self
    {
        $this->date_modification = $value;

        return $this;
    }

    public function setDate_modification(\DateTimeInterface $value): self
    {
        return $this->setDateModification($value);
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
            $session->setExercice($this);
        }
        return $this;
    }

    public function removeSession(Session $session): self
    {
        $this->sessions->removeElement($session);

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
        $this->todos->removeElement($todo);

        return $this;
    }
    
}
