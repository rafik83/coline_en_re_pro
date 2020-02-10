<?php

/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulldon\IntersaBundle\Service;

use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Rf;
use Doctrine\ORM\EntityManager;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Event\LogVar;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Intl\Intl;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\HttpFoundation\Request;

class RfServices extends ContainerAware {

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    public function getHtmlArray(Don $don, Donateur $donateur, Rf $rf, $type, $prelevements = null, $somme = null) {
        $identity = $don->getCause()->getEntity()->getCode();
        $adresse = Null;
        for ($i = 1; $i <= 4; $i++) {
            $var = 'getAdresse' . $i;
            $myadr = $donateur->$var();
            if (!is_null($myadr) && !empty($myadr)) {
                $adresse .= $myadr . '<br />';
            }
        }
        if (!is_null($donateur->getZipcode())) {
            $adresse .= $donateur->getZipcode() . ' ';
        }
        if (!is_null($donateur->getIsoville())) {
            $adresse .= $donateur->getIsoville() . ' ';
        }
        if (!is_null($donateur->getIsopays())) {
            $adresse .= Intl::getRegionBundle()->getCountryName($donateur->getIsopays()) . '<br />';
        }
        $repPersoRfs = $this->em->getRepository('FulldonIntersaBundle:PersoRfs');
        $entityRep = $this->em->getRepository('FulldonIntersaBundle:Entity');
        $myEntity = $entityRep->findOneBy(array('code' => $identity));
        $objet = $repPersoRfs->findOneBy(array('code' => $type, 'entity' => $myEntity));
//         var_dump($objet);
//        die('$objet');
        //format header
        $template = $objet->getTemplate();
       
        $template = str_replace('{{donateur_id}}', $donateur->getId(), $template);
        $company_name = $donateur->getNomEntreprise();
        if (isset($company_name) && !empty($company_name)) {

            $template = str_replace('{{nom_entreprise}}', $company_name, $template);
            $template = str_replace('{{civilite_donateur}}', '', $template);
            $template = str_replace('{{nom_donateur}}', '', $template);
            $template = str_replace('{{prenom_donateur}}', '', $template);
        } else {

            $template = str_replace('{{nom_entreprise}}', '', $template);
            $template = str_replace('{{civilite_donateur}}', $donateur->getCivilite(), $template);
            $template = str_replace('{{nom_donateur}}', $donateur->getNom(), $template);
            $template = str_replace('{{prenom_donateur}}', $donateur->getPrenom(), $template);
        }

        $template = str_replace('{{adresse_donateur}}', $adresse, $template);
        $template = str_replace('{{date_envoi}}', date('d/m/Y'), $template);
        $template = str_replace('{{ref_don}}', $don->getId(), $template);
        $template = str_replace('{{montant_don}}', number_format(round($don->getMontant(), 2), 2), $template);
        $template = str_replace('{{code_activite}}', $don->getCause()->getLibelle(), $template);
        $template = str_replace('{{adresse_donateur}}', $donateur->getId(), $template);
        $template = str_replace('{{recu_id}}', $rf->getId(), $template);
        $template = str_replace('{{mode_don}}', $don->getModePaiement()->getLibelle(), $template);

        $template = str_replace('{{date_fiscale}}', $don->getDateFiscale()->format('d/m/Y'), $template);
        $template = str_replace('{{annee_fiscale}}', $don->getDateFiscale()->format('Y'), $template);

        if ($don->getIspa()) {
            $template = str_replace('{{rum}}', $don->getAbonnement()->getRum(), $template);
            $pa_history = NULL;
            $pa_history = '<table style="width: 100%">
            <thead>
            <th>Référence</th>
            <th>Montant</th>
            <th>Date</th>
            </thead><tbody style="text-align: center;">';
            foreach ($prelevements as $pr) {
                $pa_history .= '<tr class="';
                if ($pr->getRejet())
                    $pa_history .= 'disabled" >';
                else
                    $pa_history .= '">';

                $pa_history .= '<td>' . $pr->getId() . '</td>';
                $pa_history .= '<td>' . $pr->getMontant() . ' €</td>';
                $pa_history .= '<td>' . $pr->getDatePrelevement()->format('d/m/Y') . '</td>';
                $pa_history .= '</tr>';
            }
            $pa_history .= '</tbody></table>';
//            var_dump($pa_history);
//            die('$pa_history');
            $template = str_replace('{{pa_history}}', $pa_history, $template);
//            var_dump($template);
//            die('$template');
            $template = str_replace('{{pa_somme}}', $somme, $template);
//            var_dump($template);
//            die('iciicicici');
        }
        //footer


        return $template;
    }

