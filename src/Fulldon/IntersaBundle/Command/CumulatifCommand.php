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

class CumulatifCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:cumulatif')
            ->setDescription('Création des cumulatifs par date et entité')
            ->addArgument('date_debut', InputArgument::REQUIRED, 'Date de début est invalide')
            ->addArgument('date_fin', InputArgument::REQUIRED, 'Date de fin est invalide')
            ->addArgument('entity', InputArgument::REQUIRED, 'L\'entité du traitement est invalide')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $date_debut = $input->getArgument('date_debut');
        $date_fin = $input->getArgument('date_fin');

        $date_debut =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_debut.' 00:00:00')->format('Y-m-d H:i:s');
        $date_fin =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_fin.' 23:59:59')->format('Y-m-d H:i:s');

        $entity = $input->getArgument('entity');
        $filename = 'cumulatif'.'-'.$entity.'-'.date('d-m-Y-H-m-s').md5(time());
        $data  =  $this->getContainer()->get('fulldon.intersa.global')->getCumulatifStats($date_debut,$date_fin,$entity);
        //Définition du date
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
            $this->getContainer()->get('templating')->render(
                'FulldonIntersaBundle:Stats:pdf/cumulatif.html.twig',
                array(
                    'data'  => $data,
                    'entity'=>$entity,
                    'date_debut'=>$date_debut,
                    'date_fin'=>$date_fin
                )
            ),
            '/'.$this->getContainer()->getParameter('folder_app').'/global_stats/cumulatif/'.$filename.'.pdf',
            array(),
            true
        );
        $output->writeln('Création du cummulatif avec succès');
    }

}