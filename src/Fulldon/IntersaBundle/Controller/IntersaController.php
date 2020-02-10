<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use JMS\SecurityExtraBundle\Annotation\Secure;

class IntersaController extends Controller
{

    public function getStatsSaisieAction() {
        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie') ;
        $prod = $this->getDoctrine()
            ->getRepository('FulldonIntersaBundle:Saisie')
            ->createQueryBuilder('p')
            ->select('count(p.id) as cpt')
            ->where('p.done = FALSE')
            ->getQuery()->getSingleResult();
        $data = $this->getDoctrine()
            ->getRepository('FulldonIntersaBundle:Saisie')
            ->createQueryBuilder('p')
            ->select('count(p.id) as cpt')
            ->where('p.user = :id')
            ->andwhere('p.done = FALSE')
            ->setParameter('id', $user->getId())
            ->getQuery()->getSingleResult();
        $done = $this->getDoctrine()
            ->getRepository('FulldonIntersaBundle:Saisie')
            ->createQueryBuilder('p')
            ->select('count(p.id) as cpt')
            ->where('p.done = true')
            ->andwhere('SUBSTRING(p.createdAt, 1, 10) = CURRENT_DATE()')
            ->getQuery()->getSingleResult();

        $root = $this->container->getParameter('path_scan');

        $files  = array('files'=>array(), 'dirs'=>array());
        $directories  = array();
        $last_letter  = $root[strlen($root)-1];
        $root  = ($last_letter == '\\' || $last_letter == '/') ? $root : $root.DIRECTORY_SEPARATOR;
        $directories[]  = $root;
        $depth = 0;

        while (sizeof($directories)) {
            $dir  = array_pop($directories);

            if ($handle = opendir($dir)) {
                while (false !== ($file = readdir($handle)) && $depth < 1) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $filename = $file;
                    $file  = $dir.$file;
                    if (is_dir($file)) {
                        $directory_path = $file.DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][]  = array('name'=>$filename, 'path'=>$directory_path);

                    }
                }
                closedir($handle);
            }
            $depth++;
        }
        $nbTotal = 0;
        foreach($files['dirs'] as $dir) {
            $result = $this->get('fulldon.intersa.global')->getXmlFiles($dir['path']);
            $nbTotal+=$result['nb'];
        }


        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $element = $repSaisie->createQueryBuilder('s')
            ->select('count(s.id) as cpt')
            ->where('s.done = true and s.rfDone = false  and s.verifDone = false')
            ->getQuery()
            ->getSingleResult();

        $db = $this->getDoctrine()->getManager();
        $doublonRep = $db->getRepository('FulldonDonateurBundle:Doublon') ;
        $doublons = $doublonRep->findBy(array('done' => false));

        $db = $this->getDoctrine()->getManager();
        $anomalieRep = $db->getRepository('FulldonIntersaBundle:Anomalie') ;
        $anomalies = $anomalieRep->findBy(array('corriger' => false));


        $this->init['data']= $data['cpt'].'|'.$element['cpt'].'|'.count($doublons).'|'.count($anomalies);
        $this->init['done']= $done;
        $this->init['nbtotal'] = $nbTotal;

        return $this->render('FulldonIntersaBundle:Intersa:stats.html.twig',array('init'=>$this->init));

    }

    public function indexAction()
    {
        $data =  $this->get('fulldon.intersa.global')->getGlobalStats();
        return $this->render('FulldonIntersaBundle:Intersa:accueil.html.twig',array(
            'data'=>$data
        ));
    }
    function getGlobalPdfAction()
    {
        $data =  $this->get('fulldon.intersa.global')->getGlobalStats();
        $filename = md5(time());
        //Génération du duplicata
        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView(
                'FulldonIntersaBundle:Intersa:pdf/tableau_bord.html.twig',
                array(
                    'data'  => $data,
                    'today' => new \DateTime()
                )
            ),
            '/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf',
            array(),
            true
        );
        $response = new Response(file_get_contents( '/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf'), 200);
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }
    function getGlobalExcelAction()
    {
        $data =  $this->get('fulldon.intersa.global')->getGlobalStats();
        // ask the service for a Excel5
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
            ->setLastModifiedBy("FulldonV2.0")
            ->setTitle("Tableau de bord géneral")
            ->setSubject("Edition du ".date('d/m/Y'));
        $keyValuesA = array(
            'Donateurs actifs ' => $data['donateur_actif'],
            'Donateurs inactifs '  => $data['donateur_removed'],
            'Nombre total de donateurs ' =>  $data['donateur_actif']+ $data['donateur_removed']  ,
            'Nombre de téléphones portables ' => $data['donateur_tmobile'] ,
            'Nombre d\'emails dans la base' =>  $data['donateur_emails'] ,
            'Moyenne d\'âge des donateurs ' =>  $data['moyenne_age'] ,
            'Nombre de donateurs actifs 0/12 mois  ' =>  $data['nb_12mois'] ,
            'Nombre de donateurs actifs 12/24 mois ' => $data['nb_24mois'] ,
            'Nombre de donateurs actifs 24/36 mois ' =>  $data['nb_36mois'] ,

        );
        $pa_actif = 0 ;
        $pa_innactif = 0 ;
        foreach ($data['abo'] as $abo) {
            if ($abo['name'] == null) {
                $pa_actif = $abo['cpt'];
            } else {
                $pa_innactif =$abo['cpt'];
            }
        }
        $col_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M');

        $keyValuesB = array(
            'Dons CS '=> $data['don_cs'],
            'Dons BC '=> $data['don_bc'] ,
            'Dons ESPECES '=>$data['don_espece'] ,
            'Dons VIREMENT'=> $data['don_virement'] ,
            'Dons PA '=> $data['don_pa'] ,
            'Dons PA Actifs (Non stopés)'=> $pa_actif ,
            'Dons PA Inactifs'=>  $pa_innactif ,
            'Dons PA Actifs sur les 12 derniers mois' => $data['nb_12mois_pa']  ,
            'Dons PA Actifs sur les 12/24 derniers mois' => $data['nb_24mois_pa']  ,
            'Dons PA Stopés depuis le début de l\'année '=> $data['stops_pa'],
            'Total des prelevements '=> $data['pre_rejet'] + $data['pre_accept']  ,
            'Prélevements Acceptés'=> $data['pre_accept'],
            'Prelevements Rejetés'=> $data['pre_rejet'],

        );
        $phpExcelObject->setActiveSheetIndex(0);
        $phpExcelObject->getActiveSheet()->setCellValue('A1', 'Nombre de donateurs');
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => 'ed6c1a')
        ));
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => 'ed6c1a')
        ));
        $phpExcelObject->getActiveSheet()->getColumnDimension('A')->setWidth(60);
        $phpExcelObject->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
        $phpExcelObject->getActiveSheet()->mergeCells('A1:B1');

        $i = 2;
        foreach($keyValuesA as $key => $value) {
            $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,$key);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);
            $phpExcelObject->getActiveSheet()->setCellValue('B'.$i,$value);
            $i++;
        }

        $phpExcelObject->getActiveSheet()->setCellValue('A'.$i, 'Statistiques des dons');
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => 'ed6c1a')
        ));
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(20);
        $phpExcelObject->getActiveSheet()->mergeCells('A'.$i.':B'.$i);
        $i++;
        foreach($keyValuesB as $key => $value) {
            $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,$key);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);
            $phpExcelObject->getActiveSheet()->setCellValue('B'.$i,$value);
            $i++;
        }
        $total = 0 ;
        $isnull = 1 ;
        $array_month = array(
            'Janvier'=>'01',
            'Février'=>'02',
            'Mars'=>'03',
            'Avril'=>'04',
            'Mai'=>'05',
            'Juin'=>'06',
            'Juillet'=>'07',
            'Aout'=>'08',
            'Septembre'=>'09',
            'Octobre'=>'10',
            'Novembre'=>'11',
            'Décembre'=>'12',
        );
        // tableau
        // Thead
        // Nouveaux donateurs de l'année précédente.
        $start_col = 3;
        $i = 1;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, 'Nouveaux donateurs de l\'année précédente');
        $phpExcelObject->getActiveSheet()->mergeCells($col_array[$start_col].$i.':'.$col_array[$start_col+count($data['entities'])+1].$i);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => 'ed6c1a')
        ));
        $i++;
        $start_col = 3;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, 'Mois');
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
        $start_col++;
        foreach ($data['entities'] as $ent)
        {

            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, $ent->getName());
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
            $start_col++;
        }
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, 'Total');
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
        $start_col++;
        $i++;
        // Tbody
        $start_col = 3;
        $i = 3;
        foreach($array_month as $key => $value)
        {
        $start_col = 3;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, $key);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
        $start_col++;
        foreach ($data['entities'] as $ent)
        {
            $isnull = 1;
            foreach ($data['tab_nouveau_donateur_preyear'] as $tnd)
            {
                if($tnd['nom_entity'] == $ent->getName() && $tnd['mois'] == $value) {
                    $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, $tnd['cpt']);
                    $start_col++;
                    $total = $total + $tnd['cpt'];
                    $isnull = 0;
                }
            }
            if($isnull == 1) {
                $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, 0);
                $start_col++;
            }

        }
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, $total);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
            $start_col++;
            $total = 0;
            $i++;

        }
        // Donateurs

        $start_col = 3;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, 'Nouveaux donateurs de l\'année courante');
        $phpExcelObject->getActiveSheet()->mergeCells($col_array[$start_col].$i.':'.$col_array[$start_col+count($data['entities'])+1].$i);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => 'ed6c1a')
        ));

        $i++;
        $start_col = 3;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, 'Mois');
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
        $start_col++;
        foreach ($data['entities'] as $ent)
        {

            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, $ent->getName());
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
            $start_col++;
        }
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, 'Total');
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
        $start_col++;
        $i++;
        // Tbody
        $start_col = 3;
        foreach($array_month as $key => $value)
        {
            $start_col = 3;
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, $key);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
            $start_col++;
            foreach ($data['entities'] as $ent)
            {
                $isnull = 1;
                foreach ($data['tab_nouveau_donateur_curyear'] as $tnd)
                {
                    if($tnd['nom_entity'] == $ent->getName() && $tnd['mois'] == $value) {
                        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, $tnd['cpt']);
                        $start_col++;
                        $total = $total + $tnd['cpt'];
                        $isnull = 0;
                    }
                }
                if($isnull == 1) {
                    $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, 0);
                    $start_col++;
                }

            }
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col].$i, $total);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col].$i)->getFont()->setSize(14);
            $start_col++;
            $total = 0;
            $i++;

        }

        $phpExcelObject->getActiveSheet()->setTitle('Simple');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=tableau-bord.xlsx');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1")
     */
    public function gestionAction()
    {
        $user = $this->getUser();
        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $repStat = $db->getRepository('FulldonIntersaBundle:Stat') ;
        $rsm = new ResultSetMapping();

        // mapping results to the message entity
        $rsm->addScalarResult( 'nbre', 'nbre');
        $rsm->addScalarResult( 'curday', 'curday');
        $rsm->addScalarResult( 'somme', 'somme');
        $rsm->addScalarResult( 'temps', 'temps');
        $sql_sdn = "SELECT COUNT(id) as nbre, DATE_FORMAT(created_at,'%d%m') as curday
        FROM stat
        WHERE type_stat = '".StatVar::_STAT_TYPE_SAISIE_DONATEUR_NEW_."'
        GROUP BY DAY(created_at)
        LIMIT 0,30";
        $query = $db->createNativeQuery($sql_sdn, $rsm);
        $stats_sdn = $query->getResult();

        $sql_time = "SELECT SUM(temps) as temps, DATE_FORMAT(created_at,'%d%m') as curday
        FROM temps_saisie
        GROUP BY curday
        LIMIT 0,30";
        $query = $db->createNativeQuery($sql_time, $rsm);
        $stats_time = $query->getResult();



        $sql_sdo = "SELECT COUNT(id) as nbre, DATE_FORMAT(created_at,'%d%m') as curday
        FROM stat
        WHERE type_stat = '".StatVar::_STAT_TYPE_SAISIE_DONATEUR_OLD_."'
        GROUP BY DAY(created_at)
        LIMIT 0,30";
        $query = $db->createNativeQuery($sql_sdo, $rsm);
        $stats_sdo = $query->getResult();


        $sql_dc = "SELECT IFNULL(SUM(f.nbre),0) as somme from (select (COUNT(id)) as nbre, DATE_FORMAT(created_at,'%d%m') as curday
        FROM stat
        WHERE type_stat = '".StatVar::_STAT_TYPE_DOUBLON_CREATED_."'
        GROUP BY DAY(created_at)
        LIMIT 0,30) as f";
        $query = $db->createNativeQuery($sql_dc, $rsm);
        $stats_dc = $query->getOneOrNullResult();

        $sql_dm = "SELECT IFNULL(SUM(f.nbre),0) as somme from (select (COUNT(id)) as nbre, DATE_FORMAT(created_at,'%d%m') as curday
        FROM stat
        WHERE type_stat = '".StatVar::_STAT_TYPE_DOUBLON_MERGED_."'
        GROUP BY DAY(created_at)
        LIMIT 0,30) as f";
        $query = $db->createNativeQuery($sql_dm, $rsm);
        $stats_dm = $query->getOneOrNullResult();

        $sql_lg = "SELECT IFNULL(SUM(f.nbre),0) as somme from (select (COUNT(id)) as nbre, DATE_FORMAT(created_at,'%d%m') as curday
        FROM stat
        WHERE type_stat = '".StatVar::_STAT_TYPE_LOT_GENERATED_."'
        GROUP BY DAY(created_at)
        LIMIT 0,30) as f";
        $query = $db->createNativeQuery($sql_lg, $rsm);
        $stats_lg = $query->getOneOrNullResult();

        $sql_lv = "SELECT IFNULL(SUM(f.nbre),0) as somme from (select (COUNT(id)) as nbre, DATE_FORMAT(created_at,'%d%m') as curday
        FROM stat
        WHERE type_stat = '".StatVar::_STAT_TYPE_LOT_VERIFIED_."'
        GROUP BY DAY(created_at)
        LIMIT 0,30) as f";
        $query = $db->createNativeQuery($sql_lv, $rsm);
        $stats_lv = $query->getOneOrNullResult();

        if (null === $user) {
            // Ici, l'utilisateur est anonyme ou l'URL n'est pas derrière un pare-feu
        } else {
            // Ici, $user est une instance de notre classe User
        }
        //var_dump($user->getRoles());
        if ( ! $this->container->get('security.context')->isGranted('ROLE_INTERSA_N1')) {
            //throw new AccessDeniedException('You do not have permission to use this resource!');
        } else {
        }

        $jours = array();
        $values_sdn = array();
        $values_sdo = array();
        $values_time = array();

        $days = 30;
        //CLEAR OUTPUT FOR USE
        $output = array();

        //SET CURRENT DATE
        $month = date("m");
        $day = date("d");
        $year = date("Y");
        $max_val = 0;
        $max_val_time = 0;
        //LOOP THROUGH DAYS
        for($i=0; $i<=$days; $i++){
            $found_sdn = false;
            $found_sdo = false;
            $found_time = false;
            $output[] = date('Y, m, d',mktime(0,0,0,$month-1,($day-$i),$year));
            foreach($stats_sdn as $s){
                if(date('dm',mktime(0,0,0,$month,($day-$i),$year)) == $s['curday'])
                {
                    $values_sdn[] = $s['nbre'];
                    $found_sdn = true;
                    if($max_val < $s['nbre']) {
                        $max_val = $s['nbre'];
                    }
                    break;
                }
            }
            if(!$found_sdn){
                $values_sdn[] = 0;
            }
            // Old donateur
            foreach($stats_sdo as $s){
                if(date('dm',mktime(0,0,0,$month,($day-$i),$year)) == $s['curday'])
                {
                    $values_sdo[] = $s['nbre'];
                    if($max_val < $s['nbre']) {
                        $max_val = $s['nbre'];
                    }
                    $found_sdo = true;
                    break;
                }
            }
            if(!$found_sdo){
                $values_sdo[] = 0;
            }
            foreach($stats_time as $s){
                if(date('dm',mktime(0,0,0,$month,($day-$i),$year)) == $s['curday'])
                {
                    $t = round($s['temps']/60,2);
                    $values_time[] = $t;
                    if($max_val_time < $t) {
                        $max_val_time = $t;
                    }
                    $found_time = true;
                    break;
                }
            }
            if(!$found_time){
                $values_time[] = 0;
            }


        }

        //RETURN DATE ARRAY
        $dates = array_reverse($output);
        $values_sdn = array_reverse($values_sdn);
        $values_sdo = array_reverse($values_sdo);
        $values_time = array_reverse($values_time);

        $data = array(
            'values_sdn'=>implode(',',$values_sdn),
            'values_sdo'=>implode(',',$values_sdo),
            'values_dc'=>$stats_dc['somme'],
            'values_dm'=>$stats_dm['somme'],
            'values_lg'=>$stats_lg['somme'],
            'values_lv'=>$stats_lv['somme'],
            'values_time'=>$values_time,
        );
        return $this->render('FulldonIntersaBundle:Intersa:gestion.html.twig',array(
            'dates'=>$dates,
            'data'=>$data,
            'max_val'=>$max_val,
            'max_val_time'=>$max_val_time
        ));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1")
     */
    public function prodAction()
    {

        $db = $this->getDoctrine()->getManager();
        $jours = array();
        $values_time = array();
        $output = array();
        $stats_time = array();
        //SET CURRENT DATE
        $month = date("m");
        $day = date("d");
        $year = date("Y");
        $memo_search = array();
        $repStat = $db->getRepository('FulldonIntersaBundle:Stat') ;
        $rsm = new ResultSetMapping();
        // mapping results to the message entity
        $rsm->addScalarResult( 'nbre', 'nbre');
        $rsm->addScalarResult( 'curday', 'curday');
        $rsm->addScalarResult( 'temps', 'temps');
        $days = 30;
        $request = $this->getRequest();

        if ($request->getMethod() == 'POST') {
            $date_debut = $request->get('date_debut');
            $memo_search['date_debut']= $date_debut;
            $date_fin = $request->get('date_fin');
            $memo_search['date_fin']= $date_fin;
            $username = $request->get('username');
            $memo_search['username']= $username;
            if(!empty($date_debut) && !empty($date_fin) && !empty($username)) {
                //request
                $date_debut = \DateTime::createFromFormat('d/m/Y', $date_debut);
                $date_fin = \DateTime::createFromFormat('d/m/Y', $date_fin);
                if(strtotime($date_debut->format('Y-m-d')) > strtotime($date_fin->format('Y-m-d')))
                {
                echo 'erreur';
                }
                $diff = $date_debut->diff($date_fin);

                $days = $diff->format('%a');

                $month =$date_fin->format('m');
                $day = $date_fin->format("d");
                $year =$date_fin->format("Y");

                $repUser = $db->getRepository('FulldonSecurityBundle:User') ;
                $user = $repUser->findOneBy(array('username'=>$username));
                $sql_time = "SELECT SUM(temps) as temps, DATE_FORMAT(created_at,'%d%m') as curday
                FROM temps_saisie
                WHERE user_id = ".$user->getId()." AND (DATE_FORMAT(created_at,'%Y-%m-%d') >= '".$date_debut->format('Y-m-d')."' AND DATE_FORMAT(created_at,'%Y-%m-%d') <= '".$date_fin->format('Y-m-d')."')
                GROUP BY curday
                LIMIT 0,30";
                $query = $db->createNativeQuery($sql_time, $rsm);
                $stats_time = $query->getResult();
                //echo $sql_time;die;



            } else {
                //display error
            }
        }





        $max_val_time = 1;
        //LOOP THROUGH DAYS
        for($i=0; $i<=$days; $i++){

            $found_time = false;
            $output[] = date('Y, m, d',mktime(0,0,0,$month-1,($day-$i),$year));
            foreach($stats_time as $s){
                if(date('dm',mktime(0,0,0,$month,($day-$i),$year)) == $s['curday'])
                {
                    $t = round($s['temps']/60, 2);
                    $values_time[] = $t;
                    if($max_val_time < $t) {
                        $max_val_time = $t;
                    }
                    $found_time = true;
                    break;
                }
            }
            if(!$found_time){
                $values_time[] = 0;
            }
        }

        //RETURN DATE ARRAY
        $dates = array_reverse($output);
        $values_time = array_reverse($values_time);

        $data = array(
            'values_time'=>$values_time,
        );
        $ids =  array( 'ROLE_INTERSA_N1', 'ROLE_INTERSA_N2', 'ROLE_INTERSA_N3');
        $repUsers = $db->getRepository('FulldonSecurityBundle:User') ;
        $users = $repUsers->createQueryBuilder('u')
            ->join('u.roles','r')
            ->where("r.role in (:role) ")
            ->setParameter('role' ,$ids)
            ->getQuery()->getResult();

        return $this->render('FulldonIntersaBundle:Intersa:stat_prod.html.twig',array(
            'dates'=>$dates,
            'data'=>$data,
            'max_val_time'=>$max_val_time,
            'memo_search'=>$memo_search,
            'users'=>$users
        ));
    }
    function validateDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public function faqAction() {
        return $this->render('FulldonIntersaBundle:Intersa:faq.html.twig');
    }


    public function getCausesAction() {
        $db = $this->getDoctrine()->getManager();
        $causeRep = $db->getRepository('FulldonDonateurBundle:Cause');//25/01/2015_COIGNIERES
        $result = array();
        $causes = $causeRep->findAll();
        foreach($causes as $cause ) {
            $result[] = array('id' => $cause->getId(), 'name' => $cause->getCode());
//            if ($cause->getCode() =='25/01/2015_COIGNIERES') {
//                
//                die('hererere');
//            }

        }
//        var_dump($result);
//        die('hounnn');
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }

    public function getCodeOccasionsAction() {
        $db = $this->getDoctrine()->getManager();
        $occasionRep = $db->getRepository('FulldonDonateurBundle:CodeOccasion');
        $result = array();
        $occasions = $occasionRep->findAll();
        foreach($occasions as $occasion ) {
            $result[] = array('id' => $occasion->getId(), 'name' => $occasion->getCode());

        }
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getCodeCampagnesAction() {
        $db = $this->getDoctrine()->getManager();
        $campagneRep = $db->getRepository('FulldonDonateurBundle:CodeCompagne');
        $result = array();
        $campages = $campagneRep->findAll();
        foreach($campages as $campage ) {
            $result[] = array('id' => $campage->getId(), 'name' => $campage->getCode());

        }
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
