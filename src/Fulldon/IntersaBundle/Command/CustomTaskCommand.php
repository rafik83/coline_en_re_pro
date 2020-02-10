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
use Fulldon\IntersaBundle\Entity\StatCom;
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
use Fulldon\DonateurBundle\Entity\EmailingInterface;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\CodeOccasion;
use Fulldon\DonateurBundle\Entity\Cause;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Fulldon\IntersaBundle\Vars;
use Fulldon\IntersaBundle\Entity\CourrierAttente;
use Fulldon\SecurityBundle\Entity\User;
use Hip\MandrillBundle\Message;
use Hip\MandrillBundle\Dispatcher;
use Symfony\Component\HttpFoundation\Request;

//use Symfony\Component\Validator\Constraints\DateTime;
//use Symfony\Component\Validator\Constraints\DateTime;



class CustomTaskCommand extends ContainerAwareCommand {

    private $output;

    protected function configure() {
        $this
                ->setName('custom:tasks:run')
                ->setDescription('Taches personnalisee');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('<comment>Running Cron Tasks...</comment>');

        $this->output = $output;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
//        $customtasks = $em->getRepository('FulldonIntersaBundle:CustomCronTask')->findAll();
        $customtasks = $em->getRepository('FulldonIntersaBundle:CustomCronTask')->findBy(array('progress' => false, 'done' => false)); //, array('done' => false)
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');

        gc_collect_cycles();
        $app = new Application();
        $progress = $app->getHelperSet()->get('progress');

        foreach ($customtasks as $crontask) {

            if (!$crontask->getDone() && !$crontask->getProgress()) {
                $output->writeln(var_dump($crontask->getDone()));
                $output->writeln('$crontask->getDone()');
                $output->writeln(var_dump($crontask->getProgress()));
                $output->writeln('$crontask->getProgress()');

                $crontask->setProgress(true);
                $em->flush();
                // Set $lastrun for this crontask
                $crontask->setLastRun(new \DateTime());
                $output->writeln(sprintf('Running Cron Task <info>%s</info>', $crontask->getId()));
                $tag = $this->getContainer()->getParameter('prefix_tag') . '-' . $this->getContainer()->getParameter('tag_rf') . '-' . $crontask->getId();
//                $output->writeln(var_dump($tag));
//                $output->writeln('$tag');
                $tag_rf = $this->getContainer()->getParameter('prefix_tag') . '-' . $this->getContainer()->getParameter('tag_rf');
//                $output->writeln(var_dump($tag_rf));
//                $output->writeln('$tag_rf');
                try {

//                    $output->writeln(var_dump($crontask->getAction()));
//                    $output->writeln('$crontask->getAction()');

                    switch ($crontask->getAction()) {
                        case 'rf-cible':
                            foreach ($crontask->getRecherches() as $favObj) {
                                if (is_object($favObj)) { // is_object($favObj)
                                    if ($favObj->getUrl() != '') {
                                        $name = $favObj->getTitle();
//                                        $output->writeln('name');
//                                        $output->writeln(var_dump($name));
                                        $request = Request::create($this->getContainer()->get('router')->generate('elastic_don') . '?' . $favObj->getUrl());
                                        $i = 1;
//                                        PHP Fatal error:  Call to a member function format() on a non-object in /var/www/coline_pro_mai/src/Fulldon/IntersaBundle/Service/RfServices.php on line 85
                                        do {
                                            $res = $this->getContainer()->get('fulldon.intersa.global')->getDonsResult($request, $i, 1000);
                                            $myresult = $res['result'];
                                            $this->getContainer()->get('fulldon.intersa.rf_service')->buildRfs($myresult, $name);
                                            $i++;
                                        } while ($i <= $res['nboffset']);
                                    }
                                }
                            }
                            break;
                        case 'an-at':
                            foreach ($crontask->getRecherches() as $favObj) {

                                if (is_object($favObj)) {

                                    if ($favObj->getUrl() != '') {
                                        $request = Request::create($this->getContainer()->get('router')->generate('elastic_don') . '?' . $favObj->getUrl());
                                        $i = 1;
                                        do {
                                            $res = $this->getContainer()->get('fulldon.intersa.global')->getDonsResult($request, $i, 1000);
                                            $myresult = $res['result'];
                                            $this->getContainer()->get('fulldon.intersa.global')->changeStatus($myresult);
                                            $i++;
                                        } while ($i <= $res['nboffset']);
                                    }
                                }
                            }
                            break;
                        case 'dons-pdf':
                            $data = array();
                            $colDispaly = null;
                            foreach ($crontask->getRecherches() as $favObj) {
                                $output->writeln(var_dump($crontask->getRecherches()));
//                                $output->writeln('$crontask->getRecherches()');
                                if (is_object($favObj)) {
                                    if (($favObj->getUrl()) != '') {
//                                        $output->writeln(var_dump($favObj->getUrl()));
//                                        $output->writeln('favObj->getUrl()');
                                        $request = Request::create($this->getContainer()->get('router')->generate('elastic_don') . '?' . $favObj->getUrl());
                                        $output->writeln(var_dump($request));
                                        $output->writeln('request');
                                        $i = 1;
                                        do {
                                            $res = $this->getContainer()->get('fulldon.intersa.global')->getDonsResult($request, $i, 1000);
                                            $data[] = $res['result'];
                                            $colDispaly = $res['coldisplay'];
                                            $i++;
                                        } while ($i <= $res['nboffset']);
                                    }
                                }

                                $filename = 'Extract' . '-' . date('d-m-Y-H-m-s') . md5(time());
                                $output->writeln(var_dump($filename));
                                $output->writeln(var_dump('file name'));
                                //Définition du date
                                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                                        $this->getContainer()->get('templating')->render(
                                                'FulldonIntersaBundle:Extract:pdf/dons.html.twig', array(
                                            'data' => $data,
                                            'col_display' => $colDispaly
                                                )
                                        ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
                                );
//                                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
//                                        $this->getContainer()->get('templating')->render(
//                                                'FulldonIntersaBundle:Extract:pdf/home_search_dons.html.twig', array(
//                                            'data' => $data,
//                                            'col_display' => $colDispaly
//                                                )
//                                        ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
//                                );
                                $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';
                                $output->writeln(var_dump($file));
                                $output->writeln('file');
                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $output->writeln($admins);
                                $output->writeln('admin email');
                                $object = "Extraction automatique";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
                                $output->writeln('Création de l\'extraction avec succès');
                            }
                            break;
                        case 'dons-pdf-gestion-don':
//                            $request2 = Request::createFromGlobals();
                            $data = array();
                            $colDispaly = null;
//                            foreach ($crontask->getRecherches() as $favObj) {
                            $favObj = $em->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-pdf-gestion-don'));
//                            $output->writeln(var_dump($favObj));
//                            $output->writeln('$favObj');
//                            $output->writeln(var_dump($crontask->getRecherches()));
//                            $output->writeln('$crontask->getRecherches()');
                            if (is_object($favObj)) {
                                if ($favObj->getUrl() != '') {
//                                    $output->writeln(var_dump($favObj->getUrl()));
//                                    $output->writeln('favObj->getUrl()');
                                    $request = Request::create($this->getContainer()->get('router')->generate('elastic_don') . '?' . $favObj->getUrl());
//                                    $output->writeln(var_dump($request));
                                    $output->writeln('request');
                                    $i = 1;
                                    do {
                                        $res = $this->getContainer()->get('fulldon.intersa.global')->getDonsResult($request, $i, 1000);
                                        $data[] = $res['result'];
                                        $colDispaly = $res['coldisplay'];
                                        $i++;
                                    } while ($i <= $res['nboffset']);
                                }
                            }



                            if ($favObj) {
                                $filename = 'Extract' . '-' . date('d-m-Y-H-m-s') . md5(time());
//                            $output->writeln(var_dump($filename));
                                $output->writeln(var_dump('file name'));
                                //Définition du date
                                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                                        $this->getContainer()->get('templating')->render(
                                                'FulldonIntersaBundle:Extract:pdf/home_search_dons.html.twig', array(
                                            'data' => $data,
                                            'col_display' => $colDispaly
                                                )
                                        ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
                                );
                                $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';
                                $output->writeln(var_dump($file));
                                $output->writeln('file');
                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $output->writeln('admin email');
                                $output->writeln($admins);
                                $object = "Extraction automatique";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
                                $output->writeln('Création de l\'extraction avec succès');
                                $output->writeln('delete rechereche favoris dons-pdf-gestion-don');
                                $em->remove($favObj);
                                $em->flush();
                                $output->writeln('supression avec succeé');
                            }

//                            }//end foreach
                            break;
                        case 'dons-xsl-gestion-don':

                            $fileLocations = $this->getDonsCsv($crontask);
                            $output->writeln(var_dump($fileLocations));
                            $output->writeln('filelocations');

                            foreach ($fileLocations as $title => $fileLocation) {


                                $objReader = \PHPExcel_IOFactory::createReader('CSV');

                                // If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
                                //$objReader->setDelimiter("\t");
                                // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
                                $objReader->setInputEncoding('UTF-8');

                                $objPHPExcel = $objReader->load($fileLocation);
                                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                                $name = uniqid(mt_rand());
                                $fileName = $name . '-' . date('Y') . '.xlsx';
                                $output->writeln(var_dump($fileName));
                                $output->writeln('$fileName');
                                $fileLocationXsl = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
                                $output->writeln(var_dump($fileLocationXsl));
                                $output->writeln('$fileLocationXsl');

                                $objWriter->save($fileLocationXsl);

                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $object = "Extraction automatique CSV-DON : $title";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $fileLocationXsl, $object, 'xlsx');
                                $output->writeln('Création de l\'extraction CSV-DON avec succès');
                            }
                            break;
                        case 'donateurs-pdf':
                            $data = array();
                            $colDispaly = null;
                            foreach ($crontask->getRecherches() as $favObj) {

                                if (is_object($favObj)) {

                                    if ($favObj->getUrl() != '') {
                                        $request = Request::create($this->getContainer()->get('router')->generate('elastic_donateur') . '?' . $favObj->getUrl());
                                        $i = 1;
                                        do {
                                            $res = $this->getContainer()->get('fulldon.intersa.global')->getDonateurResult($request, $i, 1000);
                                            $data[] = $res['result'];
                                            $colDispaly = $res['coldisplay'];
                                            $i++;
                                        } while ($i <= $res['nboffset']);
                                    }
                                }
                                $filename = 'Extract' . '-' . date('d-m-Y-H-m-s') . md5(time());
                                //Définition du date
                                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                                        $this->getContainer()->get('templating')->render(
                                                'FulldonIntersaBundle:Extract:pdf/donateurs.html.twig', array(
                                            'data' => $data,
                                            'col_display' => $colDispaly
                                                )
                                        ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
                                );
                                $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';
                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $object = "Extraction automatique";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
                                $output->writeln('Création de l\'extraction avec succès');
                            }
                            break;
                        case 'dons-csv':

                            $fileLocations = $this->getDonsCsv($crontask);

                            foreach ($fileLocations as $title => $fileLocation) {
                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $object = "Extraction automatique CSV-DON : $title";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $fileLocation, $object, 'csv');
                                $output->writeln('Création de l\'extraction CSV-DON avec succès');
                            }
                            break;
                        case 'donateurs-csv':

                            $fileLocations = $this->getDonateurCsv($crontask);

                            foreach ($fileLocations as $title => $fileLocation) {

                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $object = "Extraction automatique CSV-DONATEUR : $title";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $fileLocation, $object, 'csv');
                                $output->writeln('Création de l\'extraction CSV-DON avec succès');
                            }
                            break;
                        case 'dons-xsl':

                            $fileLocations = $this->getDonsCsv($crontask);


                            foreach ($fileLocations as $title => $fileLocation) {


                                $objReader = \PHPExcel_IOFactory::createReader('CSV');

                                // If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
                                //$objReader->setDelimiter("\t");
                                // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
                                $objReader->setInputEncoding('UTF-8');

                                $objPHPExcel = $objReader->load($fileLocation);
                                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                                $name = uniqid(mt_rand());
                                $fileName = $name . '-' . date('Y') . '.xlsx';
//                                $output->writeln(var_dump($fileName));
//                                $output->writeln('$fileName');
                                $fileLocationXsl = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
//                                $output->writeln(var_dump($fileLocationXsl));// coline_en_re/Export/195131332259945046cde1f-2017.xlsx
//                                $output->writeln('$fileLocationXsl');

                                $objWriter->save($fileLocationXsl);



                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $object = "Extraction automatique CSV-DON : $title";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $fileLocationXsl, $object, 'xlsx');
                                $output->writeln('Création de l\'extraction CSV-DON avec succès');
                            }
                            break;
                        case 'donateurs-xsl':

                            $fileLocations = $this->getDonateurCsv($crontask);

                            foreach ($fileLocations as $title => $fileLocation) {
                                echo $fileLocation;
                                $objReader = \PHPExcel_IOFactory::createReader('CSV');

                                // If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
                                //$objReader->setDelimiter("\t");
                                // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
                                $objReader->setInputEncoding('UTF-8');

                                $objPHPExcel = $objReader->load($fileLocation);
                                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                                $name = uniqid(mt_rand());
                                $fileName = $name . '-' . date('Y') . '.xlsx';
                                $fileLocationXsl = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;

                                $objWriter->save($fileLocationXsl);

                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $object = "Extraction automatique CSV-DONATEUR : $title";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $fileLocationXsl, $object, 'xlsx');
                                $output->writeln('Création de l\'extraction CSV-DON avec succès');
                            }
                            break;
                        case 'donateur-xsl-gestion-donateur':
                            $fileLocations = $this->getDonateurCsv($crontask);
//                            $output->writeln(var_dump($fileLocations));
//                            $output->writeln('filelocations');

