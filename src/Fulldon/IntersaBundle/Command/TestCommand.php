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
use Fulldon\DonateurBundle\Entity\CategoryDonateur;
use Fulldon\DonateurBundle\Entity\CodeOccasion;
use Fulldon\DonateurBundle\Entity\Cause;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Fulldon\IntersaBundle\Vars;
use Fulldon\IntersaBundle\Entity\CourrierAttente;
use Hip\MandrillBundle\Message;
use Hip\MandrillBundle\Dispatcher;
use Symfony\Component\HttpFoundation\Request;

//use Symfony\Component\Validator\Constraints\DateTime;
//use Symfony\Component\Validator\Constraints\DateTime;



class TestCommand extends ContainerAwareCommand {

    private $output;

    protected function configure() {
        $this
                ->setName('test:extract')
                ->setDescription('Extraction des fichiers selon des requettes spécifique');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('<comment>Running Test Command...</comment>');

        $this->output = $output;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $customtasks = $em->getRepository('FulldonIntersaBundle:CustomCronTask')->findAll();
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');

//        gc_collect_cycles();
//        $app = new Application();
//        $progress = $app->getHelperSet()->get('progress');
//        $donateur_isnull_email = $repDonateur->findBy(array('email' => NULL));
//        $output->w riteln("count");
//        $output->writeln(var_dump(count($donateur_isnull_email)));
//        die('ici');
//        $count = count($donateur_isnull_email);
//        $progress->start($output, count($donateur_isnull_email));
        $output->writeln("Début du traitement Donateur \n");


//        $fileLocations = $this->Test4();//DonateurWithMailISNULL
//        $fileLocations = $this->Test();
//        $fileLocations = $this->Don2016byModePaiement();
//        var_dump($fileLocations);
//        die ;
//        $output->writeln("<------debut comptage de donateur sans email-------->");
//        $_count_donateur_email_null = 0;
//        $_count_donateur_email_null = $this->CountDonateurWithMailISNULL(); //4017
//        $output->writeln(var_dump($_count_donateur_email_null));
//        $output->writeln("<------end comptage de donateur sans email-------->");
//        die('count');
//        $output->writeln("<------debut comptage de donateur avec email contient un  chiffre-------->");
//        $_count = 0;
//        $_count = $this->CountDonateurWithMailContainNumbre();
//        $output->writeln(var_dump($_count)); //471
//        $output->writeln("<------end comptage de donateur avec email contient un chiffre-------->");
//        die('count');
//        $output->writeln("<------debut Extraction donateur sans don et sans coordonner-------->");
//
//        $output->writeln("<------end Extraction donateur sans don et sans coordonner-------->");
//
//        die('extraction ');
//        $output->writeln("<------debut Extraction donateur emailcontain number-------->");
//        $fileLocations = $this->ExtractionDonateurWithMailContainNumbre();
//        $output->writeln("<------end Extraction donateur emailcontain number-------->");
//        $fileLocations = $this->DonateurWithMailISNULL();
//        $output->writeln("<------debut Extraction donateur sans don sans coordonner-------->");
//        $fileLocations = $this->ExtractionDonateurSansDonSansCoordonnees();
//        $output->writeln("<------end Extraction donateur sans don sans coordonner-------->");
        $output->writeln("<------debut Extraction2 Recu Fiscaux 2017-------->");
        $this->Extraction2RecusFiscaux2017();
        $output->writeln("<------end Extraction2 Recu Fiscaux 2017-------->");
        die('end Extraction2 Recu Fiscaux 2017 ');
//        

        $output->writeln("<------debut Extraction Etat3 Recapitulatif2017-------->");
        $this->Extraction3EtaREcapitulatif2017();
        $output->writeln("<------end Extraction Etat3 Recapitulatif2017-------->");
        die('end Extraction Etat3 Recapitulatif2017 ');
        
        
        
        
        $output->writeln("<------debut Extraction1 Recu Fiscaux 2017-------->");
        $this->Extraction1RecusFiscaux2017();
        $output->writeln("<------end Extraction1 Recu Fiscaux 2017-------->");
        die('end Extraction1 Recu Fiscaux 2017 ');


        $output->writeln("<------debut Extraction liste2 Donateur 2017-------->");
        $this->ExtractionEtaREcapitulati2fOrderByDonateur2017();
        $output->writeln("<------end Extraction liste2 Donateur 2017-------->");
        die('end Extraction liste2 Donateur 2017 ');


        $output->writeln("<------debut Extraction liste1 Donateur 2017-------->");
        $this->ExtractionEtaREcapitulati1fOrderByDonateur2017();
        $output->writeln("<------end Extraction liste1 Donateur 2017-------->");
        die('end Extraction liste1 Donateur 2017 ');





        $output->writeln("<------debut Extraction Etat2 Recapitulatif2017-------->");
        $this->Extraction2EtaREcapitulatif2017();
        $output->writeln("<------end Extraction Etat2 Recapitulatif2017-------->");
        die('end Extraction Etat2 Recapitulatif2017 ');

        $output->writeln("<------debut Extraction Etat1 Recapitulatif2017-------->");
        $this->Extraction1EtaREcapitulatif2017();
        $output->writeln("<------end Extraction Etat1 Recapitulatif2017-------->");
        die('end Extraction Etat1 Recapitulatif2017 ');


































//        $output->writeln("<------debut Extraction Etat don + id_donateur=10626-------->");
//       $fileLocations = $this->ExtractionDetailleDonWithIdDonateur10626();
//        $output->writeln("<------end Extraction Etat don + id_donateur=10626-------->");






        foreach ($fileLocations as $fileLocation) {
            $objReader = \PHPExcel_IOFactory::createReader('CSV');
            $objReader->setInputEncoding('UTF-8');

            $objPHPExcel = $objReader->load($fileLocation);
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $name = 'detaille-don-pour-donateur-id=10626';
//             $name = 'donateur-with-email-is-null';
            $fileName = $name . '.xlsx';
            $output->writeln('$fileName');
            $output->writeln(var_dump($fileName));
            $fileLocationXsl = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
            $output->writeln(var_dump($fileLocationXsl));
            $output->writeln('$fileLocationXsl');
            $objWriter->save($fileLocationXsl);
            $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:comptage_don2016.html.twig');
            $admins = explode(',', $this->getContainer()->getParameter('administrator_email')); //$this->getContainer()->getParameter('administrator_email'); //explode(',', $this->getContainer()->getParameter('administrator_email'));
            $output->writeln('$admins');
            $output->writeln(var_dump($admins));
            $object = "Extraction automatique CSV-DON : ";
            $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $fileLocationXsl, $object, 'xlsx');
            $output->writeln('Création de l\'extraction CSV-DON avec succès');
//            $progress->advance();
        }
//        $progress->finish();
        $output->writeln("<------end Extraction donateur emailcontain number-------->");

