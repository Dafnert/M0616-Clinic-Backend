<?php

namespace App\Command;

use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stress-patients'
)]
class StressPatientsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute($input, $output): int
    {
        for ($i = 0; $i < 1000; $i++) {

            $patient = new Patient();

            $patient->setUsername('user' . $i);
            $patient->setPassword(password_hash('password' . $i, PASSWORD_BCRYPT));

            $patient->setName('Test' . $i);
            $patient->setSurname('Stress');
            $patient->setAge(rand(1, 90));
            $patient->setDni('DNI' . $i);
            $patient->setDisease('None');
            $patient->setAlergias('None');
            $patient->setObservations('Stress test');
            $patient->setAcceptedPrivacy(true);
            $patient->setAcceptedAnesthesia(true);

            $this->em->persist($patient);

            // importante para no reventar memoria
            if ($i % 50 === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