                            foreach ($fileLocations as $title => $fileLocation) {


                                $objReader = \PHPExcel_IOFactory::createReader('CSV');

                                // If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
                                //$objReader->setDelimiter("\t");
                                // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
                                $objReader->setInputEncoding('UTF-8');

                                $objPHPExcel = $objReader->load($fileLocation);
                                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                                $name = uniqid(mt_rand());
                                $fileName = $name . '-' . date('Y') . '.xlsx';
                                $output->writeln(var_dump($fileName));
                                $output->writeln('$fileName');
                                $fileLocationXsl = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
                                $output->writeln(var_dump($fileLocationXsl));
                                $output->writeln('$fileLocationXsl');

                                $objWriter->save($fileLocationXsl);

                                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_extract.html.twig');
                                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                                $object = "Extraction automatique CSV-DON : $title";
                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $fileLocationXsl, $object, 'xlsx');
                                $output->writeln('delete rechereche favoris donateur-xsl-gestion-donateur');
                                $output->writeln('supression avec succeé');
                                $output->writeln('Création de l\'extraction CSV-DON avec succès');
                            }
                            break;
                    }
                    $crontask->setDone(true);
                    $crontask->setProgress(false);
                    $output->writeln('<info>SUCCESS</info>');
                } catch
                (\Exception $e) {
                    echo $e->getMessage();
                    $output->writeln('<error>ERROR</error>');
                }
                // Persist crontask
                $em->flush($crontask);
            } else {
                $output->writeln(sprintf('Skipping Cron Task <info>%s</info>', $crontask->getId()));
            }

            // Flush database changes

            $em->flush();
        }


        $output->writeln('<comment>Done!</comment>');
    }

    private function getDonsCsv2($crontask) {
        $data = array();
        $colDispaly = null;
        $fileLocations = array();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $favObj = $em->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-xsl-gestion-don'));
        if (is_object($favObj)) {
            if ($favObj->getUrl() != '') {
                $request = Request::create($this->getContainer()->get('router')->generate('elastic_don') . '?' . $favObj->getUrl());
                $i = 1;
                do {
                    $res = $this->getContainer()->get('fulldon.intersa.global')->getDonsResult($request, $i, 1000);
                    $data[] = $res['result'];
                    $colDispaly = $res['coldisplay'];
                    $i++;
                } while ($i <= $res['nboffset']);
            }
        }
//        foreach ($crontask->getRecherches() as $favObj) {
        // Création du fichier CSV
        $fields = array();
        $head = array();
//        $cols = array('numdon' => '#REF', 'nom' => 'Nom', 'prenom' => 'Prénom', 'statut' => 'Statut',
//            'nomentreprise' => 'Nom d\'entreprise', 'modepay' => 'Mode de paiement', 'typedon' => 'Type de don',
//            'lot' => 'Lot', 'rfs' => 'Reçus fiscaux', 'createdat' => 'Date de création', 'datefiscale' => 'Date fiscale',
//            'codeactivite' => 'Code d\'activité', 'codeoccasion' => 'Code occasion', 'amount' => 'Montant', 'codecampagne' => 'Code campagne');

        $cols = array('nom' => 'Nom', 'prenom' => 'Prénom', 'amount' => 'Montant', 'modepay' => 'Mode de paiement', 'rfs' => 'Reçus fiscaux',
            'datefiscale' => 'Date fiscale', 'codeactivite' => 'Code d\'activité', 'codeoccasion' => 'Code occasion');

        foreach ($colDispaly as $cd) {

            if (array_key_exists($cd, $cols)) {
                $head[] = $cols[$cd];
            }
        }

        $fields[] = $head;
        foreach ($data as $dons) {
            foreach ($dons as $don) {

                $donateur_ix = $em->getRepository('FulldonDonateurBundle:Donateur')
                        ->findOneBy(array('user' => $don->getUser()));
//                var_dump($donateur_ix->getNom());
//                die('$donateur_ix->getNom()');
                $body = array();
                foreach ($colDispaly as $col) {
                    if ($col == 'nom') { // nom   $body[] = $donateur_ix->getNom();
                        if ($donateur_ix->getNom()) {
                            $body[] = $donateur_ix->getNom();
                        } else {
                            $body[] = "";
                        }
                    }
//                    elseif ($col == 'numdon') {
//                        $body[] = $don->getId();
//                    } 
                    elseif ($col == 'prenom') {
                        $body[] = $donateur_ix->getPrenom();
                    }
//                    elseif ($col == 'statut') {
//                        $statut = $don->getTransaction()->getStatut();
//                        if (isset($statut)) {
//                            $body[] = $don->getTransaction()->getStatut()->getLibelle();
//                        } else {
//                            $body[] = 'Abandonné';
//                        }
//                    } 
                    elseif ($col == 'modepay') {
                        $modepay = $don->getModePaiement();
                        if (isset($modepay) && $modepay !== null) {
                            $body[] = $modepay->getLibelle();
                        }
                    }
//                    elseif ($col == 'nomentreprise') {
//                        $body[] = $donateur_ix->getNomEntreprise();
//                    }
//                    } elseif ($col == 'createdat') {
//                        $body[] = $don->getCreatedAt()->format('d/m/Y');
//                    } 
                    elseif ($col == 'amount') {
                        $body[] = round($don->getMontant(), 2);
                    }
//                    elseif ($col == 'typedon') {
//                        if ($don->getIspa()) {
//                            $body[] = 'Régulier';
//                        } else {
//                            $body[] = 'Ponctuel';
//                        }
//                    } 
                    elseif ($col == 'rfs') {
                        $rfs_ids = array();
                        foreach ($don->getRfs() as $rf) {
                            $rfs_ids[] = $rf->getId();
                        }

                        $body[] = implode('|', $rfs_ids);
                    } elseif ($col == 'datefiscale') {

                        if ($don->getDateFiscale() != null) {
                            $future = array($don->getDateFiscale()->format('d-m-Y'));
                            $explode = explode("-", $future[0]);
                            $date_fiscale = $explode[0] . '/' . $explode[1] . '/' . $explode[2];
                            $body[] = $date_fiscale;
//                            var_dump($date_fiscale);
//                            die('ici');
//                        $date_form = \DateTime::createFromFormat("d-m-Y",$date);
//                        $datefiscale = $date_form->format('d-m-Y');
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'codeactivite') {
                        $cause = $don->getCause();
                        if ($cause) {
                            $body[] = $cause->getCode();
                        }
                    } elseif ($col == 'codeoccasion') {
                        $occasion = $don->getCause()->getOccasion();

                        if ($occasion) {
                            $body[] = $occasion->getCode();
                        }
                    }
//                    elseif ($col == 'codecampagne') {
//                        $campagne = $don->getCause()->getOccasion()->getCampagne();
//                        if ($campagne) {
//                            $body[] = $campagne;
//                        }
//                    }
//                    elseif ($col == 'lot') {
//                        $body[] = $don->getLot();
//                    }
//                    elseif ($col == 'datefiscale') {
//                        $body[] = $don->getDateFiscale()->format('d/m/Y');
//                    }
                }
                $fields[] = $body;
            }// end foreach2
        }// end foreach1


        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations[$favObj->getTitle()] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
//        }//end foreach
        var_dump('delete rechereche favoris dons-xsl-gestion-don');
        $em->remove($favObj);
        $em->flush();
        var_dump('supression avec succeé');
        return $fileLocations;
    }

    public function RequetteGETDONATEURByUSERID($IdUser) {
        $sql = "SELECT * FROM coline_en_re_full_db.donateur dnt

inner join coline_en_re_full_db.User u

on dnt.user_id = u.id

where u.id ='" . $IdUser . "'";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getDonsCsv($crontask) {
        $data = array();
        $colDispaly = null;
        $fileLocations = array();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $favObj = $em->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-xsl-gestion-don'));
        //foreach ($crontask->getRecherches() as $favObj) {

        if (is_object($favObj)) {

            if ($favObj->getUrl() != '') {
                $request = Request::create($this->getContainer()->get('router')->generate('elastic_don') . '?' . $favObj->getUrl());
                $i = 1;
                do {
                    $res = $this->getContainer()->get('fulldon.intersa.global')->getDonsResult($request, $i, 1000);
                    $data[] = $res['result'];
                    $colDispaly = $res['coldisplay'];
                    $i++;
                } while ($i <= $res['nboffset']);
            }
        }

        // Création du fichier CSV
        $fields = array();
        $head = array();
        $cols = array('numdon' => '#REF', 'nom' => 'Nom', 'prenom' => 'Prénom', 'statut' => 'Statut',
            'nomentreprise' => 'Nom d\'entreprise', 'modepay' => 'Mode de paiement', 'typedon' => 'Type de don',
            'lot' => 'Lot', 'rfs' => 'Reçus fiscaux', 'createdat' => 'Date de création', 'datefiscale' => 'Date fiscale',
            'codeactivite' => 'Code d\'activité', 'codeoccasion' => 'Code occasion', 'amount' => 'Montant', 'codecampagne' => 'Code campagne');

        foreach ($colDispaly as $cd) {

            if (array_key_exists($cd, $cols)) {
                $head[] = $cols[$cd];
            }
        }

        $fields[] = $head;
        $array_donateur_user_iner_join = array();
        // dont le data il faut préciser le donateur du dons
        foreach ($data as $dons) {
            foreach ($dons as $don) {

                $user_id = $don->getUser()->getId();

                $array_donateur_user_iner_join = $this->RequetteGETDONATEURByUSERID($user_id);
//                    var_dump($array_donateur_user_iner_join[0]);
//                        die('$array_donateur_user_iner_join');
//                    $donateur_ix = $em->getRepository('FulldonDonateurBundle:Donateur')
//                            ->findOneBy(array('user' => $don->getUser()));

                $nom_donateur = NULL;
                $prenom_donateur = NULL;
                $entreprise_donateur = NULL;
                if (count($array_donateur_user_iner_join) > 0) {
                    $nom_donateur = $array_donateur_user_iner_join[0]['nom'];
                    $prenom_donateur = $array_donateur_user_iner_join[0]['prenom'];
                    $entreprise_donateur = $array_donateur_user_iner_join[0]['nom_entreprise'];
                }
                $body = array();
                foreach ($colDispaly as $col) {
                    if ($col == 'numdon') {
                        $body[] = $don->getId();
                    } elseif ($col == 'nom') {
//                            $body[] = $donateur_ix->getNom();
                        if ($nom_donateur) {
                            $body[] = $nom_donateur;
                        } else {
                            $body[] = ' ';
                        }
                    } elseif ($col == 'prenom') {
//                            $body[] = $donateur_ix->getPrenom();
                        if ($prenom_donateur) {
                            $body[] = $prenom_donateur;
                        } else {
                            $body[] = ' ';
                        }
                    } elseif ($col == 'statut') {
                        $statut = $don->getTransaction()->getStatut();
                        if (isset($statut)) {
                            $body[] = $don->getTransaction()->getStatut()->getLibelle();
                        } else {
                            $body[] = 'Abandonné';
                        }
                    } elseif ($col == 'modepay') {
                        $modepay = $don->getModePaiement();
                        if (isset($modepay) && $modepay !== null) {
                            $body[] = $modepay->getLibelle();
                        }
                    } elseif ($col == 'nomentreprise') {
//                            $body[] = $donateur_ix->getNomEntreprise();
                        if ($entreprise_donateur) {
                            $body[] = $entreprise_donateur;
                        } else {
                            $body[] = ' ';
                        }
                    } elseif ($col == 'createdat') {
                        $body[] = $don->getCreatedAt()->format('d/m/Y');
                    } elseif ($col == 'amount') {
                        $body[] = round($don->getMontant(), 2);
                    } elseif ($col == 'typedon') {
                        if ($don->getIspa()) {
                            $body[] = 'Régulier';
                        } else {
                            $body[] = 'Ponctuel';
                        }
                    } elseif ($col == 'rfs') {
                        $rfs_ids = array();
                        foreach ($don->getRfs() as $rf) {
                            $rfs_ids[] = $rf->getId();
                        }

                        $body[] = implode('|', $rfs_ids);
                    } elseif ($col == 'codeactivite') {
                        $cause = $don->getCause();
                        if ($cause) {
                            $body[] = $cause->getCode();
                        }
                    } elseif ($col == 'codeoccasion') {
                        $occasion = $don->getCause()->getOccasion();

                        if ($occasion) {
                            $body[] = $occasion->getCode();
                        }
                    } elseif ($col == 'codecampagne') {
                        $campagne = $don->getCause()->getOccasion()->getCampagne();
                        if ($campagne) {
                            $body[] = $campagne;
                        }
                    } elseif ($col == 'lot') {
                        $body[] = $don->getLot();
                    } elseif ($col == 'datefiscale') {
                        if ($don->getDateFiscale()) {
                            $body[] = $don->getDateFiscale()->format('d/m/Y');
                        } else {
                            $datetime_now = new \DateTime();
                            $body[] = $datetime_now->format('d/m/Y');
                        }
                    }
                }
                $fields[] = $body;
            }
        }


        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations[$favObj->getTitle()] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        //$fileLocations ==> "/coline_en_re/Export/19468641859944e1c6075e-2017.csv"
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        //}//endforeach
        var_dump('delete rechereche favoris dons-xsl-gestion-don');
        $em->remove($favObj);
        $em->flush();
        var_dump('supression avec succeé');
        return $fileLocations;
    }

    private function getDonateurCsv($crontask) {
        $data = array();
        $colDispaly = null;
        $fileLocations = array();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $favObj = $em->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'donateur-xsl-gestion-donateur'));
        if (is_object($favObj)) {
            if ($favObj->getUrl() != '') {
                $request = Request::create($this->getContainer()->get('router')->generate('elastic_donateur') . '?' . $favObj->getUrl());
                $i = 1;
                do {
                    $res = $this->getContainer()->get('fulldon.intersa.global')->getDonateurResult($request, $i, 1000);
                    $data[] = $res['result'];
                    $colDispaly = $res['coldisplay'];
                    $i++;
                } while ($i <= $res['nboffset']);
            }
        }
        //foreach ($crontask->getRecherches() as $favObj) {

