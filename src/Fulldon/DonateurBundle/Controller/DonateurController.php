<?php
namespace Fulldon\DonateurBundle\Controller;
use Fulldon\DonateurBundle\Entity\Abonnement;
use Fulldon\DonateurBundle\Entity\Transaction;
use Fulldon\DonateurBundle\Entity\Cheque;
use Fulldon\DonateurBundle\Entity\Virement;
use Fulldon\DonateurBundle\Entity\StatutPaiement;
use Fulldon\DonateurBundle\Form\DonPonctuelType;
use Fulldon\DonateurBundle\Form\VirementType;
use Fulldon\DonateurBundle\Form\DonRegulierType;
use Fulldon\DonateurBundle\Form\DonType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\IntersaBundle\Entity\TypeDon;

#payment
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use Symfony\Component\Validator\Constraints\DateTime;

use Hip\MandrillBundle\Message;
use Hip\MandrillBundle\Dispatcher;

class DonateurController extends Controller{
    const _PAYPAL_ = 'paypal';
    const _CHEQUE_= 'cheque';
    const _VIREMENT_ = 'virement';
    const _CB_ = 'cb';
    const _STATUT_ATTENTE_ = 1;
    const _STATUT_TRAITEMENT_PAIEMENT_ = 2;
    const _STATUT_DON_VALIDE_ = 3;
    const _STATUT_DON_ANNULE_ = 4;
    private $init = array();

    public function preExecute() {

        if($this->container->getParameter('donor_space') == 0) {
//            die('Espace donateur non disponible !');
        }

        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
        $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);
        $data = $this->getDoctrine()
            ->getRepository('FulldonDonateurBundle:Rf')
            ->createQueryBuilder('r')
            ->join('r.don','d')
            ->join('d.transaction','t')
            ->join('t.statut','s')
            ->select('count(d.id) as cpt')
            ->where('d.user = :id and r.don is not null and t.statut =:statut and r.sended = 0')
            ->setParameter('id', $user->getId())
            ->setParameter('statut', $statut )
            ->getQuery()->getSingleResult();
        $persoRep = $db->getRepository('FulldonIntersaBundle:Personnalisation') ;
        $perso = $persoRep->find(1);