        $output->writeln("Fin du traitement \n");
        $output->writeln('<comment>Done!</comment>');
    }

    private function ExtractionDetailleDonWithIdDonateur10626() {

        $colDispaly = null;
        $fileLocations = array();
        $fields = array();
        $head = array();
        $cols = array('id_don' => 'ID DON', 'id_donateur' => 'ID DONATEUR', 'montant_don' => 'Montant DON', 'date_creation' => 'DATE CREATION', 'date_fiscale' => 'DATE FISCALE', 'lot' => 'LOT', 'nom_donatur' => 'NOM DONATEUR', 'prenom_donateur' => 'PRENOM DONATEUR', 'ref_donateur' => 'REFERENCE DONATEUR');
        foreach ($cols as $key => $cd) {
            $head[] = $cols[$key];
        }
        $fields[] = $head;


        $all_don__bydate = $this->RequetteDetailleDonWithIdDonateur10626(); // 

        foreach ($all_don__bydate as $key => $value) {


//id,nom,prenom,email,adresse3,telephone_mobile,telephone_fixe,zipcode

            $body = array();
            foreach ($cols as $col) {
                if ($col == 'ID DON') {
                    $body[] = $value['id_don'];
                } elseif ($col == 'ID DONATEUR') {
                    $body[] = $value['donateur_id'];
                } elseif ($col == 'Montant DON') {
                    $body[] = $value['montant_don'];
                } elseif ($col == 'DATE CREATION') {
                    $body[] = $value['date_creation'];
                } elseif ($col == 'DATE FISCALE') {
                    $body[] = $value['date_fiscale'];
                } elseif ($col == 'LOT') {
                    $body[] = $value['lot'];
                } elseif ($col == 'NOM DONATEUR') {
                    $body[] = $value['nom_donateur'];
                } elseif ($col == 'PRENOM DONATEUR') {
                    $body[] = $value['prenom_donateur'];
                } elseif ($col == 'REFERENCE DONATEUR') {
                    $body[] = $value['ref_donateur'];
                }
            }//end foreach2
            $fields[] = $body;
        }//end foreach1
//        echo 'fin';
//        die();




        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['detaille-don-pour-donateur-id=10626'] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        return $fileLocations;
    }

    public function RequetteDetailleDonWithIdDonateur10626() {
        $sql = "SELECT d.id as id_don,d.user_id as donateur_id, d.montant as montant_don, d.created_at as date_creation, d.date_fiscale as date_fiscale,

d.lot as lot, dnt.nom as nom_donateur, dnt.prenom as prenom_donateur, dnt.adresse3 as adresse3, dnt.telephone_mobile as tel_mobile,dnt.ref_donateur as ref_donateur


 FROM coline_en_re_full_db.don d


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.id

where d.user_id = 10626";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function ExtractionEtaREcapitulati1fOrderByDonateur2017() {
        $date_debut = '01/01/2017';
        $date_fin = '31/12/2017';
        $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut . ' 00:00:00')->format('Y-m-d H:i:s');
        $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin . ' 23:59:59')->format('Y-m-d H:i:s');
        $data_detaill = $this->EtaREcapitulatifOrderByDonateur2017();
        $code = "Liste" . " " . "des" . " " . "RF" . " " . "classée" . " " . "par" . " " . "Donateur" . " ";
        $filename = "donateur" . '-' . date('d-m-Y-H-m-s') . md5(time());
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/recap1_donateur_2017.html.twig', array(
                    'data_detaill' => $data_detaill,
                    'code' => $code,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin
                        )
                ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
        );
        $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';

        var_dump('file location');
        var_dump($file);
        $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_recap.html.twig');
        $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
        $object = $code;
        $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
    }

    private function ExtractionEtaREcapitulati2fOrderByDonateur2017() {
        $date_debut = '01/01/2017';
        $date_fin = '31/12/2017';
        $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut . ' 00:00:00')->format('Y-m-d H:i:s');
        $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin . ' 23:59:59')->format('Y-m-d H:i:s');
        $data_detaill = $this->EtaREcapitulatif2OrderByDonateur2017();
        $code = "Liste" . " " . "des" . " " . "RF" . " " . "classée" . " " . "par" . " " . "Donateur" . " ";
        $filename = "donateur" . '-' . date('d-m-Y-H-m-s') . md5(time());
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/recap2_donateur_2017.html.twig', array(
                    'data_detaill' => $data_detaill,
                    'code' => $code,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin
                        )
                ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
        );
        $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';

        var_dump('file location');
        var_dump($file);
        $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_recap.html.twig');
        $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
        $object = $code;
        $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
    }

    private function Extraction2RecusFiscaux2017() {
        $date_debut = '01/01/2017';
        $date_fin = '31/12/2017';
        $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut . ' 00:00:00')->format('Y-m-d H:i:s');
        $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin . ' 23:59:59')->format('Y-m-d H:i:s');
        $data_detaill = $this->Requette2GetAllRecuFiscal2017();
//        var_dump($data_detaill);
//        die('detaille');

        $code = "Reçu" . " " . "fiscaux" . " " . "2017";
        $filename = "recu-fiscaux" . '-' . date('d-m-Y-H-m-s') . md5(time());
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/recap2_recu_fiscaux2017.html.twig', array(
                    'data_detaill' => $data_detaill,
                    'code' => $code,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin
                        )
                ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
        );
        $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';

        var_dump('file location');
        var_dump($file);
        $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_recap.html.twig');
        $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
        $object = $code;
        $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
    }

    private function Extraction1RecusFiscaux2017() {
        $date_debut = '01/01/2017';
        $date_fin = '31/12/2017';
        $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut . ' 00:00:00')->format('Y-m-d H:i:s');
        $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin . ' 23:59:59')->format('Y-m-d H:i:s');
        $data_detaill = $this->Requette1GetAllRecuFiscal2017();
//        var_dump($data_detaill);
//        die('detaille');

        $code = "Reçu" . " " . "fiscaux" . " " . "2017";
        $filename = "recu-fiscaux" . '-' . date('d-m-Y-H-m-s') . md5(time());
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/recap1_recu_fiscaux2017.html.twig', array(
                    'data_detaill' => $data_detaill,
                    'code' => $code,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin
                        )
                ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
        );
        $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';

        var_dump('file location');
        var_dump($file);
        $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_recap.html.twig');
        $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
        $object = $code;
        $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
    }

    private function Extraction1EtaREcapitulatif2017() {
        $date_debut = '01/01/2017';
        $date_fin = '31/12/2017';
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');

        $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut . ' 00:00:00')->format('Y-m-d H:i:s');
        $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin . ' 23:59:59')->format('Y-m-d H:i:s');

//        $code = 'concerts-cordes-2016';
        $code = 'dons-2017';
        $filename = 'recap' . '-' . $code . '-' . date('d-m-Y-H-m-s') . md5(time());

//        $data_detaill = array();
        $data_detaill = $this->LigneEtaREcapitulatifConcertVDCPianoCordes2017();
//        $data_detaill = $this->RequetteEtaREcapitulatif2015(); 
//        $headers = array();
//        $headers = array();
        $headers = $this->getConcertVDCPianoCordesInCauseBetwen2017();


        $all_donateur = $repDonateur->findAll();