    public function buildRfs($dons, $name) {
        $this->container->enterScope('request');
        $this->container->set('request', new Request(), 'request');
        $pdf = new PDFMerger();
        $files = array();
        $files_pa = array();

        foreach ($dons as $key => $don) {

            $file = $this->manageRf($don);
            if ($file != null) {
                if ($don->getIspa()) {
                    $files_pa[] = $file;
                } else {
                    $files[] = $file;
                }
            }
        }


        foreach ($files as $f) {
            $pdf->addPDF($f, 'all');
        }
        if (count($files) > 0) {
            $zipfile = '/' . $this->container->getParameter('folder_app') . '/RF_PONC/TOSEND/' . date('YmdHis') . 'zip.pdf';

            @$pdf->merge('file', $zipfile);
            $html = $this->container->get('templating')->render('FulldonIntersaBundle:Email:admin_zip_rf.html.twig');
            $admins = explode(',', $this->container->getParameter('administrator_email'));
            $object = 'Reçus fiscaux planifiés [' . $name . ']';
            $this->container->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $zipfile, $object);
        }
        $pdf = new PDFMerger();
        foreach ($files_pa as $f) {
            $pdf->addPDF($f, 'all');
        }
        //return
        if (count($files_pa) > 0) {
            $zipfile = '/' . $this->container->getParameter('folder_app') . '/RF_PA/TOSEND/' . date('YmdHis') . 'zip.pdf';
            @$pdf->merge('file', $zipfile);
            $html = $this->container->get('templating')->render('FulldonIntersaBundle:Email:admin_zip_rf.html.twig');
            $admins = explode(',', $this->container->getParameter('administrator_email'));
            $object = 'Reçus fiscaux PA planifiés [' . $name . ']';
            $this->container->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $zipfile, $object);
        }
    }

    private function manageRf(Don $don) {
        $persoRep = $this->em->getRepository('FulldonIntersaBundle:Personnalisation');
        $perso = $persoRep->find(1);
        $init['perso'] = $perso;
        $donateurRep = $this->em->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->findOneBy(array('user' => $don->getUser()->getId()));

        $repDonateur = $this->em->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $repDonateur->findOneBy(array('user' => $don->getUser()->getId()));
        if (is_object($donateur)) {
            $rf = new Rf();
            $name = uniqid(mt_rand());
            $is_courrier = false;
            $is_email = false;
            $is_sms = false;
            $fileName = $name . '-' . date('Y') . '.pdf';
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
            if ($don->getIspa()) {
                if (is_object($donateur) && $this->container->get('fulldon.intersa.global')->canIgenerateRf($don, $donateur, 3)) {
                    $file_origin = '/' . $this->container->getParameter('folder_app') . '/RF_PA/ORIGIN/' . $fileName;
                    $prelevementRep = $this->em->getRepository('FulldonDonateurBundle:Prelevement');
                    $curdate = new \DateTime();
                    $curdate->sub(new \DateInterval('P1Y'));

                    $globalResult = $prelevementRep->createQueryBuilder('p')
                            ->select(' SUM(p.montant) as somme, a.id as abo_id ')
                            ->join('p.abo', 'a')
                            ->where('p.rejet = 0')
                            ->andwhere('p.datePrelevement LIKE :date')
                            ->setParameter('date', '%' . $curdate->format('Y') . '%')
                            ->andwhere('a.id = :id')
                            ->setParameter('id', $don->getAbonnement()->getId())
                            ->groupBy('p.abo')
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
                    $rf->setNom($fileName);
                    $rf->setNomDuplicata($fileName);
                    $rf->setDon($don);
                    $this->em->persist($rf);
                    $this->em->persist($don);
                    $this->em->flush();

                    $html = $this->container->get('fulldon.intersa.rf_service')->getHtmlArray($don, $donateur, $rf, 'rf_pa', $prelevements, $globalResult['somme']);
                    $this->container->get('knp_snappy.pdf')->generateFromHtml(
                            $this->container->get('templating')->render(
                                    'FulldonIntersaBundle:Rf:rf.html.twig', array(
                                'html' => $html
                                    )
                            ), $file_origin, array(), true
                    );

                    //Log
                    $msg = $this->container->get('fulldon.intersa.global')->getAddMsgLog($rf, 'RF');
                    $typeLog = $this->em->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_RF_);
                    $role = "AUTOMATIQUE";
                    $event = HistoryLogEvent::mainConstr("AUTOMATIQUE", $donateur, $typeLog, $msg, $role, $don);
                    $dispatcher = $this->container->get('event_dispatcher');
                    $dispatcher->dispatch(LogVar::CREATE, $event);

                    $email = $donateur->getEmail();
                    if ($is_email && $email && !empty($email)) {
                        $html = $this->container->get('templating')->render('FulldonIntersaBundle:Email:rf.html.twig', array('don' => $don, 'rf' => $rf, 'donateur' => $donateur, 'init' => $init));
                        $this->container->get('fulldon.intersa.email_servies')->sendRf($email, $html, $file_origin);
                        $msg = 'Email envoyé : RF [' . $rf->getId() . ']';
                        $typeLog = $this->em->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_EMAIL_SENT_);
                        $role = "AUTOMATIQUE";
                        // Log the user creation
                        $event = HistoryLogEvent::mainConstr("AUTOMATIQUE", $donateur, $typeLog, $msg, $role, $don);
                        $dispatcher = $this->container->get('event_dispatcher');
                        $dispatcher->dispatch(LogVar::CREATE, $event);
                    }
                    if ($is_courrier) {
                        return $file_origin;
                    } else {
                        return null;
                    }
                }
            } else {
                if ($this->container->get('fulldon.intersa.global')->canIgenerateRf($don, $donateur)) {
                    $file_origin = '/' . $this->container->getParameter('folder_app') . '/RF/ORIGIN/' . $fileName;

                    $msg = $this->container->get('fulldon.intersa.global')->getAddMsgLog($rf, 'RF');
                    $typeLog = $this->em->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_RF_);
                    $role = "AUTOMATIQUE";
                    $event = HistoryLogEvent::mainConstr("AUTOMATIQUE", $donateur, $typeLog, $msg, $role, $don);
                    $dispatcher = $this->container->get('event_dispatcher');
                    $dispatcher->dispatch(LogVar::CREATE, $event);
                    $rf->setNom($fileName);
                    $rf->setNomDuplicata($fileName);
                    $rf->setDon($don);
                    $this->em->persist($rf);
                    $this->em->persist($don);
                    $this->em->flush();
                    //Génération du RF
                    $html = $this->container->get('fulldon.intersa.rf_service')->getHtmlArray($don, $donateur, $rf, 'rf', null, null);
                    $this->container->get('knp_snappy.pdf')->generateFromHtml(
                            $this->container->get('templating')->render(
                                    'FulldonIntersaBundle:Rf:rf.html.twig', array(
                                'html' => $html
                                    )
                            ), $file_origin, array(), true
                    );
                    //Log
                    $email = $donateur->getEmail();
                    if ($is_email && $email && !empty($email)) {
                        $html = $this->container->get('templating')->render('FulldonIntersaBundle:Email:rf.html.twig', array('don' => $don, 'rf' => $rf, 'donateur' => $donateur, 'init' => $init));
                        $this->container->get('fulldon.intersa.email_servies')->sendRf($email, $html, $file_origin);
                        $msg = 'Email envoyé : RF [' . $rf->getId() . ']';
                        $typeLog = $this->em->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_EMAIL_SENT_);
                        $role = "AUTOMATIQUE";
                        // Log the user creation
                        $event = HistoryLogEvent::mainConstr("AUTOMATIQUE", $donateur, $typeLog, $msg, $role, $don);
                        $dispatcher = $this->container->get('event_dispatcher');
                        $dispatcher->dispatch(LogVar::CREATE, $event);
                    }

                    if ($is_courrier) {
                        return $file_origin;
                    } else {
                        return null;
                    }
                }
            }
        }
        return null;
    }

    /*
     * Return a Datetime object that can be useful in the StartDate method
     *
     */

    public function getGenerationDate() {

        $cur_date = new \DateTime();
        $recommanded_day = $this->container->getParameter('prelevement_jour');
        $preparePa = $this->container->getParameter('prepare_pa');
        $date_generation = new \DateTime();
        $date_generation->setDate($cur_date->format('Y'), $cur_date->format('m'), $recommanded_day);
        $date_generation->sub(new \DateInterval('P' . $preparePa . 'D'));

        return $date_generation;
    }

    public function getStartDate() {

        $date_debut = new \DateTime();
        $cur_date = new \DateTime();
        $recommanded_day = $this->container->getParameter('prelevement_jour');
        $date_generation = $this->getGenerationDate();

        if ($cur_date > $date_generation) {
            $cur_date->add(new \DateInterval('P1M'));
        }

        $date_debut->setDate($cur_date->format('Y'), $cur_date->format('m'), $recommanded_day);

        return $date_debut;
    }

}