        $this->init['data']= $data;
        $this->init['perso']= $perso;
    }

    function donPonctuelAction() {
        $don = new Don;
        $user = $this->get('security.context')->getToken()->getUser();
        $don->setUser($user);
        $db = $this->getDoctrine()->getManager();
        // format all the form

        $form = $this->createForm(new DonPonctuelType($this->get('request_stack')), $don, array(
            'cascade_validation' => true));
        $errors = array();
        // On récupère la requête
        $request = $this->get('request_stack')->getCurrentRequest();

        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire

            $form->bind($request);

            // Vérification du montant après la réception de la requête;
            $radioMontant = $request->get('full_montant');
            $currentMontant = $don->getMontant();
            if( !empty($radioMontant)) {
                if($radioMontant == 'other') {
                    if(empty($currentMontant)) {
                        $errors['error_montant'] = 'Le montant est obligatoire !' ;
                    }
                } else {
                    $don->setMontant($radioMontant);
                }
            } else {
                $errors['error_montant'] = 'Veuillez choisir un montant !' ;
            }

            if ($form->isValid() && count($errors) == 0) {
                // On vérifie que les valeurs entrées sont correctes
                $transaction = new Transaction();
                $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
                $statut = $repStatut->find(self::_STATUT_DON_ANNULE_);
                $transaction->setStatut($statut);
                $don->setTransaction($transaction);
                $don->setAbonnement(null);
                $don->setIspa(false);
                $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code'=>'BC'));
                $don->setType($obj_type);
                $db->persist($don);
                $db->flush();
                $mode = $don->getModePaiement()->getId();
                return $this->redirect($this->generateUrl('donateur_don_reglement',  array('mode'=> $mode, 'id' => $don->getId(),'init'=>$this->init )));

            } else {
                foreach($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }



            // On définit un message flash
            // $this->get('session')->getFlashBag()->add('info', 'Afin de terminer votre don, veuillez SVP confirmer votre réglement ');

            // On redirige vers la page de visualisation de l'article nouvellement créé
            return $this->redirect($this->generateUrl('donateur_don_ponctuel',array('init'=>$this->init )));

        } else {
            // dans le cas de la non validité des informations on va stoquer les informations sur la variable form.error
            foreach($errors as $key => $error) {
                $this->get('session')->getFlashBag()->add($key, $error);
            }
        }



        return $this->render('FulldonDonateurBundle:Donateur:don_ponctuel.html.twig', array('form' => $form->createView(),'init'=>$this->init));
    }
    function donRegulierAction() {
        $don = new Don;
        $user = $this->get('security.context')->getToken()->getUser();
        $don->setUser($user);

        $db = $this->getDoctrine()->getManager();
        // format all the form
        $form = $this->createForm(new DonRegulierType($this->get('request_stack')), $don, array(
            'cascade_validation' => true));
        $errors = array();
        $donateur = $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user'=>$user));
        // On récupère la requête
        $request = $this->getRequest();

        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire

            $form->bind($request);

            // Vérification du montant après la réception de la requête;
            $radioMontant = $request->get('full_montant');
            $currentMontant = $don->getMontant();
            if( !empty($radioMontant)) {
                if($radioMontant == 'other') {
                    if(empty($currentMontant)) {
                        $errors['error_montant'] = 'Le montant est obligatoire !' ;
                    }
                } else {
                    $don->setMontant($radioMontant);
                }
            } else {
                $errors['error_montant'] = 'Veuillez choisir un montant !' ;
            }

            if ($form->isValid() && count($errors) == 0) {
            // On vérifie que les valeurs entrées sont correctes

                $abo = new Abonnement();
                $abo->setActif(true);
                $abo->setRumIndice(1);
                $don->setAbonnement($abo);
                $don->setIspa(true);
                $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code'=>'PA'));
                $don->setType($obj_type);
                $transaction = new Transaction();
                $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
                $statut = $repStatut->find(self::_STATUT_DON_ANNULE_);
                $transaction->setStatut($statut);
                $don->setTransaction($transaction);
                $db->persist($don);
                $db->flush();
                $rum = '01-'.str_pad($donateur->getId(),10,0,STR_PAD_LEFT).'-R'.str_pad($don->getId(),10,0,STR_PAD_LEFT).str_pad(1,6,0,STR_PAD_LEFT);
                $abo->setRum($rum);
                $db->persist($abo);
                $db->flush();
                $mode = $don->getModePaiement()->getId();
                return $this->redirect($this->generateUrl('donateur_don_reglement',  array('mode'=> $mode, 'id' => $don->getId(),'init'=>$this->init )));
            } else {
                foreach($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }



            // On définit un message flash
            // $this->get('session')->getFlashBag()->add('info', 'Afin de terminer votre don, veuillez SVP confirmer votre réglement ');

            // On redirige vers la page de visualisation de l'article nouvellement créé
            return $this->redirect($this->generateUrl('donateur_don_regulier'));

        } else {
            // dans le cas de la non validité des informations on va stoquer les informations sur la variable form.error
            foreach($errors as $key => $error) {
                $this->get('session')->getFlashBag()->add($key, $error);
            }
        }



        return $this->render('FulldonDonateurBundle:Donateur:don_regulier.html.twig', array('form' => $form->createView(), 'init'=>$this->init));
    }
    function getBulletinAction($mode,$id) {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $don = $repDon->find($id);
        $user = $this->get('security.context')->getToken()->getUser();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur') ;
        $donateur = $donateurRep->findOneBy(array('user'=>$user->getId()));
        $repPaiement = $db->getRepository('FulldonDonateurBundle:ModePaiement') ;
        $mode = $repPaiement->find($mode)->getCodeSolution();
        switch($mode) {
            case self::_PAYPAL_:
                return $this->render('FulldonDonateurBundle:Donateur:Payment/paypal.html.twig', array('don' => $don,'donateur' => $donateur, 'init'=>$this->init));
                break;
            case self::_CHEQUE_:
                $transaction = $don->getTransaction();
                $d2 = $this->get('fulldon.donateur.barecodes');
                $string = $don->getId()."|edon";
                $d2->setStrToEncode($string);
                //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                $approx_size=60;
                // Et on trace le code barre avec comme argument la taille en pixels voulue
                $filename = $user->getId().'_'.$don->getId();
                $d2->stroke($approx_size,$filename);


                return $this->render('FulldonDonateurBundle:Donateur:Payment/cheque.html.twig', array('don' => $don,'donateur' => $donateur, 'init'=>$this->init, 'datamatrix'=> $filename));
                break;
            case self::_VIREMENT_:

                        $d2 = $this->get('fulldon.donateur.barecodes');
                        $string = $don->getId()."|edon";
                        $d2->setStrToEncode($string);
                        //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                        $approx_size=60;
                        // Et on trace le code barre avec comme argument la taille en pixels voulue
                        $filename = $user->getId().'_'.$don->getId();
                        $d2->stroke($approx_size,$filename);

                        return $this->render('FulldonDonateurBundle:Donateur:print/virement.html.twig', array('don' => $don,'donateur' => $donateur, 'init'=>$this->init, 'datamatrix'=> $filename));
                break;
            default:
                // return $this->redirect($this->generateUrl('donateur_don_reglement'));
                echo 'en cours de construction';die;
                break;
    }
    }
    function reglementAction($mode, $id) {
        $request = $this->get('request_stack')->getCurrentRequest();
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $repPaiement = $db->getRepository('FulldonDonateurBundle:ModePaiement') ;
        $mode = $repPaiement->find($mode)->getCodeSolution();
        $don = $repDon->find($id);
        if(is_object($don)) {
        $user = $this->get('security.context')->getToken()->getUser();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur') ;
        $donateur = $donateurRep->findOneBy(array('user'=>$user->getId()));
        switch($mode) {
            case self::_PAYPAL_:
                return $this->render('FulldonDonateurBundle:Donateur:Payment/paypal.html.twig', array('don' => $don,'donateur' => $donateur, 'init'=>$this->init));
                break;
            case self::_CHEQUE_:
                $transaction = $don->getTransaction();
                $d2 = $this->get('fulldon.donateur.barecodes');
                $string = $don->getId()."|edon";
                $d2->setStrToEncode($string);
                //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                $approx_size=60;
                // Et on trace le code barre avec comme argument la taille en pixels voulue
                $filename = $user->getId().'_'.$don->getId();
                $d2->stroke($approx_size,$filename);
                if(!isset($transaction)) {
                    $transaction = new Transaction();
                    $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
                    $statut = $repStatut->find(self::_STATUT_ATTENTE_);
                    $transaction->setStatut($statut);
                    $don->setTransaction($transaction);
                    $db->persist($don);
                    $db->flush();
                }
                $assoc_name = $this->container->getParameter('assoc_name');

                return $this->render('FulldonDonateurBundle:Donateur:Payment/cheque.html.twig', array('don' => $don,'donateur' => $donateur, 'init'=>$this->init, 'datamatrix'=> $filename, 'assoc_name'=>$assoc_name));
                break;
            case self::_VIREMENT_:
                    $request = $this->getRequest();
                    $repPeriod = $db->getRepository('FulldonDonateurBundle:Periodicite') ;
                    $periodicites = $repPeriod->findAll();
                    if ($request->getMethod() == 'POST') {
                        $bic = $request->get('bic');
                        $iban = $request->get('iban');
                        $periodicite = $request->get('periodicite');
                        $nom_banque = $request->get('nom_banque');
                        $champs['nom_banque'] = $nom_banque;
                        $champs['bic'] = $bic;
                        $champs['iban'] = $iban;
                        $champs['periodicite'] = $periodicite;
                        $errors = array();
                        if(!isset($bic) || empty($bic)) {

                            $errors['error_bic'] = 'Veuillez spécifier un BIC valide' ;
                        }
                        if(!isset($iban) || empty($iban)) {

                            $errors['error_iban'] = 'Veuillez spécifier un IBAN valide' ;
                        }

                        if(!isset($nom_banque) || empty($nom_banque) ) {

                            $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque' ;
                        }
                        if(count($errors) == 0) {
                            $d2 = $this->get('fulldon.donateur.barecodes');
                            $string = $don->getId()."|edon";
                            $d2->setStrToEncode($string);
                            //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                            $approx_size=60;
                            // Et on trace le code barre avec comme argument la taille en pixels voulue
                            $filename = $user->getId().'_'.$don->getId();
                            $d2->stroke($approx_size,$filename);
                            $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
                            $statut = $repStatut->find(self::_STATUT_ATTENTE_);
                            $don->getAbonnement()->setBic($bic);
                            $don->getAbonnement()->setIban($iban);
                            $don->getAbonnement()->setNomBanque($nom_banque);
                            $don->getAbonnement()->setPeriodicite($repPeriod->find($periodicite));
                            $don->getTransaction()->setStatut($statut);
                            $db->persist($don);
                            $db->flush();
                            $assoc_name = $this->container->getParameter('assoc_name');
                            $this->get('session')->getFlashBag()->add('info', 'Votre prélèvement automatique est désormais en cours de traitement,merci pour votre générosité.');
                            return $this->render('FulldonDonateurBundle:Donateur:print/virement.html.twig', array('don' => $don,'donateur' => $donateur, 'init'=>$this->init, 'datamatrix'=> $filename, 'assoc_name' => $assoc_name));
                        } else {
                            foreach($errors as $key => $error) {
                                $this->get('session')->getFlashBag()->add($key, $error);
                            }
                        }
                    }
                return $this->render('FulldonDonateurBundle:Donateur:Payment/virement.html.twig', array('don' => $don,'donateur' => $donateur,'periodes'=>$periodicites, 'init'=>$this->init));

                break;
            case self::_CB_:
                if($don->getIspa()) {
                $request = $this->getRequest();
                $repPeriod = $db->getRepository('FulldonDonateurBundle:Periodicite') ;
                $periodicites = $repPeriod->findAll();
                $periodicite = $request->get('periodicite');
                $date_debut  = new \DateTime();
                $cur_date =  new \DateTime();
                $recommanded_day = $this->container->get('fulldon.custom_params')->getParam('prelevement_jour');

                    if($cur_date->format('d') > $recommanded_day)
                    {
                        $cur_date->add(new \DateInterval('P1M'));
                    }
                    $date_debut->setDate($cur_date->format('Y'),$cur_date->format('m'),$recommanded_day);

                    return $this->render('FulldonDonateurBundle:Donateur:Payment/cb_pa.html.twig', array('don' => $don,'donateur' => $donateur,'periodes'=>$periodicites,'date_debut' => $date_debut, 'init'=>$this->init));
                }else {
                    return $this->render('FulldonDonateurBundle:Donateur:Payment/cb.html.twig', array('don' => $don,'donateur' => $donateur, 'init'=>$this->init));
                }
            default:

                break;
        }
        }else {
            $this->get('session')->getFlashBag()->add('warning'," La transaction demandée n'existe pas ! ");
            return $this->redirect($this->generateUrl('fulldon_donateur_accueil'));
        }
        //return $this->render('FulldonDonateurBundle:Donateur:reglement.html.twig');

    }
    public function historyAction($page)
    {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $user = $this->get('security.context')->getToken()->getUser();
        $dons = $repDon->findBy(array('user' => $user));
        $total_dons   = count($dons);
        $dons_per_page = $this->container->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page         = ceil($total_dons / $dons_per_page);
        $previous_page     = $page > 1 ? $page - 1 : 1;
        $next_page         = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher*/
        $dons = $this->getDoctrine()
            ->getRepository('FulldonDonateurBundle:Don')
            ->createQueryBuilder('p')
            ->where('p.user = :id')
            ->setParameter('id', $user->getId())
            ->orderBy('p.createdAt','DESC')
            ->setFirstResult(($page * $dons_per_page) - $dons_per_page)
            ->setMaxResults($this->container->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        return $this->render('FulldonDonateurBundle:Donateur:history.html.twig', array(
            'last_page' => $last_page,
            'previous_page' => $previous_page,
            'current_page' => $page,
            'next_page' => $next_page,
            'total_dons' => $total_dons,
            'dons' => $dons,
            'init'=>$this->init,
        ));
        return $this->render('FulldonDonateurBundle:Donateur:history.html.twig', array('dons' => $dons, 'init'=>$this->init));
    }
    public function rfAction($page)
    {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $user = $this->get('security.context')->getToken()->getUser();
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
        $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);

        $rfs = $this->getDoctrine()
            ->getRepository('FulldonDonateurBundle:Rf')
            ->createQueryBuilder('r')
            ->join('r.don','d')
            ->join('d.transaction','t')
            ->join('t.statut','s')
            ->where('d.user = :id and r.don is not null and t.statut =:statut and r.sended = 0')
            ->setParameter('id', $user->getId())
            ->setParameter('statut', $statut )

            ->getQuery()->getResult();
        $total_dons   = count($rfs);
        $dons_per_page = $this->container->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page         = ceil($total_dons / $dons_per_page);
        $previous_page     = $page > 1 ? $page - 1 : 1;
        $next_page         = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher*/
        $rfs = $this->getDoctrine()
            ->getRepository('FulldonDonateurBundle:Rf')
            ->createQueryBuilder('r')
            ->join('r.don','d')
            ->join('d.transaction','t')
            ->join('t.statut','s')
            ->where('d.user = :id and r.don is not null and t.statut =:statut ')
            ->setParameter('id', $user->getId())
            ->setParameter('statut', $statut )
            ->orderBy('d.createdAt','DESC')
            ->setFirstResult(($page * $dons_per_page) - $dons_per_page)
            ->setMaxResults($this->container->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        return $this->render('FulldonDonateurBundle:Donateur:rf.html.twig', array(
            'last_page' => $last_page,
            'previous_page' => $previous_page,
            'current_page' => $page,
            'next_page' => $next_page,
            'total_dons' => $total_dons,
            'rfs' => $rfs,
            'init'=>$this->init,
        ));
    }
    public function rfSendMailAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repRf = $db->getRepository('FulldonDonateurBundle:Rf') ;
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $rf = $repRf->find($id);
        $user = $this->get('security.context')->getToken()->getUser();
        $don = $repDon->findOneBy(array('user'=>$user, 'id' => $rf->getDon()->getId()));
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur') ;
        $donateur = $donateurRep->findOneBy(array('user'=>$user->getId()));

        if(is_object($don)) {
            if($don->getIspa())
            $file = '/RF_PA/DUPLICATA/'.$rf->getNom();
            else
            $file = '/RF/DUPLICATA/'.$rf->getNom();


            try {
                $email = $donateur->getEmail();
                if(isset($email) && !empty($email)) {
                    $html = $this->renderView( 'FulldonIntersaBundle:Email:rf.html.twig', array('don' => $don,'rf'=>$rf));
                    $this->get('fulldon.intersa.email_servies')->sendRf($email,$html,$file);
                }
                $rf->setSended(true);
                $db->persist($rf);
                $db->flush();

                $this->get('session')->getFlashBag()->add('info', 'L\'email contenant le duplicata du RF vient d\'être envoyé');
            } catch(Exception $e) {
                $this->get('session')->getFlashBag()->add('erreur', 'Un problème est survenu lors de l\'envoie, veuillez contacter l\'association');
            }
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Un problème est survenue lors de l\'envoie d\'email ');
        }

        return $this->redirect($this->generateUrl('donateur_rf',array('page'=>1)));
    }
    public function historyViewAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $user = $this->get('security.context')->getToken()->getUser();
        $don = $repDon->findOneBy(array('user' => $user,'id'=>$id));

        return $this->render('FulldonDonateurBundle:Donateur:history_view.html.twig', array('don' => $don,'init'=>$this->init));
    }
    public function shareAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $user = $this->get('security.context')->getToken()->getUser();
        $don = $repDon->findOneBy(array('user' => $user,'id'=>$id));

        return $this->render('FulldonDonateurBundle:Donateur:share.html.twig', array('don' => $don,'init'=>$this->init));
    }
    public function paypalValidationAction()
    {
        // lire le formulaire provenant du système PayPal et ajouter 'cmd'
        $req = 'cmd=_notify-validate';

        foreach ($_POST as $key => $value) {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }
        $header ="";
        // renvoyer au système PayPal pour validation
        $header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
        $fp = fsockopen ('www.sandbox.paypal.com', 80, $errno, $errstr, 30);

        $item_name = $_POST['item_name'];
        $item_number = $_POST['item_number'];
        $payment_status = $_POST['payment_status'];
        $payment_amount = $_POST['mc_gross'];
        $payment_currency = $_POST['mc_currency'];
        $txn_id = $_POST['txn_id'];
        $receiver_email = $_POST['receiver_email'];
        $payer_email = $_POST['payer_email'];
        $id_don = $_POST['custom'];

        if (!$fp) {
            // ERREUR HTTP
        } else {
            fputs ($fp, $header . $req);
            while (!feof($fp)) {
                $res = fgets ($fp, 1024);
                if (strcmp ($res, "VERIFIED") == 0) {
                    // transaction valide
                    $db = $this->getDoctrine()->getManager();
                    $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
                    $don = $repDon->find($id_don);
                    $don->getAbonnement()->setActif(true);
                    $db->persist($don);
                    $db->flush();
                    // vérifier que payment_status a la valeur Completed
                    if ( $payment_status == "Completed") {
                        // vérifier que txn_id n'a pas été précédemment traité: Créez une fonction qui va interroger votre base de données
                        if (VerifIXNID($txn_id) == 0) {
                            // vérifier que receiver_email est votre adresse email PayPal principale
                            if ( "votreEmailSeller" == $receiver_email) {
                                // vérifier que payment_amount et payment_currency sont corrects
                                // traiter le paiement
                            }
                            else {
                                // Mauvaise adresse email paypal
                            }
                        }
                        else {
                            // ID de transaction déjà utilisé
                        }
                    }
                    else {
                        // Statut de paiement: Echec
                    }
                }
                else if (strcmp ($res, "INVALID") == 0) {
                    // Transaction invalide
                }
            }
            fclose ($fp);
        }

    }



    /** @DI\LookupMethod("form.factory") */
    protected function getFormFactory() { }
    /** @DI\Inject("payment.plugin_controller") */
    private $ppc;
}
