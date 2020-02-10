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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;

class PrelevementCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('generate:prelevement')
            ->setDescription('Generation des fichiers XML de prélèvements')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->enterScope('request');
        $this->getContainer()->set('request', new Request(), 'request');

        $app = new Application();
        $progress = $app->getHelperSet()->get('progress');
        $details = array();
        $dfdons = array();
        $drdons = array();
        $sums = array();
        $today = new \DateTime('now');
        $generateDate = $this->getContainer()->get('fulldon.intersa.rf_service')->getGenerationDate();
        $curdate = $this->getContainer()->get('fulldon.intersa.rf_service')->getStartDate();

        if ($generateDate->format('d-m-Y') == $today->format('d-m-Y')) {
            $db = $this->getContainer()->get('doctrine')->getManager();
            //initialisation
            $where = array();
            $result_first = '';
            $result_recur = '';

            $repDon = $db->getRepository('FulldonDonateurBundle:Don');
            $g_infos = $repDon
                ->createQueryBuilder('d')
                ->select('count (d.id) as nb, SUM(d.montant) somme')
                ->join('d.abonnement', 'a')
                ->join('d.modePaiement', 'm')
                ->join('d.transaction', 't')
                ->join('t.statut', 's')
                ->where('a.date_next_pa = :date and a.actif = true and m.codeSolution != \'' . Vars\DonVars::_CB_MODE_ . '\' and s.id = ' . Vars\DonVars::_STATUT_DON_VALIDE_)
                ->setParameter('date', $curdate->format('Y-m-d'))
                ->getQuery()
                ->getSingleResult();
            $gnb = $g_infos['nb'];
            $gsum = $g_infos['somme'];
            ($gsum == null) ? $gsum = 0 : $gsum = $gsum;


            $doc = new \SimpleXMLElement('<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd"></Document>');
            $stmr = $doc->addChild('CstmrDrctDbtInitn');
            $grphdr = $stmr->addChild('GrpHdr');
            $grphdr->addChild('MsgId', $curdate->format('d/m/Y H:i:s') . ' ' . $this->getContainer()->get('fulldon.custom_params')->getParam('assoc_banque_name'));
            $grphdr->addChild('CreDtTm', $curdate->format('Y-m-d\TH:i:s'));
            $grphdr->addChild('NbOfTxs', $gnb);
            $grphdr->addChild('CtrlSum', $gsum);
            $grphdr->addChild('InitgPty')->addChild('Nm', $this->getContainer()->get('fulldon.custom_params')->getParam('assoc_banque_name'));

            // all the query here to not have a problem of db update
            $f_infos = $repDon
                ->createQueryBuilder('d')
                ->select('count (d.id) as nb, SUM(d.montant) somme')
                ->join('d.abonnement', 'a')
                ->join('d.modePaiement', 'm')
                ->join('d.transaction', 't')
                ->join('t.statut', 's')
                ->where('a.date_next_pa = :date and a.actif = true and m.codeSolution != \'' . Vars\DonVars::_CB_MODE_ . '\' and s.id = ' . Vars\DonVars::_STATUT_DON_VALIDE_)
                ->andwhere('a.preFirst = true')
                ->setParameter('date', $curdate->format('Y-m-d'))
                ->getQuery()
                ->getSingleResult();

            $r_infos = $repDon
                ->createQueryBuilder('d')
                ->select('count (d.id) as nb, SUM(d.montant) somme')
                ->join('d.abonnement', 'a')
                ->join('d.modePaiement', 'm')
                ->join('d.transaction', 't')
                ->join('t.statut', 's')
                ->where('a.date_next_pa = :date  ')
                ->andwhere('a.preFirst = false and a.actif = true and m.codeSolution != \'' . Vars\DonVars::_CB_MODE_ . '\' and s.id =\'' . Vars\DonVars::_STATUT_DON_VALIDE_ . '\'')
                ->setParameter('date', $curdate->format('Y-m-d'))
                ->getQuery()
                ->getSingleResult();

            $result_first = $repDon
                ->createQueryBuilder('d')
                ->join('d.abonnement', 'a')
                ->where('a.date_next_pa = :date and a.actif = true')
                ->andwhere('a.preFirst = true')
                ->setParameter('date', $curdate->format('Y-m-d'))
                ->getQuery()
                ->getResult();

            $result_recur = $repDon
                ->createQueryBuilder('d')
                ->join('d.abonnement', 'a')
                ->where('a.date_next_pa = :date')
                ->andwhere('a.preFirst = false and a.actif = true')
                ->setParameter('date', $curdate->format('Y-m-d'))
                ->getQuery()
                ->getResult();
            $progress->start($output, count($result_recur) + count($result_first));

            // First
            $fnb = $f_infos['nb'];
            $fsum = $f_infos['somme'];
            $sums['first'] = array('nb' => $f_infos['nb'], 'somme' => $f_infos['somme']);
            ($fsum == null) ? $fsum = 0 : $fsum = $fsum;
            if ($fnb > 0 && $fsum > 0) {
                $pmt_1 = $stmr->addChild('PmtInf');
                $pmt_1->addChild('PmtInfId', 'FRST');
                $pmt_1->addChild('PmtMtd', 'DD');
                $pmt_1->addChild('BtchBookg', 'false');
                $pmt_1->addChild('NbOfTxs', $fnb);
                $pmt_1->addChild('CtrlSum', $fsum);
                $pmttp = $pmt_1->addChild('PmtTpInf');
                $svc = $pmttp->addChild('SvcLvl');
                $lcl = $pmttp->addChild('LclInstrm');
                $svc->addChild('Cd', 'SEPA');
                $lcl->addChild('Cd', 'CORE');
                $pmttp->addChild('SeqTp', 'FRST');
                $pmt_1->addChild('ReqdColltnDt', $curdate->format('Y-m-d'));
                $cdtr = $pmt_1->addChild('Cdtr');
                $cdtr->addChild('Nm', html_entity_decode($this->getContainer()->get('fulldon.custom_params')->getParam('assoc_banque_name'), ENT_COMPAT, 'UTF-8'));
                $cdtrAcct = $pmt_1->addChild('CdtrAcct');
                $cdtrAcct->addChild('Id')->addChild('IBAN', $this->getContainer()->get('fulldon.custom_params')->getParam('assoc_iban'));
                $cdtrAgt = $pmt_1->addChild('CdtrAgt');
                $cdtrAgt->addChild('FinInstnId')->addChild('BIC', $this->getContainer()->get('fulldon.custom_params')->getParam('assoc_bic'));
                $pmt_1->addChild('ChrgBr', 'SLEV');
                $other = $pmt_1->addChild('CdtrSchmeId')->addChild('Id')->addChild('PrvtId')->addChild('Othr');
                $other->addChild('Id', $this->getContainer()->get('fulldon.custom_params')->getParam('assoc_sepa'));
                $other->addChild('SchmeNm')->addChild('Prtry', 'SEPA');
                $jump_f = false;

                foreach ($result_first as $don) {

                    if ($don->getAbonnement()->getDateFinPa() == null || strtotime($don->getAbonnement()->getDateFinPa()->format('Y-m-d')) >= strtotime($don->getAbonnement()->getDateNextPa()->format('Y-m-d'))) {
                        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
                        $donateur = $repDonateur->findOneBy(array('user' => $don->getUser()));

                        //$rum_indice = $don->getAbonnement()->getRumIndice();
                        //$rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad($rum_indice, 6, 0, STR_PAD_LEFT);
                        if ($don->getModePaiement()->getCodeSolution() != Vars\DonVars::_CB_MODE_ && $don->getTransaction()->getStatut()->getId() == Vars\DonVars::_STATUT_DON_VALIDE_) {
                            $dfdons[] = array($donateur, $don);
                            $root_don = $pmt_1->addChild('DrctDbtTxInf');
                            $PmtId = $root_don->addChild('PmtId');
                            $PmtId->addChild('InstrId', $don->getCause()->getCode());
                            $PmtId->addChild('EndToEndId', $don->getTransaction()->getId());
                            $InstdAmt = $root_don->addChild('InstdAmt', $don->getMontant());
                            $InstdAmt->addAttribute('Ccy', 'EUR');
                            $DrctDbtTx = $root_don->addChild('DrctDbtTx');
                            $MndtRltdInf = $DrctDbtTx->addChild('MndtRltdInf');
                            $MndtRltdInf->addChild('MndtId', $don->getAbonnement()->getRum());
                            $MndtRltdInf->addChild('DtOfSgntr', $don->getCreatedAt()->format('Y-m-d'));
                            $DbtrAgt = $root_don->addChild('DbtrAgt');
                            $FinInstnId = $DbtrAgt->addChild('FinInstnId');
                            $FinInstnId->addChild('BIC', $don->getAbonnement()->getBic());
                            $Dbtr = $root_don->addChild('Dbtr');
                            if (!is_null($donateur->getNomEntreprise())) {
                                $nomEntreprise = $donateur->getNomEntreprise();
                                if (!empty($nomEntreprise)) {
                                    $Dbtr->addChild('Nm', html_entity_decode($nomEntreprise, ENT_XML1));
                                }
                            } else {
                                $Dbtr->addChild('Nm', html_entity_decode($donateur->getPrenom() . ' ' . $donateur->getNom(), ENT_XML1));
                            }
                            $DbtrAcct = $root_don->addChild('DbtrAcct')->addChild('Id')->addChild('IBAN', $don->getAbonnement()->getIban());

                        }
                        if ($don->getTransaction()->getStatut()->getId() == Vars\DonVars::_STATUT_DON_VALIDE_) {
                            //incrémenter le prochain prélevement
                            $periodicite = $don->getAbonnement()->getPeriodicite();
                            $date_prelevement = $don->getAbonnement()->getDateNextPa();

                            //Création d'un prélevement
                            $prelevement = new Prelevement();
                            $prelevement->setAbo($don->getAbonnement());
                            $prelevement->setMontant($don->getMontant());
                            $prelevement->setRejet(false);
                            $prelevement->setDate($curdate);
                            $prelevement->setDatePrelevement($date_prelevement);
                            $prelevement->setRum($don->getAbonnement()->getRum());
                            $db->persist($prelevement);
                            $db->flush();

                            //Stat
                            // Log the user creation
                            $event = HistoryStatEvent::constr2(StatVar::_STAT_TYPE_SAISIE_PRELEVEMENT_NEW_);
                            $dispatcher = $this->getContainer()->get('event_dispatcher');
                            $dispatcher->dispatch(StatVar::CREATE, $event);
                            //Fin Stat


                            $msg = $this->getContainer()->get('fulldon.intersa.global')->getAddMsgLog($prelevement, 'DON:PRELEVEMENT');
                            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
                            // Log the user creation
                            $event = HistoryLogEvent::constr3($db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser())), $typeLog, $msg);
                            $dispatcher = $this->getContainer()->get('event_dispatcher');
                            $dispatcher->dispatch(LogVar::CREATE, $event);
                            //Fin Log

                            $next = $don->getAbonnement()->getDateNextPa()->add(new \DateInterval('P' . $periodicite->getCode() . 'M'));
                            $next_date = $next->format('d/m/Y');
                            $don->getAbonnement()->setPreFirst(false);
                            $don->getAbonnement()->setDateNextPa(\DateTime::createFromFormat('d/m/Y', $next_date));
                            $db->persist($don);
                            $db->flush();
                        }
                    } else {
                        $don->getAbonnement()->setActif(false);
                        $db->persist($don);
                        $msg = $this->getContainer()->get('fulldon.intersa.global')->getModMsgLog($don->getAbonnement(), 'ABONNEMENT');
                        $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
                        // Log the user creation
                        $event = HistoryLogEvent::constr3($db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser())), $typeLog, $msg);
                        $dispatcher = $this->getContainer()->get('event_dispatcher');
                        $dispatcher->dispatch(LogVar::CREATE, $event);
                        //stoper l'abonnement
                    }

                    $progress->advance();


                }

            }
            // Récurrents


            $rnb = $r_infos['nb'];
            $rsum = $r_infos['somme'];
            $sums['recur'] = array('nb' => $r_infos['nb'], 'somme' => $r_infos['somme']);
            if ($rnb > 0 && $rsum > 0) {
                ($rsum == null) ? $rsum = 0 : $rsum = $rsum;
                $pmt_1 = $stmr->addChild('PmtInf');
                $pmt_1->addChild('PmtInfId', 'RCUR');
                $pmt_1->addChild('PmtMtd', 'DD');
                $pmt_1->addChild('BtchBookg', 'false');
                $pmt_1->addChild('NbOfTxs', $rnb);
                $pmt_1->addChild('CtrlSum', $rsum);
                $pmttp = $pmt_1->addChild('PmtTpInf');
                $svc = $pmttp->addChild('SvcLvl');
                $lcl = $pmttp->addChild('LclInstrm');
                $svc->addChild('Cd', 'SEPA');
                $lcl->addChild('Cd', 'CORE');
                $pmttp->addChild('SeqTp', 'RCUR');
                $pmt_1->addChild('ReqdColltnDt', $curdate->format('Y-m-d'));
                $cdtr = $pmt_1->addChild('Cdtr');
                $cdtr->addChild('Nm', $this->getContainer()->getParameter('assoc_name'));
                $cdtrAcct = $pmt_1->addChild('CdtrAcct');
                $cdtrAcct->addChild('Id')->addChild('IBAN', $this->getContainer()->get('fulldon.custom_params')->getParam('assoc_iban'));
                $cdtrAgt = $pmt_1->addChild('CdtrAgt');
                $cdtrAgt->addChild('FinInstnId')->addChild('BIC', $this->getContainer()->get('fulldon.custom_params')->getParam('assoc_bic'));
                $pmt_1->addChild('ChrgBr', 'SLEV');
                $other = $pmt_1->addChild('CdtrSchmeId')->addChild('Id')->addChild('PrvtId')->addChild('Othr');
                $other->addChild('Id', $this->getContainer()->get('fulldon.custom_params')->getParam('assoc_sepa'));
                $other->addChild('SchmeNm')->addChild('Prtry', 'SEPA');

                foreach ($result_recur as $don) {
                    if ($don->getAbonnement()->getDateFinPa() == null || strtotime($don->getAbonnement()->getDateFinPa()->format('Y-m-d')) >= strtotime($don->getAbonnement()->getDateNextPa()->format('Y-m-d'))) {
                        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
                        $donateur = $repDonateur->findOneBy(array('user' => $don->getUser()));

                        if ($don->getModePaiement()->getCodeSolution() != Vars\DonVars::_CB_MODE_ && $don->getTransaction()->getStatut()->getId() == Vars\DonVars::_STATUT_DON_VALIDE_) {
                            $drdons[] = array($donateur, $don);
                            $root_don = $pmt_1->addChild('DrctDbtTxInf');
                            $PmtId = $root_don->addChild('PmtId');
                            $PmtId->addChild('InstrId', $don->getCause()->getCode());
                            $PmtId->addChild('EndToEndId', $don->getTransaction()->getId());
                            $InstdAmt = $root_don->addChild('InstdAmt', $don->getMontant());
                            $InstdAmt->addAttribute('Ccy', 'EUR');
                            $DrctDbtTx = $root_don->addChild('DrctDbtTx');
                            $MndtRltdInf = $DrctDbtTx->addChild('MndtRltdInf');
                            $MndtRltdInf->addChild('MndtId', $don->getAbonnement()->getRum());
                            $MndtRltdInf->addChild('DtOfSgntr', $don->getCreatedAt()->format('Y-m-d'));
                            $DbtrAgt = $root_don->addChild('DbtrAgt');
                            $FinInstnId = $DbtrAgt->addChild('FinInstnId');
                            $FinInstnId->addChild('BIC', $don->getAbonnement()->getBic());
                            $Dbtr = $root_don->addChild('Dbtr');

                            if (!is_null($donateur->getNomEntreprise())) {
                                $nomEntreprise = $donateur->getNomEntreprise();
                                if (!empty($nomEntreprise)) {
                                    $Dbtr->addChild('Nm', html_entity_decode($nomEntreprise, ENT_XML1));
                                }
                            } else {
                                $Dbtr->addChild('Nm', html_entity_decode($donateur->getPrenom() . ' ' . $donateur->getNom(), ENT_XML1));
                            }
                            $DbtrAcct = $root_don->addChild('DbtrAcct')->addChild('Id')->addChild('IBAN', $don->getAbonnement()->getIban());


                        }
                        if ($don->getTransaction()->getStatut()->getId() == Vars\DonVars::_STATUT_DON_VALIDE_) {
                            //incrémenter le prochain prélevement
                            $periodicite = $don->getAbonnement()->getPeriodicite();
                            $date_prelevement = $don->getAbonnement()->getDateNextPa();

                            //Création d'un prélevement
                            $prelevement = new Prelevement();
                            $prelevement->setAbo($don->getAbonnement());
                            $prelevement->setMontant($don->getMontant());
                            $prelevement->setRejet(false);
                            $prelevement->setDate($curdate);
                            $prelevement->setDatePrelevement($date_prelevement);
                            $prelevement->setRum($don->getAbonnement()->getRum());
                            $db->persist($prelevement);
                            $db->flush();

                            //Stat
                            // Log the user creation
                            $event = HistoryStatEvent::constr2(StatVar::_STAT_TYPE_SAISIE_PRELEVEMENT_OLD_);
                            $dispatcher = $this->getContainer()->get('event_dispatcher');
                            $dispatcher->dispatch(StatVar::CREATE, $event);
                            //Fin Stat

                            $msg = $this->getContainer()->get('fulldon.intersa.global')->getAddMsgLog($prelevement, 'DON:PRELEVEMENT');
                            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
                            // Log the user creation
                            $event = HistoryLogEvent::constr3($db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser())), $typeLog, $msg);
                            $dispatcher = $this->getContainer()->get('event_dispatcher');
                            $dispatcher->dispatch(LogVar::CREATE, $event);
                            //Fin Log

                            $next = $don->getAbonnement()->getDateNextPa()->add(new \DateInterval('P' . $periodicite->getCode() . 'M'));
                            $next_date = $next->format('d/m/Y');
                            $don->getAbonnement()->setDateNextPa(\DateTime::createFromFormat('d/m/Y', $next_date));
                            $db->persist($don);
                            $db->flush();
                        }
                    } else {
                        $don->getAbonnement()->setActif(false);
                        $db->persist($don);
                        $msg = $this->getContainer()->get('fulldon.intersa.global')->getModMsgLog($don->getAbonnement(), 'ABONNEMENT');
                        $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
                        // Log the user creation
                        $event = HistoryLogEvent::constr3($db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser())), $typeLog, $msg);
                        $dispatcher = $this->getContainer()->get('event_dispatcher');
                        $dispatcher->dispatch(LogVar::CREATE, $event);
                        //stoper l'abonnement
                    }
                    $progress->advance();
                }
            }
            if ((count($result_first) == 0 && count($result_recur) == 0) || $gnb == 0) {
                $progress->finish();
                if (count($result_first) == 0 && count($result_recur) == 0) {
                    $output->writeln('Pas de prelevement a generer !');
                } else {
                    $output->writeln('Des prelevements internet ont ete generes !');
                }
            } else {
                $details = array('dons_f' => $dfdons, 'dons_r' => $drdons);
                $progress->finish();
                $dom = dom_import_simplexml($doc)->ownerDocument;
                $dom->encoding = 'UTF-8';
                $dom->formatOutput = true;
                $prelevement_file = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' .$curdate->format('Y-m-d') . '.xml';
                $dom->save($prelevement_file);

                //Send file by email
                $object = $this->getContainer()->getParameter('assoc_name') . ':  Génération des prélèvements automatiques';
                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:monthly_prelevements.html.twig');
                $admins = explode(',', $this->getContainer()->getParameter('fulldon_admins'));
                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $prelevement_file, $object, 'xml');

                //Send details prélèvement
                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                $file_origin = '/' . $this->getContainer()->getParameter('folder_app') . '/Export/' . $curdate->format('Y-m-d') . '.pdf';
                $object = $this->getContainer()->getParameter('assoc_name') . ':  Rapport des prélèvements générés ';
                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:rapport_prelevement.html.twig');
                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                    $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Intersa/pdf:rapport_prelevement.html.twig',
                        array(
                            'details' => $details,
                            'sums' => $sums
                        )
                    ),
                    $file_origin,
                    array(),
                    true
                );
                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $file_origin, $object, 'pdf');
                $output->writeln('Fichier xml cree avec succes !');
            }
        }


    }
    function dqlDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d->format('Y-m-d');
    }

}