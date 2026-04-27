<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
class Journalemotionnel
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idJ;

    #[ORM\Column(type: "text")]
    private string $NotePersonnelle;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotNull(message: 'La date de creation est obligatoire.')]
    #[Assert\LessThanOrEqual('now', message: 'La date de creation ne peut pas etre dans le futur.')]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $screenshotPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $audioPath = null;

    public function getIdJ(): int
    {
        return $this->idJ;
    }

    public function setIdJ(int $value): void
    {
        $this->idJ = $value;
    }

    public function getNotePersonnelle(): string
    {
        return $this->NotePersonnelle;
    }

    public function setNotePersonnelle(string $value): void
    {
        $this->NotePersonnelle = $value;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $value): void
    {
        $this->dateCreation = $value;
    }

    public function getScreenshotPath(): ?string
    {
        return $this->screenshotPath;
    }

    public function setScreenshotPath(?string $value): void
    {
        $this->screenshotPath = $value;
    }

    public function getAudioPath(): ?string
    {
        return $this->audioPath;
    }

    public function setAudioPath(?string $value): void
    {
        $this->audioPath = $value;
    }

    #[Assert\Callback]
    public function validateNotePersonnelle(ExecutionContextInterface $context): void
    {
        $plainText = $this->getPlainTextNote();
        $length = mb_strlen($plainText);

        if ($plainText === '') {
            $context->buildViolation('La note personnelle est obligatoire.')
                ->atPath('notePersonnelle')
                ->addViolation();

            return;
        }

        if ($length < 5) {
            $context->buildViolation('La note doit contenir au moins 5 caracteres utiles.')
                ->atPath('notePersonnelle')
                ->addViolation();
        }

        if ($length > 5000) {
            $context->buildViolation('La note ne doit pas depasser 5000 caracteres utiles.')
                ->atPath('notePersonnelle')
                ->addViolation();
        }
    }

    public function getPlainTextNote(): string
    {
        $plainText = html_entity_decode(strip_tags($this->NotePersonnelle), \ENT_QUOTES | \ENT_HTML5);
        $plainText = preg_replace('/\s+/u', ' ', $plainText) ?? '';

        return trim($plainText);
    }
}
