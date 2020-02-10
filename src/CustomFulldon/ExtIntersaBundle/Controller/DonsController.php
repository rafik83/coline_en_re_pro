<?php

namespace CustomFulldon\ExtIntersaBundle\Controller;

use CustomFulldon\ExtDonateurBundle\Entity\Note;
use Fulldon\IntersaBundle\Controller\DonsController as BaseController;
use Fulldon\DonateurBundle\Entity\MotifRejetPrelevement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Rf;
use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\DonateurBundle\Entity\Cause;
use Fulldon\DonateurBundle\Entity\Cheque;
use Fulldon\DonateurBundle\Form\DonSearchType;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\IntersaBundle\Entity\Saisie;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Fulldon\IntersaBundle\Vars;
use Fulldon\DonateurBundle\Entity\Transaction;
use Fulldon\DonateurBundle\Entity\Abonnement;
use Fulldon\DonateurBundle\Entity\Virement;
use Fulldon\IntersaBundle\Entity\CourrierAttente;
use JMS\SecurityExtraBundle\Annotation\SatisfiesParentSecurityPolicy;

class DonsController extends BaseController {

    const _BC_ = 'BC';
    const _CS_ = 'CS';
    const _ESPECES_ = 'ESPECE';
    const _PA_ = 'PA';
    const _VIREMENT_ = 'VIREMENT';
    const _INTERNET_ = 'INTERNET';
    const _MIX_ = 'MIX';

