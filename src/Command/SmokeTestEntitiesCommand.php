<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Password_reset_tokens;
use App\Entity\Habitude;
use App\Entity\Exercice;
use App\Entity\Jalonprogression;
use App\Entity\Objectif;
use App\Entity\Rappel_habitude;
use App\Entity\Session;
use App\Entity\Suivihabitude;
use App\Entity\Todo;
use App\Entity\Utilisateur;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:smoke-test:entities',
    description: 'Safely smoke-test Doctrine entities by persisting inside a transaction and rolling back.',
)]
final class SmokeTestEntitiesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Entity smoke test (transaction rollback)');

        $useSavepoint = false;
        $savepointName = 'smoke_test_entities';

        if ($this->connection->isTransactionActive()) {
            $this->connection->createSavepoint($savepointName);
            $useSavepoint = true;
        } else {
            $this->connection->beginTransaction();
        }

        try {
            $io->section('Testing Utilisateur + Password_reset_tokens relation');

            $nextUserId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_u), 0) + 1 FROM utilisateur');
            $nextTokenId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id), 0) + 1 FROM password_reset_tokens');

            $user = new Utilisateur();
            $user->setIdU($nextUserId);
            $user->setNomU('Smoke');
            $user->setPrenomU('Test');
            $user->setEmailU(sprintf('smoke-test-%d@example.local', $nextUserId));
            $user->setMdpsU('not-a-real-password');
            $user->setAgeU(30);
            $user->setRoleU('USER');
            $user->setFace_subject('');
            $user->setFace_image_id('');
            $user->setFace_enabled(false);
            $user->setProfile_picture_path('');
            $user->setTotp_secret('');
            $user->setTotp_enabled(false);

            $token = new Password_reset_tokens();
            $token->setId($nextTokenId);
            $token->setUser_id($user);
            $token->setCode_hash('dummy_hash');
            $token->setExpires_at(new DateTime('+1 day'));
            $token->setUsed(false);
            $token->setAttempts(0);
            $token->setCreated_at(new DateTime());

            $this->entityManager->persist($user);
            $this->entityManager->persist($token);
            $this->entityManager->flush();

            /** @var Password_reset_tokens|null $reloaded */
            $reloaded = $this->entityManager->find(Password_reset_tokens::class, $nextTokenId);

            if ($reloaded === null) {
                $io->error('Reload of Password_reset_tokens failed (find() returned null).');
                throw new \RuntimeException('Entity reload failed');
            }

            $reloadedUser = $reloaded->getUser_id();
            if ($reloadedUser === null || $reloadedUser->getIdU() !== $nextUserId) {
                $io->error('Reloaded relation user_id does not match expected Utilisateur.');
                throw new \RuntimeException('Relation check failed');
            }

            $io->success('Persist + reload succeeded, relation looks correct.');

            $io->section('Testing Objectif + Jalonprogression relation');

            $nextObjectifId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_obj), 0) + 1 FROM objectif');
            $nextJalonId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_jalon), 0) + 1 FROM jalonprogression');

            $objectif = new Objectif();
            $objectif->setIdObj($nextObjectifId);
            $objectif->setTitre('Smoke objectif');
            $objectif->setDescriprion('Smoke description');
            $objectif->setDateDebut(new DateTime('today'));
            $objectif->setDateFin(new DateTime('+7 days'));
            $objectif->setStatut('EN_COURS');

            $jalon = new Jalonprogression();
            $jalon->setIdJalon($nextJalonId);
            $jalon->setIdObj($objectif);
            $jalon->setTitre('Smoke jalon');
            $jalon->setDateCible(new DateTime('+3 days'));
            $jalon->setAtteint(false);
            $jalon->setDateAtteinte(new DateTime('today'));
            $jalon->setPourcentageProgression(0);

            $this->entityManager->persist($objectif);
            $this->entityManager->persist($jalon);
            $this->entityManager->flush();

            /** @var Jalonprogression|null $reloadedJalon */
            $reloadedJalon = $this->entityManager->find(Jalonprogression::class, $nextJalonId);

            if ($reloadedJalon === null) {
                $io->error('Reload of Jalonprogression failed (find() returned null).');
                throw new \RuntimeException('Entity reload failed');
            }

            $reloadedObjectif = $reloadedJalon->getIdObj();
            if ($reloadedObjectif === null || $reloadedObjectif->getIdObj() !== $nextObjectifId) {
                $io->error('Reloaded relation idObj does not match expected Objectif.');
                throw new \RuntimeException('Relation check failed');
            }

            $io->success('Persist + reload succeeded, relation looks correct.');

            $io->section('Testing Habitude + Rappel_habitude relation');

            $nextHabitudeId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_habitude), 0) + 1 FROM habitude');
            $nextRappelId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_rappel), 0) + 1 FROM rappel_habitude');

            $habitude = new Habitude();
            $habitude->setIdHabitude($nextHabitudeId);
            $habitude->setNom('Smoke habitude');
            $habitude->setFrequence('QUOTIDIEN');
            $habitude->setObjectif('Smoke objectif');
            $habitude->setHabitType('BOOLEAN');
            $habitude->setTargetValue(1);
            $habitude->setUnit('fois');

            $rappel = new Rappel_habitude();
            $rappel->setIdRappel($nextRappelId);
            $rappel->setIdHabitude($habitude);
            $rappel->setHeureRappel('08:00');
            $rappel->setJours('Lun,Mar');
            $rappel->setActif(true);
            $rappel->setMessage('Smoke rappel');
            $rappel->setCreatedAt(new DateTime());
            $rappel->setAutoGenerated(false);

            $this->entityManager->persist($habitude);
            $this->entityManager->persist($rappel);
            $this->entityManager->flush();

            /** @var Rappel_habitude|null $reloadedRappel */
            $reloadedRappel = $this->entityManager->find(Rappel_habitude::class, $nextRappelId);

            if ($reloadedRappel === null) {
                $io->error('Reload of Rappel_habitude failed (find() returned null).');
                throw new \RuntimeException('Entity reload failed');
            }

            $reloadedHabitude = $reloadedRappel->getIdHabitude();
            if ($reloadedHabitude === null || $reloadedHabitude->getIdHabitude() !== $nextHabitudeId) {
                $io->error('Reloaded relation idHabitude does not match expected Habitude.');
                throw new \RuntimeException('Relation check failed');
            }

            $io->success('Persist + reload succeeded, relation looks correct.');

            $io->section('Testing Habitude + Suivihabitude relation');

            $nextHabitudeId2 = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_habitude), 0) + 1 FROM habitude');
            $nextSuiviId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_suivi), 0) + 1 FROM suivihabitude');

            $habitude2 = new Habitude();
            $habitude2->setIdHabitude($nextHabitudeId2);
            $habitude2->setNom('Smoke habitude (suivi)');
            $habitude2->setFrequence('QUOTIDIEN');
            $habitude2->setObjectif('Smoke objectif');
            $habitude2->setHabitType('NUMERIC');
            $habitude2->setTargetValue(10);
            $habitude2->setUnit('min');

            $suivi = new Suivihabitude();
            $suivi->setIdSuivi($nextSuiviId);
            $suivi->setIdHabitude($habitude2);
            $suivi->setDate(new DateTime('today'));
            $suivi->setEtat(true);
            $suivi->setValeur(5);

            $this->entityManager->persist($habitude2);
            $this->entityManager->persist($suivi);
            $this->entityManager->flush();

            /** @var Suivihabitude|null $reloadedSuivi */
            $reloadedSuivi = $this->entityManager->find(Suivihabitude::class, $nextSuiviId);

            if ($reloadedSuivi === null) {
                $io->error('Reload of Suivihabitude failed (find() returned null).');
                throw new \RuntimeException('Entity reload failed');
            }

            $reloadedHabitude2 = $reloadedSuivi->getIdHabitude();
            if ($reloadedHabitude2 === null || $reloadedHabitude2->getIdHabitude() !== $nextHabitudeId2) {
                $io->error('Reloaded relation idHabitude does not match expected Habitude.');
                throw new \RuntimeException('Relation check failed');
            }

            $io->success('Persist + reload succeeded, relation looks correct.');

            $io->section('Testing Exercice + Session relation');

            $nextExerciceId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_ex), 0) + 1 FROM exercice');
            $nextSessionId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_session), 0) + 1 FROM session');

            $exercice = new Exercice();
            $exercice->setIdEx($nextExerciceId);
            $exercice->setNom('Smoke exercice');
            $exercice->setType('Smoke type');
            $exercice->setDuree(10);
            $exercice->setDifficulte('FACILE');
            $exercice->setDescription('Smoke description');
            $exercice->setDemarche('Smoke demarche');
            $exercice->setDate_creation(new DateTime());
            $exercice->setDate_modification(new DateTime());

            $session = new Session();
            $session->setIdSession($nextSessionId);
            $session->setIdEx($exercice);
            $session->setDateSession(new DateTime('today'));
            $session->setDateDebut(new DateTime());
            $session->setDateFin(new DateTime('+10 minutes'));
            $session->setResultat('OK');
            $session->setCommentaires('Smoke commentaires');
            $session->setDureeReelle(10);
            $session->setTerminee(false);

            $this->entityManager->persist($exercice);
            $this->entityManager->persist($session);
            $this->entityManager->flush();

            /** @var Session|null $reloadedSession */
            $reloadedSession = $this->entityManager->find(Session::class, $nextSessionId);

            if ($reloadedSession === null) {
                $io->error('Reload of Session failed (find() returned null).');
                throw new \RuntimeException('Entity reload failed');
            }

            $reloadedExercice = $reloadedSession->getIdEx();
            if ($reloadedExercice === null || $reloadedExercice->getIdEx() !== $nextExerciceId) {
                $io->error('Reloaded relation idEx does not match expected Exercice.');
                throw new \RuntimeException('Relation check failed');
            }

            $io->success('Persist + reload succeeded, relation looks correct.');

            $io->section('Testing Exercice + Todo relation');

            $nextTodoId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id_todo), 0) + 1 FROM todo');

            $todo = new Todo();
            $todo->setIdTodo($nextTodoId);
            $todo->setIdExercice($exercice);
            $todo->setTitre('Smoke todo');
            $todo->setDescription('Smoke todo description');
            $todo->setStatut('A_FAIRE');
            $todo->setPriorite('NORMALE');
            $todo->setDateCreation(new DateTime());
            $todo->setDateEcheance(new DateTime('+1 day'));
            $todo->setDateCompletion(new DateTime('+2 days'));
            $todo->setTempsEstime(5);
            $todo->setProgression(0);
            $todo->setNotes('Smoke notes');
            $todo->setCouleur('#123456');

            $this->entityManager->persist($todo);
            $this->entityManager->flush();

            /** @var Todo|null $reloadedTodo */
            $reloadedTodo = $this->entityManager->find(Todo::class, $nextTodoId);

            if ($reloadedTodo === null) {
                $io->error('Reload of Todo failed (find() returned null).');
                throw new \RuntimeException('Entity reload failed');
            }

            $reloadedTodoExercice = $reloadedTodo->getIdExercice();
            if ($reloadedTodoExercice === null || $reloadedTodoExercice->getIdEx() !== $nextExerciceId) {
                $io->error('Reloaded relation idExercice does not match expected Exercice.');
                throw new \RuntimeException('Relation check failed');
            }

            $io->success('Persist + reload succeeded, relation looks correct.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        } finally {
            try {
                if ($useSavepoint) {
                    $this->connection->rollbackSavepoint($savepointName);
                } else {
                    $this->connection->rollBack();
                }
            } catch (\Throwable) {
                // Ignore rollback errors; we never want the command to crash here.
            }
        }
    }
}
