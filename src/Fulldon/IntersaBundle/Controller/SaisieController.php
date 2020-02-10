<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\DonateurBundle\Entity\Transaction;
use Fulldon\DonateurBundle\Entity\Abonnement;
use Fulldon\DonateurBundle\Entity\Virement;
use Fulldon\IntersaBundle\Entity\TimeSaisie;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\IntersaBundle\Entity\Saisie;
use Fulldon\IntersaBundle\Entity\Anomalie;
use Fulldon\IntersaBundle\Form\SaisieType;
use Fulldon\IntersaBundle\Form\AnomalieType;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\DonateurBundle\Entity\StatutPaiement;
use Fulldon\DonateurBundle\Entity\ModePaiement;
use Fulldon\DonateurBundle\Entity\Cheque;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Entity\Eclat;
use Fulldon\IntersaBundle\Event\StatVar;
use Fulldon\DonateurBundle\Entity\Doublon;
use Fulldon\IntersaBundle\Entity\TypeTraitementCourrier;
use Fulldon\IntersaBundle\Entity\CourrierDoc;
use Fulldon\IntersaBundle\Entity\CourrierTraitement;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use Fulldon\IntersaBundle\Entity\ProspectionDonateur;
use Fulldon\IntersaBundle\Entity\TypeDon;
use Fulldon\IntersaBundle\Vars;
use Fulldon\IntersaBundle\Entity\CourrierAttente;

/**
 * @PreAuthorize(" hasRole('ROLE_INTERSA_N1') or hasRole('ROLE_INTERSA_N2') or hasRole('ROLE_STREET')")
 */
class SaisieController extends Controller
{
    const _BC_ = 'BC';
    const _CS_ = 'CS';
    const _ESPECES_ = 'ESPECES';
    const _PA_ = 'PA';
    const _VIREMENT_ = 'VIREMENT';
    const _MIX_ = 'MIX';

    const _CHEQUE_MODE_ = 2;
    const _VIREMENT_MODE_ = 4;
    const _ESPECE_MODE_ = 5;
    const _STATUT_ATTENTE_ = 1;
    const _STATUT_TRAITEMENT_PAIEMENT_ = 2;
    const _STATUT_DON_VALIDE_ = 3;
    const _STATUT_DON_ANNULE_ = 4;

    private $special_mode = array('ESPECES', 'VIREMENT', 'MIX');

    private $init = array();

    public function indexAction()
    {

        $root = $this->container->getParameter('path_scan');

        $files = array('files' => array(), 'dirs' => array());
        $directories = array();
        $last_letter = $root[strlen($root) - 1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;

        $directories[] = $root;
        $depth = 0;

        while (sizeof($directories)) {
            $dir = array_pop($directories);

//            if ($handle = opendir($dir)) {
//                while (false !== ($file = readdir($handle)) && $depth < 1) {
//                    if ($file == '.' || $file == '..') {
//                        continue;
//                    }
//                    $filename = $file;
//                    $file = $dir . $file;
//                    if (is_dir($file)) {
//                        $directory_path = $file . DIRECTORY_SEPARATOR;
//                        array_push($directories, $directory_path);
//                        $files['dirs'][] = array('name' => $filename, 'path' => $directory_path);
//
//                    }
//                }
//                closedir($handle);
//            }
            $depth++;
        }
        $display = array();
        $nbTotal = 0;
        foreach ($files['dirs'] as $dir) {
            $result = $this->getXmlFiles($dir['path']);
            $nbTotal += $result['nb'];
            $display[] = array($dir['name'], $result['files'], $result['nb']);
        }

        return $this->render('FulldonIntersaBundle:Saisie:index.html.twig', array('display' => $display, 'nb_total' => $nbTotal));
    }

    public function saisieLotAction($type, $nom)
    {
        /* START OF INITILIZATION */

        $request = $this->getRequest();
        $errors = array();
        $champs = array(
            'id' => null,
            'montant' => null,
            'code' => null,
            'date_cheque' => null,
            'nom_banque' => null,
        );
        $montant = null;
        $code_activite = null;
        $num_cheque = null;
        $date_cheque = null;
        $nom_banque = null;
        $db = $this->getDoctrine()->getManager();
        $repMode = $db->getRepository('FulldonDonateurBundle:ModePaiement');
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $repPeriod = $db->getRepository('FulldonDonateurBundle:Periodicite');
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $repProspection = $db->getRepository('FulldonDonateurBundle:Prospection');


        $root = $this->container->getParameter('path_scan');
        $file = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . '.XML';
        $done_folder = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'DONE';
        //Création du dossier s'il n'existe pas
        if (!is_dir($done_folder)) {
            mkdir($done_folder);
        }

        $total_sequence = 0;
        if (file_exists($file)) {
            $xml = simplexml_load_file($file);
            $total_sequence = count($xml->Batch->Page);
        }
        if (in_array($type, $this->special_mode)) {
            $total_sequence = $total_sequence / 2;
        }
        /* END OF INITIALIZATION */

        $is_new = false;
        $user = $this->get('security.context')->getToken()->getUser();
        $donateur = new Donateur;
        $form = $this->createForm(new SaisieType(), $donateur, array(
            'cascade_validation' => true));
        $anoform = $this->createForm(new AnomalieType(), new Anomalie(), array(
            'cascade_validation' => true));
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $allow_rf = $request->get('allow_rf');
            $champs['allow_rf'] = $allow_rf;
            $ids_donateur = $request->get('num_donateur');
            $id = $ids_donateur[0];

            if (isset($id) && !empty($id) && $doid = $repDonateur->findOneBy(array('refDonateur' => $id))) {
                //existing user
                $champs['id'] = $id;
                if (!is_object($donateur = $doid)) {
                    $errors['error_date_cheque'] = 'Donateur introuvable';
                }
                $form = $this->createForm(new SaisieType(), $donateur, array(
                    'cascade_validation' => true));
            } else {
                //new user
                $is_new = true;
                $duser = new User();
                $i = 0;
                $plain_password = null;
                do {
                    $i++;
                    $login = 'donateur' . $i;
                    $plain_password = base64_encode('p@s' . $i);
                } while (!$this->isNewUser($login));

                $duser->setUsername($login);
                $duser->setPassword($plain_password);
                $donateur->setUser($duser);
                // On enregistre notre objet $article dans la base de données
                $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                $donateur->getUser()->setIsActive(true);
                $repRole = $db->getRepository('FulldonSecurityBundle:Role');
                $role_donateur = $repRole->findOneBy(array('role' => 'ROLE_DONATEUR'));
                $donateur->getUser()->addRole($role_donateur);
                // Set encrypted password
                $encoder = $this->container->get('security.encoder_factory')
                    ->getEncoder($donateur->getUser());
                $password = $encoder->encodePassword($donateur->getUser()->getPassword(), $donateur->getUser()->getSalt());
                $donateur->getUser()->setPassword($password);
            }
            //RF donateurs
            if ($allow_rf == "true") {
                $donateur->setAllowRf(true);
            } else {
                $donateur->setAllowRf(false);
            }
            switch ($type) {
                case self::_BC_:

                    $num_cheque = $request->get('num_cheque');
                    $date_cheque = $request->get('date_cheque');
                    $champs['date_cheque'] = $date_cheque;
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;

                    if (!isset($date_cheque) || empty($date_cheque) || !$this->validateDate($date_cheque)) {

                        $errors['error_date_cheque'] = ' La date du chèque n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }
                    break;
                case self::_CS_:
                    $num_cheque = $request->get('num_cheque');
                    $date_cheque = $request->get('date_cheque');
                    $champs['date_cheque'] = $date_cheque;
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;
                    if (!isset($date_cheque) || empty($date_cheque) || !$this->validateDate($date_cheque)) {

                        $errors['error_date_cheque'] = ' La date du chèque n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }
                    break;
                case self::_PA_:
                    $bic = $request->get('bic');
                    $iban = $request->get('iban');
                    $date_first_pa = $request->get('date_first_pa');
                    $periodicite = $request->get('periodicite');
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;
                    $champs['bic'] = $bic;
                    $champs['iban'] = $iban;
                    $champs['date_first_pa'] = $date_first_pa;
                    $champs['date_next_pa'] = $date_first_pa;
                    $champs['periodicite'] = $periodicite;
                    $date_fin_pa = $request->get('date_fin_pa');
                    $champs['date_fin_pa'] = $date_fin_pa;
                    if (isset($date_fin_pa) && !empty($date_fin_pa) && !$this->validateDate($date_fin_pa)) {

                        $errors['error_date_fin_pa'] = ' La date du fin de l\'engagement n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($bic) || empty($bic)) {

                        $errors['error_bic'] = ' Veuillez spécifier un BIC valide';
                    }
                    if (!isset($iban) || empty($iban)) {

                        $errors['error_iban'] = ' Veuillez spécifier un IBAN valide';
                    }
                    if (!isset($date_first_pa) || empty($date_first_pa) || !$this->validateDate($date_first_pa)) {

                        $errors['error_date_first_pa'] = ' La date du premier prélevement n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }
                    break;
                case self::_VIREMENT_:
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;

                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }
                    break;
                case self::_MIX_:
                    $choice_type = $request->get('choice_type');
                    $champs['choice_type'] = $choice_type;

                    if (!isset($choice_type) || empty($choice_type)) {

                        $errors['error_choice_type'] = 'Veuillez spécifier le type du don';
                    }
                    break;
                default:
                    break;
            }
            //Contrôle des champs
            $montants = $request->get('montant');
            $code_activites = $request->get('code_activite');
            $coll_don = $request->get('coll_don'); //Le collecteur est à son tour un donateur
            $champs['coll_don'] = $coll_don;
            $champs['eclate'] = count($montants);

            foreach ($montants as $key => $montant) {
                if ($coll_don || $key != 0) {
                    // controle des champs
                    if (!isset($montant)  || !is_numeric($montant)) {

                        $errors['error_montant_' . $key] = ' Veuillez soumettre un montant valide ';
                    }
                    $champs['montant_' . $key] = $montant;
                }
            }

            foreach ($code_activites as $key => $code) {
                if ($coll_don || $key != 0) {
                    if (!$this->checkCodeActivite($code)) {

                        $errors['error_code_activite_' . $key] = ' Le code fourni n\'est pas valide ';
                    }
                    $champs['code_activite_' . $key] = $code;
                }
            }
            $form->bind($request);
            if ($form->isValid() && count($errors) == 0) {
                $donateur->setRemoved(false);
                if ($donateur->getRemoved()) {
                    $donateur->getUser()->setIsActive(false);
                } else {
                    $donateur->getUser()->setIsActive(true);
                }

                if (isset($id) && !empty($id)) {
                    //Stat
                    $current_user = $this->get('security.context')->getToken()->getUser();
                    // Log the user creation
                    $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_SAISIE_DONATEUR_OLD_);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(StatVar::CREATE, $event);
                    //Fin Stat
                    $db->persist($donateur);
                    $db->flush();

                } else {

                    //Stat
                    $current_user = $this->get('security.context')->getToken()->getUser();
                    // Log the user creation
                    $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_SAISIE_DONATEUR_NEW_);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(StatVar::CREATE, $event);
                    $refId = $this->get('fulldon.intersa.global')->getUniqueRefDonateur();
                    $donateur->setRefDonateur($refId);
                    //Gestion des doublons
                    $percent = 0;
                    $pid = 0;
                    $pdonateur = $repDonateur->findOneBy(array('nom' => $donateur->getNom(), 'prenom' => $donateur->getPrenom(), 'removed' => false));
                    if ($pdonateur) {
                        $percent += 60;
                        $pid = $pdonateur->getId();
                        $pdonateur2 = $repDonateur->findOneBy(array('adresse3' => $donateur->getAdresse3(), 'id' => $pid));
                        if ($pdonateur2) {
                            $percent += 30;
                        }
                        //Création d'un doublon
                        $doublon = new Doublon();
                        $doublon->setDone(false);
                        $doublon->setDonateur1($pid);

                        //Sauvegarder le nouveau donateur
                        $db->persist($donateur);
                        $db->flush();
                        //required to  retreive the donateur ID

                        $doublon->setDonateur2($donateur->getId());
                        $doublon->setPourcentage($percent);
                        $db->persist($doublon);
                        $db->flush();
                        $current_user = $this->get('security.context')->getToken()->getUser();
                        $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_DOUBLON_CREATED_);
                        $dispatcher = $this->get('event_dispatcher');
                        $dispatcher->dispatch(StatVar::CREATE, $event);
                        $this->get('session')->getFlashBag()->add('warning', 'Un doublon a été detecté il sera géreable dans la gestion des doublons ! ');


                    } else {
                        $db->persist($donateur);
                        $db->flush();
                    }
                    // log new User
                    $msg = $this->get('log.helper')->getAddMsgLog($donateur, 'DONATEUR');
                    $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DONATEUR_);
                    $role = $this->get('log.helper')->getRole($current_user);
                    $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, null);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(LogVar::CREATE, $event);

                }