    /**
     * @SatisfiesParentSecurityPolicy
     */
    public function editAction($id) {

        
        
//        die('icici');
        $errors = array();
        $data = array();
        $type = null;
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $repPeriod = $db->getRepository('FulldonDonateurBundle:Periodicite');
        $repNote = $db->getRepository('CustomFulldonExtDonateurBundle:Note');
        $don = $repDon->find($id);
        $currentNote = $repNote->findOneBy(array('don' => $don));
        $nom = $don->getLot();
        if (is_object($don->getType()))
            $type = $don->getType()->getCode();
        $sequence = $don->getSequence();
        $request = $this->getRequest();
        $periodes = $repPeriod->findAll();
        $champs = array();
        if ($request->getMethod() == 'POST') {

            //Contrôle des champs
            if (count($don->getRfs()) >= 0) {
                $montant = $request->get('montant');
                $champs['montant'] = $montant;
                if (!isset($montant) || !is_numeric($montant)) {

                    $errors['error_montant'] = ' Veuillez soumettre un montant valide ';
                }
            }
            $code_activite = $request->get('code_activite');
            $champs['code_activite'] = $code_activite;
            $date_fiscale = $request->get('date_fiscale');
            $champs['date_fiscale'] = $date_fiscale;
            if (!$this->checkCodeActivite($code_activite)) {

                $errors['error_code_activite'] = ' Le code fourni n\'est pas valide ';
            }
            if (isset($date_fiscale) && !empty($date_fiscale) && !$this->validateDate($date_fiscale)) {

                $errors['error_date_fiscale'] = ' La date fiscale n\'est pas valide : jj/mm/aaaa';
            }

            switch ($type) {
                case self::_BC_:
                    if (count($don->getRfs()) == 0) {

                        $num_cheque = $request->get('num_cheque');
                        $date_cheque = $request->get('date_cheque');
                        $champs['date_cheque'] = $date_cheque;
                        $nom_banque = $request->get('nom_banque');
                        $champs['nom_banque'] = $nom_banque;
//                                if(!isset($date_cheque) || empty($date_cheque) || !$this->validateDate($date_cheque)) {
//
//                                    $errors['error_date_cheque'] = ' La date du chèque n\'est pas valide : jj/mm/aaaa' ;
//                                }
//                                if(!isset($nom_banque) || empty($nom_banque) ) {
//
//                                    $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque' ;
//                                }
                    }
                    if (count($errors) == 0) {
                        if (count($don->getRfs()) == 0) {
                            if (!is_object($don->getTransaction()->getCheque())) {
                                $cheque = new Cheque();
                                $don->getTransaction()->setCheque($cheque);
                            }
                            $don->getTransaction()->getCheque()->setNumeroCheque($num_cheque);
                            if (isset($date_cheque) && !empty($date_cheque)) {
                                $don->getTransaction()->getCheque()->setDateCheque(\DateTime::createFromFormat('d/m/Y', $date_cheque));
                            }
                            $don->getTransaction()->getCheque()->setNomBanque($nom_banque);
                            $don->setMontant($montant);
                        }
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $don->setCause($cause);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        else {
                            $don->setDateFiscale(null);
                        }
                    }
                    break;
                case self::_CS_:
                    if (count($don->getRfs()) == 0) {
                        $num_cheque = $request->get('num_cheque');
                        $date_cheque = $request->get('date_cheque');
                        $champs['date_cheque'] = $date_cheque;
                        $nom_banque = $request->get('nom_banque');
                        $champs['nom_banque'] = $nom_banque;
//                        if(!isset($date_cheque) || empty($date_cheque) || !$this->validateDate($date_cheque)) {
//
//                            $errors['error_date_cheque'] = ' La date du chèque n\'est pas valide : jj/mm/aaaa' ;
//                        }
//                        if(!isset($nom_banque) || empty($nom_banque) ) {
//
//                            $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque' ;
//                        }
                    }
                    if (count($errors) == 0) {
                        if (count($don->getRfs()) == 0) {
                            if (!is_object($don->getTransaction()->getCheque())) {
                                $cheque = new Cheque();
                                $don->getTransaction()->setCheque($cheque);
                            }
                            $don->getTransaction()->getCheque()->setNumeroCheque($num_cheque);
                            if (isset($date_cheque) && !empty($date_cheque)) {
                                $don->getTransaction()->getCheque()->setDateCheque(\DateTime::createFromFormat('d/m/Y', $date_cheque));
                            }
                            $don->getTransaction()->getCheque()->setNomBanque($nom_banque);
                            $don->setMontant($montant);
                        }
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $don->setCause($cause);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                    }
                    break;
                case self::_PA_:
                    //if(count($don->getRfs()) == 0) {

                    $bic = $request->get('bic');
                    $bic = strtoupper($bic);
                    $iban = $request->get('iban');
                    $date_first_pa = $request->get('date_first_pa');
                    $periodicite = $request->get('periodicite');
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;
                    $champs['bic'] = $bic;
                    $champs['iban'] = $iban;
                    $champs['date_first_pa'] = $date_first_pa;
                    $champs['periodicite'] = $periodicite;
                    $donateur = $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser()));
                    $date_fin_pa = $request->get('date_fin_pa');
                    $champs['date_fin_pa'] = $date_fin_pa;
                    if (isset($date_fin_pa) && !empty($date_fin_pa) && !$this->validateDate($date_fin_pa)) {

                        $errors['error_date_fin_pa'] = ' La date du fin de l\'engagement n\'est pas valide : jj/mm/aaaa';
                    }
                    if (!isset($bic) || empty($bic) || !$this->checkBic($bic)) {

                        $errors['error_bic'] = ' Veuillez spécifier un BIC valide';
                    }
                    if (!isset($iban) || empty($iban)) {

                        $errors['error_iban'] = ' Veuillez spécifier un IBAN valide';
                    }
                    if (!isset($date_first_pa) || empty($date_first_pa) || !$this->validateDate($date_first_pa)) {

                        $errors['error_date_first_pa'] = ' La date du premier prélevement n\'est pas valide : jj/mm/aaaa';
                    }

                    // }
                    if (count($errors) == 0) {
                        //if(count($don->getRfs()) == 0) {
                        if ($don->getAbonnement()->getIban() != $iban && $don->getAbonnement()->getBic() != $bic) {
                            $don->getAbonnement()->setPreFirst(true);
                        }
                        //
                        if ($don->getAbonnement()->getIban() != $iban || $don->getAbonnement()->getBic() != $bic) {
                            //Création d'un courrier en attente.
                            $courrierAttente = new CourrierAttente();
                            $courrierAttente->setDonateur($donateur);
                            $courrierAttente->setDon($don);
                            $courrierAttente->setTypeTraitements($db->getRepository('FulldonIntersaBundle:TypeTraitementCourrier')->findOneBy(array('code' => Vars\DonVars::_COURRIER_MAJ_COB_)));
                            $courrierAttente->setDone(false);
                            $db->persist($courrierAttente);
                            $db->flush();
                            // Fin des traitements des courriers en attente
                        }
                        if ($don->getMontant() != $montant) {
                            //Création d'un courrier en attente.
                            $courrierAttente = new CourrierAttente();
                            $courrierAttente->setDonateur($donateur);
                            $courrierAttente->setDon($don);
                            $courrierAttente->setTypeTraitements($db->getRepository('FulldonIntersaBundle:TypeTraitementCourrier')->findOneBy(array('code' => Vars\DonVars::_COURRIER_MAJ_MO_PA_)));
                            $courrierAttente->setDone(false);
                            $db->persist($courrierAttente);
                            $db->flush();
                            // Fin des traitements des courriers en attente
                        }
                        $periode = $repPeriod->find($periodicite);
                        if ($don->getAbonnement()->getPeriodicite() != $periode) {
                            //Création d'un courrier en attente.
                            $courrierAttente = new CourrierAttente();
                            $courrierAttente->setDonateur($donateur);
                            $courrierAttente->setDon($don);
                            $courrierAttente->setTypeTraitements($db->getRepository('FulldonIntersaBundle:TypeTraitementCourrier')->findOneBy(array('code' => Vars\DonVars::_COURRIER_MAJ_PE_PA_)));
                            $courrierAttente->setDone(false);
                            $db->persist($courrierAttente);
                            $db->flush();
                            // Fin des traitements des courriers en attente
                        }
                        $don->getAbonnement()->setIban($iban);
                        $don->getAbonnement()->setBic($bic);
                        $don->getAbonnement()->setNomBanque($nom_banque);
                        $don->getAbonnement()->setDateFirstPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                        if (isset($date_fin_pa) && !empty($date_fin_pa))
                            $don->getAbonnement()->setDateFinPa(\DateTime::createFromFormat('d/m/Y', $date_fin_pa));
                        else {
                            $don->getAbonnement()->setDateFinPa(null);
                        }

                        $don->getAbonnement()->setPeriodicite($periode);
                        $don->setMontant($montant);
                        //}
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $don->setCause($cause);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        else {
                            $don->setDateFiscale(null);
                        }
                    }
                    break;
                case self::_VIREMENT_:
                    if (count($don->getRfs()) == 0) {
                        $nom_banque = $request->get('nom_banque');
                        $champs['nom_banque'] = $nom_banque;
                    }
                    if (count($errors) == 0) {
                        if (count($don->getRfs()) == 0) {
                            $don->getTransaction()->getVirement()->setNomBanque($nom_banque);
                            $don->setMontant($montant);
                        }
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $don->setCause($cause);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        else {
                            $don->setDateFiscale(null);
                        }
                    }
                    break;
                default:
                    if (count($errors) == 0) {
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $don->setCause($cause);
                        if (count($don->getRfs()) == 0) {
                            $don->setMontant($montant);
                        }
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        else {
                            $don->setDateFiscale(null);
                        }
                    }
                    break;
            }


