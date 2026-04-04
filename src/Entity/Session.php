<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Exercice;

#[ORM\Entity]
class Session
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idSession;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateSession;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateFin;

    #[ORM\Column(type: "string", length: 255)]
    private string $Resultat;

    #[ORM\Column(type: "text")]
    private string $commentaires;

    #[ORM\Column(type: "integer")]
    private int $dureeReelle;

    #[ORM\Column(type: "boolean")]
    private bool $terminee;

        #[ORM\ManyToOne(targetEntity: Exercice::class, inversedBy: "sessions")]
    #[ORM\JoinColumn(name: 'idEx', referencedColumnName: 'id_ex', onDelete: 'CASCADE')]
    private Exercice $idEx;

    public function getIdSession()
    {
        return $this->idSession;
    }

    public function setIdSession($value)
    {
        $this->idSession = $value;
    }

    public function getDateSession()
    {
        return $this->dateSession;
    }

    public function setDateSession($value)
    {
        $this->dateSession = $value;
    }

    public function getDateDebut()
    {
        return $this->dateDebut;
    }

    public function setDateDebut($value)
    {
        $this->dateDebut = $value;
    }

    public function getDateFin()
    {
        return $this->dateFin;
    }

    public function setDateFin($value)
    {
        $this->dateFin = $value;
    }

    public function getResultat()
    {
        return $this->Resultat;
    }

    public function setResultat($value)
    {
        $this->Resultat = $value;
    }

    public function getCommentaires()
    {
        return $this->commentaires;
    }

    public function setCommentaires($value)
    {
        $this->commentaires = $value;
    }

    public function getDureeReelle()
    {
        return $this->dureeReelle;
    }

    public function setDureeReelle($value)
    {
        $this->dureeReelle = $value;
    }

    public function getTerminee()
    {
        return $this->terminee;
    }

    public function setTerminee($value)
    {
        $this->terminee = $value;
    }

    public function getIdEx()
    {
        return $this->idEx;
    }

    public function setIdEx($value)
    {
        $this->idEx = $value;
    }
}