//        if (is_object($favObj)) {
//
//            if ($favObj->getUrl() != '') {
//                $request = Request::create($this->getContainer()->get('router')->generate('elastic_donateur') . '?' . $favObj->getUrl());
//                $i = 1;
//                do {
//                    $res = $this->getContainer()->get('fulldon.intersa.global')->getDonateurResult($request, $i, 1000);
//                    $data[] = $res['result'];
//                    $colDispaly = $res['coldisplay'];
//                    $i++;
//                } while ($i <= $res['nboffset']);
//            }
//        }
        // Création du fichier CSV
        $fields = array();
        $cumulDon = 0;
        $head = array();
        $cols = array('numdonateur' => '#REF', 'nom' => 'Nom', 'prenom' => 'Prénom', 'statut' => 'Statut',
            'nomentreprise' => 'Nom d\'entreprise', 'email' => 'Email', 'birthday' => 'Date de naissance',
            'telmobile' => 'Téléphone mobile', 'telfixe' => 'Téléphone fixe', 'cat' => 'Catégories',
            'adresse' => 'Adresse', 'ville' => 'Ville', 'pays' => 'Pays', 'zipcode' => 'Code postal', 'createdat' => 'Date de création', 'cumuldon' => 'Cumul des dons');
        foreach ($colDispaly as $key => $cd) {
            if ($cd == "cumuldon") {
                //array_splice($colDispaly, 0, 0);array_splice ==> numeric keys
                unset($colDispaly[0]);
                array_values($colDispaly);
            }
        }

        foreach ($colDispaly as $cd) {
            if (array_key_exists($cd, $cols)) {
                $head[] = $cols[$cd];
            }
        }