            if (count($errors) == 0) {
                if (!is_object($currentNote)) {
                    $currentNote = new Note();
                }

                //Gestion des notes
                $q1 = $request->get('q1');
                $q2 = $request->get('q2');
                $q3 = $request->get('q3');
                $q4 = $request->get('q4');
                $q5 = $request->get('q5');
                $q6 = $request->get('q6');
                $q7 = $request->get('q7');
                $q8 = $request->get('q8');
                $q9 = $request->get('q9');
                $q10 = $request->get('q10');

                $isnotes = $request->get('isnotes');
                if ($isnotes == 1) {
                    $currentNote->setIsNote(1);
                } else {
                    $currentNote->setIsNote(0);
                }
                if ($isnotes != 1) {
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q1)) {
                        $currentNote->setQ1($q1);
                    } else {
                        $currentNote->setQ1(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q2)) {
                        $currentNote->setQ2($q2);
                    } else {
                        $currentNote->setQ2(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q3)) {
                        $currentNote->setQ3($q3);
                    } else {
                        $currentNote->setQ3(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q4)) {
                        $currentNote->setQ4($q4);
                    } else {
                        $currentNote->setQ4(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q5)) {
                        $currentNote->setQ5($q5);
                    } else {
                        $currentNote->setQ5(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q6)) {
                        $currentNote->setQ6($q6);
                    } else {
                        $currentNote->setQ6(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q7)) {
                        $currentNote->setQ7($q7);
                    } else {
                        $currentNote->setQ7(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q8)) {
                        $currentNote->setQ8($q8);
                    } else {
                        $currentNote->setQ8(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q9)) {
                        $currentNote->setQ9($q9);
                    } else {
                        $currentNote->setQ9(null);
                    }
                    if ($this->get('extfulldon.intersa.factorize')->checkNote($q10)) {
                        $currentNote->setQ10($q10);
                    } else {
                        $currentNote->setQ10(null);
                    }
                } else {
                    $currentNote->setQ1(null);
                    $currentNote->setQ2(null);
                    $currentNote->setQ3(null);
                    $currentNote->setQ4(null);
                    $currentNote->setQ5(null);
                    $currentNote->setQ6(null);
                    $currentNote->setQ7(null);
                    $currentNote->setQ8(null);
                    $currentNote->setQ9(null);
                    $currentNote->setQ10(null);
                }

                //Log
                $current_user = $this->get('security.context')->getToken()->getUser();
                $msg = $this->get('log.helper')->getModMsgLog($don, 'DON');
                if (is_object($don->getAbonnement())) {
                    $msg .= "\n" . $this->get('log.helper')->getModMsgLog($don->getAbonnement(), 'ABONNNEMENT');
                }
                $donateur = $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser()));
                $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_DON_MOD_);
                $role = $this->get('log.helper')->getRole($current_user);
                $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, $don);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);
                //Fin Log


                $db->flush();
                if (is_object($currentNote)) {
                    $currentNote->setDon($don);
                    $db->persist($currentNote);
                    $db->flush();
                }
                $this->get('session')->getFlashBag()->add('info', 'Modification effectuée avec succès ! ');
                return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $id)));
            } else {
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }
        if (isset($type) && !empty($type) && isset($nom) && !empty($nom) && isset($sequence) && !empty($sequence)) {
            $data = array();

            switch ($type) {
                case self::_BC_:
                    //Je commence le traitement
                    $data['image'][] = $nom . '_' . $sequence;
                    $data['image'][] = $nom . '_' . ($sequence + 1);
                    break;
                case self::_CS_:

                    //Je commence le traitement
                    $data['image'][] = $nom . '_' . $sequence;
                    break;
                case self::_ESPECES_:
                    $data['image'][] = $nom . '_' . $sequence;
                    break;
                case self::_VIREMENT_:
                    $data['image'][] = $nom . '_' . $sequence;
                    break;
                case self::_PA_:
                    //Je commence le traitement
                    $data['image'][] = $nom . '_' . $sequence;
                    break;
                case self::_MIX_:
                    $data['image'][] = $nom . '_' . $sequence . 'F';
                    $data['image'][] = $nom . '_' . $sequence . 'B';
                    break;
                default:
                    break;
            }
        }

        //$mode =  $don->getModePaiement()->getCodeSolution();
        if (is_object($currentNote)) {
            if ($currentNote->getIsNote() == 1)
                $data['isnotes'] = 1;
            else {
                $data['isnotes'] = 0;
            }
            $data['q1'] = $currentNote->getQ1();
            $data['q2'] = $currentNote->getQ2();
            $data['q3'] = $currentNote->getQ3();
            $data['q4'] = $currentNote->getQ4();
            $data['q5'] = $currentNote->getQ5();
            $data['q6'] = $currentNote->getQ6();
            $data['q7'] = $currentNote->getQ7();
            $data['q8'] = $currentNote->getQ8();
            $data['q9'] = $currentNote->getQ9();
            $data['q10'] = $currentNote->getQ10();
        }
        $data['montant'] = $don->getMontant();
        $data['cause'] = $don->getCause();
        $data['date_fiscale'] = $don->getDateFiscale();
        $data['id'] = $don->getId();
        if (count($don->getRfs()) == 0) {
            $data['rf'] = false;
        } else {
            $data['rf'] = true;
        }
        switch ($type) {
            case self::_BC_:
            case self::_CS_:
                if (is_object($don->getTransaction()->getCheque())) {
                    $data['num_cheque'] = $don->getTransaction()->getCheque()->getNumeroCheque();
                    $data['date_cheque'] = $don->getTransaction()->getCheque()->getDateCheque();
                    $data['nom_banque'] = $don->getTransaction()->getCheque()->getNomBanque();
                }
                break;
            case self::_PA_:


                $data['mode'] = $don->getModePaiement()->getCodeSolution();
                $data['date_first_pa'] = $don->getAbonnement()->getDateFirstPa();
                $data['date_fin_pa'] = $don->getAbonnement()->getDateFinPa();
                if (is_object($don->getAbonnement()->getPeriodicite())) {
                    $data['periodicite'] = $don->getAbonnement()->getPeriodicite()->getId();
                }
                $data['bic'] = $don->getAbonnement()->getBic();
                $data['iban'] = $don->getAbonnement()->getIban();
                $data['nom_banque'] = $don->getAbonnement()->getNomBanque();
                break;
            case self::_VIREMENT_:
                if (is_object($don->getTransaction()->getVirement()))
                    $data['nom_banque'] = $don->getTransaction()->getVirement()->getNomBanque();
                else
                    $data['nom_banque'] = '';
                break;
        }

