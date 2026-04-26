<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity(fields: ['emailU'], message: 'There is already an account with this email')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', name: 'id_u')]
    private ?int $idU = null;

    #[ORM\Column(type: 'string', length: 255, name: 'nom_u')]
    private string $nomU;

    #[ORM\Column(type: 'string', length: 255, name: 'prenom_u')]
    private string $prenomU;

    #[ORM\Column(type: 'string', length: 255, name: 'email_u')]
    private string $emailU;

    #[ORM\Column(type: 'string', length: 255, name: 'mdps_u')]
    private string $mdpsU;

    #[ORM\Column(type: 'integer', name: 'age_u')]
    private int $ageU;

    #[ORM\Column(type: 'string', length: 20, name: 'role_u')]
    private string $roleU;

    #[ORM\Column(type: 'string', length: 255)]
    private string $face_subject;

    #[ORM\Column(type: 'string', length: 64)]
    private string $face_image_id;

    #[ORM\Column(type: 'boolean')]
    private bool $face_enabled;

    #[ORM\Column(type: 'string', length: 512)]
    private string $profile_picture_path;

    #[ORM\Column(type: 'string', length: 128)]
    private string $totp_secret;

    #[ORM\Column(type: 'boolean')]
    private bool $totp_enabled;

    #[ORM\OneToMany(mappedBy: 'idU', targetEntity: Habitude::class)]
    private Collection $habitudes;

    #[ORM\OneToMany(mappedBy: 'user_id', targetEntity: Password_reset_tokens::class)]
    private Collection $password_reset_tokenss;

    #[ORM\OneToMany(mappedBy: 'idU', targetEntity: Profilpsychologique::class)]
    private Collection $profilpsychologiques;

    public function __construct()
    {
        $this->habitudes = new ArrayCollection();
        $this->password_reset_tokenss = new ArrayCollection();
        $this->profilpsychologiques = new ArrayCollection();
    }

    public function getIdU(): ?int
    {
        return $this->idU;
    }

    public function setIdU(?int $value): self
    {
        $this->idU = $value;

        return $this;
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

    public function getFaceSubject()
    {
        return $this->face_subject;
    }

    public function getFace_subject()
    {
        return $this->getFaceSubject();
    }

    public function setFaceSubject($value)
    {
        $this->face_subject = $value;
    }

    public function setFace_subject($value)
    {
        $this->setFaceSubject($value);
    }

    public function getFaceImageId()
    {
        return $this->face_image_id;
    }

    public function getFace_image_id()
    {
        return $this->getFaceImageId();
    }

    public function setFaceImageId($value)
    {
        $this->face_image_id = $value;
    }

    public function setFace_image_id($value)
    {
        $this->setFaceImageId($value);
    }

    public function isFaceEnabled(): bool
    {
        return $this->face_enabled;
    }

    public function getFace_enabled(): bool
    {
        return $this->isFaceEnabled();
    }

    public function setFaceEnabled(bool $value): void
    {
        $this->face_enabled = $value;
    }

    public function setFace_enabled(bool $value): void
    {
        $this->setFaceEnabled($value);
    }

    public function getProfilePicturePath()
    {
        return $this->profile_picture_path;
    }

    public function getProfile_picture_path()
    {
        return $this->getProfilePicturePath();
    }

    public function setProfilePicturePath($value)
    {
        $this->profile_picture_path = $value;
    }

    public function setProfile_picture_path($value)
    {
        $this->setProfilePicturePath($value);
    }

    public function getTotpSecret()
    {
        return $this->totp_secret;
    }

    public function getTotp_secret()
    {
        return $this->getTotpSecret();
    }

    public function setTotpSecret($value)
    {
        $this->totp_secret = $value;
    }

    public function setTotp_secret($value)
    {
        $this->setTotpSecret($value);
    }

    public function isTotpEnabled(): bool
    {
        return $this->totp_enabled;
    }

    public function getTotp_enabled(): bool
    {
        return $this->isTotpEnabled();
    }

    public function setTotpEnabled(bool $value): void
    {
        $this->totp_enabled = $value;
    }

    public function setTotp_enabled(bool $value): void
    {
        $this->setTotpEnabled($value);
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

    public function getHabitudes(): Collection
    {
        return $this->habitudes;
    }

    public function addHabitude(Habitude $habitude): self
    {
        if (!$this->habitudes->contains($habitude)) {
            $this->habitudes->add($habitude);
            $habitude->setIdU($this);
        }

        return $this;
    }

    public function removeHabitude(Habitude $habitude): self
    {
        if ($this->habitudes->removeElement($habitude) && $habitude->getIdU() === $this) {
            $habitude->setIdU(null);
        }

        return $this;
    }

    public function getPasswordResetTokenss(): Collection
    {
        return $this->password_reset_tokenss;
    }

    public function getPassword_reset_tokenss(): Collection
    {
        return $this->getPasswordResetTokenss();
    }

    public function addPasswordResetTokens(Password_reset_tokens $password_reset_tokens): self
    {
        if (!$this->password_reset_tokenss->contains($password_reset_tokens)) {
            $this->password_reset_tokenss[] = $password_reset_tokens;
            $password_reset_tokens->setUser_id($this);
        }

        return $this;
    }

    public function addPassword_reset_tokens(Password_reset_tokens $password_reset_tokens): self
    {
        return $this->addPasswordResetTokens($password_reset_tokens);
    }

    public function removePasswordResetTokens(Password_reset_tokens $password_reset_tokens): self
    {
        if ($this->password_reset_tokenss->removeElement($password_reset_tokens)) {
            if ($password_reset_tokens->getUser_id() === $this) {
                $password_reset_tokens->setUser_id(null);
            }
        }

        return $this;
    }

    public function removePassword_reset_tokens(Password_reset_tokens $password_reset_tokens): self
    {
        return $this->removePasswordResetTokens($password_reset_tokens);
    }

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
            if ($profilpsychologique->getIdU() === $this) {
                $profilpsychologique->setIdU(null);
            }
        }

        return $this;
    }
}
