<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Rf;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\HttpFoundation\Session\Session;
use Fulldon\IntersaBundle\Service\PDFMerger;
/**
 * @PreAuthorize(" hasRole('ROLE_INTERSA_N1')  ")
 */
class RfController extends Controller
{
    public function indexAction()
    {
        // Affichage de tous les lots .
        $db = $this->getDoctrine()->getManager();
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $saisies = $repSaisie->createQueryBuilder('s')
            ->where('s.done = true and s.rfDone = false and s.type != \'PA\' and s.verifDone = true')
            ->getQuery()
            ->getResult();
        return $this->render('FulldonIntersaBundle:Rf:index.html.twig', array('saisies'=>$saisies));
       // } else {
           // return $this->render('FulldonIntersaBundle:UpgradeAccess:info.html.twig');
        //}

    }
    public function saisieRfAction($lot)
    {
        // Affichage de tous les lots .
        $db = $this->getDoctrine()->getManager();
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $saisie = $repSaisie->findOneBy(array('lot' => $lot ));
        $saisie->setRfDone(true);
        $db->persist($saisie);
        $db->flush();
        // Log the user creation
        $current_user = $this->get('security.context')->getToken()->getUser();
        // Log the user creation
        $event = HistoryStatEvent::constr1($current_user,StatVar::_STAT_TYPE_LOT_GENERATED_);
        $dispatcher =$this->get('event_dispatcher');
        $dispatcher->dispatch( StatVar::CREATE  , $event);
        //Fin Stat
        return $this->render('FulldonIntersaBundle:Rf:ajax/generate.html.twig');
    }
    public function generateAction()
    {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $prelevementRep = $db->getRepository('FulldonDonateurBundle:Prelevement');

        $request = $this->getRequest();
        $all = array();
        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        $totalDon = 0;
        if ($request->getMethod() == 'POST') {
            $rf_pa = $request->get('rf_pa');
            if(isset($rf_pa) && !empty($rf_pa)) {
            $curdate = new \DateTime();
            $curdate->sub(new \DateInterval('P1Y'));
                $prelevements = $prelevementRep->createQueryBuilder('p')
                    ->select(' SUM(p.montant) as somme, a.id as abo_id ')
                    ->join('p.abo','a')
                    ->where('p.datePrelevement LIKE :date')
                    ->andwhere('p.rejet = 0')
                    ->setParameter('date', '%'.$curdate->format('Y').'%')
                    ->groupBy('p.abo')
                    ->getQuery()
                    ->getResult();

                return $this->render('FulldonIntersaBundle:Rf:generate_pa.html.twig', array('prelevements' => $prelevements));
            } else {
            $lots = $request->get('lots');
            if(!empty($lots) ) {
                foreach($lots as $lot) {
                    $all[$lot] = $repDon->findBy(array('lot'=>$lot));
                    $totalDon = $totalDon + count($all[$lot]);
                }
            } else {
                $this->get('session')->getFlashBag()->add('warning','Il fau impérativement sélectionner au moins un seul lot pour le traitement');
                return $this->redirect($this->generateUrl('intersa_rf'));
            }


            return $this->render('FulldonIntersaBundle:Rf:generate.html.twig', array('total_lot' => count($lots),'all'=>$all,'total_don'=>$totalDon));
            }
        }
    }
    public function generateRfAction($id,$last=false)
    {
        $session = new Session();
        $session = $this->getRequest()->getSession();
//        $files = $session->get('rfs');
//        if($files == null) {
//            $files = array();
//        }
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $don = $repDon->find($id);
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur') ;
        $donateur = $donateurRep->findOneBy(array('user'=>$don->getUser()->getId()));

        // On génére le reçu fiscal ?
        if($this->get('fulldon.intersa.global')->canIgenerateRf($don, $donateur)) {
            $rf = new Rf();
            $name = uniqid(mt_rand());

            $is_courrier = false;
            $is_email = false;
            $is_sms = false;
            //Traitement des modes de récéption
            $modes = $donateur->getReceptionMode();
            foreach($modes as $mode) {
                switch($mode->getCode())
                {
                    case Vars\DonVars::_COM_COURRIER_ :
                        $is_courrier = true;
                        break;
                    case Vars\DonVars::_COM_EMAIL_:
                        $is_email = true;
                        break;
                    case Vars\DonVars::_COM_SMS_:
                        $is_sms = true;
                        break;
                }
            }

            $fileName = $name.'-'.date('d-m-Y').'.pdf';

            $file_origin =  '/'.$this->container->getParameter('folder_app').'//RF/ORIGIN/'.$fileName;
            $file_duplicata =  '/'.$this->container->getParameter('folder_app').'//RF/DUPLICATA/'.$fileName;
//            if(!in_array($file_duplicata,$files)) {
//                $files[] = $file_duplicata;
//            }
//            $session->set('rfs', $files );
//

            $rf->setNom($fileName);
            $rf->setNomDuplicata($fileName);
            $rf->setDon($don);
            $db->persist($rf);
            $db->persist($don);
            $db->flush();
            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg =$this->get('fulldon.intersa.global')->getAddMsgLog($rf,'RF');
            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_RF_);
            // Log the user creation
            $event = HistoryLogEvent::constr1($current_user,$db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user'=>$don->getUser())),$typeLog,$msg);
            $dispatcher =$this->get('event_dispatcher');
            $dispatcher->dispatch( LogVar::CREATE  , $event);
            //Fin Log

        $html = $this->get('fulldon.intersa.rf_service')->getHtmlArray( $don, $donateur,$rf,  'rf_duplicata', null, null);
        //Génération du duplicata
        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView(
                'FulldonIntersaBundle:Rf:rf.html.twig',
                array(
                    'html'  => $html
                )
            ),
            $file_duplicata,
            array(),
            true
        );
        //Génération du de l'original.
            $html = $this->get('fulldon.intersa.rf_service')->getHtmlArray( $don, $donateur,$rf,  'rf', null, null);
        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView(
                'FulldonIntersaBundle:Rf:rf.html.twig',
                array(
                    'html'  => $html
                )
            ),
            $file_origin,
            array(),
            true
        );
            $email = $donateur->getEmail();
            if($is_email and !empty($email)) {
                $html = $this->renderView( 'FulldonIntersaBundle:Email:rf.html.twig', array('don' => $don,'rf'=>$rf));
                $this->get('fulldon.intersa.email_servies')->sendRf($email,$html,$file);

        }
//            var_dump($files);
//            if($last == true) {
//                var_dump($files);
//                $pdf = new PDFMerger;
//                foreach ($files as $f) {
//                    $pdf->addPDF($f, 'all');
//                }
//                @$pdf->merge('file', '/RF_PA/TOSEND/'.date('Y').'TEST2.pdf');
//                $session->set('rfs', null );
//            }

        }
        //Vérification de toutes les options
        //Génerer le Rf
        return $this->render('FulldonIntersaBundle:Rf:ajax/generate.html.twig');


    }

}