// switch mode de paiement
        // cheque
        // PA
        // ESPECE

        return $this->render('FulldonIntersaBundle:Dons:edit.html.twig', array(
                    'don' => $don,
                    'data' => $data,
                    'type' => $type,
                    'periodes' => $periodes,
                    'champs' => $champs,
        ));
    }

    public function showAction($id) {



//        die('showAction + dons controller ExtIntersaBundle');
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $repPrelevement = $db->getRepository('FulldonDonateurBundle:Prelevement');
        $user = $this->get('security.context')->getToken()->getUser();
        $don = $repDon->findOneBy(array('id' => $id));




        $repNote = $db->getRepository('CustomFulldonExtDonateurBundle:Note');
//        $id = 606 ;
        $don = $repDon->find($id);
//         var_dump($don);
//        die('don');
//        var_dump($don->getRfs());
//        die('rf');
        $currentNote = $repNote->findOneBy(array('don' => $don));

        $eclates = null;
        if ($don->getEclat() != null) {
            $eclates = $repDon->findBy(array('eclat' => $don->getEclat()));
        }
        $donateur = $repDonateur->findOneBy(array('user' => $don->getUser()));
        $pr = $repPrelevement->findBy(array('abo' => $don->getAbonnement()));
        $repMotif = $db->getRepository('FulldonDonateurBundle:MotifAbo');
        $motifs = $repMotif->findAll();
        $cumul = 0.00;
        $date = date("Y-m-d H:i:s");
        return $this->render('FulldonIntersaBundle:Dons:view.html.twig', array(
                    'note' => $currentNote,
                    'don' => $don,
                    'donateur' => $donateur,
                    'prelevements' => $pr,
                    'motifs' => $motifs,
                    'eclates' => $eclates,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    function dqlDate($date, $format = 'd/m/Y') {
//        die('1');

        $d = \DateTime::createFromFormat($format, $date);
        return $d->format('Y-m-d');
    }

    function validateDate($date, $format = 'd/m/Y') {
//        die('1');

        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * @SatisfiesParentSecurityPolicy
     */
    public function addDonAction($id, $type) {
//        die('add_don + exintersabundle');
        $errors = array();
        $db = $this->getDoctrine()->getManager();
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $repMode = $db->getRepository('FulldonDonateurBundle:ModePaiement');
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
        $donateur = $repDonateur->find($id);
        $dataxx = $this->getCumulDernierDonBy($id);
        $cumul = $cumul = $dataxx[0]['cumul'];
        $date = $dataxx[0]['dernier_don'];
        $repPeriod = $db->getRepository('FulldonDonateurBundle:Periodicite');
        $repProspection = $db->getRepository('FulldonDonateurBundle:Prospection');
        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => $type));
        $request = $this->getRequest();
        $periodes = $repPeriod->findAll();
        $data = array();
        $champs = array();
        $statut = $repStatut->find(Vars\DonVars::_STATUT_DON_VALIDE_);
        $modes = $repMode->findBy(array('type' => $obj_type));
        if ($request->getMethod() == 'POST') {
//            die('POST');
            //Contrôle des champs
            $don = new Don();
            $montant = $request->get('montant');
            $champs['montant'] = $montant;
            if (!isset($montant) || !is_numeric($montant)) {

                $errors['error_montant'] = ' Veuillez soumettre un montant valide ';
            }
            $code_activite = $request->get('code_activite');
            $champs['code_activite'] = $code_activite;
            $date_fiscale = $request->get('date_fiscale');
            $champs['date_fiscale'] = $date_fiscale;
            if (!$this->checkCodeActivite($code_activite)) {

                $errors['error_code_activite'] = ' Le code fourni n\'est pas valide ';
            }
            if (isset($date_fiscale) && !empty($date_fiscale) && !$this->validateDate($date_fiscale)) {

                $errors['error_date_fiscale'] = ' La date fiscale n\'est pas valide : jj/mm/aaaa';
            }
            $q1 = $request->get('q1');
            $q2 = $request->get('q2');
            $q3 = $request->get('q3');
            $q4 = $request->get('q4');
            $q5 = $request->get('q5');
            $q6 = $request->get('q6');
            $q7 = $request->get('q7');
            $q8 = $request->get('q8');
            $q9 = $request->get('q9');
            $q10 = $request->get('q10');

            $isnotes = $request->get('isnotes');

            $note = new Note();
            if ($isnotes == 1) {
                $note->setIsNote(1);
            } else {
                $note->setIsNote(0);
            }
            if ($isnotes != 1) {
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q1)) {
                    $note->setQ1($q1);
                } else {
                    $note->setQ1(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q2)) {
                    $note->setQ2($q2);
                } else {
                    $note->setQ2(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q3)) {
                    $note->setQ3($q3);
                } else {
                    $note->setQ3(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q4)) {
                    $note->setQ4($q4);
                } else {
                    $note->setQ4(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q5)) {
                    $note->setQ5($q5);
                } else {
                    $note->setQ5(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q6)) {
                    $note->setQ6($q6);
                } else {
                    $note->setQ6(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q7)) {
                    $note->setQ7($q7);
                } else {
                    $note->setQ7(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q8)) {
                    $note->setQ8($q8);
                } else {
                    $note->setQ8(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q9)) {
                    $note->setQ9($q9);
                } else {
                    $note->setQ9(null);
                }
                if ($this->get('extfulldon.intersa.factorize')->checkNote($q10)) {
                    $note->setQ10($q10);
                } else {
                    $note->setQ10(null);
                }
            }

            switch ($type) {
                case self::_BC_:
                case self::_CS_:

                    $num_cheque = $request->get('num_cheque');
                    $date_cheque = $request->get('date_cheque');
                    $champs['date_cheque'] = $date_cheque;
                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;
//                        if(!isset($date_cheque) || empty($date_cheque) || !$this->validateDate($date_cheque)) {
//
//                            $errors['error_date_cheque'] = ' La date du chèque n\'est pas valide : jj/mm/aaaa' ;
//                        }
//                        if(!isset($nom_banque) || empty($nom_banque) ) {
//
//                            $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque' ;
//                        }

                    if (count($errors) == 0) {
                        $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_));
                        $cheque = new Cheque();
                        if (isset($date_cheque) && !empty($date_cheque)) {
                            $cheque->setDateCheque(\DateTime::createFromFormat('d/m/Y', $date_cheque));
                        }
                        $cheque->setNomBanque($nom_banque);
                        $cheque->setNumeroCheque($num_cheque);
                        $transaction = new Transaction();
                        $transaction->setStatut($statut);
                        $transaction->setCheque($cheque);
                        $don->setTransaction($transaction);
                        $don->setMontant($montant);
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                        if (is_object($pros)) {
                            $pros->setRetour(true);
                        }
                        $don->setIspa(false);
                        $don->setUser($donateur->getUser());
                        $don->setModePaiement($mode);
                        $don->setCause($cause);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        $don->setType($obj_type);
                        $db->persist($don);
                        $db->flush();
                    }
                    break;
                case self::_PA_:

                    $bic = $request->get('bic');
                    $bic = strtoupper($bic);
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
                    if (!isset($bic) || empty($bic) || !$this->checkBic($bic)) {

                        $errors['error_bic'] = ' Veuillez spécifier un BIC valide';
                    }
                    if (!isset($iban) || empty($iban)) {

                        $errors['error_iban'] = ' Veuillez spécifier un IBAN valide';
                    }
                    if (!isset($date_first_pa) || empty($date_first_pa) || !$this->validateDate($date_first_pa)) {

                        $errors['error_date_first_pa'] = ' La date du premier prélevement n\'est pas valide : jj/mm/aaaa';
                    }


                    if (count($errors) == 0) {
                        $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_PA_MODE_));

                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                        if (is_object($pros)) {
                            $pros->setRetour(true);
                        }

                        $abonnement = new Abonnement();
                        $abonnement->setActif(true);
                        $periode = $repPeriod->find($periodicite);
                        $abonnement->setPeriodicite($periode);
                        $abonnement->setDateFirstPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                        $abonnement->setDateNextPa(\DateTime::createFromFormat('d/m/Y', $date_first_pa));
                        if (isset($date_fin_pa) && !empty($date_fin_pa))
                            $abonnement->setDateFinPa(\DateTime::createFromFormat('d/m/Y', $date_fin_pa));
                        $abonnement->setBic($bic);
                        $abonnement->setIban($iban);
                        $abonnement->setRumIndice(0);
                        $abonnement->setNomBanque($nom_banque);
                        $transaction = new Transaction();
                        $transaction->setStatut($statut);
                        $don->setTransaction($transaction);
                        $don->setAbonnement($abonnement);
                        $don->setModePaiement($mode);
                        $don->setUser($donateur->getUser());
                        $don->setIspa(true);
                        $don->setMontant($montant);

                        $don->setCause($cause);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        $don->setType($obj_type);
                        $db->persist($don);
                        $db->flush();


                        //Gestion des Rums
                        $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                        $abonnement->setRum($rum);
                        $abonnement->setRumIndice(1);
                        $db->persist($abonnement);
                        $db->flush();
