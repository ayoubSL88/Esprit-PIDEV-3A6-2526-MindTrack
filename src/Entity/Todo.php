<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Exercice;

#[ORM\Entity]
class Todo
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idTodo;

    #[ORM\Column(type: "string", length: 255)]
    private string $titre;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string", length: 50)]
    private string $statut;

    #[ORM\Column(type: "string", length: 50)]
    private string $priorite;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateEcheance;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateCompletion;

        #[ORM\ManyToOne(targetEntity: Exercice::class, inversedBy: "todos")]
    #[ORM\JoinColumn(name: 'idExercice', referencedColumnName: 'id_ex', onDelete: 'CASCADE')]
    private Exercice $idExercice;

    #[ORM\Column(type: "integer")]
    private int $tempsEstime;

    #[ORM\Column(type: "integer")]
    private int $progression;

    #[ORM\Column(type: "text")]
    private string $notes;

    #[ORM\Column(type: "string", length: 7)]
    private string $couleur;

    public function getIdTodo()
    {
        return $this->idTodo;
    }

    public function setIdTodo($value)
    {
        $this->idTodo = $value;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($value)
    {
        $this->titre = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    public function getPriorite()
    {
        return $this->priorite;
    }

    public function setPriorite($value)
    {
        $this->priorite = $value;
    }

    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    public function setDateCreation($value)
    {
        $this->dateCreation = $value;
    }

    public function getDateEcheance()
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance($value)
    {
        $this->dateEcheance = $value;
    }

    public function getDateCompletion()
    {
        return $this->dateCompletion;
    }

    public function setDateCompletion($value)
    {
        $this->dateCompletion = $value;
    }

    public function getIdExercice()
    {
        return $this->idExercice;
    }

    public function setIdExercice($value)
    {
        $this->idExercice = $value;
    }

    public function getTempsEstime()
    {
        return $this->tempsEstime;
    }

    public function setTempsEstime($value)
    {
        $this->tempsEstime = $value;
    }

    public function getProgression()
    {
        return $this->progression;
    }

    public function setProgression($value)
    {
        $this->progression = $value;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes($value)
    {
        $this->notes = $value;
    }

    public function getCouleur()
    {
        return $this->couleur;
    }

    public function setCouleur($value)
    {
        $this->couleur = $value;
    }
}
