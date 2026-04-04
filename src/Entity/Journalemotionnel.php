<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity]
class Journalemotionnel
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idJ;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: 'La note personnelle est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'La note doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'La note ne doit pas depasser {{ limit }} caracteres.'
    )]
    private string $NotePersonnelle;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotNull(message: 'La date de creation est obligatoire.')]
    #[Assert\LessThanOrEqual('now', message: 'La date de creation ne peut pas etre dans le futur.')]
    private \DateTimeInterface $dateCreation;

    public function getIdJ()
    {
        return $this->idJ;
    }

    public function setIdJ($value)
    {
        $this->idJ = $value;
    }

    public function getNotePersonnelle()
    {
        return $this->NotePersonnelle;
    }

    public function setNotePersonnelle($value)
    {
        $this->NotePersonnelle = $value;
    }

    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    public function setDateCreation($value)
    {
        $this->dateCreation = $value;
    }
}
