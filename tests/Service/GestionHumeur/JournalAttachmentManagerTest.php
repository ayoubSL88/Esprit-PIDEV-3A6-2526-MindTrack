<?php

declare(strict_types=1);

namespace App\Tests\Service\GestionHumeur;

use App\Entity\Journalemotionnel;
use App\Service\GestionHumeur\JournalAttachmentManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class JournalAttachmentManagerTest extends TestCase
{
    public function testUpdateJournalAttachmentsRemovesCurrentPathsWhenRequested(): void
    {
        $tmp = sys_get_temp_dir() . '/mindtrack-tests-' . bin2hex(random_bytes(4));
        mkdir($tmp . '/public/uploads/screens', 0777, true);
        mkdir($tmp . '/public/uploads/audio', 0777, true);
        file_put_contents($tmp . '/public/uploads/screens/a.png', 'x');
        file_put_contents($tmp . '/public/uploads/audio/a.mp3', 'x');

        $service = new JournalAttachmentManager($tmp, 'uploads/screens', 'uploads/audio', new Filesystem(), new AsciiSlugger());
        $journal = new Journalemotionnel();
        $journal->setScreenshotPath('uploads/screens/a.png');
        $journal->setAudioPath('uploads/audio/a.mp3');

        $service->updateJournalAttachments($journal, null, null, true, true);

        self::assertNull($journal->getScreenshotPath());
        self::assertNull($journal->getAudioPath());
        self::assertFileDoesNotExist($tmp . '/public/uploads/screens/a.png');
        self::assertFileDoesNotExist($tmp . '/public/uploads/audio/a.mp3');
    }

    public function testRemoveJournalAttachmentsClearsPaths(): void
    {
        $tmp = sys_get_temp_dir() . '/mindtrack-tests-' . bin2hex(random_bytes(4));
        mkdir($tmp . '/public/uploads/screens', 0777, true);
        file_put_contents($tmp . '/public/uploads/screens/b.png', 'x');

        $service = new JournalAttachmentManager($tmp, 'uploads/screens', 'uploads/audio', new Filesystem(), new AsciiSlugger());
        $journal = new Journalemotionnel();
        $journal->setScreenshotPath('uploads/screens/b.png');
        $journal->setAudioPath(null);

        $service->removeJournalAttachments($journal);

        self::assertNull($journal->getScreenshotPath());
        self::assertNull($journal->getAudioPath());
        self::assertFileDoesNotExist($tmp . '/public/uploads/screens/b.png');
    }
}

