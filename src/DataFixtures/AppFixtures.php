<?php
namespace App\DataFixtures;

use App\Entity\Exercice;
use App\Entity\Utilisateur;
use App\Entity\Session;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // Créer 10 exercices
        $exercices = [
            ['Respiration carrée', 'Respiration', 5, 'FACILE', 'Une technique simple...', 'Inspirez 4s, retenez 4s, expirez 4s...'],
            ['Scan corporel', 'Méditation', 15, 'MOYEN', 'Parcourez chaque partie...', 'Allongez-vous, fermez les yeux...'],
            ['Méditation de pleine conscience', 'Méditation', 10, 'MOYEN', 'Soyez présent dans l\'instant...', 'Asseyez-vous confortablement...'],
            ['Journal de gratitude', 'Gratitude', 5, 'FACILE', 'Notez 3 choses positives...', 'Prenez un carnet...'],
            ['5-4-3-2-1', 'Anxiété', 3, 'FACILE', 'Technique d\'ancrage...', 'Nommez 5 choses que vous voyez...'],
            ['Danse de la victoire', 'Bien-être', 3, 'FACILE', 'Bougez votre corps...', 'Mettez votre musique préférée...'],
            ['Respiration alternée', 'Respiration', 8, 'MOYEN', 'Équilibre les énergies...', 'Bouchez la narine droite...'],
            ['Visualisation positive', 'Visualisation', 10, 'MOYEN', 'Imaginez votre succès...', 'Fermez les yeux...'],
            ['Auto-compassion', 'Méditation', 12, 'DIFFICILE', 'Soyez bienveillant...', 'Placez vos mains sur votre cœur...'],
            ['Rituel du matin', 'Routine', 20, 'DIFFICILE', 'Commencez la journée...', 'Levez-vous, étirez-vous...'],
        ];

        foreach ($exercices as $data) {
            $exercice = new Exercice();
            $exercice->setNom($data[0]);
            $exercice->setType($data[1]);
            $exercice->setDuree($data[2]);
            $exercice->setDifficulte($data[3]);
            $exercice->setDescription($data[4]);
            $exercice->setDemarche($data[5]);
            $manager->persist($exercice);
        }

        // Créer un utilisateur admin
        $admin = new Utilisateur();
        $admin->setNomU('Admin');
        $admin->setPrenomU('System');
        $admin->setEmailU('admin@admin.com');
        $admin->setMdpsU($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setAgeU(30);
        $admin->setRoleU('ROLE_ADMIN');
        $admin->setFace_enabled(false);
        $admin->setTotp_enabled(false);
        $manager->persist($admin);

        $manager->flush();
    }
}