//        $artiste_distinct = $this->RequetteGetDistinctArtisteBetwen2017();
//
//        $hote_distinct = $this->RequetteGetDistinctHoteBetwen2017();
        //FulldonIntersaBundle:Stats:pdf/recap_rafik.html.twig
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/recap_rafik.html.twig', array(
                    'data_detaill' => $data_detaill,
                    'code' => $code,
                    'headers' => $headers,
                    'alldonateur' => $all_donateur,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin
                        )
                ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
        );
        $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';

        var_dump('file location');
        var_dump($file);
        $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_recap.html.twig');
        $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
        $object = "Récapitulatif $date_debut -> $date_fin";
        $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
    }

    private function Extraction2EtaREcapitulatif2017() {
        $date_debut = '01/01/2017';
        $date_fin = '31/12/2017';
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');

        $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut . ' 00:00:00')->format('Y-m-d H:i:s');
        $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin . ' 23:59:59')->format('Y-m-d H:i:s');

//        $code = 'concerts-cordes-2016';
        $code = 'dons-2017';
        $filename = 'recap' . '-' . $code . '-' . date('d-m-Y-H-m-s') . md5(time());

//        $data_detaill = array();
        $data_detailles = $this->LigneEtaREcapitulatifDEL_DHV2017();
        $headers = $this->GroupByEtaREcapitulatifDEL_DHV2017();
//        $data_detaill = $this->RequetteEtaREcapitulatif2015(); 
//        $artiste_distinct = $this->RequetteGetDistinctArtisteBetwen2017();
//
//        $hote_distinct = $this->RequetteGetDistinctHoteBetwen2017();
        //FulldonIntersaBundle:Stats:pdf/recap_rafik.html.twig
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/recap_rafik3.html.twig', array(
                    'data_detailles' => $data_detailles,
                    'headers' => $headers,
                    'code' => $code,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin
                        )
                ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
        );
        $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';

        var_dump('file location');
        var_dump($file);
        $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_recap.html.twig');
        $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
        $object = "Récapitulatif $date_debut -> $date_fin";
        $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
    }
    
    
    private function Extraction3EtaREcapitulatif2017() {
        $date_debut = '01/01/2017';
        $date_fin = '31/12/2017';
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');

        $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut . ' 00:00:00')->format('Y-m-d H:i:s');
        $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin . ' 23:59:59')->format('Y-m-d H:i:s');

//        $code = 'concerts-cordes-2016';
        $code = 'dons-2017';
        $filename = 'recap' . '-' . $code . '-' . date('d-m-Y-H-m-s') . md5(time());

//        $data_detaill = array();
        $data_detaill = $this->LigneEtaREcapitulatifConcertColine2017();
//        $data_detaill = $this->RequetteEtaREcapitulatif2015(); 
//        $headers = array();
//        $headers = array();
        $headers = $this->getConcertColineInCauseBetwen2017();


        $all_donateur = $repDonateur->findAll();

