<?php

namespace App\Service\GestionHumeur;

use App\Entity\Journalemotionnel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class JournalAttachmentManager
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $screenshotUploadDir,
        private readonly string $audioUploadDir,
        private readonly Filesystem $filesystem,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function updateJournalAttachments(
        Journalemotionnel $journal,
        ?UploadedFile $screenshotFile,
        ?UploadedFile $audioFile,
        bool $removeScreenshot = false,
        bool $removeAudio = false,
    ): void {
        $journal->setScreenshotPath($this->updateStoredFile(
            $screenshotFile,
            $journal->getScreenshotPath(),
            $this->screenshotUploadDir,
            $removeScreenshot,
        ));

        $journal->setAudioPath($this->updateStoredFile(
            $audioFile,
            $journal->getAudioPath(),
            $this->audioUploadDir,
            $removeAudio,
        ));
    }

    public function removeJournalAttachments(Journalemotionnel $journal): void
    {
        $this->removeStoredFile($journal->getScreenshotPath());
        $this->removeStoredFile($journal->getAudioPath());

        $journal->setScreenshotPath(null);
        $journal->setAudioPath(null);
    }

    private function updateStoredFile(
        ?UploadedFile $file,
        ?string $currentPath,
        string $relativeDirectory,
        bool $removeCurrent,
    ): ?string {
        if ($removeCurrent && null === $file) {
            $this->removeStoredFile($currentPath);

            return null;
        }

        if (null === $file) {
            return $currentPath;
        }

        $newPath = $this->storeUploadedFile($file, $relativeDirectory);
        $this->removeStoredFile($currentPath);

        return $newPath;
    }

    private function storeUploadedFile(UploadedFile $file, string $relativeDirectory): string
    {
        $absoluteDirectory = $this->projectDir.'/public/'.$relativeDirectory;
        $this->filesystem->mkdir($absoluteDirectory);

        $originalName = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeName = (string) $this->slugger->slug($originalName);
        if ($safeName === '') {
            $safeName = 'journal-file';
        }

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $filename = sprintf('%s-%s.%s', $safeName, bin2hex(random_bytes(8)), $extension);

        try {
            $file->move($absoluteDirectory, $filename);
        } catch (FileException $exception) {
            throw new \RuntimeException('The attachment could not be uploaded. Please try again.', 0, $exception);
        }

        return trim($relativeDirectory.'/'.$filename, '/');
    }

    private function removeStoredFile(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $absolutePath = $this->projectDir.'/public/'.$relativePath;
        if (is_file($absolutePath)) {
            $this->filesystem->remove($absolutePath);
        }
    }
}
