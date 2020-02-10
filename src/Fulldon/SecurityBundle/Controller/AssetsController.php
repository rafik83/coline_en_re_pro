<?php

namespace Fulldon\SecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\DonateurBundle\Entity\Rf;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Fulldon\IntersaBundle\Vars;

class AssetsController extends Controller {

    public function imgAction($token = null) {
        //fetch asset record from the database -- which also contains its file path
        //check the TOKEN matches the one stored against this image
        //set content-type headers and serve the image
        $response = new Response(file_get_contents($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg/' . $token . '.png'), 200);
        $response->headers->set('Content-Type', 'image/png');
        return $response;
    }

    public function clientFileAction($id, $token = null) {
        $response = new Response(file_get_contents('/' . $this->container->getParameter('folder_app') . '/MARKETING/' . $id . '/' . $token), 200);
        $response->headers->set('Content-Type', 'text/csv');
        return $response;
    }

    public function getMandatAction($token = null) {
        $response = new Response(file_get_contents('/' . $this->container->getParameter('folder_app') . '/users/' . $token), 200);
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }

    public function getSignedMandatAction($token = null) {
        $response = new Response(file_get_contents('/' . $this->container->getParameter('folder_app') . '/signed_pdf/' . $token), 200);
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }

    public function duplicataAction($id, $token = null) {
//        die('duplicataAction + asset_duplicata + securitybundle');
//Log
        $db = $this->getDoctrine()->getManager();
        $current_user = $this->get('security.context')->getToken()->getUser();
        $rf = $db->getRepository('FulldonDonateurBundle:Rf')->findOneBy(array('id' => $id));
        $repAbo = $db->getRepository('FulldonDonateurBundle:Abonnement');
        $prelevementRep = $db->getRepository('FulldonDonateurBundle:Prelevement');
        $don = $rf->getDon();

        // Fin Log
        // Start Stat
        // Log the user creation
        $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_DUPLICATA_SENDED_);
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(StatVar::CREATE, $event);
        //Fin Stat
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->findOneBy(array('user' => $don->getUser()->getId()));
        $name = uniqid(mt_rand());

        $msg = 'Regénération d\'une copie duplicata du RF [' . $rf->getId() . ']';
        $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_DUPLICATA_ADD_);
        $role = $this->get('log.helper')->getRole($current_user);
        // Log the user creation
        $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, $don);
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(LogVar::CREATE, $event);
        try {
            if (empty($token)) {


                if ($don->getIspa()) {
                    $token = $name . '-' . date('Y') . '.pdf';
                    $type_dir = '/' . $this->container->getParameter('folder_app') . '/RF_PA/DUPLICATA/';
                    $curdate = $rf->getCreatedAt();
                    $curdate->sub(new \DateInterval('P1Y'));

                    $pr = $prelevementRep->createQueryBuilder('p')
                            ->select(' SUM(p.montant) as somme')
                            ->join('p.abo', 'a')
                            ->where('p.datePrelevement LIKE :date AND p.abo = :abo')
                            ->andwhere('p.rejet = 0')
                            ->setParameter('date', '%' . $curdate->format('Y') . '%')
                            ->setParameter('abo', $don->getAbonnement())
                            ->getQuery()
                            ->getOneOrNullResult();
                    $prelevements = $prelevementRep->createQueryBuilder('p')
                            ->join('p.abo', 'a')
                            ->where('p.datePrelevement LIKE :date')
                            ->andwhere('a.id = :id')
                            ->setParameter('id', $don->getAbonnement()->getId())
                            ->andwhere('p.rejet = 0')
                            ->setParameter('date', '%' . $curdate->format('Y') . '%')
                            ->getQuery()
                            ->getResult();



                    //Génération du duplicata
                    $html = $this->get('fulldon.intersa.rf_service')->getHtmlArray($don, $donateur, $rf, 'duplicata_rf_pa', $prelevements, $pr['somme']);
                    $this->get('knp_snappy.pdf')->generateFromHtml(
                            $this->get('templating')->render(
                                    'FulldonIntersaBundle:Rf:rf.html.twig', array(
                                'html' => $html
                                    )
                            ), $type_dir . '' . $token, array(), true
                    );
                } else {
                    $token = $name . '-' . date('d-m-Y') . '.pdf';
                    $type_dir = '/' . $this->container->getParameter('folder_app') . '/RF/DUPLICATA/';
                    $rf->setNomDuplicata($token);
                    $db->persist($rf);
                    $db->flush();
										
                    //Génération du duplicata
                    $html = $this->get('fulldon.intersa.rf_service')->getHtmlArray($don, $donateur, $rf, 'duplicata_rf', null, null);
                    $this->get('knp_snappy.pdf')->generateFromHtml(
                            $this->renderView(
                                    'FulldonIntersaBundle:Rf:rf.html.twig', array(
                                'html' => $html
                                    )
                            ), $type_dir . '' . $token, array(), true
                    );
                }
            } else {
                if ($don->getIspa())
                    $type_dir = '/' . $this->container->getParameter('folder_app') . '/RF_PA/DUPLICATA/';
                else
                    $type_dir = '/' . $this->container->getParameter('folder_app') . '/RF/DUPLICATA/';
            }

            $is_courrier = false;
            $is_email = false;
            $is_sms = false;
            //Traitement des modes de récéption
            $modes = $donateur->getReceptionMode();


            foreach ($modes as $mode) {
                switch ($mode->getCode()) {
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



            $persoRep = $db->getRepository('FulldonIntersaBundle:Personnalisation');
            $perso = $persoRep->find(1);
            $init['perso'] = $perso;
            $email = $donateur->getEmail();
            if ($email and ! empty($email)) {
                $html = $this->renderView('FulldonIntersaBundle:Email:rf.html.twig', array('don' => $don, 'rf' => $rf, 'donateur' => $donateur, 'init' => $init));
                $this->get('fulldon.intersa.email_servies')->sendRf($email, $html, $type_dir . '' . $token);
                $msg = 'Email envoyé :  duplicata du RF [' . $rf->getId() . ']';
                $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_EMAIL_SENT_);
                $role = $this->get('log.helper')->getRole($current_user);
                // Log the user creation
                $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, $don);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);
            }
        } catch (\ContextErrorException $e) {
            $this->get('session')->getFlashBag()->add('warning', "Un problème technique est survenu lors de la génération du duplicata ! Contactez l'équipe FULLDON ");
            return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $don->getId())));
        }
        $response = new Response(file_get_contents($type_dir . '' . $token), 200);
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }

    public function rfAction($id) {

        //Log
//        die('rf action');
        $db = $this->getDoctrine()->getManager();
        $persoRep = $db->getRepository('FulldonIntersaBundle:Personnalisation');
        $perso = $persoRep->find(1);
        $init['perso'] = $perso;
        $current_user = $this->get('security.context')->getToken()->getUser();
        $don = $db->getRepository('FulldonDonateurBundle:Don')->findOneBy(array('id' => $id));
//        var_dump($don);
//        die('$don by id');
//        var_dump($don->getIspa());
//        die('don');
        $prelevementRep = $db->getRepository('FulldonDonateurBundle:Prelevement');
//        var_dump($prelevementRep);
//        die('prevelemeent');



        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->findOneBy(array('user' => $don->getUser()->getId()));
        $name = uniqid(mt_rand());
//        var_dump($name);

        $rf = null;


        $toalt_don_pardonateur = 0;
        if ($don->getIspa()) {
//            die('$don->getIspa()');
//            $tt = $this->get('fulldon.intersa.global')->canIgenerateRf($don, $donateur, 3);
//            var_dump($tt);
//            die('tt');
            if ($this->get('fulldon.intersa.global')->canIgenerateRf($don, $donateur, 3)) {
                $rf = new Rf();
                $token = $name . '-' . date('Y') . '.pdf';
//                var_dump($token);
//                die('$token');
                $type_dir = '/' . $this->container->getParameter('folder_app') . '/RF_PA/ORIGIN/';
//                var_dump($type_dir);
//                die('$type_dir');
//                var_dump($rf->getCreatedAt());
//                die('$rf->getCreatedAt()');
                $curdate = clone $rf->getCreatedAt();
//                 var_dump($curdate);
//                die('$curdate + clone');
                $curdate->sub(new \DateInterval('P1Y'));
//                var_dump($curdate);
//                die('$curdate + dateintervalle');

                $pr = $prelevementRep->createQueryBuilder('p')
                        ->select(' SUM(p.montant) as somme')
                        ->join('p.abo', 'a')
                        ->where('p.datePrelevement LIKE :date AND p.abo = :abo')
                        ->andwhere('p.rejet = 0')
                        ->setParameter('date', '%' . $curdate->format('Y') . '%')
                        ->setParameter('abo', $don->getAbonnement())
                        ->getQuery()
                        ->getOneOrNullResult();
                $prelevements = $prelevementRep->createQueryBuilder('p')
                        ->join('p.abo', 'a')
                        ->where('p.datePrelevement LIKE :date')
                        ->andwhere('a.id = :id')
                        ->setParameter('id', $don->getAbonnement()->getId())
                        ->andwhere('p.rejet = 0')
                        ->setParameter('date', '%' . $curdate->format('Y') . '%')
                        ->getQuery()
                        ->getResult();


                // $pr['somme'] = 1200
                $toalt_don_pardonateur = $pr['somme'];

                $rf->setNom($token);
                $rf->setDon($don);
                $db->persist($rf);
                $db->flush();
                $html = $this->get('fulldon.intersa.rf_service')->getHtmlArray($don, $donateur, $rf, 'rf_pa', $prelevements, $pr['somme']);
//                var_dump($html);
//                die('$html');
                $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->get('templating')->render(
                                'FulldonIntersaBundle:Rf:rf.html.twig', array(
                            'html' => $html
                                )
                        ), $type_dir . '' . $token, array(), true
                );
            }
        } else {

//            die('else don not ispa');
            if ($this->get('fulldon.intersa.global')->canIgenerateRf($don, $donateur)) {
                $rf = new Rf();

                $token = $name . '-' . date('d-m-Y') . '.pdf';
                $rf->setNom($token);
                $rf->setDon($don);
                $db->persist($rf);
                $db->flush();
                $type_dir = '/' . $this->container->getParameter('folder_app') . '/RF/ORIGIN/';
                $html = $this->get('fulldon.intersa.rf_service')->getHtmlArray($don, $donateur, $rf, 'rf', $prelevements = null, $somme = null);
                //Génération du duplicata
                $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->renderView(
                                'FulldonIntersaBundle:Rf:rf.html.twig', array(
                            'html' => $html
                                )
                        ), $type_dir . '' . $token, array(), true
                );
            }
        }


