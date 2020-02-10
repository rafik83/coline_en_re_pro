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
use Symfony\Component\Validator\Constraints\DateTime;

class RecapulatifCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
                ->setName('generate:recapulatif')
                ->setDescription('Création des récapulatifs par date et entité')
                ->addArgument('date_debut', InputArgument::REQUIRED, 'Date de début est invalide')
                ->addArgument('date_fin', InputArgument::REQUIRED, 'Date de fin est invalide')
                ->addArgument('code', InputArgument::REQUIRED, 'Le code occasion n\'est invalide')
                ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

//        $date_debut = '01/01/2016'; //$input->getArgument('date_debut');
//        $date_fin = '31/12/2016'; //$input->getArgument('date_fin');

        $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut . ' 00:00:00')->format('Y-m-d H:i:s');

//        $output->writeln('date_debut');
//        $output->writeln(var_dump($date_debut));
//        die('$date_debut');



        $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin . ' 23:59:59')->format('Y-m-d H:i:s');

        $code = $input->getArgument('code');
//        $output->writeln('code');
//        $output->writeln(var_dump($code));
//        die('code');
        $filename = 'recap' . '-' . $code . '-' . date('d-m-Y-H-m-s') . md5(time());
        $data = $this->getContainer()->get('fulldon.intersa.global')->getRecapStats($date_debut, $date_fin, $code);
//        $output->writeln('data');
//        $output->writeln(var_dump($data));
//        die('data');
        //Définition du date
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/recap.html.twig', array(
                    'data' => $data,
                    'code' => $code,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin
                        )
                ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
        );
        $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';
        $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_recap.html.twig');
        $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
        $object = "Récapitulatif $date_debut -> $date_fin";
        $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);

        $output->writeln('Création du recap avec succès');
    }

}
