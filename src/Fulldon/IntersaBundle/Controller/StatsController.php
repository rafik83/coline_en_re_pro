<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @PreAuthorize(" hasRole('ROLE_INTERSA_N1') or hasRole('ROLE_ASSOC_N1') ")
 */
class StatsController extends Controller
{


    public function indexAction()
    {
        $db = $this->getDoctrine()->getManager();
        $repEntite = $db->getRepository('FulldonIntersaBundle:Entity');
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $repOccasion = $db->getRepository('FulldonDonateurBundle:CodeOccasion');
        $repType = $db->getRepository('FulldonIntersaBundle:TypeLog');
        $causes = $repCause->findAll();
        $entities = $repEntite->findAll();
        $occasions = $repOccasion->findAll();
        $types = $repType->findAll();


        return $this->render('FulldonIntersaBundle:Stats:index.html.twig',array(
            'causes' => $causes,
            'entities' => $entities,
            'occasions' => $occasions,
            'types' => $types
        ));
    }

    public function globalAction()
    {
        $request = $this->getRequest();
        $db = $this->getDoctrine()->getManager();

        $data = array();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('code_entity');
            $date_debut = $request->get('date_stat_debut');
            $date_fin = $request->get('date_stat_fin');
            if(!empty($date_debut) && !empty($date_fin) && $this->validateDate($date_debut) && $this->validateDate($date_fin)) {
                $date_debut =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_debut.' 00:00:00')->format('Y-m-d H:i:s');
                $date_fin =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_fin.' 23:59:59')->format('Y-m-d H:i:s');
                $data =  $this->get('fulldon.intersa.global')->getAdvancedGlobalStats($date_debut,$date_fin,$entity);
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'Veuillez remplir une marge de date valide .');
                return $this->redirect($this->generateUrl('intersa_advanced_stats'));
            }

        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }

        return $this->render('FulldonIntersaBundle:Stats:global.html.twig',array(
            'data'=>$data,
            'entity'=>$entity,
            'date_debut'=>$date_debut,
            'date_fin'=>$date_fin
        ));
    }
    public function billAction()
    {
        $request = $this->getRequest();
        $db = $this->getDoctrine()->getManager();
        $data = array();
        if($request->getMethod() == 'POST') {

            $date_debut = $request->get('bill_date_stat_debut');
            $date_fin = $request->get('bill_date_stat_fin');
            if(!empty($date_debut) && !empty($date_fin) && $this->validateDate($date_debut) && $this->validateDate($date_fin)) {
                $date_debut =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_debut.' 00:00:00')->format('Y-m-d H:i:s');
                $date_fin =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_fin.' 23:59:59')->format('Y-m-d H:i:s');
                $data =  $this->get('fulldon.intersa.global')->getAdvancedBillStats($date_debut,$date_fin);
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'Veuillez remplir une marge de date valide .');
                return $this->redirect($this->generateUrl('intersa_advanced_stats'));
            }

        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }

        return $this->render('FulldonIntersaBundle:Stats:bill.html.twig',array(
            'data'=>$data,
            'date_debut'=>$date_debut,
            'date_fin'=>$date_fin
        ));
    }
    public function operationAction()
    {
        $request = $this->getRequest();
        $db = $this->getDoctrine()->getManager();

        $data = array();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('code_entity');
            $code_occasion = $request->get('code_occasion');

            $data =  $this->get('fulldon.intersa.global')->getAdvancedOperationStats($code_occasion,$entity);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }

        return $this->render('FulldonIntersaBundle:Stats:operation.html.twig',array(
            'data'=>$data,
            'entity'=>$entity,
            'occasion'=>$code_occasion,
        ));
    }

    public function attritionAction()
    {
        $request = $this->getRequest();
        $db = $this->getDoctrine()->getManager();

        $data = array();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('code_entity');
            $code_occasion = $request->get('code_occasion');

            $data =  $this->get('fulldon.intersa.global')->getAdvancedAttritionStats($code_occasion,$entity);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }

        return $this->render('FulldonIntersaBundle:Stats:attrition.html.twig',array(
            'data'=>$data,
            'entity'=>$entity,
            'occasion'=>$code_occasion,
        ));
    }

    function getOperationPdfAction()
    {

        $filename = md5(time());
        $request = $this->getRequest();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('pdf_entity');
            $code_occasion = $request->get('pdf_occasion');

            $data =  $this->get('fulldon.intersa.global')->getAdvancedOperationStats($code_occasion,$entity);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }
        //Génération du duplicata
        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView(
                'FulldonIntersaBundle:Stats:pdf/operation.html.twig',
                array(
                    'data'=>$data,
                    'entity'=>$entity,
                    'occasion'=>$code_occasion,
                )
            ),
            '/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf',
            array(),
            true
        );
        $response = new Response(file_get_contents('/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf'), 200);
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }

    function getOperationExcelAction()
    {
        $request = $this->getRequest();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('excel_entity');
            $code_occasion = $request->get('excel_occasion');

            $data =  $this->get('fulldon.intersa.global')->getAdvancedOperationStats($code_occasion,$entity);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }
        // ask the service for a Excel5
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
            ->setLastModifiedBy("FulldonV2.0")
            ->setTitle("Statistiques globales par entité")
            ->setSubject("Edition du ".date('d/m/Y'));
        $header = array(
                0=>'CODE ACTIVITE',
                1=>'CIBLE',
                2=>'NB ENVOIS',
                3=>'NB DONS',
                4=> 'TX DE RDT',
                5=> 'MONTANT COLLECTE',
                6=> 'DON MOYEN',
                7=> 'Pianiste',
                8=> 'Hôte',
                9=> 'Date',
                10=> 'Note moyenne',
                11=> 'Note moyenne (comptage de 10)'

        );

        $col_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N');


        $phpExcelObject->setActiveSheetIndex(0);
        $phpExcelObject->getActiveSheet()->setCellValue('A1', 'STATISTIQUES PAR ENTITE ('.$entity.') et par opération ('.$code_occasion.')');
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));


        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
        $phpExcelObject->getActiveSheet()->mergeCells('A1:G1');

        $i = 2;

        foreach($header as $key => $value) {

            $phpExcelObject->getActiveSheet()->getStyle($col_array[$key].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$key].$i)->getFont()->setSize(12);
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$key].$i,$value);
            $phpExcelObject->getActiveSheet()->getColumnDimension($col_array[$key])->setWidth(25);
        }
        $i++;
        $total_pros = 0;
        $total_comptage = 0;
        $total_cumul = 0;
        $total_moyenne = 0;
        foreach($data['result'] as $obj) {

            $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,$obj['code']);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('B'.$i,$obj['cible']);
            $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setSize(12);


            $phpExcelObject->getActiveSheet()->setCellValue('C'.$i,$obj['pros']);
            $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('D'.$i,$obj['comptage']);
            $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setSize(12);
            if($obj['pros'] != 0) {
                $moyenne = round($obj['comptage']/$obj['pros']);
            }
            else {
                $moyenne = 0;
            }

            $phpExcelObject->getActiveSheet()->setCellValue('E'.$i,$moyenne);
            $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('F'.$i,$obj['cumul']);
            $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('G'.$i,round($obj['moyenne']));
            $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('H'.$i,$this->get('fulldon.notes_servies')->getPianiste($obj['code']));
            $phpExcelObject->getActiveSheet()->getStyle('H'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('H'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('I'.$i,$this->get('fulldon.notes_servies')->getHote($obj['code']));
            $phpExcelObject->getActiveSheet()->getStyle('I'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('I'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('J'.$i,$this->get('fulldon.notes_servies')->getDate($obj['code']));
            $phpExcelObject->getActiveSheet()->getStyle('J'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('J'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('K'.$i,$this->get('fulldon.notes_servies')->getMoyenne($obj['code']));
            $phpExcelObject->getActiveSheet()->getStyle('K'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('K'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('L'.$i,$this->get('fulldon.notes_servies')->getMoyenneWithTen($obj['code']));
            $phpExcelObject->getActiveSheet()->getStyle('L'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('L'.$i)->getFont()->setSize(12);

            $total_pros = $total_pros +$obj['pros'] ;
            $total_comptage = $total_comptage + $obj['comptage'] ;
            $total_cumul = $total_cumul + $obj['cumul'] ;
            $total_moyenne = $total_moyenne + round($obj['moyenne']) ;

            $i++;
        }
        // Total
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i.':G'.$i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => 'cccccc')
        ));
        $phpExcelObject->getActiveSheet()->mergeCells('A'.$i.':B'.$i);
        $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,'Total : ');
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('C'.$i,$total_pros);
        $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('D'.$i,$total_comptage);
        $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('E'.$i,'');
        $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('F'.$i,$total_cumul);
        $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('G'.$i,round($total_moyenne/count($data['result'])));
        $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setSize(12);

        // Fin total
        $i++;

        $phpExcelObject->getActiveSheet()->setTitle('Simple');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=operation-'.date('d-m-Y-H-i-s').'.xlsx');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }
    // Attrition
    function getAttritionPdfAction()
    {

        $filename = md5(time());
        $request = $this->getRequest();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('pdf_entity');
            $code_occasion = $request->get('pdf_occasion');

            $data =  $this->get('fulldon.intersa.global')->getAdvancedAttritionStats($code_occasion,$entity);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }
        //Génération du duplicata
        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView(
                'FulldonIntersaBundle:Stats:pdf/attrition.html.twig',
                array(
                    'data'=>$data,
                    'entity'=>$entity,
                    'occasion'=>$code_occasion,
                )
            ),
            '/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf',
            array(),
            true
        );
        $response = new Response(file_get_contents('/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf'), 200);
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }

    function getAttritionExcelAction()
    {
        $request = $this->getRequest();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('excel_entity');
            $code_occasion = $request->get('excel_occasion');

            $data =  $this->get('fulldon.intersa.global')->getAdvancedAttritionStats($code_occasion,$entity);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }
        // ask the service for a Excel5
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
            ->setLastModifiedBy("FulldonV2.0")
            ->setTitle("Statistiques globales par entité")
            ->setSubject("Edition du ".date('d/m/Y'));
        $header = array(
            0=>'CODE ACTIVITE',
            1=>'CIBLE',
            2=>'NB ENVOIS',
            3=>'NB A VIE=0',
            4=>'%',
            5=>'NB A VIE<=5',
            6=>'%',
            7=>'NB A VIE<=11',
            8=>'%',

        );

        $col_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M');


        $phpExcelObject->setActiveSheetIndex(0);
        $phpExcelObject->getActiveSheet()->setCellValue('A1', 'STATISTIQUES D\'ATTRITION PAR ENTITE ('.$entity.') et par opération ('.$code_occasion.')');
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));


        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
        $phpExcelObject->getActiveSheet()->mergeCells('A1:I1');

        $i = 2;

        foreach($header as $key => $value) {

            $phpExcelObject->getActiveSheet()->getStyle($col_array[$key].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$key].$i)->getFont()->setSize(12);
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$key].$i,$value);
            $phpExcelObject->getActiveSheet()->getColumnDimension($col_array[$key])->setWidth(25);
        }
        $i++;

        foreach($data['result'] as $obj) {

            $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,$obj['code']);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('B'.$i,$obj['cible']);
            $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('C'.$i,$obj['pros']);
            $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('D'.$i,$obj['nb0']);
            $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('E'.$i,round(($obj['nb0']/$obj['pros'])*100, 2));
            $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('F'.$i,$obj['nb5']);
            $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('G'.$i,round(($obj['nb5']/$obj['pros'])*100, 2));
            $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('H'.$i,$obj['nb11']);
            $phpExcelObject->getActiveSheet()->getStyle('H'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('H'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('I'.$i,round(($obj['nb11']/$obj['pros'])*100, 2));
            $phpExcelObject->getActiveSheet()->getStyle('I'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('I'.$i)->getFont()->setSize(12);

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
        $response->headers->set('Content-Disposition', 'attachment;filename=attrition-'.date('d-m-Y-H-i-s').'.xlsx');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }
    function getGlobalPdfAction()
    {

        $filename = md5(time());
        $request = $this->getRequest();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('pdf_entity');
            $date_debut = $request->get('pdf_date_debut');
            $date_fin = $request->get('pdf_date_fin');

            $date_debut =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_debut.' 00:00:00')->format('Y-m-d H:i:s');
            $date_fin =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_fin.' 23:59:59')->format('Y-m-d H:i:s');
            $data =  $this->get('fulldon.intersa.global')->getAdvancedGlobalStats($date_debut,$date_fin,$entity);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }
        //Génération du duplicata
        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView(
                'FulldonIntersaBundle:Stats:pdf/global.html.twig',
                array(
                    'data'  => $data,
                    'entity'=>$entity,
                    'date_debut'=>$date_debut,
                    'date_fin'=>$date_fin
                )
            ),
            '/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf',
            array(),
            true
        );
        $response = new Response(file_get_contents('/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf'), 200);
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }

    function getGlobalExcelAction()
    {
        $request = $this->getRequest();
        if($request->getMethod() == 'POST') {

            $entity = $request->get('excel_entity');
            $date_debut = $request->get('excel_date_debut');
            $date_fin = $request->get('excel_date_fin');

            $date_debut =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_debut.' 00:00:00')->format('Y-m-d H:i:s');
            $date_fin =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_fin.' 23:59:59')->format('Y-m-d H:i:s');
            $data =  $this->get('fulldon.intersa.global')->getAdvancedGlobalStats($date_debut,$date_fin,$entity);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }
        // ask the service for a Excel5
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
            ->setLastModifiedBy("FulldonV2.0")
            ->setTitle("Statistiques globales par entité")
            ->setSubject("Edition du ".date('d/m/Y'));
        $header = array(
            0=>'CODE OCCASION',
            1=>'CIBLE',
            2=>'NB ENVOIS',
            3=>'NB DONS',
            4=> 'TX DE RDT',
            5=> 'MONTANT COLLECTE',
            6=> 'DON MOYEN'

        );

        $col_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M');


        $phpExcelObject->setActiveSheetIndex(0);
        $phpExcelObject->getActiveSheet()->setCellValue('A1', 'STATISTIQUES GLOBALES PAR ENTITE ('.$entity.')');
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));


        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
        $phpExcelObject->getActiveSheet()->mergeCells('A1:G1');

        $i = 2;

        foreach($header as $key => $value) {

            $phpExcelObject->getActiveSheet()->getStyle($col_array[$key].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$key].$i)->getFont()->setSize(12);
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$key].$i,$value);
            $phpExcelObject->getActiveSheet()->getColumnDimension($col_array[$key])->setWidth(25);
        }
        $i++;
        $total_pros = 0;
        $total_comptage = 0;
        $total_cumul = 0;
        $total_moyenne = 0;
        foreach($data['result'] as $obj) {

            $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,$obj['code']);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('B'.$i,$obj['cible']);
            $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('C'.$i,$obj['pros']);
            $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('D'.$i,$obj['comptage']);
            $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setSize(12);
            if($obj['pros'] != 0) {
                $moyenne = round($obj['comptage']/$obj['pros']);
            }
            else {
                $moyenne = 0;
            }

            $phpExcelObject->getActiveSheet()->setCellValue('E'.$i,$moyenne);
            $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('F'.$i,$obj['cumul']);
            $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('G'.$i,round($obj['moyenne']));
            $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setSize(12);

            $total_pros = $total_pros +$obj['pros'] ;
            $total_comptage = $total_comptage + $obj['comptage'] ;
            $total_cumul = $total_cumul + $obj['cumul'] ;
            $total_moyenne = $total_moyenne + round($obj['moyenne']) ;
            $i++;
        }
        // Total
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i.':G'.$i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => 'cccccc')
        ));
        $phpExcelObject->getActiveSheet()->mergeCells('A'.$i.':B'.$i);
        $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,'Total : ');
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('C'.$i,$total_pros);
        $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('D'.$i,$total_comptage);
        $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('E'.$i,'');
        $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('F'.$i,$total_cumul);
        $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('G'.$i,round($total_moyenne/count($data['result'])));
        $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setSize(12);

        // Fin total
        $i++;

        $phpExcelObject->getActiveSheet()->setTitle('Simple');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=global-'.date('d-m-Y-H-i-s').'.xlsx');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }
    function getBillPdfAction()
    {

        $filename = md5(time());
        $request = $this->getRequest();
        if($request->getMethod() == 'POST') {
            $date_debut = $request->get('pdf_date_debut');
            $date_fin = $request->get('pdf_date_fin');
            $date_debut =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_debut.' 00:00:00')->format('Y-m-d H:i:s');
            $date_fin =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_fin.' 23:59:59')->format('Y-m-d H:i:s');
            $data = $this->get('fulldon.intersa.global')->getAdvancedBillStats($date_debut,$date_fin);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }
        //Génération du duplicata
        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView(
                'FulldonIntersaBundle:Stats:pdf/bill.html.twig',
                array(
                    'data'  => $data,
                    'date_debut'=>$date_debut,
                    'date_fin'=>$date_fin
                )
            ),
            '/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf',
            array(),
            true
        );
        $response = new Response(file_get_contents('/'.$this->container->getParameter('folder_app').'/global_stats/'.$filename.'.pdf'), 200);
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }

    function getBillExcelAction()
    {
        $request = $this->getRequest();
        if($request->getMethod() == 'POST') {

            $date_debut = $request->get('excel_date_debut');
            $date_fin = $request->get('excel_date_fin');

            $date_debut =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_debut.' 00:00:00')->format('Y-m-d H:i:s');
            $date_fin =  \DateTime::createFromFormat ( 'd/m/Y H:i:s',$date_fin.' 23:59:59')->format('Y-m-d H:i:s');
            $data = $this->get('fulldon.intersa.global')->getAdvancedBillStats($date_debut,$date_fin);
        } else {
            return $this->redirect($this->generateUrl('intersa_advanced_stats'));
        }
        // ask the service for a Excel5
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
            ->setLastModifiedBy("FulldonV2.0")
            ->setTitle("Statistiques")
            ->setSubject("Edition du ".date('d/m/Y'));
        $header = array(
            0=>'Jour',
            1=>'Nombre de nouveaux donateurs',
            2=>'Nombre dons ponctuels ',
            3=>'Nombre des prélevements',
            4=> 'Nombre des dons CS',
            5=> 'Nombre des dons BC',
            6=> 'Nombre des dons espece',
            7=> 'Nombre des topages',
            8=> 'Nombre des emails',
            9=> 'Nombre des duplicatas',
            10=> 'Nombre des reçus fiscaux',
            11=> 'Nombre des mise à jours donateurs',
            12 => 'Nombre des mise à jours dons'
        );

        $col_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M');


        $phpExcelObject->setActiveSheetIndex(0);
        $phpExcelObject->getActiveSheet()->setCellValue('A1', 'Facture de production ');
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));


        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
        $phpExcelObject->getActiveSheet()->mergeCells('A1:M1');

        $i = 2;

        foreach($header as $key => $value) {

            $phpExcelObject->getActiveSheet()->getStyle($col_array[$key].$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$key].$i)->getFont()->setSize(12);
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$key].$i,$value);
            $phpExcelObject->getActiveSheet()->getColumnDimension($col_array[$key])->setWidth(25);
        }

        $i++;
        $total_new_donateur = 0;
        $total_dons_ponc = 0;
        $total_dons_pa = 0;
        $total_dons_cs = 0;
        $total_dons_bc = 0;
        $total_dons_espece = 0;
        $total_topages = 0;
        $total_emails = 0;
        $total_duplicatas = 0;
        $total_rf = 0;
        $total_maj_donateur = 0;
        $total_maj_dons = 0;

        foreach($data['result'] as $obj) {

            $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,$obj['created_at']);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('B'.$i,$obj['nb_new_donateur']);
            $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('C'.$i,$obj['nb_don_ponctuel']);
            $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('D'.$i,$obj['nb_don_prelevement']);
            $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('E'.$i,$obj['nb_cs']);
            $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('F'.$i,$obj['nb_bc']);
            $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('G'.$i,$obj['nb_espece']);
            $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('H'.$i,$obj['nb_topage']);
            $phpExcelObject->getActiveSheet()->getStyle('H'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('H'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('I'.$i,$obj['nb_email']);
            $phpExcelObject->getActiveSheet()->getStyle('I'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('I'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('J'.$i,$obj['nb_duplicata']);
            $phpExcelObject->getActiveSheet()->getStyle('J'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('J'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('K'.$i,$obj['nb_rf']);
            $phpExcelObject->getActiveSheet()->getStyle('K'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('K'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('L'.$i,$obj['maj_donateur']);
            $phpExcelObject->getActiveSheet()->getStyle('L'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('L'.$i)->getFont()->setSize(12);

            $phpExcelObject->getActiveSheet()->setCellValue('M'.$i,$obj['maj_don']);
            $phpExcelObject->getActiveSheet()->getStyle('M'.$i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('M'.$i)->getFont()->setSize(12);


            $total_new_donateur = $total_new_donateur + $obj['nb_new_donateur'];
            $total_dons_ponc = $total_dons_ponc + $obj['nb_don_ponctuel'] ;
            $total_dons_pa = $total_dons_pa + $obj['nb_don_prelevement'] ;
            $total_dons_cs = $total_dons_cs + $obj['nb_cs'];
            $total_dons_bc = $total_dons_bc + $obj['nb_bc'];
            $total_dons_espece = $total_dons_espece + $obj['nb_espece'];
            $total_topages = $total_topages + $obj['nb_topage'] ;
            $total_emails = $total_emails + $obj['nb_email'] ;
            $total_duplicatas = $total_duplicatas + $obj['nb_duplicata'];
            $total_rf = $total_rf + $obj['nb_rf'] ;
            $total_maj_donateur = $total_maj_donateur + $obj['maj_donateur'];
            $total_maj_dons = $total_maj_dons + $obj['maj_don'];

            $i++;
        }
        // Total
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i.':L'.$i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => 'cccccc')
        ));

        $phpExcelObject->getActiveSheet()->setCellValue('A'.$i,'Total : ');
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('B'.$i,$total_new_donateur);
        $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('B'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('C'.$i,$total_dons_ponc);
        $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('C'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('D'.$i,$total_dons_pa);
        $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('D'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('E'.$i,$total_dons_cs);
        $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('E'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('F'.$i,$total_dons_bc);
        $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('F'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('G'.$i,$total_dons_espece);
        $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('G'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('H'.$i,$total_topages);
        $phpExcelObject->getActiveSheet()->getStyle('H'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('H'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('I'.$i,$total_emails);
        $phpExcelObject->getActiveSheet()->getStyle('I'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('I'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('J'.$i,$total_duplicatas);
        $phpExcelObject->getActiveSheet()->getStyle('J'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('J'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('K'.$i,$total_rf);
        $phpExcelObject->getActiveSheet()->getStyle('K'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('K'.$i)->getFont()->setSize(12);

        $phpExcelObject->getActiveSheet()->setCellValue('L'.$i,$total_maj_donateur);
        $phpExcelObject->getActiveSheet()->getStyle('L'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('L'.$i)->getFont()->setSize(12);


        $phpExcelObject->getActiveSheet()->setCellValue('M'.$i,$total_maj_dons);
        $phpExcelObject->getActiveSheet()->getStyle('M'.$i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('M'.$i)->getFont()->setSize(12);

        // Fin total
        $i++;

        $phpExcelObject->getActiveSheet()->setTitle('Simple');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=global-'.date('d-m-Y-H-i-s').'.xlsx');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }
    public function getGlobalStatsAction()
    {
        $data =  $this->get('fulldon.intersa.global')->getGlobalStats();
        return $this->render('FulldonIntersaBundle:Intersa:accueil.html.twig',array(
            'data'=>$data
        ));
    }
    public function getStatNewDonateurAction()
    {
        $data =  $this->get('fulldon.intersa.global')->getStatNewDonateur();
        return $this->render('FulldonIntersaBundle:Intersa:accueil.html.twig',array(
            'data'=>$data
        ));
    }

    function validateDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}
