<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\DonateurBundle\Form\DonateurSearchType;
use Fulldon\IntersaBundle\Form\IntersaDonateurType;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Fulldon\DonateurBundle\Entity\Ville;
use JMS\SecurityExtraBundle\Annotation\Secure;

class DoublonController extends Controller {

    const _BC_ = 'BC';
    const _CS_ = 'CS';
    const _ESPECES_ = 'ESPECES';
    const _PA_ = 'PA';
    const _VIREMENT_ = 'VIREMENT';

    public function indexAction($page) {
        $db = $this->getDoctrine()->getManager();
        $doublonRep = $db->getRepository('FulldonDonateurBundle:Doublon');
        $doublons = $doublonRep->findBy(array('done' => false));
        $total_doublons = count($doublons);
        $doublons_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_doublons / $doublons_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $doublons = $this->getDoctrine()
                        ->getRepository('FulldonDonateurBundle:Doublon')
                        ->createQueryBuilder('p')
                        ->where('p.done = false')
                        ->orderBy('p.createdAt', 'DESC')
                        ->setFirstResult(($page * $doublons_per_page) - $doublons_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        
        return $this->render('FulldonIntersaBundle:Doublon:index.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_doublons' => $total_doublons,
                    'doublons' => $doublons
        ));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function mergeAction() {

//        die('mergeAction');

        $db = $this->getDoctrine()->getManager();
        $pndRep = $db->getRepository('FulldonDonateurBundle:Pnd');
        $doublonRep = $db->getRepository('FulldonDonateurBundle:Doublon');
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donRep = $db->getRepository('FulldonDonateurBundle:Don');
        $request = $this->getRequest();
        $vdonateur1 = null;
        $vdonateur2 = null;
        $view_donateur1 = $request->get('vdonateur1');
        $view_donateur2 = $request->get('vdonateur2');
        $dons_1 = null;
        $dons_2 = null;
        if (!empty($view_donateur1) && !empty($view_donateur2)) {
            $vdonateur1 = $donateurRep->find($view_donateur1);
            $vdonateur2 = $donateurRep->find($view_donateur2);
            $dons_1 = $donRep->findBy(array('user' => $vdonateur1->getUser()));
            $dons_2 = $donRep->findBy(array('user' => $vdonateur2->getUser()));
        }

        if ($request->getMethod() == 'POST') {
            $is_doublon = $request->get('doublon');
//            if($is_doublon == 'false'){
//                die('enter');
//            }
//            else{
//                die('non enter');
//            }
//            var_dump($is_doublon);
//
//            die('post');
            $id_donateur1 = $request->get('donateur1');
//            var_dump('donateur1');
//            var_dump($id_donateur1);
            $id_donateur2 = $request->get('donateur2');
//            var_dump('donateur2');
//            var_dump($id_donateur2);
//            die('post');
            $save_adresse1 = $request->get('save_adresse1');
            $save_adresse2 = $request->get('save_adresse2');
            $save_adresse3 = $request->get('save_adresse3');
            $save_adresse4 = $request->get('save_adresse4');
            $save_zipcode = $request->get('save_zipcode');
            $save_ville = $request->get('save_ville');
            $save_civilite = $request->get('save_civilite');
            $save_email = $request->get('save_email');

//            var_dump($save_adresse1);
//            var_dump($save_adresse2);
//            var_dump($save_adresse3);
//            var_dump($save_adresse4);
//            var_dump($save_zipcode);
//            var_dump($save_ville);
//            var_dump($save_civilite);
//            var_dump($save_email);
//
//            die('hgg');

            if ($is_doublon == 'false') {

                $doublon = $doublonRep->findOneBy(array('donateur1' => $id_donateur1, 'donateur2' => $id_donateur2));
                $doublon->setDone(true);
                $db->persist($doublon);
                $db->flush($doublon);
                $this->get('session')->getFlashBag()->add('info', 'Les deux donateurs sont bien identifiés comme non doublons.');
                return $this->redirect($this->generateUrl('intersa_doublon', array('page' => 1)));
            }
            $donateur1 = $donateurRep->find($id_donateur1);
//            var_dump('$donateur1');
//            var_dump($donateur1);
//            die('post');
            $donateur2 = $donateurRep->find($id_donateur2);
//            var_dump('$donateur2');
//            var_dump($donateur2);
//            die('post');
//            if (is_null($donateur1) || is_null($donateur2)) {
//                die('if');
//            } else {
//                die('else');
//            }

            if (is_null($donateur1) || is_null($donateur2)) {
                $this->get('session')->getFlashBag()->add('warning', 'L\'opération ne peut pas aboutir car l\'un des donateurs ou bien les deux n\'existent pas.');
                return $this->redirect($this->generateUrl('intersa_doublon', array('page' => 1)));
            }

//            var_dump(isset($save_adresse1));
//            var_dump($donateur2->getAdresse1());
//            die('die begin');
            //Les informations du donateur
            if (isset($save_adresse1) || !empty($save_adresse1)) {
                if ($donateur1->getAdresse1() != null) {
                    $donateur1->setAdresse1($donateur2->getAdresse1());
                }
            }
            if (isset($save_adresse2) || !empty($save_adresse2)) {
                if ($donateur1->getAdresse2() != null) {
                    $donateur1->setAdresse2($donateur2->getAdresse2());
                }

//                die('die 2');
            }
            if (isset($save_adresse3) || !empty($save_adresse3)) {
                if ($donateur1->getAdresse3() != null) {
                    $donateur1->setAdresse3($donateur2->getAdresse3());
                }

//                die('die 3');
            }
            if (isset($save_adresse4) || !empty($save_adresse4)) {
                if ($donateur1->getAdresse4() != null) {
                    $donateur1->setAdresse4($donateur2->getAdresse4());
                }
            }
            if (isset($save_zipcode) || !empty($save_zipcode)) {
                if ($donateur1->getZipcode() != null) {
                    $donateur1->setZipcode($donateur2->getZipcode());
                }
            }
//            var_dump($donateur2);
//            die('houniii');
            if (isset($save_ville) || !empty($save_ville)) {
                if ($donateur1->getVille() != null) {
                    $donateur1->setVille($donateur2->getVille());
                }
            }
            if (isset($save_email) || !empty($save_email)) {
                // old email
                if ($donateur1->getEmail() != null) {
                    $donateur1->setEmail($donateur2->getEmail());
                }
//                if ($donateur2->getEmail() != null) {
//                    $donateur2->setEmail('old' . $donateur2->getEmail());
//                }
            }
            if (isset($save_civilite) || !empty($save_civilite)) {
                if ($donateur1->getCivilite() != null) {
                    $donateur1->setCivilite($donateur2->getCivilite());
                }
            }
//            var_dump('$donateur2->getAdresse3()');
//            var_dump($donateur2->getAdresse3());
//            
//            var_dump('$donateur1');
//            var_dump($donateur1);
            //Fin de traitement pour la migration des infos donateur.
            $dons_1 = $donRep->findBy(array('user' => $donateur1->getUser()));
            $dons_2 = $donRep->findBy(array('user' => $donateur2->getUser()));

            // Les dons
            foreach ($dons_2 as $don_2) {
//                var_dump($donateur2->getUser());
//                var_dump($donateur1->getUser());
//                die('die $dons_2');
                $don_2->setUser($donateur1->getUser());
                $db->persist($don_2);
                $db->flush();
            }

            // Les PND
            $pnds = $pndRep->findBy(array('donateur' => $donateur2));

            foreach ($pnds as $pnd) {
                $pnd->setDonateur($donateur1);
                $db->persist($pnd);
                $db->flush();
            }

            // doublon
            // Extraire tous les doublons sur les quels le donateur qu'on va supprimer représente la source à conserver
            $doublons = $doublonRep->findBy(array('donateur1' => $donateur2->getId()));
            foreach ($doublons as $doublon) {
                $doublon->setDonateur1($donateur1->getId());
                $db->persist($doublon);
                $db->flush();
            }


            $doublon = $doublonRep->findOneBy(array('donateur1' => $donateur1->getId(), 'donateur2' => $donateur2->getId()));
            if (is_object($doublon)) {
                $doublon->setDone(true);
                $db->persist($doublon);
                $db->flush($doublon);
            }

            //Fin de traitement des doublons.
            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg = $this->get('fulldon.intersa.global')->getAddMsgLog($donateur1, ' MERGE:DOUBLON: LE DONATEUR #' . $donateur1->getId() . ' A REMPLACE LE DONATEUR #' . $donateur2->getId() . '');
            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_RF_);
            // Log the user creation
            $event = HistoryLogEvent::constr1($current_user, $donateur1, $typeLog, $msg);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);
            //Fin Log
            $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_DOUBLON_MERGED_);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(StatVar::CREATE, $event);
            $this->get('session')->getFlashBag()->add('info', 'Les deux donateurs #' . $donateur1->getId() . '  #' . $donateur2->getId() . ' ont été mergés');
            // Désactiver le donateur
            if ($donateur2->getEmail() != null) {
                $old_email_donateur2 = 'old'.'.' . $donateur2->getEmail();
                $donateur2->setEmail($old_email_donateur2);
            }
            
           
            $donateur2->setRemoved(true);
            $db->persist($donateur2);
            $db->flush();
//            die('fin +');

            return $this->redirect($this->generateUrl('intersa_doublon', array('page' => 1)));
        }
        return $this->render('FulldonIntersaBundle:Doublon:merge.html.twig', array(
                    'vdonateur1' => $vdonateur1,
                    'vdonateur2' => $vdonateur2,
                    'dons1' => $dons_1,
                    'dons2' => $dons_2,
        ));
    }

    public function infosDonateurAction($did) {
        $db = $this->getDoctrine()->getManager();

        $repElement = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $repElement->find($did);
        return $this->render('FulldonIntersaBundle:Doublon:ajax/info_donateur.html.twig', array(
                    'donateur' => $donateur
        ));
        ;
    }

}
