<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Profilpsychologique;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idU;

    #[ORM\Column(type: "string", length: 255)]
    private string $nomU;

    #[ORM\Column(type: "string", length: 255)]
    private string $prenomU;

    #[ORM\Column(type: "string", length: 255)]
    private string $emailU;

    #[ORM\Column(type: "string", length: 255)]
    private string $mdpsU;

    #[ORM\Column(type: "integer")]
    private int $ageU;

    #[ORM\Column(type: "string", length: 20)]
    private string $roleU;

    #[ORM\Column(type: "string", length: 255)]
    private string $face_subject;

    #[ORM\Column(type: "string", length: 64)]
    private string $face_image_id;

    #[ORM\Column(type: "boolean")]
    private bool $face_enabled;

    #[ORM\Column(type: "string", length: 512)]
    private string $profile_picture_path;

    #[ORM\Column(type: "string", length: 128)]
    private string $totp_secret;

    #[ORM\Column(type: "boolean")]
    private bool $totp_enabled;

    public function getIdU()
    {
        return $this->idU;
    }

    public function setIdU($value)
    {
        $this->idU = $value;
    }

    public function getNomU()
    {
        return $this->nomU;
    }

    public function setNomU($value)
    {
        $this->nomU = $value;
    }

    public function getPrenomU()
    {
        return $this->prenomU;
    }

    public function setPrenomU($value)
    {
        $this->prenomU = $value;
    }

    public function getEmailU()
    {
        return $this->emailU;
    }

    public function setEmailU($value)
    {
        $this->emailU = $value;
    }

    public function getMdpsU()
    {
        return $this->mdpsU;
    }

    public function setMdpsU($value)
    {
        $this->mdpsU = $value;
    }

    public function getAgeU()
    {
        return $this->ageU;
    }

    public function setAgeU($value)
    {
        $this->ageU = $value;
    }

    public function getRoleU()
    {
        return $this->roleU;
    }

    public function setRoleU($value)
    {
        $this->roleU = $value;
    }

    public function getFace_subject()
    {
        return $this->face_subject;
    }

    public function setFace_subject($value)
    {
        $this->face_subject = $value;
    }

    public function getFace_image_id()
    {
        return $this->face_image_id;
    }

    public function setFace_image_id($value)
    {
        $this->face_image_id = $value;
    }

    public function getFace_enabled()
    {
        return $this->face_enabled;
    }

    public function setFace_enabled($value)
    {
        $this->face_enabled = $value;
    }

    public function getProfile_picture_path()
    {
        return $this->profile_picture_path;
    }

    public function setProfile_picture_path($value)
    {
        $this->profile_picture_path = $value;
    }

    public function getTotp_secret()
    {
        return $this->totp_secret;
    }

    public function setTotp_secret($value)
    {
        $this->totp_secret = $value;
    }

    public function getTotp_enabled()
    {
        return $this->totp_enabled;
    }

    public function setTotp_enabled($value)
    {
        $this->totp_enabled = $value;
    }

    public function getUserIdentifier(): string
    {
        return $this->emailU;
    }

    public function getRoles(): array
    {
        if ($this->roleU === 'ADMIN' || $this->roleU === 'ROLE_ADMIN') {
            return ['ROLE_ADMIN'];
        }

        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->mdpsU;
    }

    public function eraseCredentials(): void
    {
    }

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: Password_reset_tokens::class)]
    private Collection $password_reset_tokenss;

        public function getPassword_reset_tokenss(): Collection
        {
            return $this->password_reset_tokenss;
        }
    
        public function addPassword_reset_tokens(Password_reset_tokens $password_reset_tokens): self
        {
            if (!$this->password_reset_tokenss->contains($password_reset_tokens)) {
                $this->password_reset_tokenss[] = $password_reset_tokens;
                $password_reset_tokens->setUser_id($this);
            }
    
            return $this;
        }
    
        public function removePassword_reset_tokens(Password_reset_tokens $password_reset_tokens): self
        {
            if ($this->password_reset_tokenss->removeElement($password_reset_tokens)) {
                // set the owning side to null (unless already changed)
                if ($password_reset_tokens->getUser_id() === $this) {
                    $password_reset_tokens->setUser_id(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "idU", targetEntity: Profilpsychologique::class)]
    private Collection $profilpsychologiques;

        public function getProfilpsychologiques(): Collection
        {
            return $this->profilpsychologiques;
        }
    
        public function addProfilpsychologique(Profilpsychologique $profilpsychologique): self
        {
            if (!$this->profilpsychologiques->contains($profilpsychologique)) {
                $this->profilpsychologiques[] = $profilpsychologique;
                $profilpsychologique->setIdU($this);
            }
    
            return $this;
        }
    
        public function removeProfilpsychologique(Profilpsychologique $profilpsychologique): self
        {
            if ($this->profilpsychologiques->removeElement($profilpsychologique)) {
                // set the owning side to null (unless already changed)
                if ($profilpsychologique->getIdU() === $this) {
                    $profilpsychologique->setIdU(null);
                }
            }
    
            return $this;
        }
}