//        die('apres if $don->getIspa()');
//        var_dump($rf);
//        die('rf');



        $is_courrier = false;
        $is_email = false;
        $is_sms = false;
        //Traitement des modes de récéption
        if (is_object($rf)) {
            $modes = $donateur->getReceptionMode();


            foreach ($modes as $mode) {

//                var_dump($mode->getCode());
//                die('switch');
                switch ($mode->getCode()) {
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
            $msg = 'Regénération d\'un RF [' . $rf->getId() . ']';

            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_RF_); // LogVar::_LOG_TYPE_INFO_RF_ = 3
//            var_dump($typeLog);
//            die('$typeLog');
            $role = $this->get('log.helper')->getRole($current_user);
//            var_dump($role);//Role Intersa
//            die('$role');
            $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, $don);
//            var_dump($event);
//            die('$event');
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);
//            var_dump($dispatcher->dispatch(LogVar::CREATE, $event));
//            die('dispatch');
            $email = $donateur->getEmail();
//            $email = 'turki@intersa.fr'; //'turki@intersa.fr'; rehahlia@intersa
            if ($email and ! empty($email)) {

//                var_dump($don->getModePaiement()->getLibelle());
                $modepayement = '';
                switch ($don->getModePaiement()->getLibelle()) {
                    case 'CB':
                        $modepayement = 'Carte Bancaire'; // ESPECE  Autre
                        break;
                    case 'Chèque':
                        $modepayement = 'Chèque';
                        break;
                    case 'Paypal':
                        $modepayement = 'Paypal';
                        break;
                    case 'Virement Bancaire':
                        $modepayement = 'Virement Bancaire';
                        break;
                    case 'ESPECE':
                        $modepayement = 'Espèce';
                        break;
                    case 'Autre':
                        $modepayement = 'Autre';
                        break;
                }
//                die('mode payement don');
//                var_dump($modepayement);
//                die('$toalt_don_pardonateur');

                if ($toalt_don_pardonateur === 0) {
                    $html = $this->renderView('FulldonIntersaBundle:Email:print_recu_fiscale_other_payement.html.twig', array('don' => $don, 'rf' => $rf, 'donateur' => $donateur, 'init' => $init, 'modepayement' => $modepayement));
                } else {
                    $html = $this->renderView('FulldonIntersaBundle:Email:print_recu_fiscale.html.twig', array('don' => $don, 'rf' => $rf, 'donateur' => $donateur, 'init' => $init, 'sumtotal' => $toalt_don_pardonateur));
                }

                $this->get('fulldon.intersa.email_servies')->sendRf($email, $html, $type_dir . '' . $token);
                $msg = 'Email envoyé : RF [' . $rf->getId() . ']';
                $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_EMAIL_SENT_);
                $role = $this->get('log.helper')->getRole($current_user);
                // Log the user creation
                $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, $don);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);
