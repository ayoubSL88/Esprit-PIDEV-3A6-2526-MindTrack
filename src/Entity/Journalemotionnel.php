<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Journalemotionnel
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idJ;

    #[ORM\Column(type: "string", length: 255)]
    private string $NotePersonnelle;

    #[ORM\Column(type: "datetime")]
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