                if ($is_new) {
                    //Génération du de l'original.
                    $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->renderView(
                            'FulldonIntersaBundle:Donateurs:pdf/identifiants.html.twig',
                            array(
                                'login' => $login,
                                'password' => $plain_password,
                                'donateur' => $donateur,
                                'init' => $this->init,

                            )
                        ),
                        '/' . $this->container->getParameter('folder_app') . '/users/donateur_fiche_' . $donateur->getId() . '.pdf'
                    );

                }

                $this->get('session')->getFlashBag()->add('info', 'Saisie de la sequence est réussie  ');
            } else {
                // dans le cas de la non validité des informations on va stoquer les informations sur la variable form.error
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }


        // format all the form
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');


        //Initialisation de la sequence
        $sequence = 1;
        if ($this->isUsedLot($nom)) {
            $saisie = $repSaisie->findOneBy(array('lot' => $nom));
            $space = round(abs($saisie->getCreatedAt()->getTimestamp() - (new \Datetime())->getTimestamp()) / 60);
            if ($space >= 60) {
                //update
                $saisie->setUser($user);
                $saisie->setCreatedAt(new \Datetime());
                $db->persist($saisie);
                $db->flush();
            } else {
                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Veuillez SVP Essayer dans ' . (60 - $space) . ' minutes ');
                // On redirige vers la page de visualisation de l'article nouvellement créé
                return $this->redirect($this->generateUrl('intersa_saisie_serie'));
            }
        } else {
            if ($this->isMyLot($nom)) {
                //Initialisation
                $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);
                $mode = null;
                // update the lot
                $saisie = $repSaisie->findOneBy(array('lot' => $nom));

                $id_don = $request->get('id_don');
                $don = null;
                $transaction = null;
                $sequence = $saisie->getSequence();
                if ($request->getMethod() == 'POST' && $form->isValid() && count($errors) == 0) {
                    $timeSaisie = new TimeSaisie();
                    $timeSaisie->setUser($user);
                    $timeSaisie->setSaisie($saisie);
                    $timeSaisie->setSequence($saisie->getSequence());
                    $timeSaisie->setTemps(round(abs($saisie->getCreatedAt()->getTimestamp() - (new \Datetime())->getTimestamp()) / 60, 2));

                    $db->persist($timeSaisie);

                    if (isset($id_don) && !empty($id_don)) {
                        echo $id_don;
                        $don = $repDon->find($id_don);
                        $transaction = $don->getTransaction();

                    } else {
                        $don = new Don();
                        $transaction = new Transaction();
                    }
                    //Simulation de validation
                    switch ($type) {
                        case self::_BC_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 2 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 2);
                            }
                            $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_));

                            $cheque = new Cheque();
                            $cheque->setDateCheque(\DateTime::createFromFormat('d/m/Y', $date_cheque));
                            $cheque->setNomBanque($nom_banque);
                            $cheque->setNumeroCheque($num_cheque);
                            $transaction->setStatut($statut);
                            $transaction->setCheque($cheque);

                            if (count($montants) > 1) {
                                $objEclat = new Eclat();
                                $objEclat->setDonateur($donateur);
                                for ($i = 0; $i < count($montants); $i++) {
                                    if ($coll_don || $i != 0) {
                                        $don = new Don();
                                        $cause = $repCause->findOneBy(array('code' => $code_activites[$i]));
                                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                        if (is_object($pros)) {
                                            $pros->setRetour(true);
                                        }
                                        $don->setSequence($sequence);
                                        $don->setLot($nom);
                                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                        $don->setType($obj_type);
                                        if (is_object($mode))
                                            $don->setModePaiement($mode);
                                        $don->setMontant($montants[$i]);
                                        //$don->setUser($repDonateur->find($ids_donateur[$i])->getUser());
                                        $don->setUser($donateur->getUser());
                                        $don->setTransaction($transaction);
                                        $don->setIspa(false);
                                        $don->setCause($cause);
                                        $don->setEclat($objEclat);
                                        $db->persist($don);
                                        $db->flush();
                                    }
                                    //$donateur->getUser()
                                }
                            } else {
                                $cause = $repCause->findOneBy(array('code' => $code_activites[0]));
                                $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                if (is_object($pros)) {
                                    $pros->setRetour(true);
                                }
                                $don->setSequence($sequence);
                                $don->setLot($nom);
                                $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                $don->setType($obj_type);
                                if (is_object($mode))
                                    $don->setModePaiement($mode);
                                $don->setMontant($montants[0]);
                                $don->setUser($donateur->getUser());
                                $don->setTransaction($transaction);
                                $don->setIspa(false);
                                $don->setCause($cause);
                                $db->persist($don);
                                $db->flush();
                            }

                            break;
                        case self::_MIX_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 1 > $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 1);
                            }
                            // Type de don
                            switch ($choice_type) {
                                case 'cbc':
                                    $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_));
                                    $transaction = new Transaction();
                                    $transaction->setStatut($statut);
                                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'BC'));
                                    break;
                                case 'ces':
                                    $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_ESPECE_MODE_));
                                    $transaction = new Transaction();
                                    $transaction->setStatut($statut);
                                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'ESPECE'));
                                    break;
                            }


                            if (count($montants) > 1) {
                                $objEclat = new Eclat();
                                $objEclat->setDonateur($donateur);
                                for ($i = 0; $i < count($montants); $i++) {
                                    if ($coll_don || $i != 0) {
                                        $don = new Don();
                                        $cause = $repCause->findOneBy(array('code' => $code_activites[$i]));
                                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                        if (is_object($pros)) {
                                            $pros->setRetour(true);
                                        }
                                        $don->setSequence($sequence);
                                        $don->setLot($nom);
                                        $don->setType($obj_type);
                                        if (is_object($mode))
                                            $don->setModePaiement($mode);
                                        $don->setMontant($montants[$i]);
                                        //$don->setUser($repDonateur->find($ids_donateur[$i])->getUser());
                                        $don->setUser($donateur->getUser());
                                        $don->setTransaction($transaction);
                                        $don->setIspa(false);
                                        $don->setCause($cause);
                                        $don->setEclat($objEclat);
                                        $db->persist($don);
                                        $db->flush();
                                    }
                                    //$donateur->getUser()
                                }
                            } else {
                                $cause = $repCause->findOneBy(array('code' => $code_activites[0]));
                                $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                if (is_object($pros)) {
                                    $pros->setRetour(true);
                                }
                                $don->setSequence($sequence);
                                $don->setLot($nom);
                                $don->setType($obj_type);
                                if (is_object($mode))
                                    $don->setModePaiement($mode);
                                $don->setMontant($montants[0]);
                                $don->setUser($donateur->getUser());
                                $don->setTransaction($transaction);
                                $don->setIspa(false);
                                $don->setCause($cause);
                                $db->persist($don);
                                $db->flush();
                            }

                            break;
                        case self::_CS_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 2 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 2);
                            }

                            $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_));
                            $cheque = new Cheque();
                            $cheque->setDateCheque(\DateTime::createFromFormat('d/m/Y', $date_cheque));
                            $cheque->setNomBanque($nom_banque);
                            $cheque->setNumeroCheque($num_cheque);
                            $transaction->setStatut($statut);
                            $transaction->setCheque($cheque);

                            if (count($montants) > 1) {
                                $objEclat = new Eclat();
                                $objEclat->setDonateur($donateur);
                                for ($i = 0; $i < count($montants); $i++) {
                                    if ($coll_don || $i != 0) {
                                        $don = new Don();
                                        $cause = $repCause->findOneBy(array('code' => $code_activites[$i]));
                                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                        if (is_object($pros)) {
                                            $pros->setRetour(true);
                                        }
                                        $don->setSequence($sequence);
                                        $don->setLot($nom);
                                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                        $don->setType($obj_type);
                                        if (is_object($mode))
                                            $don->setModePaiement($mode);
                                        $don->setMontant($montants[$i]);
                                        //$don->setUser($repDonateur->find($ids_donateur[$i])->getUser());
                                        $don->setUser($donateur->getUser());
                                        $don->setTransaction($transaction);
                                        $don->setIspa(false);
                                        $don->setCause($cause);
                                        $don->setEclat($objEclat);
                                        $db->persist($don);
                                        $db->flush();

                                    }
                                }
                            } else {
                                $cause = $repCause->findOneBy(array('code' => $code_activites[0]));
                                $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                if (is_object($pros)) {
                                    $pros->setRetour(true);
                                }
                                $don->setSequence($sequence);
                                $don->setLot($nom);
                                $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                $don->setType($obj_type);
                                if (is_object($mode))
                                    $don->setModePaiement($mode);
                                $don->setMontant($montants[0]);
                                $don->setUser($donateur->getUser());
                                $don->setTransaction($transaction);
                                $don->setIspa(false);
                                $don->setCause($cause);
                                $db->persist($don);
                                $db->flush();
                            }

                            break;
                        case self::_ESPECES_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 1 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 1);
                            }
                            $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_ESPECE_MODE_));

                            $transaction->setStatut($statut);
                            if (count($montants) > 1) {
                                $objEclat = new Eclat();
                                $objEclat->setDonateur($donateur);
                                for ($i = 0; $i < count($montants); $i++) {
                                    if ($coll_don || $i != 0) {
                                        $don = new Don();
                                        $cause = $repCause->findOneBy(array('code' => $code_activites[$i]));
                                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                        if (is_object($pros)) {
                                            $pros->setRetour(true);
                                        }
                                        $don->setSequence($sequence);
                                        $don->setLot($nom);
                                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                        $don->setType($obj_type);
                                        if (is_object($mode))
                                            $don->setModePaiement($mode);
                                        $don->setMontant($montants[$i]);
                                        //$don->setUser($repDonateur->find($ids_donateur[$i])->getUser());
                                        $don->setUser($donateur->getUser());
                                        $don->setTransaction($transaction);
                                        $don->setIspa(false);
                                        $don->setCause($cause);
                                        $don->setEclat($objEclat);
                                        $db->persist($don);
                                        $db->flush();
                                    }
                                }
                            } else {
                                $cause = $repCause->findOneBy(array('code' => $code_activites[0]));
                                $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                if (is_object($pros)) {
                                    $pros->setRetour(true);
                                }
                                $don->setSequence($sequence);
                                $don->setLot($nom);
                                $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                $don->setType($obj_type);
                                if (is_object($mode))
                                    $don->setModePaiement($mode);
                                $don->setMontant($montants[0]);
                                $don->setUser($donateur->getUser());
                                $don->setTransaction($transaction);
                                $don->setIspa(false);
                                $don->setCause($cause);
                                $db->persist($don);
                                $db->flush();
                            }

                            break;
                        case self::_PA_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 1 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 1);
                            }
                            $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_PA_MODE_));
                            $periode = $repPeriod->find($periodicite);
                            $transaction->setStatut($statut);


                            if (count($montants) > 1) {
                                $objEclat = new Eclat();
                                $objEclat->setDonateur($donateur);
                                for ($i = 0; $i < count($montants); $i++) {
                                    if ($coll_don || $i != 0) {
                                        $don = new Don();
                                        $cause = $repCause->findOneBy(array('code' => $code_activites[$i]));
                                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                        if (is_object($pros)) {
                                            $pros->setRetour(true);
                                        }
                                        $don->setSequence($sequence);
                                        $don->setLot($nom);
                                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                        $don->setType($obj_type);
                                        if (is_object($mode))
                                            $don->setModePaiement($mode);
                                        $don->setMontant($montants[$i]);
                                        //$don->setUser($repDonateur->find($ids_donateur[$i])->getUser());
                                        $don->setUser($donateur->getUser());
                                        $don->setTransaction($transaction);
                                        $don->setIspa(true);
                                        $don->setCause($cause);
                                        $don->setEclat($objEclat);
                                        $abonnement = new Abonnement();
                                        $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                                        $abonnement->setRum($rum);
                                        $abonnement->setActif(true);
                                        $abonnement->setPeriodicite($periode);
                                        $abonnement->setDateFirstPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                                        $abonnement->setDateNextPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                                        if (isset($date_fin_pa) && !empty($date_fin_pa))
                                            $abonnement->setDateFinPa(\DateTime::createFromFormat('d/m/Y', $date_fin_pa));
                                        $abonnement->setBic($bic);
                                        $abonnement->setIban($iban);
                                        $abonnement->setNomBanque($nom_banque);
                                        $abonnement->setRumIndice(0);
                                        $don->setAbonnement($abonnement);
                                        $db->persist($don);
                                        $db->flush();
                                        //Gestion des Rums
                                        $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                                        $abonnement->setRum($rum);
                                        $abonnement->setRumIndice(1);
                                        $db->persist($abonnement);
                                        $db->flush();

                                        //Création d'un courrier en attente.
                                        $courrierAttente = new CourrierAttente();
                                        $courrierAttente->setDonateur($donateur);
                                        $courrierAttente->setDon($don);
                                        $courrierAttente->setTypeTraitements($db->getRepository('FulldonIntersaBundle:TypeTraitementCourrier')->findOneBy(array('code' => Vars\DonVars::_COURRIER_CREATE_PA_)));
                                        $courrierAttente->setDone(false);
                                        $db->persist($courrierAttente);
                                        $db->flush();
                                        // Fin des traitements des courriers en attente
                                    }
                                }
                            } else {
                                $cause = $repCause->findOneBy(array('code' => $code_activites[0]));
                                $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                if (is_object($pros)) {
                                    $pros->setRetour(true);
                                }
                                $abonnement = new Abonnement();
                                $abonnement->setActif(true);
                                $abonnement->setPeriodicite($periode);
                                $abonnement->setDateFirstPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                                $abonnement->setDateNextPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                                if (isset($date_fin_pa) && !empty($date_fin_pa))
                                    $abonnement->setDateFinPa(\DateTime::createFromFormat('d/m/Y', $date_fin_pa));
                                $abonnement->setBic($bic);
                                $abonnement->setIban($iban);
                                $abonnement->setNomBanque($nom_banque);
                                $abonnement->setRumIndice(0);
                                $don->setAbonnement($abonnement);
                                $don->setSequence($sequence);
                                $don->setLot($nom);
                                $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                $don->setType($obj_type);
                                if (is_object($mode))
                                    $don->setModePaiement($mode);
                                $don->setMontant($montants[0]);
                                $don->setUser($donateur->getUser());
                                $don->setTransaction($transaction);
                                $don->setIspa(true);
                                $don->setCause($cause);
                                $db->persist($don);
                                $db->flush();
                                //Gestion des Rums
                                $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                                $abonnement->setRum($rum);
                                $abonnement->setRumIndice(1);
                                $db->persist($abonnement);
                                $db->flush();
                                //Création d'un courrier en attente.
                                $courrierAttente = new CourrierAttente();
                                $courrierAttente->setDonateur($donateur);
                                $courrierAttente->setDon($don);
                                $courrierAttente->setTypeTraitements($db->getRepository('FulldonIntersaBundle:TypeTraitementCourrier')->findOneBy(array('code' => Vars\DonVars::_COURRIER_CREATE_PA_)));
                                $courrierAttente->setDone(false);
                                $db->persist($courrierAttente);
                                $db->flush();
                                // Fin des traitements des courriers en attente
                            }

                            break;
                        case self::_VIREMENT_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 1 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 1);
                            }
                            $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_VIREMENT_MODE_));
                            $cause = $repCause->findOneBy(array('code' => $code_activite));
                            $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                            if (is_object($pros)) {
                                $pros->setRetour(true);
                            }
                            $virement = new Virement();
                            $virement->setNomBanque($nom_banque);
                            $transaction->setStatut($statut);
                            $transaction->setVirement($virement);
                            if (count($montants) > 1) {
                                $objEclat = new Eclat();
                                $objEclat->setDonateur($donateur);
                                for ($i = 0; $i < count($montants); $i++) {
                                    if (!$coll_don || $i != 0) {
                                        $don = new Don();
                                        $cause = $repCause->findOneBy(array('code' => $code_activites[$i]));
                                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                        if (is_object($pros)) {
                                            $pros->setRetour(true);
                                        }
                                        $don->setSequence($sequence);
                                        $don->setLot($nom);
                                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                        $don->setType($obj_type);
                                        if (is_object($mode))
                                            $don->setModePaiement($mode);
                                        $don->setMontant($montants[$i]);
                                        //$don->setUser($repDonateur->find($ids_donateur[$i])->getUser());
                                        $don->setUser($donateur->getUser());
                                        $don->setTransaction($transaction);
                                        $don->setIspa(false);
                                        $don->setCause($cause);
                                        $don->setEclat($objEclat);
                                        $db->persist($don);
                                        $db->flush();
                                    }
                                }
                            } else {
                                $cause = $repCause->findOneBy(array('code' => $code_activites[0]));
                                $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                                if (is_object($pros)) {
                                    $pros->setRetour(true);
                                }
                                $don->setSequence($sequence);
                                $don->setLot($nom);
                                $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                                $don->setType($obj_type);
                                if (is_object($mode))
                                    $don->setModePaiement($mode);
                                $don->setMontant($montants[0]);
                                $don->setUser($donateur->getUser());
                                $don->setTransaction($transaction);
                                $don->setIspa(false);
                                $don->setCause($cause);
                                $db->persist($don);
                                $db->flush();
                            }

                            break;
                        default:
                            break;
                    }

                    //Log
                    $current_user = $this->get('security.context')->getToken()->getUser();
                    $msg = $this->get('log.helper')->getAddMsgLog($don, 'DON');
                    $donateur = $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser()));
                    $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
                    $role = $this->get('log.helper')->getRole($current_user);
                    $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, $don);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(LogVar::CREATE, $event);
                    //Fin Log
                    $saisie->setCreatedAt(new \Datetime());
                    $db->persist($saisie);
                    $db->flush();
                    if ($saisie->getDone()) {
                        //Message plus redirection
                        $this->get('session')->getFlashBag()->add('info', 'Lot terminé avec succès ! ');
                        return $this->redirect($this->generateUrl('intersa_saisie_serie'));
                    }

                    return $this->redirect($this->generateUrl('intersa_saisie_serie_lot', array('type' => $type, 'nom' => $nom)));
                } else {
                    $saisie->setCreatedAt(new \Datetime());
                    $db->persist($saisie);
                    $db->flush();
                }

            } else {
                // create
                $saisie = new Saisie();
                $saisie->setLot($nom);
                $saisie->setUser($user);
                $saisie->setType($type);
                $saisie->setSequence(1);
                $saisie->setDone(false);
                $saisie->setCreatedAt(new \Datetime());
                $db->persist($saisie);
                $db->flush();
            }
        }
        $saisie = $repSaisie->findOneBy(array('lot' => $nom));
        $sequence = $saisie->getSequence();
        //Initialisation des data pour les prospections afin d'éviter plusieurs controle au niveau de la vue.
        $data = array(
            'nom' => '',
            'prenom' => '',
            'adresse1' => '',
            'adresse2' => '',
            'adresse3' => '',
            'adresse4' => '',
            'zipcode' => '',
            'ville' => ''
        );
        $data['image'] = array();
        if (file_exists($file)) {
            $front = false;
            foreach ($xml->Batch->Page as $key => $page) {
                switch ($type) {
                    case self::_BC_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $sequence % 2 != 0 && $page->Fields->Side == 'F') {
                            //Front
                            //Je commence le traitement

                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                            $image = new \Imagick($root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . DIRECTORY_SEPARATOR . $originimg);
                            $image->setImageFormat('png');
                            $image->writeImage($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg' . DIRECTORY_SEPARATOR . $nom . '_' . $page->Fields->SequenceNumber . '.png');
                            $datamatrix = $page->Fields->Barcode;
                            if (!empty($datamatrix)) {
                                $datamatrix = trim($datamatrix, '|');
                                $arrdata = explode('|', $datamatrix);
                                if (count($arrdata) > 1) {
                                    if ($arrdata[1] == 'edon') {
                                        $don = $arrdata[0];
                                        $label = $arrdata[1];
                                        // On a besoin du donateur Id
                                        $don = $repDon->find($don);
                                        if (is_object($don)) {
                                            $data['montant'] = $don->getMontant();
                                            $data['cause'] = $don->getCause();
                                            $data['don'] = $don->getId();
                                            $donateur = $repDonateur->findOneBy(array('user' => $don->getUser()));
                                            $data['donateur'] = $donateur->getRefDonateur();
                                            // On a  besoin du don.
                                        }
                                    }
                                } else {
                                    //trouver le donateur prospection associé
                                    $repReposDonateur = $db->getRepository('FulldonIntersaBundle:ProspectionDonateur');
                                    $prosdonateur = $repReposDonateur->findOneBy(array('datamatrix' => $datamatrix));
                                    if (is_object($prosdonateur)) {
                                        $data['nom'] = $prosdonateur->getNom();
                                        $data['prenom'] = $prosdonateur->getPrenom();
                                        $data['adresse1'] = $prosdonateur->getAdresse1();
                                        $data['adresse2'] = $prosdonateur->getAdresse2();
                                        $data['adresse3'] = $prosdonateur->getAdresse3();
                                        $data['adresse4'] = $prosdonateur->getAdresse4();
                                        $data['zipcode'] = $prosdonateur->getZipcode();
                                        $data['cause'] = $prosdonateur->getCause();
                                        $data['ville'] = $prosdonateur->getVille();
                                        $data['datamatrix'] = true;
                                        $donateur->setVille($prosdonateur->getVille());
                                    }

                                }
                            }
                        } elseif ($page->Fields->SequenceNumber == $sequence + 1 && $sequence % 2 != 0 && $page->Fields->Side == 'F') {

                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                            $image = new \Imagick($root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . DIRECTORY_SEPARATOR . $originimg);
                            $image->setImageFormat('png');
                            $image->writeImage($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg' . DIRECTORY_SEPARATOR . $nom . '_' . $page->Fields->SequenceNumber . '.png');
                            $chequenum = $page->Fields->CodeLineCMC7Field;
                            $chequenum = preg_replace("#[^0-9]#", "", $chequenum);
                            $data['chequenum'] = $chequenum;
                        }

                        break;
                    case self::_MIX_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence &&  $page->Fields->Side == 'F') {
                            //Front
                            //Je commence le traitement

                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber.'F';
                            $image = new \Imagick($root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . DIRECTORY_SEPARATOR . $originimg);
                            $image->setImageFormat('png');
                            $image->writeImage($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg' . DIRECTORY_SEPARATOR . $nom . '_' . $page->Fields->SequenceNumber . 'F.png');
                            $datamatrix = $page->Fields->Barcode;
                            if (!empty($datamatrix)) {
                                $datamatrix = trim($datamatrix, '|');
                                $arrdata = explode('|', $datamatrix);
                                if (count($arrdata) > 1) {
                                    if ($arrdata[1] == 'edon') {
                                        $don = $arrdata[0];
                                        $label = $arrdata[1];
                                        // On a besoin du donateur Id
                                        $don = $repDon->find($don);
                                        if (is_object($don)) {
                                            $data['montant'] = $don->getMontant();
                                            $data['cause'] = $don->getCause();
                                            $data['don'] = $don->getId();
                                            $donateur = $repDonateur->findOneBy(array('user' => $don->getUser()));
                                            $data['donateur'] = $donateur->getRefDonateur();
                                            // On a  besoin du don.
                                        }
                                    }
                                } else {
                                    //trouver le donateur prospection associé
                                    $repReposDonateur = $db->getRepository('FulldonIntersaBundle:ProspectionDonateur');
                                    $prosdonateur = $repReposDonateur->findOneBy(array('datamatrix' => $datamatrix));
                                    if (is_object($prosdonateur)) {
                                        $data['nom'] = $prosdonateur->getNom();
                                        $data['prenom'] = $prosdonateur->getPrenom();
                                        $data['adresse1'] = $prosdonateur->getAdresse1();
                                        $data['adresse2'] = $prosdonateur->getAdresse2();
                                        $data['adresse3'] = $prosdonateur->getAdresse3();
                                        $data['adresse4'] = $prosdonateur->getAdresse4();
                                        $data['zipcode'] = $prosdonateur->getZipcode();
                                        $data['cause'] = $prosdonateur->getCause();
                                        $data['ville'] = $prosdonateur->getVille();
                                        $data['datamatrix'] = true;
                                        $donateur->setVille($prosdonateur->getVille());
                                    }

                                }
                            }
                        } elseif ($page->Fields->SequenceNumber == $sequence &&   $page->Fields->Side == 'B') {

                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber.'B';
                            $image = new \Imagick($root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . DIRECTORY_SEPARATOR . $originimg);
                            $image->setImageFormat('png');
                            $image->writeImage($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg' . DIRECTORY_SEPARATOR . $nom . '_' . $page->Fields->SequenceNumber.'B.png');
                        }

                        break;
                    case self::_CS_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $page->Fields->Side == 'F') {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                            $image = new \Imagick($root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . DIRECTORY_SEPARATOR . $originimg);
                            $image->setImageFormat('png');
                            $image->writeImage($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg' . DIRECTORY_SEPARATOR . $nom . '_' . $page->Fields->SequenceNumber . '.png');
                            $chequenum = $page->Fields->CodeLineCMC7Field;
                            $chequenum = preg_replace("#[^0-9]#", "", $chequenum);
                            $data['chequenum'] = $chequenum;
                        }
                        break;
                    case self::_ESPECES_:
                    case self::_VIREMENT_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $front == false) {
                            $front = true;
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                            $image = new \Imagick($root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . DIRECTORY_SEPARATOR . $originimg);
                            $image->setImageFormat('png');
                            $image->writeImage($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg' . DIRECTORY_SEPARATOR . $nom . '_' . $page->Fields->SequenceNumber . '.png');
                            $chequenum = $page->Fields->CodeLineCMC7Field;
                            $chequenum = preg_replace("#[^0-9]#", "", $chequenum);
                            $data['chequenum'] = $chequenum;
                        }
                        break;
                    case self::_PA_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $front == false) {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                            $image = new \Imagick($root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . DIRECTORY_SEPARATOR . $originimg);
                            $image->setImageFormat('png');
                            $image->writeImage($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg' . DIRECTORY_SEPARATOR . $nom . '_' . $page->Fields->SequenceNumber . '.png');
                            $datamatrix = $page->Fields->Barcode;
                            if (!empty($datamatrix)) {
                                $datamatrix = trim($datamatrix, '|');
                                $arrdata = explode('|', $datamatrix);
                                if (count($arrdata) > 1) {
                                    if ($arrdata[1] == 'edon') {
                                        $don = $arrdata[0];
                                        $label = $arrdata[1];
                                        // On a besoin du donateur Id
                                        $don = $repDon->find($don);
                                        if (is_object($don) && $don->getTransaction() != NULL) {
                                            $data['montant'] = $don->getMontant();
                                            $data['cause'] = $don->getCause();
                                            $data['don'] = $don->getId();
                                            $data['bic'] = $don->getAbonnement()->getBic();
                                            $data['iban'] = $don->getAbonnement()->getIban();
                                            $data['periodicite'] = $don->getAbonnement()->getPeriodicite()->getId();
                                            $data['nom_banque'] = $don->getAbonnement()->getNomBanque();
                                            $donateur = $repDonateur->findOneBy(array('user' => $don->getUser()));
                                            $data['donateur'] = $donateur->getRefDonateur();
                                            // On a  besoin du don.
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        break;
                }

            }
        } else {
            exit('Echec lors de l\'ouverture du fichier ' . $file . '.');
        }
        $periodes = $repPeriod->findAll();
        return $this->render('FulldonIntersaBundle:Saisie:saisie.html.twig', array(
            'lot' => $nom,
            'type' => $type,
            'sequence' => $sequence,
            'total_sequence' => $total_sequence,
            'data' => $data,
            'form' => $form->createView(),
            'champs' => $champs,
            'periodes' => $periodes,
            'anoform' => $anoform->createView()
        ));

    }

    private function getXmlFiles($dir)
    {

        $db = $this->getDoctrine()->getManager();
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $files = array();
        $files['files'] = array();
        $nbLots = 0;
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);

                if (!is_dir($file) && ($ext == 'XML' || $ext == 'xml')) {
                    $filename = explode('.', $file);
                    $filename = $filename[0];
                    $files['files'][] = array('name' => $file, 'path' => $dir . $file);
                    $nbLots++;
                }
            }
            closedir($handle);
        }
        $files['nb'] = $nbLots;
        return $files;
    }

    public function isUsedLot($nom)
    {
        // Check if it exists first
        $user = $this->get('security.context')->getToken()->getUser();
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonIntersaBundle:Saisie');
        $query = $repUsers->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->where("u.lot = :lot ")
            ->setParameter('lot', $nom)
            ->andwhere("u.user <> :user ")
            ->setParameter('user', $user)
            ->getQuery();

        if ($query->getSingleScalarResult() > 0) {
            return true;
        }

        return false;
    }

    public function isMyLot($nom)
    {
        // Check if it exists first
        $user = $this->get('security.context')->getToken()->getUser();
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonIntersaBundle:Saisie');
        $query = $repUsers->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->where("u.lot = :lot ")
            ->setParameter('lot', $nom)
            ->andwhere("u.user = :user ")
            ->setParameter('user', $user)
            ->getQuery();

        if ($query->getSingleScalarResult() > 0) {
            return true;
        }

        return false;
    }

    public function ajaxExistsAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->findOneBy(array('refDonateur' => $id, 'removed' => false));
        $form = $this->createForm(new SaisieType(), $donateur, array(
            'cascade_validation' => true));
        $data = null;
        if (is_object($donateur)) {

            if ($donateur->getAllowRf()) {
                $val = "true";
            } else {
                $val = "false";
            }
            $data['curville'] = $donateur->getIsoville();
            $data['allow_rf'] = $val;
            // format all the form

            $errors = array();
            // On récupère la requête
            $request = $this->getRequest();
        }

        return $this->render('FulldonIntersaBundle:Saisie:ajax/exists.html.twig', array('form' => $form->createView(), 'donateur' => $donateur, 'data' => $data));


    }

    public function ajaxExistsByNameAction($nom, $prenom)
    {
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $prosRep = $db->getRepository('FulldonIntersaBundle:ProspectionDonateur');
        $donateurs = $donateurRep->findBy(array('nom' => $nom, 'prenom' => $prenom, 'removed' => false));
        $pros = $prosRep->findBy(array('nom' => $nom, 'prenom' => $prenom));

        return $this->render('FulldonIntersaBundle:Saisie:ajax/list_exists.html.twig', array('donateurs' => $donateurs, 'pros' => $pros));
    }
    public function ajaxAddExistsByNameAction($nom, $prenom)
    {
        $cumul = 0.00;
        $date = date("Y-m-d H:i:s");
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $prosRep = $db->getRepository('FulldonIntersaBundle:ProspectionDonateur');
        $donateurs = $donateurRep->findBy(array('nom' => $nom, 'prenom' => $prenom, 'removed' => false));
        $pros = $prosRep->findBy(array('nom' => $nom, 'prenom' => $prenom));

        return $this->render('FulldonIntersaBundle:Donateurs/ajax:list_exists.html.twig', array(
            'donateurs' => $donateurs,
            'pros' => $pros,
            'cumul' => $cumul,
            'date' => $date
                ));
    }
    public function ajaxStatsAction($name, $type)
    {
        $db = $this->getDoctrine()->getManager();
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $saisie = $repSaisie->findOneBy(array('lot' => $name));
        $root = $this->container->getParameter('path_scan');
        $anomalie_folder = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'ANOMALIE';
        if (!is_dir($anomalie_folder)) {
            mkdir($anomalie_folder);
        }
        $file = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $name . '.XML';
        $xml = simplexml_load_file($file);
        $total_sequence = count($xml->Batch->Page);
        // Gestion des anomalies
        if ($total_sequence == 0) {

            rename($file, $anomalie_folder . DIRECTORY_SEPARATOR . $name . '.XML');
            return false;

        }
        if (in_array($type, $this->special_mode)) {
            $total_sequence = $total_sequence / 2;
        }

        if (is_object($saisie)) {

            $cursequence = $saisie->getSequence();
            $avancement = (($cursequence == 0) ? 0 : ($cursequence - 1) / $total_sequence) * 100;
        } else {
            $avancement = 0;
            $cursequence = 0;
        }
        ($cursequence == 0) ? $curseq = 0 : $curseq = $cursequence - 1;
        return $this->render('FulldonIntersaBundle:Saisie:ajax/stats.html.twig', array(
            'etat' => $this->isUsedLot($name),
            'mylot' => $this->isMyLot($name),
            'avancement' => round($avancement, 2) . '% (' . $curseq . '/' . $total_sequence . ')',
            'name' => $name,
            'type' => $type

        ));
    }

    public function isNewUser($login)
    {
        // Check if it exists first
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User');
        $query = $repUsers->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->where("u.username = :username ")
            ->setParameter('username', $login)
            ->getQuery();

        if ($query->getSingleScalarResult() > 0) {
            return false;
        }

        return true;
    }

    public function checkCodeActivite($code)
    {
        // Check if it exists first
        $db = $this->getDoctrine()->getManager();
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $query = $repCause->findOneBy(array('code' => $code));

        if (is_null($query)) {
            return false;
        }

        return true;
    }

    function validateDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public function anoAction()
    {
        $db = $this->getDoctrine()->getManager();
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $repAnomalie = $db->getRepository('FulldonIntersaBundle:Anomalie');
        $request = $this->getRequest();
        $anomalie = new Anomalie();
        $anoform = $this->createForm(new AnomalieType(), $anomalie, array(
            'cascade_validation' => true));
        $root = $this->container->getParameter('path_scan');
        $total_sequence = 0;
        $current_sequence = 0;
        $current_lot = '';
        $finished = false;
        if ($request->getMethod() == 'POST') {
            $anoform->bind($request);
            if ($anoform->isValid()) {
                //persistance
                $anomalie->setCorriger(false);
                $db->persist($anomalie);
                $db->flush();
                //Log
                $current_user = $this->get('security.context')->getToken()->getUser();
                $msg = $this->get('fulldon.intersa.global')->getAddMsgLog($anomalie, 'ANOMALIE');
                $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
                // Log the user creation
                $event = HistoryLogEvent::constr2($current_user, $typeLog, $msg);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);
                //Fin Log

                $nom = $anomalie->getLot();
                $type = $anomalie->getType();
                $current_lot = $nom;
                $file = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . '.XML';
                $done_folder = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'DONE';
                if (file_exists($file)) {
                    $xml = simplexml_load_file($file);
                    $total_sequence = count($xml->Batch->Page);
                }
                if (in_array($type, $this->special_mode)) {
                    $total_sequence = $total_sequence / 2;
                }

                if ($this->isMyLot($nom)) {
                    // update the lot
                    $saisie = $repSaisie->findOneBy(array('lot' => $nom));
                    $current_sequence = $saisie->getSequence();
                    switch ($type) {
                        case self::_BC_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 2 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                                $finished = true;
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 2);
                            }
                            break;
                        case self::_CS_:

                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 2 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                                $finished = true;
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 2);
                            }
                            break;
                        case self::_MIX_:
                        case self::_ESPECES_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 1 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                                $finished = true;
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 1);
                            }

                            break;
                        case self::_PA_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 1 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                                $finished = true;
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 1);
                            }
                            break;
                        case self::_VIREMENT_:
                            //Validation du lot et incrémentation de la séquence
                            if ($saisie->getSequence() + 1 >= $total_sequence) {
                                //validation de la séquence
                                //déplacer le fichier
                                rename($file, $done_folder . DIRECTORY_SEPARATOR . $nom . '.XML');
                                //Mise à jour de la base.
                                $saisie->setSequence($total_sequence + 1);
                                $saisie->setDone(true);
                                $finished = true;
                            } else {
                                //incrémentation de la séquence
                                $saisie->setSequence($saisie->getSequence() + 1);
                            }

                            break;
                    }
                }
                $this->get('session')->getFlashBag()->add('info', 'La séquence #' . $current_sequence . ' est flaguée comme anomalie ');
                $db->persist($saisie);
                $db->flush();
                //redirection et continuer la saisie en série
                if ($finished) {
                    $this->get('session')->getFlashBag()->add('info', 'Le Lot  #' . $current_lot . ' est terminé avec succès.');
                    return $this->redirect($this->generateUrl('intersa_saisie_serie'));
                } else {

                    return $this->redirect($this->generateUrl('intersa_saisie_serie_lot', array('type' => $type, 'nom' => $nom)));
                }

            }
        }


    }

    public function saisieAnomalieAction($type, $nom, $anomalie)
    {
        /* START OF INITILIZATION */

        $request = $this->getRequest();
        $errors = array();
        $champs = array(
            'id' => null,
            'montant' => null,
            'code' => null,
            'date_cheque' => null,
            'nom_banque' => null,
        );
        $montant = null;
        $code_activite = null;
        $num_cheque = null;
        $date_cheque = null;
        $nom_banque = null;
        $db = $this->getDoctrine()->getManager();
        $repMode = $db->getRepository('FulldonDonateurBundle:ModePaiement');
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $repPeriod = $db->getRepository('FulldonDonateurBundle:Periodicite');
        $repAnomalie = $db->getRepository('FulldonIntersaBundle:Anomalie');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        //Dans le cas ou l'anomlie n'existe pas.
        $ano = $repAnomalie->findOneBy(array('sequence' => $anomalie));
        if (!is_object($ano)) {
            $this->get('session')->getFlashBag()->add('info', 'L\'anomalie en question n\'existe pas ');
            return $this->redirect($this->generateUrl('intersa_saisie_anomalie', array('page' => 1)));
        }
        $root = $this->container->getParameter('path_scan');
        $file = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . '.XML';
        $donefile = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'DONE' . DIRECTORY_SEPARATOR . $nom . '.XML';
        //Création du dossier s'il n'existe pas
        if (file_exists($donefile)) {
            $file = $donefile;
        }
        $total_sequence = 0;
        if (file_exists($file)) {
            $xml = simplexml_load_file($file);
            $total_sequence = count($xml->Batch->Page);
        }

        /* END OF INITIALIZATION */

        $is_new = false;
        $user = $this->get('security.context')->getToken()->getUser();
        $donateur = new Donateur;
        $form = $this->createForm(new SaisieType(), $donateur, array(
            'cascade_validation' => true));
        $anoform = $this->createForm(new AnomalieType(), new Anomalie(), array(
            'cascade_validation' => true));
        if ($request->getMethod() == 'POST') {
            $allow_rf = $request->get('allow_rf');
            $champs['allow_rf'] = $allow_rf;
            $ids_donateur = $request->get('num_donateur');
            $id = $ids_donateur[0];
            if (isset($id) && !empty($id)) {
                //existing user
                $champs['id'] = $id;
                $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
                $donateur = $repDonateur->findOneBy(array('refDonateur' => $id));
                $form = $this->createForm(new SaisieType(), $donateur, array(
                    'cascade_validation' => true));
            } else {
                //new user
                $is_new = true;
                $duser = new User();
                $i = 0;
                $plain_password = null;
                do {
                    $i++;
                    $login = 'donateur' . $i;
                    $plain_password = base64_encode('p@s' . $i);
                } while (!$this->isNewUser($login));

                $duser->setUsername($login);
                $duser->setPassword($plain_password);
                $donateur->setUser($duser);
                // On enregistre notre objet $article dans la base de données
                $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                $donateur->getUser()->setIsActive(true);
                $repRole = $db->getRepository('FulldonSecurityBundle:Role');
                $role_donateur = $repRole->findOneBy(array('role' => 'ROLE_DONATEUR'));
                $donateur->getUser()->addRole($role_donateur);
                // Set encrypted password
                $encoder = $this->container->get('security.encoder_factory')
                    ->getEncoder($donateur->getUser());
                $password = $encoder->encodePassword($donateur->getUser()->getPassword(), $donateur->getUser()->getSalt());
                $donateur->getUser()->setPassword($password);
            }
            //Traitement de la transaction
            //RF donateurs
            if ($allow_rf == "true") {
                $donateur->setAllowRf(true);
            } else {
                $donateur->setAllowRf(false);
            }
            switch ($type) {
                case self::_BC_:
                    $num_cheque = $request->get('num_cheque');
                    $date_cheque = $request->get('date_cheque');
                    $champs['date_cheque'] = $date_cheque;
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;
                    if (!isset($date_cheque) || empty($date_cheque) || !$this->validateDate($date_cheque)) {

                        $errors['error_date_cheque'] = ' La date du chèque n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }
                    break;
                case self::_CS_:
                    $num_cheque = $request->get('num_cheque');
                    $date_cheque = $request->get('date_cheque');
                    $champs['date_cheque'] = $date_cheque;
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;
                    if (!isset($date_cheque) || empty($date_cheque) || !$this->validateDate($date_cheque)) {

                        $errors['error_date_cheque'] = ' La date du chèque n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }
                    break;
                case self::_PA_:
                    $bic = $request->get('bic');
                    $iban = $request->get('iban');
                    $date_first_pa = $request->get('date_first_pa');
                    $periodicite = $request->get('periodicite');
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;
                    $champs['bic'] = $bic;
                    $champs['iban'] = $iban;
                    $champs['date_first_pa'] = $date_first_pa;
                    $champs['periodicite'] = $periodicite;
                    $date_fin_pa = $request->get('date_fin_pa');
                    $champs['date_fin_pa'] = $date_fin_pa;
                    if (isset($date_fin_pa) && !empty($date_fin_pa) && !$this->validateDate($date_fin_pa)) {

                        $errors['error_date_fin_pa'] = ' La date du fin de l\'engagement n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($bic) || empty($bic)) {

                        $errors['error_bic'] = ' Veuillez spécifier un BIC valide';
                    }
                    if (!isset($iban) || empty($iban)) {

                        $errors['error_iban'] = ' Veuillez spécifier un IBAN valide';
                    }
                    if (!isset($date_first_pa) || empty($date_first_pa) || !$this->validateDate($date_first_pa)) {

                        $errors['error_date_first_pa'] = ' La date du premier prélevement n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }
                    break;
                case self::_VIREMENT_:
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;

                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }
                    break;
                case self::_MIX_:
                    $choice_type = $request->get('choice_type');
                    $champs['choice_type'] = $choice_type;

                    if (!isset($choice_type) || empty($choice_type)) {

                        $errors['error_choice_type'] = 'Veuillez spécifier le type du don';
                    }
                    break;
                default:
                    break;
            }
            //Contrôle des champs
            $montant = $request->get('montant');
            $champs['montant'] = $montant;
            $code_activite = $request->get('code_activite');
            $champs['code_activite'] = $code_activite;

            // controle des champs
            if (!isset($montant) || !is_numeric($montant)) {

                $errors['error_montant'] = ' Veuillez soumettre un montant valide ';
            }
            if (!$this->checkCodeActivite($code_activite)) {

                $errors['error_code_activite'] = ' Le code fourni n\'est pas valide ';
            }

            $form->bind($request);
            if ($form->isValid() && count($errors) == 0) {
                $donateur->setRemoved(false);
                if ($donateur->getRemoved()) {
                    $donateur->getUser()->setIsActive(false);
                } else {
                    $donateur->getUser()->setIsActive(true);
                }
                $db->persist($donateur);
                $db->flush();

                if (isset($id) && !empty($id)) {
                    //Stat
                    $current_user = $this->get('security.context')->getToken()->getUser();
                    // Log the user creation
                    $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_SAISIE_DONATEUR_OLD_);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(StatVar::CREATE, $event);
                    //Fin Stat
                    $db->persist($donateur);
                    $db->flush();

                } else {

                    //Stat
                    $current_user = $this->get('security.context')->getToken()->getUser();
                    // Log the user creation
                    $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_SAISIE_DONATEUR_NEW_);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(StatVar::CREATE, $event);
                    $refId = $this->get('fulldon.intersa.global')->getUniqueRefDonateur();
                    $donateur->setRefDonateur($refId);
                    //Gestion des doublons
                    $percent = 0;
                    $pid = 0;
                    $pdonateur = $repDonateur->findOneBy(array('nom' => $donateur->getNom(), 'prenom' => $donateur->getPrenom(), 'removed' => false));
                    if ($pdonateur) {
                        $percent += 60;
                        $pid = $pdonateur->getId();
                        $pdonateur2 = $repDonateur->findOneBy(array('adresse3' => $donateur->getAdresse3(), 'id' => $pid));
                        if ($pdonateur2) {
                            $percent += 30;
                        }
                        //Création d'un doublon
                        $doublon = new Doublon();
                        $doublon->setDone(false);
                        $doublon->setDonateur1($pid);


                        //Sauvegarder le nouveau donateur
                        $db->persist($donateur);
                        $db->flush();
                        //required to  retreive the donateur ID

                        $doublon->setDonateur2($donateur->getId());
                        $doublon->setPourcentage($percent);
                        $db->persist($doublon);
                        $db->flush();
                        $current_user = $this->get('security.context')->getToken()->getUser();
                        $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_DOUBLON_CREATED_);
                        $dispatcher = $this->get('event_dispatcher');
                        $dispatcher->dispatch(StatVar::CREATE, $event);
                        $this->get('session')->getFlashBag()->add('warning', 'Un doublon a été detecté il sera géreable dans la gestion des doublons ! ');

                    } else {

                        $db->persist($donateur);
                        $db->flush();
                    }
                    $msg = $this->get('log.helper')->getAddMsgLog($donateur, 'DONATEUR');
                    $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DONATEUR_);
                    $role = $this->get('log.helper')->getRole($current_user);
                    $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, null);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(LogVar::CREATE, $event);
                }
                if ($is_new) {
                    //Génération du de l'original.
                    $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->renderView(
                            'FulldonIntersaBundle:Donateurs:pdf/identifiants.html.twig',
                            array(
                                'login' => $login,
                                'password' => $plain_password,
                                'donateur' => $donateur,
                                'init' => $this->init,
                            )
                        ),
                        '/' . $this->container->getParameter('folder_app') . '/users/donateur_fiche_' . $donateur->getId() . '.pdf'
                    );

                }
                $this->get('session')->getFlashBag()->add('info', 'Saisie de la sequence est réussie  ');

            } else {
                // dans le cas de la non validité des informations on va stoquer les informations sur la variable form.error
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }
        //Initialisation
        $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);
        $mode = null;
        // update the lot

        if ($request->getMethod() == 'POST' && $form->isValid() && count($errors) == 0) {
            //Simulation de validation
            switch ($type) {
                case self::_BC_:
                    $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_));
                    $cause = $repCause->findOneBy(array('code' => $code_activite));
                    $cheque = new Cheque();
                    $cheque->setDateCheque(\DateTime::createFromFormat('d/m/Y', $date_cheque));
                    $cheque->setNomBanque($nom_banque);
                    $cheque->setNumeroCheque($num_cheque);
                    $transaction = new Transaction();
                    $transaction->setStatut($statut);
                    $transaction->setCheque($cheque);
                    $don = new Don();
                    $don->setSequence($anomalie);
                    $don->setLot($nom);
                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                    $don->setType($obj_type);
                    $don->setModePaiement($mode);
                    $don->setMontant($montant);
                    $don->setUser($donateur->getUser());
                    $don->setTransaction($transaction);
                    $don->setIspa(false);
                    $don->setCause($cause);
                    $db->persist($don);
                    $db->flush();
                    break;
                case self::_MIX_:

                    switch ($choice_type) {
                        case 'cbc':
                            $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_));
                            $transaction = new Transaction();
                            $transaction->setStatut($statut);
                            $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'BC'));
                            break;
                        case 'ces':
                            $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_ESPECE_MODE_));
                            $transaction = new Transaction();
                            $transaction->setStatut($statut);
                            $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'ESPECE'));
                            break;
                    }


                    $cause = $repCause->findOneBy(array('code' => $code_activite));
                    $don = new Don();
                    $don->setSequence($anomalie);
                    $don->setLot($nom);
                    $don->setLot($nom);
                    $don->setLot($nom);
                    $don->setLot($nom);
                    $don->setType($obj_type);
                    $don->setModePaiement($mode);
                    $don->setMontant($montant);
                    $don->setUser($donateur->getUser());
                    $don->setTransaction($transaction);
                    $don->setIspa(false);
                    $don->setCause($cause);
                    $db->persist($don);
                    $db->flush();
                    break;
                case self::_CS_:

                    $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_));
                    $cause = $repCause->findOneBy(array('code' => $code_activite));
                    $cheque = new Cheque();
                    $cheque->setDateCheque(\DateTime::createFromFormat('d/m/Y', $date_cheque));
                    $cheque->setNomBanque($nom_banque);
                    $cheque->setNumeroCheque($num_cheque);
                    $transaction = new Transaction();
                    $transaction->setStatut($statut);
                    $transaction->setCheque($cheque);

                    $don = new Don();
                    $don->setSequence($anomalie);
                    $don->setLot($nom);
                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                    $don->setType($obj_type);
                    $don->setModePaiement($mode);
                    $don->setMontant($montant);
                    $don->setUser($donateur->getUser());
                    $don->setTransaction($transaction);
                    $don->setIspa(false);
                    $don->setCause($cause);
                    $db->persist($don);
                    $db->flush();
                    break;
                case self::_ESPECES_:

                    $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_ESPECE_MODE_));
                    $cause = $repCause->findOneBy(array('code' => $code_activite));
                    $transaction = new Transaction();
                    $transaction->setStatut($statut);
                    $don = new Don();
                    $don->setSequence($anomalie);
                    $don->setLot($nom);
                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                    $don->setType($obj_type);
                    $don->setModePaiement($mode);
                    $don->setMontant($montant);
                    $don->setUser($donateur->getUser());
                    $don->setTransaction($transaction);
                    $don->setIspa(false);
                    $don->setCause($cause);
                    $db->persist($don);
                    $db->flush();
                    break;
                case self::_PA_:
                    $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_PA_MODE_));
                    $periode = $repPeriod->find($periodicite);
                    $cause = $repCause->findOneBy(array('code' => $code_activite));

                    $transaction = new Transaction();
                    $transaction->setStatut($statut);
                    $abonnement = new Abonnement();
                    $abonnement->setActif(true);
                    $abonnement->setPeriodicite($periode);
                    $abonnement->setDateFirstPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                    $abonnement->setDateNextPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                    if (isset($date_fin_pa) && !empty($date_fin_pa))
                        $abonnement->setDateFinPa(\DateTime::createFromFormat('d/m/Y', $date_fin_pa));
                    $abonnement->setBic($bic);
                    $abonnement->setIban($iban);
                    $abonnement->setRumIndice(0);
                    $abonnement->setNomBanque($nom_banque);
                    $don = new Don();
                    $don->setAbonnement($abonnement);
                    $don->setSequence($anomalie);
                    $don->setLot($nom);
                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                    $don->setType($obj_type);
                    $don->setModePaiement($mode);
                    $don->setMontant($montant);
                    $don->setUser($donateur->getUser());
                    $don->setTransaction($transaction);
                    $don->setIspa(true);
                    $don->setCause($cause);
                    $db->persist($don);
                    $db->flush();
                    //Gestion des Rums
                    $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                    $abonnement->setRum($rum);
                    $abonnement->setRumIndice(1);
                    $db->persist($abonnement);
                    $db->flush();
                    //Création d'un courrier en attente.
                    $courrierAttente = new CourrierAttente();
                    $courrierAttente->setDonateur($donateur);
                    $courrierAttente->setDon($don);
                    $courrierAttente->setTypeTraitements($db->getRepository('FulldonIntersaBundle:TypeTraitementCourrier')->findOneBy(array('code' => Vars\DonVars::_COURRIER_CREATE_PA_)));
                    $courrierAttente->setDone(false);
                    $db->persist($courrierAttente);
                    $db->flush();
                    // Fin des traitements des courriers en attente
                    break;
                case self::_VIREMENT_:
                    $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_VIREMENT_MODE_));
                    $cause = $repCause->findOneBy(array('code' => $code_activite));
                    $virement = new Virement();
                    $virement->setNomBanque($nom_banque);
                    $transaction = new Transaction();
                    $transaction->setStatut($statut);
                    $transaction->setVirement($virement);
                    $don = new Don();
                    $don->setSequence($anomalie);
                    $don->setLot($nom);
                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
                    $don->setType($obj_type);
                    $don->setModePaiement($mode);
                    $don->setMontant($montant);
                    $don->setUser($donateur->getUser());
                    $don->setTransaction($transaction);
                    $don->setIspa(false);
                    $don->setCause($cause);
                    $db->persist($don);
                    $db->flush();
                    break;
                default:
                    break;
            }

            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg = $this->get('log.helper')->getAddMsgLog($don, 'ANOMALIE:DON');
            $donateur = $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser()));
            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
            $role = $this->get('log.helper')->getRole($current_user);
            $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, $don);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);

            //Fin Log

            $ano->setCorriger(true);
            $db->persist($ano);
            $db->flush();
            $this->get('session')->getFlashBag()->add('info', 'La resaisie de l\'anomalie est effectuée avec succès ! ');
            return $this->redirect($this->generateUrl('intersa_saisie_anomalie', array('page' => 1)));
        }
        $sequence = $anomalie;
        $data = array(
            'nom' => '',
            'prenom' => '',
            'adresse1' => '',
            'adresse2' => '',
            'adresse3' => '',
            'adresse4' => '',
            'zipcode' => '',
            'ville' => ''
        );
        $data['image'] = array();

        if (file_exists($file)) {
            $front = false;
            foreach ($xml->Batch->Page as $page) {
                switch ($type) {
                    case self::_BC_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $sequence % 2 != 0 && $page->Fields->Side == 'F') {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;

                        } elseif ($page->Fields->SequenceNumber == $sequence + 1 && $sequence % 2 != 0 && $page->Fields->Side == 'F') {
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                            $chequenum = $page->Fields->CodeLineCMC7Field;
                            $chequenum = preg_replace("#[^0-9]#", "", $chequenum);
                            $data['chequenum'] = $chequenum;
                        }

                        break;
                    case self::_MIX_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $page->Fields->Side == 'F') {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber.'F';

                        } elseif ($page->Fields->SequenceNumber == $sequence && $page->Fields->Side == 'B') {
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber.'B';
                        }

                        break;
                    case self::_CS_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $page->Fields->Side == 'F') {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                            $chequenum = $page->Fields->CodeLineCMC7Field;
                            $chequenum = preg_replace("#[^0-9]#", "", $chequenum);
                            $data['chequenum'] = $chequenum;
                        }

                        break;
                    case self::_ESPECES_:
                    case self::_VIREMENT_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $front == false) {
                            $front = true;
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                            $chequenum = $page->Fields->CodeLineCMC7Field;
                            $chequenum = preg_replace("#[^0-9]#", "", $chequenum);
                            $data['chequenum'] = $chequenum;
                        }

                        break;
                    case self::_PA_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $front == false) {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                        }
                        break;
                    default:
                        break;
                }

            }
        } else {
            exit('Echec lors de l\'ouverture du fichier ' . $file . '.');
        }
        $periodes = $repPeriod->findAll();
        return $this->render('FulldonIntersaBundle:Saisie:anomalie.html.twig', array(
            'lot' => $nom,
            'type' => $type,
            'sequence' => $sequence,
            'total_sequence' => $total_sequence,
            'data' => $data,
            'form' => $form->createView(),
            'champs' => $champs,
            'anomalie' => $ano->getId(),
            'periodes' => $periodes
        ));

    }

    public function saisieLotCourrierAction($nom)
    {
        /* START OF INITILIZATION */

        $request = $this->getRequest();
        $errors = array();

        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $repProspection = $db->getRepository('FulldonDonateurBundle:Prospection');
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
        $repTypeCourrier = $db->getRepository('FulldonIntersaBundle:TypeTraitementCourrier');

        $all_courrier_type = $repTypeCourrier->findBy(array('visible' => true));
        $champs = array();
        $type = 'COURRIER';
        $next_sequence = null;
        $root = $this->container->getParameter('path_scan');
        $file = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . '.XML';
        $done_folder = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'DONE';
        //Création du dossier s'il n'existe pas
        if (!is_dir($done_folder)) {
            mkdir($done_folder);
        }
        $saisie = $repSaisie->findOneBy(array('lot' => $nom));
        $total_sequence = 0;
        if (file_exists($file)) {
            $xml = simplexml_load_file($file);
            $xml2 = clone $xml;
            $total_sequence = count($xml->Batch->Page);
        }
        if (in_array($type, $this->special_mode)) {
            $total_sequence = $total_sequence / 2;
        }
        /* END OF INITIALIZATION */

        $is_new = false;
        $errors = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $donateur = new Donateur;
        $sequence = null;
        if (is_object($saisie)) {
            $sequence = $saisie->getSequence();
        } else {
            $sequence = 1;
            $saisie = new Saisie();
            $saisie->setLot($nom);
            $saisie->setUser($user);
            $saisie->setType($type);
            $saisie->setSequence($sequence);
            $saisie->setDone(false);
            $saisie->setCreatedAt(new \Datetime());
            $db->persist($saisie);
            $db->flush();
        }

        if ($request->getMethod() == 'POST') {
            $id = $request->get('donateur_id');
            $type_traitement = $request->get('type_traitement');
            $escape = $request->get('escape');
            if (isset($escape) && !empty($escape)) {

                if (file_exists($file)) {

                    $front = false;
                    foreach ($xml->Batch->Page as $page) {
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->DocType == 400 && $page->Fields->SequenceNumber > $sequence) {
                            $next_sequence = $page->Fields->SequenceNumber;
                            break;
                        }
                    }

                } else {
                    exit('Echec lors de l\'ouverture du fichier ' . $file . '.');
                }

                $saisie->setCreatedAt(new \Datetime());
                $saisie->setSequence($next_sequence);
                $saisie->setDone(false);
                $db->persist($saisie);
                $db->flush();

                $timeSaisie = new TimeSaisie();
                $timeSaisie->setUser($user);
                $timeSaisie->setSaisie($saisie);
                $timeSaisie->setSequence($saisie->getSequence());
                $timeSaisie->setTemps(round(abs($saisie->getCreatedAt()->getTimestamp() - (new \Datetime())->getTimestamp()) / 60, 2));
                $db->persist($timeSaisie);
                $this->get('session')->getFlashBag()->add('info', 'Séquence ignorée avec succès ! ');
                return $this->redirect($this->generateUrl('intersa_saisie_serie_courrier_lot', array('nom' => $nom)));
            }
            if (!isset($id) || empty($id)) {

                $errors['error_id_donateur'] = ' La selection d\'un donateur est obligatoire';
            }
            if (!isset($type_traitement) || empty($type_traitement)) {

                $errors['error_type_traitement'] = 'Veuillez au moins un type de traitement';
            }
            if (count($errors) == 0) {
                $donateur = $repDonateur->findOneBy(array('refDonateur' => $id));
                // Création d'un  CourrierTraitement.
                $courrier = new CourrierTraitement();
                $courrier->setSequence($sequence);
                $courrier->setLot($nom);
                $courrier->setDonateur($donateur);
                $courrier->setDone(true);
                $db->persist($courrier);
                $db->flush();

                if (file_exists($file)) {

                    $front = false;
                    foreach ($xml->Batch->Page as $page) {

                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->DocType != 400 && $page->Fields->SequenceNumber > $sequence) {
                            //Je commence le traitement
                            $courrier_doc = new CourrierDoc();
                            $courrier_doc->setCourrierTraitement($courrier);
                            $courrier_doc->setNom($nom . $page->Fields->SequenceNumber);
                            $db->persist($courrier_doc);
                            $db->flush();
                        }

                        if ($page->Fields->DocType == 400 && $page->Fields->SequenceNumber > $sequence) {
                            $next_sequence = $page->Fields->SequenceNumber;
                            break;

                        }

                    }

                } else {
                    exit('Echec lors de l\'ouverture du fichier ' . $file . '.');
                }
                //Sauvegarder les informations
                //Test et contrôle des informations
                $user = $this->get('security.context')->getToken()->getUser();
                $msg = $this->get('fulldon.intersa.global')->getAddMsgLog($courrier, 'COURRIER');
                $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DONATEUR_);
                // Log the user creation
                $event = HistoryLogEvent::constr1($user, $donateur, $typeLog, $msg);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);


                $saisie->setCreatedAt(new \Datetime());
                $saisie->setSequence($next_sequence);
                $saisie->setDone(false);
                $db->persist($saisie);
                $db->flush();


                $timeSaisie = new TimeSaisie();
                $timeSaisie->setUser($user);
                $timeSaisie->setSaisie($saisie);
                $timeSaisie->setSequence($saisie->getSequence());
                $timeSaisie->setTemps(round(abs($saisie->getCreatedAt()->getTimestamp() - (new \Datetime())->getTimestamp()) / 60, 2));
                $db->persist($timeSaisie);
                $this->get('session')->getFlashBag()->add('info', 'Séquence terminée avec succès ! ');
                return $this->redirect($this->generateUrl('intersa_saisie_serie_courrier_lot', array('nom' => $nom)));
            } else {
                // erreur
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }

        if (!is_null($next_sequence)) {
            $sequence = $next_sequence;
        }
        if ($this->isUsedLot($nom)) {
            $saisie = $repSaisie->findOneBy(array('lot' => $nom));
            $space = round(abs($saisie->getCreatedAt()->getTimestamp() - (new \Datetime())->getTimestamp()) / 60);
            if ($space >= 60) {
                //update
                $saisie->setUser($user);
                $saisie->setCreatedAt(new \Datetime());
                $db->persist($saisie);
                $db->flush();
            } else {
                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Veuillez SVP Essayer dans ' . (60 - $space) . ' minutes ');
                // On redirige vers la page de visualisation de l'article nouvellement créé
                return $this->redirect($this->generateUrl('intersa_saisie_serie'));
            }
        }


        $data = array();
        $data['image'] = array();

        if (file_exists($file)) {

            foreach ($xml->Batch->Page as $page) {


                $patharr = explode('\\', $page['Path']);
                $originimg = end($patharr);
                if ($page->Fields->DocType != 400 && $page->Fields->SequenceNumber > $sequence) {
                    //Je commence le traitement
                    $data['image'][] = $nom . $page->Fields->SequenceNumber;
                    $image = new \Imagick($root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . DIRECTORY_SEPARATOR . $originimg);
                    $image->setImageFormat('png');
                    $image->writeImage($this->container->getParameter('scan_convert') . '/' . $this->container->getParameter('folder_app') . '/secureimg' . DIRECTORY_SEPARATOR . $nom . $page->Fields->SequenceNumber . '.png');
                }

                if ($page->Fields->DocType == 400 && $page->Fields->SequenceNumber > $sequence) {
                    break;
                }

            }

        } else {
            exit('Echec lors de l\'ouverture du fichier ' . $file . '.');
        }

        return $this->render('FulldonIntersaBundle:Saisie:courrier.html.twig', array(
            'lot' => $nom,
            'type' => $type,
            'sequence' => $sequence,
            'total_sequence' => $total_sequence,
            'data' => $data,
            'champs' => $champs,
            'all_courrier_type' => $all_courrier_type
        ));

    }
}
