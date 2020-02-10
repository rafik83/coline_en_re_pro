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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class SollCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:soll')
            ->setDescription('Création des sollicitation')
            ->addArgument('file', InputArgument::REQUIRED, 'Le fichier est introuvalbe')
            ->addArgument('startline', InputArgument::OPTIONAL)
            ->addArgument('endline', InputArgument::OPTIONAL)
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            $startLine = $input->getArgument('startline');
            $endLine = $input->getArgument('endline');
            $filename = $input->getArgument('file');
            $db = $this->getContainer()->get('doctrine')->getManager();
            $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur') ;
            $repOccasion = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
            $repCause = $db->getRepository('FulldonDonateurBundle:Cause') ;
            $repUPros = $db->getRepository('FulldonIntersaBundle:UploadProspection') ;
            $repRec= $db->getRepository('FulldonDonateurBundle:ReceptionMode');
            $fullpath = "/sollicitations/" . $filename;

            $app = new Application();
            $progress = $app->getHelperSet()->get('progress');

        if ( $file = fopen( $fullpath , 'r' ) ) {

            echo "Traitement de fichier ....\n";

            $firstline = fgets ($file, 4096 );
            //Gets the number of fields, in CSV-files the names of the fields are mostly given in the first line
            $num = strlen($firstline) - strlen(str_replace(";", "", $firstline));

            //save the different fields of the firstline in an array called fields
            $fields = array();
            $fields = explode( ";", $firstline, ($num+1) );

            $line = array();
            $i = 0;
            $firstline = false;

            //CSV: one line is one record and the cells/fields are seperated by ";"
            //so $dsatz is an two dimensional array saving the records like this: $dsatz[number of record][number of cell]
            while ( $line[$i] = fgets ($file, 4096) ) {

                $dsatz[$i] = array();
                $dsatz[$i] = explode( ";", $line[$i], ($num+1) );

                $i++;
            }
            $db->getConnection()->beginTransaction();

            if(!empty($startLine)) {
                if(!empty($endLine)) {
                    $nbLines = ($endLine -$startLine)+1;
                } else {
                    $nbLines = ((count(file($fullpath))-1) - $startLine)+1;
                }
            } else {
                $nbLines = count(file($fullpath))-1;
            }

            $progress->start($output, $nbLines);
            foreach ($dsatz as $key => $number) {
                if(($key+1) >= $startLine || is_null($startLine)) {
                //new table row for every record
                $prospection = new Prospection();
                $uploadPros = new UploadProspection();

                foreach ($number as $k => $content) {

                    switch(trim($fields[$k])) {
                        case 'NoReference' :
                        $donateur = $repDonateur->find($content);
                        if(!is_object($donateur)) {
                            $db->getConnection()->rollback();
                            exit('Fichier erroné : Vérifier l\'existance des donateurs sur la base');
                        } else {
                            $prospection->setDonateur($donateur);
                        }
                            break;
                        case 'EnvCodeActivite':
                            $cause = $repCause->findOneBy(array('code'=>$content));
                            if(!is_object($cause)) {
                                $db->getConnection()->rollback();
                                exit('Fichier erroné : Vérifier l\'existance des codes d\'activité sur la base');
                            } else {
                            $prospection->setCause($cause);
                            }
                            break;
                        case 'EnvCodeOccasion':

                            if($firstline == false) {
                            //Test
                            $occasion = $repOccasion->findOneBy(array('code'=>$content));
                            if(!is_object($occasion)) {
                                $db->getConnection()->rollback();
                                exit('Fichier erroné : Vérifier l\'existance des codes d\'occasion sur la base');
                            } else {
                                $isuploadDone  = $repUPros->findOneBy(array('occasion'=>$occasion));
                                if(is_object($isuploadDone)) {
                                    $db->getConnection()->rollback();
                                    exit('Fichier erroné : Le fichier est déjà traité');
                                } else {
                                    $uploadPros->setOccasion($occasion);
                                    $uploadPros->setFileName($filename);
                                    $db->persist($uploadPros);
                                    $db->flush();
                                }

                            }
                                $firstline = true;
                            }
                            break;
                        case 'EnvDateInsc':
                            $prospection->setCreatedAt( \DateTime::createFromFormat ( 'd/m/Y',$content));
                            break;
                        case 'EnvReponse':

                                $prospection->setRetour(0);
                            break;
                        case 'EnvReception':
                            $reception = $repRec->find($content);
                            if(!is_object($reception)) {
                                $db->getConnection()->rollback();
                                exit('Fichier erroné : La méthode de réception n\'existe pas');
                            } else {
                                $prospection->addReception($reception);
                            }
                            break;
                    }
                }
                $db->persist($prospection);
                $db->flush();
                $progress->advance();
                }
                if(($key+1) == $endLine && !empty($endLine)) {
                    //Arréter le traitement
                    break;
                }
            }

        }
            $progress->finish();
            $db->getConnection()->commit();
            $output->writeln('Fichier des prospections est importé avec succes !');
    }
    function dqlDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d->format('Y-m-d');
    }

}