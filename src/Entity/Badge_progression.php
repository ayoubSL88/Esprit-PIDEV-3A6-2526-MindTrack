<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Badge_progression
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $exercices_completes;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getExercices_completes()
    {
        return $this->exercices_completes;
    }

    public function setExercices_completes($value)
    {
        $this->exercices_completes = $value;
    }
}