//                        die('flush2');
                    }
                    break;
                case self::_VIREMENT_:

                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;


                    if (count($errors) == 0) {


                        $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_VIREMENT_MODE_));

                        $virement = new Virement();
                        $virement->setNomBanque($nom_banque);
                        $transaction = new Transaction();
                        $transaction->setStatut($statut);
                        $transaction->setVirement($virement);
                        $don->setModePaiement($mode);
                        $don->setMontant($montant);
                        $don->setUser($donateur->getUser());
                        $don->setTransaction($transaction);
                        $don->setMontant($montant);
                        $don->setIspa(false);
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                        if (is_object($pros)) {
                            $pros->setRetour(true);
                        }
                        $don->setCause($cause);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        $don->setType($obj_type);
                        $db->persist($don);
                        $db->flush();
                    }
                    break;
                case self::_INTERNET_:
                    if (count($errors) == 0) {
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                        if (is_object($pros)) {
                            $pros->setRetour(true);
                        }
                        $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_CB_MODE_));
                        $transaction = new Transaction();
                        $transaction->setStatut($statut);
                        $don->setTransaction($transaction);
                        $don->setModePaiement($mode);
                        $don->setCause($cause);
                        $don->setMontant($montant);
                        $don->setUser($donateur->getUser());
                        $don->setIspa(false);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        $don->setType($obj_type);
                        $db->persist($don);
                        $db->flush();
                    }
                    break;
                default:
                    if (count($errors) == 0) {
                        $cause = $repCause->findOneBy(array('code' => $code_activite));
                        $pros = $repProspection->findOneBy(array('cause' => $cause, 'donateur' => $donateur, 'retour' => false));
                        if (is_object($pros)) {
                            $pros->setRetour(true);
                        }
                        $mode = $repMode->findOneBy(array('codeSolution' => Vars\DonVars::_ESPECE_MODE_));
                        $transaction = new Transaction();
                        $transaction->setStatut($statut);
                        $don->setTransaction($transaction);
                        $don->setModePaiement($mode);
                        $don->setCause($cause);
                        $don->setMontant($montant);
                        $don->setUser($donateur->getUser());
                        $don->setIspa(false);
                        if (isset($date_fiscale) && !empty($date_fiscale))
                            $don->setDateFiscale(\DateTime::createFromFormat('d/m/Y', $date_fiscale));
                        $don->setType($obj_type);
                        $db->persist($don);
                        $db->flush();
                    }
                    break;
            }



            if (count($errors) == 0) {
//                die('if + count=0');
                //Gestion des notes
                $note->setDon($don);
                $db->persist($note);
                $db->flush();
//                die('flush + count=0');
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
//                die('fin log + count=0');

                $this->get('session')->getFlashBag()->add('info', 'Création du don effectuée avec succès ! ');
//                return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $id)));
                return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $id, 'cumul' => $cumul, 'date' => $date)));
            } else {
//                die('else + count=0');
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }



// switch mode de paiement
        // cheque
        // PA
        // ESPECE

        return $this->render('FulldonIntersaBundle:Dons:add_don.html.twig', array(
                    'donateur' => $donateur,
                    'type' => $type,
                    'periodes' => $periodes,
                    'champs' => $champs,
                    'modes' => $modes,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function getCumulDernierDonBy($donateur_id) {

        $query = "SELECT d.id as don_id,d.user_id,dt.id as donateur_id, SUM(d.montant) AS cumul,MAX(d.created_at) AS dernier_don,dt.removed as statut_donateur    FROM coline_en_re_full_db.don d

LEFT JOIN coline_en_re_full_db.donateur dt ON  d.user_id=dt.id

WHERE dt.removed=0 AND d.removed=0 

and dt.id = '" . $donateur_id . "'

group by user_id DESC ";

        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function checkBic($bic) {
//        die('1');

        $bic = strtoupper($bic);
        if (preg_match($this->getBicCompare(), $bic)) {
            return true;
        } else {
            return false;
        }
    }

    private function getBicCompare() {


        return '/^[A-Z]{6,6}[A-Z2-9][A-NP-Z0-9]([A-Z0-9]{3,3}){0,1}/';
    }

}