//        $artiste_distinct = $this->RequetteGetDistinctArtisteBetwen2017();
//
//        $hote_distinct = $this->RequetteGetDistinctHoteBetwen2017();
        //FulldonIntersaBundle:Stats:pdf/recap_rafik.html.twig
        $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/recap_coline_2017.html.twig', array(
                    'data_detaill' => $data_detaill,
                    'code' => $code,
                    'headers' => $headers,
                    'alldonateur' => $all_donateur,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin
                        )
                ), '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf', array(), true
        );
        $file = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/recap/' . $filename . '.pdf';

        var_dump('file location');
        var_dump($file);
        $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:admin_recap.html.twig');
        $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
        $object = "Récapitulatif $date_debut -> $date_fin";
        $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file, $object);
    }
    
    
    
    
    

    public function LigneEtaREcapitulatifDEL_DHV2017() {

        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,c.code_occasion as code_occassion,dnt.id as donateur_id,d.montant as montant,

c.libelle as libelle_cause,c.code as code_activiter,

rf.id as num_rf,rf.createAt as date_creation_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id




WHERE (d.date_fiscale BETWEEN '2017-01-01' AND '2017-12-31') and dnt.removed = 0 and d.removed = 0 and montant <> 0 
and (code_occasion = 2 or code_occasion = 3 or code_occasion = 24)

  order by libelle_cause DESC";
        // normallement on fait left join avec la table prelevement avec la clé abo_id et ajouter
        // la condition prelevement.rejet = 0 si il yon a des rejets


        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function GroupByEtaREcapitulatifDEL_DHV2017() {

        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,c.code_occasion as code_occassion,dnt.id as donateur_id,d.montant as montant,

c.libelle as libelle_cause,c.code as code_activiter,

rf.id as num_rf,rf.createAt as date_creation_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id




WHERE (d.date_fiscale BETWEEN '2017-01-01' AND '2017-12-31') and dnt.removed = 0 and d.removed = 0 and montant <> 0 
and (code_occasion = 2 or code_occasion = 3 or code_occasion = 24)

  group by libelle_cause ";
        // normallement on fait left join avec la table prelevement avec la clé abo_id et ajouter
        // la condition prelevement.rejet = 0 si il yon a des rejets


        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function RequetteGetAllCauseBetwen2015() {
        $sql = "SELECT * FROM coline_en_re_full_db.cause 

WHERE date_concert BETWEEN '2015-01-01' AND '2015-12-31' group by libelle";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function RequetteEtaREcapitulatif2015() {


        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,d.user_id as donateur_id,d.montant as montant,

c.libelle as libelle_cause,c.date_concert as date_concert,

rf.id as num_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.id


WHERE date_concert BETWEEN '2015-01-01' AND '2015-12-31'  order by libelle_cause ASC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function EtaREcapitulatif2OrderByDonateur2017() {
        $sql = "SELECT d.id as id,c.code_occasion as code_occasion,d.mode_id as mode_id,d.cause_id as cause_id,dnt.id as donateur_id,d.montant as montant,

c.libelle as libelle_cause,c.code as code_activiter,d.date_fiscale as date_fiscale,

rf.id as num_rf,rf.createAt as date_creation_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id




WHERE (d.date_fiscale BETWEEN '2017-01-01' AND '2017-12-31') and dnt.removed = 0 and d.removed = 0 and montant <> 0 
and (code_occasion = 2 or code_occasion = 3 or code_occasion = 24)

  order by nom_donateur,prenom_donateur ASC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function EtaREcapitulatifOrderByDonateur2017() {
//        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,d.user_id as donateur_id,d.montant as montant,d.created_at as date_creation,
//
//c.libelle as libelle_cause,c.date_concert as date_concert,c.lieu_concert as lieu_concert,
//
//rf.id as num_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur
//
// FROM coline_en_re_full_db.don d
//
//left join coline_en_re_full_db.cause c
//
//on d.cause_id = c.id
//
//
//left join coline_en_re_full_db.rf rf
//
//on rf.don_id = d.id
//
//
//left join coline_en_re_full_db.mode_paiement mp
//
//on d.mode_id = mp.id
//
//
//left join coline_en_re_full_db.donateur dnt
//
//on d.user_id = dnt.user_id
//
//
//WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31' and dnt.removed = 0  order by nom_donateur,prenom_donateur ASC";



        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,d.user_id as donateur_id,d.montant as montant,d.created_at as date_creation,

c.libelle as libelle_cause,c.date_concert as date_concert,c.lieu_concert as lieu_concert,

rf.id as num_rf,rf.createAt as date_creation_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id


WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31' and dnt.removed = 0 and d.removed = 0  and montant <>0


order by nom_donateur,prenom_donateur ASC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function Requette2GetAllRecuFiscal2017() {



        $sql = "SELECT d.id as id,c.code_occasion as code_occasion, d.mode_id as mode_id,d.cause_id as cause_id,d.user_id as donateur_id,d.montant as montant,d.created_at as date_creation,d.date_fiscale as date_fiscale,

c.libelle as libelle_cause,c.date_concert as date_concert,c.lieu_concert as lieu_concert,c.code as code_activiter,

rf.id as num_rf,rf.createAt as date_creation_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id


 

WHERE (d.date_fiscale BETWEEN '2017-01-01' AND '2017-12-31') and dnt.removed = 0 and d.removed = 0 and montant <> 0 
and (code_occasion = 2 or code_occasion = 3 or code_occasion = 24)

order by num_rf ASC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function Requette1GetAllRecuFiscal2017() {
//        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,d.user_id as donateur_id,d.montant as montant,d.created_at as date_creation,
//
//c.libelle as libelle_cause,c.date_concert as date_concert,c.lieu_concert as lieu_concert,
//
//rf.id as num_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur
//
// FROM coline_en_re_full_db.don d
//
//left join coline_en_re_full_db.cause c
//
//on d.cause_id = c.id
//
//
//left join coline_en_re_full_db.rf rf
//
//on rf.don_id = d.id
//
//
//left join coline_en_re_full_db.mode_paiement mp
//
//on d.mode_id = mp.id
//
//
//left join coline_en_re_full_db.donateur dnt
//
//on d.user_id = dnt.user_id
//
//
//WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31'  order by num_rf ASC";


        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,d.user_id as donateur_id,d.montant as montant,d.created_at as date_creation,

c.libelle as libelle_cause,c.date_concert as date_concert,c.lieu_concert as lieu_concert,

rf.id as num_rf,rf.createAt as date_creation_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id


WHERE (date_concert BETWEEN '2017-01-01' AND '2017-12-31') and dnt.removed = 0 and d.removed = 0 and montant <> 0  

order by num_rf ASC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getConcertVDCPianoCordesInCauseBetwen2017() {
//        $sql = "SELECT c.id as id,c.code_occasion as code_occasion,c.libelle as libelle,c.code as code,c.artiste as artiste,c.hote as hote, c.date_concert as date_concert,
//
//c.lieu_concert as lieu_concert,co.id as id_code_occasion,co.name as name_code_occasion
//
//FROM coline_en_re_full_db.cause c
//
//left join coline_en_re_full_db.code_occasion co on c.code_occasion = co.id
//
//
//
//WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31'  order by name_code_occasion ASC";
//        $sql = "SELECT c.id as id,c.code_occasion as code_occasion,c.libelle as libelle,c.code as code,c.artiste as artiste,c.hote as hote, c.date_concert as date_concert,
//
//c.lieu_concert as lieu_concert,co.id as id_code_occasion,co.name as name_code_occasion
//
//FROM coline_en_re_full_db.cause c
//
//left join coline_en_re_full_db.code_occasion co on c.code_occasion = co.id
//
//
//
//WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31'  group by hote ASC";


        $sql = "SELECT c.id as id,c.code_occasion as code_occasion,c.libelle as libelle,c.code as code,c.artiste as artiste,c.hote as hote, c.date_concert as date_concert,

c.lieu_concert as lieu_concert,co.id as id_code_occasion,co.name as name_code_occasion,co.code as code_occasion

FROM coline_en_re_full_db.cause c

left join coline_en_re_full_db.code_occasion co on c.code_occasion = co.id


WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31'  order by name_code_occasion ASC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
     public function LigneEtaREcapitulatifConcertColine2017() {



        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,dnt.id as donateur_id,d.montant as montant,

c.libelle as libelle_cause,c.date_concert as date_concert, c.code as code_activiter,

rf.id as num_rf,rf.createAt as date_creation_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id


WHERE (date_concert BETWEEN '2017-01-01' AND '2017-12-31')

 and dnt.removed = 0 and d.removed = 0 and montant <> 0 and c.libelle = 'CONCERT COLINE 2017'

  order by c.libelle  DESC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getConcertColineInCauseBetwen2017() {


        $sql = "SELECT c.id as id,c.code_occasion as code_occasion,c.libelle as libelle,c.code as code,c.artiste as artiste,c.hote as hote, c.date_concert as date_concert,

c.lieu_concert as lieu_concert,co.id as id_code_occasion,co.name as name_code_occasion,co.code as code_occasion

FROM coline_en_re_full_db.cause c

left join coline_en_re_full_db.code_occasion co on c.code_occasion = co.id


WHERE (date_concert BETWEEN '2017-01-01' AND '2017-12-31') and co.name = 'CONCERT COLINE 2017'


order by name_code_occasion ASC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function RequetteGetDistinctArtisteBetwen2017() {
        $sql = "SELECT DISTINCT artiste FROM coline_en_re_full_db.cause

where date_concert BETWEEN '2017-01-01' AND '2017-12-31' ";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function RequetteGetDistinctHoteBetwen2017() {
        $sql = "SELECT DISTINCT hote FROM coline_en_re_full_db.cause

where date_concert BETWEEN '2017-01-01' AND '2017-12-31' ";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function LigneEtaREcapitulatifConcertVDCPianoCordes2017() {


//        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,dnt.id as donateur_id,d.montant as montant,rf.createAt  as date_creation,
//
//c.libelle as libelle_cause,c.date_concert as date_concert, c.code as code_activiter,
//
//rf.id as num_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur
//
// FROM coline_en_re_full_db.don d
//
//left join coline_en_re_full_db.cause c
//
//on d.cause_id = c.id
//
//
//left join coline_en_re_full_db.rf rf
//
//on rf.don_id = d.id
//
//
//left join coline_en_re_full_db.mode_paiement mp
//
//on d.mode_id = mp.id
//
//
//left join coline_en_re_full_db.donateur dnt
//
//on d.user_id = dnt.user_id
//
//
//WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31' and dnt.removed = 0 and montant <> 0 
//
//  order by libelle_cause DESC";


        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,dnt.id as donateur_id,d.montant as montant,

c.libelle as libelle_cause,c.date_concert as date_concert, c.code as code_activiter,

rf.id as num_rf,rf.createAt as date_creation_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id


WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31' and dnt.removed = 0 and d.removed = 0 and montant <> 0 

  order by libelle_cause DESC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function Requette2EtaREcapitulatif2017() {


        $sql = "SELECT d.id as id,d.mode_id as mode_id,d.cause_id as cause_id,d.user_id as donateur_id,d.montant as montant,d.created_at as date_creation,

c.libelle as libelle_cause,c.date_concert as date_concert,

rf.id as num_rf, mp.libelle as mode_payement, dnt.nom as nom_donateur , dnt.prenom as prenom_donateur

 FROM coline_en_re_full_db.don d

left join coline_en_re_full_db.cause c

on d.cause_id = c.id


left join coline_en_re_full_db.rf rf

on rf.don_id = d.id


left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id


left join coline_en_re_full_db.donateur dnt

on d.user_id = dnt.user_id


WHERE date_concert BETWEEN '2017-01-01' AND '2017-12-31'  order by libelle_cause DESC";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function RequetteDonateurSansDonSansCoordonnees() {

        $sql = "SELECT  * FROM    coline_en_re_full_db.donateur dnt
         WHERE   NOT EXISTS
        (
        SELECT  * 
        FROM    coline_en_re_full_db.don d
        WHERE   d.user_id = dnt.user_id
        )
        and adresse1 is null and adresse2 is null and adresse3 is null and adresse4 is null and adresse5 is null and zipcode is null";
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function ExtractionDonateurSansDonSansCoordonnees() {
        $colDispaly = null;
        $fileLocations = array();
        $fields = array();
        $head = array();
        $cols = array('id_donateur' => 'ID DONATEUR', 'nom' => 'NOM', 'prenom' => 'PRENOM', '' => 'DATE CREATION', '' => 'CATEGORIE');
        foreach ($cols as $key => $cd) {
            $head[] = $cols[$key];
        }
        $fields[] = $head;

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $donateur = $em->getRepository('FulldonDonateurBundle:Donateur')->find(1);
        $all_cat_donateur = $em->getRepository('FulldonDonateurBundle:CategoryDonateur')->findAll();
        foreach ($all_cat_donateur as $key => $value) {
            if ($value != NULL) {

                if ($value->getDonateurs() != NULL) {

                    $_array = array();
                    $_array = $value->getDonateurs();
                    var_dump($_array);

//                    var_dump($value->getId());
                    die('dump + $value->getDonateurs');
                }
            }
        }

//        $cat_donateur->getDonateurs();
//        $categorie_donateur = $donateur->getCategories();

        die('heree + not enter foreach all cat_donateur');



        $all_don__bydate = $this->RequetteDonateurSansDonSansCoordonnees(); //

        var_dump($all_don__bydate);
        die('heree');

        foreach ($all_don__bydate as $key => $value) {


//id,nom,prenom,email,adresse3,telephone_mobile,telephone_fixe,zipcode

            $body = array();
            foreach ($cols as $col) {
                if ($col == 'ID DONATEUR') {
                    $body[] = $value['id'];
                } elseif ($col == 'NOM') {
                    $body[] = $value['nom'];
                } elseif ($col == 'PRENOM') {
                    $body[] = $value['prenom'];
                } elseif ($col == 'DATE CREATION') {
                    $body[] = $value[''];
                } elseif ($col == 'CATEGORIE') {
                    $body[] = $value[''];
                }
            }//end foreach2
            $fields[] = $body;
        }//end foreach1
//        echo 'fin';
//        die();




        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['donateur-sans-don-sans-coordonner'] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        return $fileLocations;
    }

    public function RequetteCountDonateurWithMailISNULL() {


        $sql = " SELECT count(id) as id FROM coline_en_re_full_db.donateur

where email is null ;";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function RequetteDonateurWithMailContainNumber() {


        $sql = "SELECT * FROM coline_en_re_full_db.donateur 

WHERE email  LIKE '%_0__%.__%'  or email  LIKE '%_1__%.__%'  or email  LIKE '%_2__%.__%' or email  LIKE '%_3__%.__%' or email  LIKE '%_4__%.__%'

or email  LIKE '%_5__%.__%' or email  LIKE '%_6__%.__%' or email  LIKE '%_7__%.__%' or email  LIKE '%_8__%.__%' or email  LIKE '%_9__%.__%'";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function CountDonateurWithMailContainNumbre() {

        $_array_count = array();
        $_array_count = $this->RequetteDonateurWithMailContainNumber();
        return count($_array_count);
    }

    private function ExtractionDonateurWithMailContainNumbre() {

        $colDispaly = null;
        $fileLocations = array();
        $fields = array();
        $head = array();
        $cols = array('id_donateur' => 'ID DONATEUR', 'nom' => 'NOM', 'prenom' => 'PRENOM', 'email' => 'E-MAIL',);
        foreach ($cols as $key => $cd) {
            $head[] = $cols[$key];
        }
        $fields[] = $head;


        $all_don__bydate = $this->RequetteDonateurWithMailContainNumber(); // 

        foreach ($all_don__bydate as $key => $value) {


//id,nom,prenom,email,adresse3,telephone_mobile,telephone_fixe,zipcode

            $body = array();
            foreach ($cols as $col) {
                if ($col == 'ID DONATEUR') {
                    $body[] = $value['id'];
                } elseif ($col == 'NOM') {
                    $body[] = $value['nom'];
                } elseif ($col == 'PRENOM') {
                    $body[] = $value['prenom'];
                } elseif ($col == 'E-MAIL') {
                    $body[] = $value['email'];
                }
            }//end foreach2
            $fields[] = $body;
        }//end foreach1
//        echo 'fin';
//        die();




        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['donateur-email-contain-number'] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        return $fileLocations;
    }

    private function CountDonateurWithMailISNULL() {

        $_array_count_all_donateur_where_email_null = array();
        $_array_count_all_donateur_where_email_null = $this->RequetteDonateurWithMailISNULL();
        return count($_array_count_all_donateur_where_email_null);
    }

    public function RequetteDonateurWithMailISNULL() {


        $sql = " SELECT * FROM coline_en_re_full_db.donateur  WHERE email is null ";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function DonateurWithMailISNULL() {
        $colDispaly = null;
        $fileLocations = array();
//        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
//        $donateur_isnull_email = $em->getRepository('FulldonDonateurBundle:Donateur')->findBy(array('email' => NULL));
        // Création du fichier CSV
        $fields = array();
        $head = array();
//        $cols = array('id' => 'Id', 'nom' => 'Nom', 'prenom' => 'Prénom', 'email' => 'E-mail', 'zip' => 'Code Postal', 'ville' => 'Ville');
        $cols = array('id_donateur' => 'ID DONATEUR', 'nom' => 'NOM', 'prenom' => 'PRENOM', 'adresse' => 'ADRESSE', 'telmobile' => 'TEL MOBILE', 'telfixe' => 'TEL FIXE', 'zipcode' => 'CODE POSTAL');
//        $cols = array('id_donateur' => 'ID DONATEUR', 'nom' => 'NOM', 'prenom' => 'PRENOM', 'email' => 'EMAIL', 'telmobile' => 'TEL MOBILE', 'telfixe' => 'TEL FIXE');
//        foreach ($donateur_isnull_email as $donateur) {
//            
//            
//        }
        foreach ($cols as $key => $cd) {
            $head[] = $cols[$key];
        }
        $fields[] = $head;


        $all_don__bydate = $this->RequetteDonateurWithMailISNULL(); // 

        foreach ($all_don__bydate as $key => $value) {


//id,nom,prenom,email,adresse3,telephone_mobile,telephone_fixe,zipcode

            $body = array();
            foreach ($cols as $col) {
                if ($col == 'ID DONATEUR') {
                    $body[] = $value['id'];
                } elseif ($col == 'NOM') {
                    $body[] = $value['nom'];
                } elseif ($col == 'PRENOM') {
                    $body[] = $value['prenom'];
                } elseif ($col == 'TEL MOBILE') {
                    $body[] = $value['telephone_mobile'];
                } elseif ($col == 'TEL FIXE') {
                    $body[] = $value['telephone_fixe'];
                } elseif ($col == 'ADRESSE') {
                    $body[] = $value['adresse3'];
                } elseif ($col == 'CODE POSTAL') {
                    $body[] = $value['zipcode'];
                }
            }//end foreach2
            $fields[] = $body;
        }//end foreach1
//        echo 'fin';
//        die();




        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['donateur-sans-mail'] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        return $fileLocations;
    }

    public function DonateurWithMailContainNumber() {


        $sql = "SELECT * FROM coline_en_re_full_db.donateur 

        WHERE email LIKE '%0%'
         OR email LIKE '%1%'
         OR email LIKE '%2%'
         OR email LIKE '%3%'
         OR email LIKE '%4%'
         OR email LIKE '%5%'
         OR email LIKE '%6%'
         OR email LIKE '%7%'
         OR email LIKE '%8%'
         OR email LIKE '%9%'";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function Test3() {
        $colDispaly = null;
        $fileLocations = array();
//        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
//        $donateur_isnull_email = $em->getRepository('FulldonDonateurBundle:Donateur')->findBy(array('email' => NULL));
        // Création du fichier CSV
        $fields = array();
        $head = array();
//        $cols = array('id' => 'Id', 'nom' => 'Nom', 'prenom' => 'Prénom', 'email' => 'E-mail', 'zip' => 'Code Postal', 'ville' => 'Ville');
        $cols = array('id_donateur' => 'ID DONATEUR', 'nom' => 'NOM', 'prenom' => 'PRENOM', 'email' => 'EMAIL', 'adresse' => 'ADRESSE', 'telmobile' => 'TEL MOBILE', 'telfixe' => 'TEL FIXE', 'zipcode' => 'CODE POSTAL');
//        $cols = array('id_donateur' => 'ID DONATEUR', 'nom' => 'NOM', 'prenom' => 'PRENOM', 'email' => 'EMAIL', 'telmobile' => 'TEL MOBILE', 'telfixe' => 'TEL FIXE');
//        foreach ($donateur_isnull_email as $donateur) {
//            
//            
//        }
        foreach ($cols as $key => $cd) {
            $head[] = $cols[$key];
        }
        $fields[] = $head;


        $all_don__bydate = $this->DonateurWithMailContainNumber(); // 

        foreach ($all_don__bydate as $key => $value) {


//id,nom,prenom,email,adresse3,telephone_mobile,telephone_fixe,zipcode

            $body = array();
            foreach ($cols as $col) {
                if ($col == 'ID DONATEUR') {
                    $body[] = $value['id'];
                } elseif ($col == 'NOM') {
                    $body[] = $value['nom'];
                } elseif ($col == 'PRENOM') {
                    $body[] = $value['prenom'];
                } elseif ($col == 'EMAIL') {
                    $body[] = $value['email'];
                } elseif ($col == 'TEL MOBILE') {
                    $body[] = $value['telephone_mobile'];
                } elseif ($col == 'TEL FIXE') {
                    $body[] = $value['telephone_fixe'];
                } elseif ($col == 'ADRESSE') {
                    $body[] = $value['adresse3'];
                } elseif ($col == 'CODE POSTAL') {
                    $body[] = $value['zipcode'];
                }
            }//end foreach2
            $fields[] = $body;
        }//end foreach1
//        echo 'fin';
//        die();




        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['donateur-mail-contain-number'] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        return $fileLocations;
    }

    public function DonateurSansDonSnsCoordonnees() {

//        $sql = " SELECT  * FROM    coline_en_re_full_db.donateur dnt
//            
//      WHERE   NOT EXISTS
//        (
//        SELECT  null 
//        FROM    coline_en_re_full_db.don d
//        WHERE   d.user_id = dnt.user_id)";

        $sql = "SELECT  * FROM    coline_en_re_full_db.donateur dnt
         WHERE   NOT EXISTS
        (
        SELECT  null 
        FROM    coline_en_re_full_db.don d
        WHERE   d.user_id = dnt.user_id
        )
        and adresse1 is null and adresse2 is null and adresse3 is null and adresse4 is null and adresse5 is null and zipcode is null";
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function Test2() {
        $colDispaly = null;
        $fileLocations = array();
//        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
//        $donateur_isnull_email = $em->getRepository('FulldonDonateurBundle:Donateur')->findBy(array('email' => NULL));
        // Création du fichier CSV
        $fields = array();
        $head = array();
//        $cols = array('id' => 'Id', 'nom' => 'Nom', 'prenom' => 'Prénom', 'email' => 'E-mail', 'zip' => 'Code Postal', 'ville' => 'Ville');
//        $cols = array('id_donateur' => 'ID DONATEUR', 'nom' => 'NOM', 'prenom' => 'PRENOM', 'email' => 'EMAIL', 'adresse' => 'ADRESSE', 'telmobile' => 'TEL MOBILE', 'telfixe' => 'TEL FIXE', 'zipcode' => 'CODE POSTAL');
        $cols = array('id_donateur' => 'ID DONATEUR', 'nom' => 'NOM', 'prenom' => 'PRENOM', 'email' => 'EMAIL', 'telmobile' => 'TEL MOBILE', 'telfixe' => 'TEL FIXE');


//        foreach ($donateur_isnull_email as $donateur) {
//            
//            
//        }
        foreach ($cols as $key => $cd) {
            $head[] = $cols[$key];
        }
        $fields[] = $head;


        $all_don__bydate = $this->DonateurSansDonSnsCoordonnees(); // 

        foreach ($all_don__bydate as $key => $value) {


//id,nom,prenom,email,adresse3,telephone_mobile,telephone_fixe,zipcode

            $body = array();
            foreach ($cols as $col) {
                if ($col == 'ID DONATEUR') {
                    $body[] = $value['id'];
                } elseif ($col == 'NOM') {
                    $body[] = $value['nom'];
                } elseif ($col == 'PRENOM') {
                    $body[] = $value['prenom'];
                } elseif ($col == 'EMAIL') {
                    $body[] = $value['email'];
                } elseif ($col == 'TEL MOBILE') {
                    $body[] = $value['telephone_mobile'];
                } elseif ($col == 'TEL FIXE') {
                    $body[] = $value['telephone_fixe'];
                }
            }//end foreach2
            $fields[] = $body;
        }//end foreach1
//        echo 'fin';
//        die();




        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['donateur-sans-don'] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        return $fileLocations;
    }

    public function Don2016byModePaiement() {

//        $sql = 'SELECT id, montant FROM don WHERE date_fiscale <= 2016-12-31 and date_fiscale >= 2016-01-01 and mode_id = 1  and motif_disable_don_id is null ;';
//        var_dump($mode_paiement);
//        die;
        $datetime1 = new \DateTime('2016-01-01');
        $datetime2 = new \DateTime('2016-12-31');

//
//        $sql = "SELECT dnt.id as donateur_id,u.id as id_user,d.id as don_id,dnt.nom,dnt.prenom ,montant_don,mp.libelle as mode_paiement,cause.code as code_activite,s.libelle as satut_don,d.date_fiscale,p.id as prelevement_id,p.rejet,s.id as statut_id ,t.id as transaction_id,ab.id as ab_id,d.mode_id,d.motif_disable_don_id,d.cause_id,d.user_id FROM demo_full_db.don d
//
//inner join demo_full_db.User u
//
//on d.user_id = u.id
//
//left join demo_full_db.donateur dnt
//
//on u.id = dnt.user_id
//
//
//left join demo_full_db.cause cause
//
//on d.cause_id = cause.id
//
//
//inner join demo_full_db.transaction t
//
//on d.transaction_id = t.id
//
//left join demo_full_db.mode_paiement mp
//
//on d.mode_id = mp.id
//
//
//left join demo_full_db.abonnement ab
//
//on d.abo_id = ab.id
//
//left join demo_full_db.prelevement p
//
//on ab.id = p.abo_id
//
//inner join demo_full_db.statut_paiement s 
//
//on t.status_id = s.id
//
//WHERE d.date_fiscale <='" . $datetime2->format("Y-m-d H:i:s") . "' AND d.date_fiscale >='" . $datetime1->format("Y-m-d H:i:s") . "' AND s.libelle = 'Don validé'
//
//AND d.motif_disable_don_id is null  AND (p.rejet = 0 OR p.rejet is null)
//
//GROUP BY d.user_id ";



        $sql = "SELECT dnt.id as donateur_id,u.id as id_user,d.id as don_id,rf.id as recu_fiscale,dnt.nom,dnt.prenom ,d.montant as montant_don,mp.libelle as mode_paiement,cause.code as code_activite,s.libelle as satut_don,d.date_fiscale,p.id as prelevement_id,p.rejet,s.id as statut_id ,t.id as transaction_id,ab.id as ab_id,d.mode_id,d.motif_disable_don_id,d.cause_id,d.user_id FROM demo_full_db.don d


left join demo_full_db.rf rf

on d.id = rf.don_id



inner join demo_full_db.User u

on d.user_id = u.id

left join demo_full_db.donateur dnt

on u.id = dnt.user_id


left join demo_full_db.cause cause

on d.cause_id = cause.id


inner join demo_full_db.transaction t

on d.transaction_id = t.id

left join demo_full_db.mode_paiement mp

on d.mode_id = mp.id


left join demo_full_db.abonnement ab

on d.abo_id = ab.id

left join demo_full_db.prelevement p

on ab.id = p.abo_id

inner join demo_full_db.statut_paiement s 

on t.status_id = s.id

WHERE d.date_fiscale <='" . $datetime2->format("Y-m-d H:i:s") . "' AND d.date_fiscale >='" . $datetime1->format("Y-m-d H:i:s") . "' AND s.libelle = 'Don validé'

AND d.motif_disable_don_id is null  AND (p.rejet = 0 OR p.rejet is null)";





//       var_dump($datetime);
//       die;
//        $sql2 = " SELECT user_id,montant FROM don WHERE date_fiscale <= '" . $datetime2->format("Y-m-d H:i:s") . "'  and  date_fiscale >= '" . $datetime1->format("Y-m-d H:i:s") . "' and motif_disable_don_id is null and mode_id = '" . $mode_paiement . "' ";
//        var_dump($sql2);
//        die;
//        $sql = 'SELECT id, montant FROM don WHERE date_fiscale <=  and date_fiscale >= 01/01/2016 and mode_id = 1  and motif_disable_don_id is null ;';
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

//        var_dump($stmt);
//        die;
        return $stmt->fetchAll();
    }

    private function Test() {
        $colDispaly = null;
        $fileLocations = array();
//        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
//        $donateur_isnull_email = $em->getRepository('FulldonDonateurBundle:Donateur')->findBy(array('email' => NULL));
        // Création du fichier CSV
        $fields = array();
        $head = array();
//        $cols = array('id' => 'Id', 'nom' => 'Nom', 'prenom' => 'Prénom', 'email' => 'E-mail', 'zip' => 'Code Postal', 'ville' => 'Ville');
        $cols = array('id_donateur' => 'ID DONATEUR', 'id' => 'ID DON', 'rf_don' => 'N° Recu Fiscale', 'montant' => 'MONTANT DU DON', 'nom' => 'NOM', 'prenom' => 'PRENOM', 'codeactivite' => 'CODE ACTIVITE', 'statut' => 'STATUT', 'modepaiement' => 'MODE DE PAIEMENT', 'datecreation' => 'DATE DE CREATION');

//        foreach ($donateur_isnull_email as $donateur) {
//            
//            
//        }
        foreach ($cols as $key => $cd) {
            $head[] = $cols[$key];
        }
        $fields[] = $head;


        $all_don__bydate = $this->Don2016byModePaiement(); // 

        foreach ($all_don__bydate as $key => $value) {

            $tolat_prelevement_bancaire = 0;
            if ($value['mode_paiement'] == 'Prélèvement automatique') {

                $tolat_prelevement_bancaire += $value['montant_don'];
            }


            $body = array();
            foreach ($cols as $col) {
                if ($col == 'ID DONATEUR') {
                    $body[] = $value['donateur_id'];
                } elseif ($col == 'ID DON') {
                    $body[] = $value['don_id'];
                } elseif ($col == 'N° Recu Fiscale') {
                    $body[] = $value['recu_fiscale'];
                } elseif ($col == 'MONTANT DU DON') {

                    if ($value['mode_paiement'] == 'Prélèvement automatique') {
                        $body[] = $tolat_prelevement_bancaire;
                    } else {
                        $body[] = $value['montant_don'];
                    }
                } elseif ($col == 'NOM') {
                    $body[] = $value['nom'];
                } elseif ($col == 'PRENOM') {
                    $body[] = $value['prenom'];
                } elseif ($col == 'CODE ACTIVITE') {
                    $body[] = $value['code_activite'];
                } elseif ($col == 'STATUT') {
                    $body[] = $value['satut_don'];
                } elseif ($col == 'MODE DE PAIEMENT') {
                    $body[] = $value['mode_paiement'];
                } elseif ($col == 'DATE DE CREATION') {
                    $body[] = $value['date_fiscale'];
                }
            }//end foreach2
            $fields[] = $body;
        }//end foreach1
//        echo 'fin';
//        die();




        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['comptage-don-2016'] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        return $fileLocations;
    }

    private function getDonateurIsNullEmail() {
        $colDispaly = null;
        $fileLocations = array();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $donateur_isnull_email = $em->getRepository('FulldonDonateurBundle:Donateur')->findBy(array('email' => NULL));

        // Création du fichier CSV
        $fields = array();
        $head = array();
//        $cols = array('id' => 'Id', 'nom' => 'Nom', 'prenom' => 'Prénom', 'email' => 'E-mail', 'zip' => 'Code Postal', 'ville' => 'Ville');
        $cols = array('id' => 'Id', 'nom' => 'Nom', 'prenom' => 'Prénom', 'zip' => 'CodePostal', 'ville' => 'Ville');

//        foreach ($donateur_isnull_email as $donateur) {
//            
//            
//        }
        foreach ($cols as $key => $cd) {
            $head[] = $cols[$key];
        }
        $fields[] = $head;


        foreach ($donateur_isnull_email as $donateur) {

            $body = array();
            foreach ($cols as $col) {
                if ($col == 'Id') {
                    $body[] = $donateur->getId();
                } elseif ($col == 'Nom') {
                    $body[] = $donateur->getNom();
                } elseif ($col == 'Prénom') {
                    $body[] = $donateur->getPrenom();
                } elseif ($col == 'CodePostal') {
                    $body[] = $donateur->getZipcode();
                } elseif ($col == 'Ville') {
//                    $ville = $em->getRepository('FulldonDonateurBundle:Donateur')->findBy(array('ville' => $donateur->getVille()));
//                    var_dump($donateur->getVilleName());
                    if ($donateur->getVilleName() != NULL) {
//                        var_dump($ville->getName());
//                        die;
                        $body[] = $donateur->getVilleName();
                    } else {
                        $body[] = '';
                    }
                }
            }//end foreach2
            $fields[] = $body;
        }//end foreach1
//        echo 'fin';
//        die();




        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['donateur-sans-email'] = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        return $fileLocations;
    }

    private function getDonsCsv2($crontask) {
        $data = array();
        $colDispaly = null;
        $fileLocations = array();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $favObj = $em->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-xsl-home'));
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




//        for ($i = 0; $i < count($data); $i++) {
//            if ($i == 0) {
//                var_dump($data[$i]);
//            }
////            if ($i == 1) {
////                var_dump(count($data[$i]));
////                
////            }
//        }
//        die('$data');




        foreach ($data as $dons) {
            foreach ($dons as $don) {

                $donateur_ix = $em->getRepository('FulldonDonateurBundle:Donateur')
                        ->findOneBy(array('user' => $don->getUser()));
//                var_dump($donateur_ix->getNom());
//                die('$donateur_ix->getNom()');
                $body = array();
                foreach ($colDispaly as $col) {
                    if ($col == 'nom') { // nom   $body[] = $donateur_ix->getNom();
                        $body[] = $donateur_ix->getNom();
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
        var_dump('delete rechereche favoris dons-xsl-home');
        $em->remove($favObj);
        $em->flush();
        var_dump('supression avec succeé');
        return $fileLocations;
    }

    private function getDonsCsv($crontask) {
        $data = array();
        $colDispaly = null;
        $fileLocations = array();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        foreach ($crontask->getRecherches() as $favObj) {

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
            // dont le data il faut préciser le donateur du dons
            foreach ($data as $dons) {
                foreach ($dons as $don) {
                    $donateur_ix = $em->getRepository('FulldonDonateurBundle:Donateur')
                            ->findOneBy(array('user' => $don->getUser()));
                    $body = array();
                    foreach ($colDispaly as $col) {
                        if ($col == 'numdon') {
                            $body[] = $don->getId();
                        } elseif ($col == 'nom') {
                            $body[] = $donateur_ix->getNom();
                        } elseif ($col == 'prenom') {
                            $body[] = $donateur_ix->getPrenom();
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
                            $body[] = $donateur_ix->getNomEntreprise();
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
                            $body[] = $don->getDateFiscale()->format('d/m/Y');
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
        }
        return $fileLocations;
    }

    private function getDonateurCsv($crontask) {
        $data = array();
        $colDispaly = null;
        $fileLocations = array();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        foreach ($crontask->getRecherches() as $favObj) {

            if (is_object($favObj)) {

                if ($favObj->getUrl() != '') {
                    $request = Request::create($this->getContainer()->get('router')->generate('elastic_don') . '?' . $favObj->getUrl());
                    $i = 1;
                    do {
                        $res = $this->getContainer()->get('fulldon.intersa.global')->getDonateurResult($request, $i, 1000);
                        $data[] = $res['result'];
                        $colDispaly = $res['coldisplay'];
                        $i++;
                    } while ($i <= $res['nboffset']);
                }
            }
            // Création du fichier CSV
            $fields = array();
            $head = array();
            $cols = array('numdonateur' => '#REF', 'nom' => 'Nom', 'prenom' => 'Prénom', 'statut' => 'Statut',
                'nomentreprise' => 'Nom d\'entreprise', 'email' => 'Email', 'birthday' => 'Date de naissance',
                'telmobile' => 'Téléphone mobile', 'telfixe' => 'Téléphone fixe', 'cat' => 'Catégories',
                'adresse' => 'Adresse', 'ville' => 'Ville', 'pays' => 'Pays', 'zipcode' => 'Code postal', 'createdat' => 'Date de création');

            foreach ($colDispaly as $cd) {
                if (array_key_exists($cd, $cols)) {
                    $head[] = $cols[$cd];
                }
            }

            $fields[] = $head;
            foreach ($data as $donateurs) {
                foreach ($donateurs as $donateur) {

                    $body = array();
                    foreach ($colDispaly as $col) {
                        if ($col == 'numdonateur') {
                            $body[] = $donateur->getRefDonateur();
                        } elseif ($col == 'nom') {
                            $body[] = $donateur->getNom();
                        } elseif ($col == 'prenom') {
                            $body[] = $donateur->getPrenom();
                        } elseif ($col == 'statut') {
                            if ($donateur->getRemoved()) {
                                $body[] = 'Activé';
                            } else {
                                $body[] = 'Désactivé';
                            }
                        } elseif ($col == 'nomentreprise') {
                            $body[] = $donateur->getNomEntreprise();
                        } elseif ($col == 'birthday') {
                            $body[] = $donateur->getDateNaissance()->format('d/m/Y');
                        } elseif ($col == 'email') {
                            $body[] = $donateur->getEmail();
                        } elseif ($col == 'telmobile') {
                            $body[] = $donateur->getTelephoneMobile();
                        } elseif ($col == 'telfixe') {
                            $body[] = $donateur->getTelephoneFixe();
                        } elseif ($col == 'cat') {
                            $cats_ids = array();
                            foreach ($donateur->getCategories() as $cat) {
                                $rfs_ids[] = $cat->getName();
                            }
                            $body[] = implode('|', $cats_ids);
                        } elseif ($col == 'adresse') {
                            $body[] = $donateur->getAdresse3() . ' ' . $donateur->getAdresse4();
                        } elseif ($col == 'ville') {
                            $body[] = $donateur->getIsoville();
                        } elseif ($col == 'pays') {
                            $body[] = $donateur->getIsopays();
                        } elseif ($col == 'zipcode') {
                            $body[] = $donateur->getZipcode();
                        } elseif ($col == 'createdat') {
                            $body[] = $donateur->getCreatedAt()->format('d/m/Y');
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
        }
        return $fileLocations;
    }

}