//        var_dump($head);
//        die('head');
        $fields[] = $head;
        foreach ($data as $donateurs) {
            foreach ($donateurs as $donateur) {

                if ($donateur) {

                    if ($donateur->getId()) {
                        $dataxx = $this->DonateurCumulDon($donateur->getId());

                        $cumulDon = $dataxx[0]['cumul'];
                    }
                }
                $body = array();
                foreach ($colDispaly as $col) {
                    if ($col == 'numdonateur') {
                        if ($donateur->getRefDonateur()) {
                            $body[] = $donateur->getRefDonateur();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'nom') {
                        if ($donateur->getNom()) {
                            $body[] = $donateur->getNom();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'prenom') {
                        if ($donateur->getPrenom()) {
                            $body[] = $donateur->getPrenom();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'statut') {
                        if (!$donateur->getRemoved()) {
                            $body[] = 'Activé';
                        } else {
                            $body[] = 'Désactivé';
                        }
                    } elseif ($col == 'nomentreprise') {
                        if ($donateur->getNomEntreprise() == NULL) {
                            $body[] = 'N/A';
                        } else {
                            $body[] = $donateur->getNomEntreprise();
                        }
                    } elseif ($col == 'birthday') {
                        if ($donateur->getDateNaissance()) {
                            $body[] = $donateur->getDateNaissance()->format('d/m/Y');
                        } else {
                            $today = new \DateTime('now');
                            $body[] = $today->format('d/m/Y'); //"";
                        }
                    } elseif ($col == 'email') {
                        if ($donateur->getEmail()) {
                            $body[] = $donateur->getEmail();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'telmobile') {
                        if ($donateur->getTelephoneMobile()) {
                            $body[] = $donateur->getTelephoneMobile();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'telfixe') {
                        if ($donateur->getTelephoneFixe()) {
                            $body[] = $donateur->getTelephoneFixe();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'cat') {
                        if ($donateur->getCategories()) {
                            $cats_ids = array();
                            foreach ($donateur->getCategories() as $cat) {
                                $cats_ids[] = $cat->getName();
                            }
                            $body[] = implode('|', $cats_ids);
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'adresse') {
                        if ($donateur->getAdresse3()) {
                            $body[] = $donateur->getAdresse3() . ' ' . $donateur->getAdresse4();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'ville') {
                        if ($donateur->getIsoville()) {
                            $body[] = $donateur->getIsoville();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'pays') {
                        if ($donateur->getIsopays()) {
                            $body[] = $donateur->getIsopays();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'zipcode') {
                        if ($donateur->getZipcode()) {
                            $body[] = $donateur->getZipcode();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'createdat') {
                        if ($donateur->getCreatedAt()) {
                            $body[] = $donateur->getCreatedAt()->format('d/m/Y');
                        } else {
                            $today = new \DateTime('now');
                            $body[] = $today->format('d/m/Y'); //"";
                        }
                    } elseif ($col == 'cumuldon') {

                        if ($donateur) {
                            if ($donateur->getId()) {
                                $body[] = $cumulDon;
                            } else {
                                $body[] = "";
                            }
                        }
                    }
                }
                $fields[] = $body;
            }
        }


        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations[$favObj->getTitle()] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {

            fputcsv($fp, $field);
        }
        fclose($fp);

        //}end foreach
        $em->remove($favObj);
        $em->flush();
        return $fileLocations;
    }

    private function DonateurCumulDon($donateur_id) {

        $query = "SELECT d.id as don_id,d.user_id,dt.id as donateur_id, SUM(d.montant) AS cumul,MAX(d.created_at) AS dernier_don,dt.removed as statut_donateur    FROM coline_en_re_full_db.don d

LEFT JOIN coline_en_re_full_db.donateur dt ON  d.user_id=dt.user_id

WHERE dt.removed=0 AND d.removed=0 

and dt.id = '" . $donateur_id . "' ";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

}
