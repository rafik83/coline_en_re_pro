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
use Fulldon\IntersaBundle\Entity\ProspectionDonateur;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;


class ProsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:pros')
            ->setDescription('Création des prospections')
            ->addArgument('file', InputArgument::REQUIRED, 'Le fichier est introuvable')
            ->addArgument('startline', InputArgument::OPTIONAL)
            ->addArgument('endline', InputArgument::OPTIONAL)
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            $filename = $input->getArgument('file');
            $startLine = $input->getArgument('startline');
            $endLine = $input->getArgument('endline');
            $db = $this->getContainer()->get('doctrine')->getManager();
            $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur') ;
            $repOccasion = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
            $repCause = $db->getRepository('FulldonDonateurBundle:Cause') ;
            $repUPros = $db->getRepository('FulldonIntersaBundle:UploadProspection') ;
            $repRec= $db->getRepository('FulldonDonateurBundle:ReceptionMode');
            $repVille= $db->getRepository('FulldonDonateurBundle:Ville');
            $repProsDonateur= $db->getRepository('FulldonIntersaBundle:ProspectionDonateur');
            $fullpath = "/prospections/" . $filename;

       // $style = new OutputFormatterStyle('red', 'yellow', array('bold', 'blink'));
        //output->getFormatter()->setStyle('fire', $style);
        //$output->writeln('<fire>foo</fire>');
        if ( $file = fopen( $fullpath , 'r' ) ) {

            echo "Traitement de fichier ....\n";
            $fields = array(
                'TITRE'=> 41,
                'PRENOM' => 32,
                'NOM' => 32,
                'ETAGE' => 38,
                'BATIMENT' => 38,
                'VOIE' => 38,
                'LIEUDIT' => 38,
                'ZIPCODE' => 5,
                'VILLE' => 32,
                'NUMDONATEUR' => 20,
                'MEDIA'=>10,
                'LOT'=>20,
                'CODECOMPAGNE'=>10,
                'DATAMATRIX'=>20
            );
            $line = array();
            $i = 0;
            $firstline = false;
            $data = array();
            $db->getConnection()->beginTransaction();
            $app = new Application();
            $progress = $app->getHelperSet()->get('progress');
            $nbLines = 0;
            //Gestion des lignes
            if(!empty($startLine)) {
                if(!empty($endLine)) {
                    $nbLines = ($endLine -$startLine)+1;
                } else {
                    $nbLines = (count(file($fullpath)) - $startLine)+1;
                }
            } else {
                $nbLines = count(file($fullpath));
            }

            $progress->start($output, $nbLines);

            while ( $line[$i] = fgets ($file, 4096) ) {


                if(($i+1) >= $startLine || is_null($startLine)) {


                $curline =  $line[$i];
                $cursor = 0;
                foreach($fields as $key => $field) {

                    $data[$key] = substr($curline,$cursor,$field);
                    $cursor += $field;
                }
                $pros = new ProspectionDonateur();
                $pros->setNom(trim($data['NOM']));
                $pros->setPrenom(trim($data['PRENOM']));
                $pros->setAdresse1(trim($data['ETAGE']));
                $pros->setAdresse2(trim($data['BATIMENT']));
                $pros->setAdresse3(trim($data['VOIE']));
                $pros->setAdresse4(trim($data['LIEUDIT']));
                $pros->setZipcode(trim($data['ZIPCODE']));
                $pros->setVille(trim($data['VILLE']));
                $pros->setFilename($filename);
                $pros->setLine($i);

                if(!is_object($repProsDonateur->findOneBy(array('datamatrix'=>$data['DATAMATRIX'])))) {
                    $pros->setDatamatrix($data['DATAMATRIX']);
                } else {
                    $db->getConnection()->rollback();
                    exit('Le datamatrix existe déjà sur la base : LIGNE : '.($i+1));
                }
                $cause =$repCause->findOneBy(array('code'=>'stat'));
                if(is_object($cause)) {
                    $pros->setCause($cause);
                } else {
                    $db->getConnection()->rollback();
                    exit('Le Code d\'activité n\'existe pas sur la base : LIGNE : '.($i+1));
                }

                $db->persist($pros);
                $db->flush();
                $progress->advance();

                }
                if(($i+1) == $endLine && !empty($endLine)) {
                    //Arréter le traitement
                    break;
                }

                $i++;

            }
            $db->getConnection()->commit();

            $progress->finish();
            $output->writeln('Fichier des prospections est importé avec succes !');
        } else {
            print('ERROR FILE');
        }


    }
    function dqlDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d->format('Y-m-d');
    }

}