//                var_dump($dispatcher->dispatch(LogVar::CREATE, $event));
//                die('dispatche fin');
            }
            $response = new Response(file_get_contents($type_dir . '' . $token), 200);
            $response->headers->set('Content-Type', 'application/pdf');
//             var_dump($response);
//            die('$response');
            return $response;
        } else {
            $this->get('session')->getFlashBag()->add('warning', "Impossible de générer un reçu fiscal pour ce don : Le
             don n'est pas autorisé d'avoir un RF \n
             Veuillez vérifier les points suivants :\n
                                                    - La date.\n
                                                    - Le code d'activité.\n
                                                    - La fiche donateur.\n
                                                    - Et le montant minimum pour la génération du RF.\n");
            return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $don->getId())));
        }
    }

    public function showRfAction($id) {
        $db = $this->getDoctrine()->getManager();
        $rf = $db->getRepository('FulldonDonateurBundle:Rf')->findOneBy(array('id' => $id));
        $current_user = $this->get('security.context')->getToken()->getUser();
        if (is_object($rf) && $current_user->getId() == $rf->getDon()->getUser()->getId()) {
            $don = $rf->getDon();
            if ($don->getIspa())
                $type_dir = '/' . $this->container->getParameter('folder_app') . '/RF_PA/ORIGIN/';
            else
                $type_dir = '/' . $this->container->getParameter('folder_app') . '/RF/ORIGIN/';
            $token = $rf->getNom();
            $response = new Response(file_get_contents($type_dir . '' . $token), 200);
            $response->headers->set('Content-Type', 'application/pdf');
            return $response;
        } else {
            if ($current_user->getId() == $rf->getDon()->getUser()->getId())
                $this->get('session')->getFlashBag()->add('warning', "Le reçu fiscal demandé n'est pas disponible pour le moment");
            else
                $this->get('session')->getFlashBag()->add('warning', " Petit méchant ! ");
            return $this->redirect($this->generateUrl('donateur_rf', array('page' => 1)));
        }
    }

}
