<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\IntersaBundle\Command;

use Fulldon\DonateurBundle\Entity\Doublon;
use Fulldon\DonateurBundle\Entity\Prelevement;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Fulldon\IntersaBundle\Entity\UploadProspection;
use Fulldon\DonateurBundle\Entity\Prospection;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\CodeOccasion;
use Fulldon\DonateurBundle\Entity\Cause;

class DoublonCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('detect:doublons')
            ->setDescription('Détection des doublons en mass')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Début du traitement des doublons');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');
        $allDonateur = $repDonateur->findBy(array('removed' => false));
        $detected = array();
        foreach($allDonateur as $donateur) {

            if(!in_array($donateur->getId(), $detected)) {
                $nentreprise = 'null';
                $companyRqst = 'd.nomEntreprise is null';

                if(!empty($donateur->getNomEntreprise())) {

                    $nentreprise =  $donateur->getNomEntreprise();
                    $companyRqst = 'd.nomEntreprise = :nentreprise';
                }

                $duplicatedDonateurs = $repDonateur
                    ->createQueryBuilder('d')
                    ->where('d.nom = :nom and d.prenom = :prenom and d.id != :id and d.removed = :removed and '.$companyRqst)
                    ->setParameter('nom',trim($donateur->getNom()))
                    ->setParameter('prenom',trim($donateur->getPrenom()))
                    ->setParameter('id', trim($donateur->getId()));
                if(!empty($donateur->getNomEntreprise())) {
                    $duplicatedDonateurs
                    ->setParameter('nentreprise', trim($nentreprise));
                }
                $duplicatedDonateurs = $duplicatedDonateurs
                    ->setParameter('removed', false)
                    ->getQuery()
                    ->getResult();

                foreach($duplicatedDonateurs as $dd)
                {
                    $output->writeln('new doublon detected');
                    //Création d'un doublon
                    $doublon = new Doublon();
                    $doublon->setDone(false);
                    $doublon->setDonateur1($donateur->getId());

                    $percent = 60;


                    //required to  retreive the donateur ID
                    $doublon->setDonateur2($dd->getId());
                    $doublon->setPourcentage($percent);
                    $em->persist($doublon);
                    $em->flush();

                    $detected[] = $dd->getId();

                }

            }
        }

        $output->writeln('Fin du traitement des doublons');
    }

}