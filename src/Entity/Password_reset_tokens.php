<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Password_reset_tokens
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "password_reset_tokenss")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'idU', onDelete: 'CASCADE')]
    private Utilisateur $user_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $code_hash;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $expires_at;

    #[ORM\Column(type: "boolean")]
    private bool $used;

    #[ORM\Column(type: "integer")]
    private int $attempts;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getCode_hash()
    {
        return $this->code_hash;
    }

    public function setCode_hash($value)
    {
        $this->code_hash = $value;
    }

    public function getExpires_at()
    {
        return $this->expires_at;
    }

    public function setExpires_at($value)
    {
        $this->expires_at = $value;
    }

    public function getUsed()
    {
        return $this->used;
    }

    public function setUsed($value)
    {
        $this->used = $value;
    }

    public function getAttempts()
    {
        return $this->attempts;
    }

    public function setAttempts($value)
    {
        $this->attempts = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }
}
