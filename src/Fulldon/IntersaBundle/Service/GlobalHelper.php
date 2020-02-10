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

use Doctrine\ORM\EntityManager;
use Fulldon\IntersaBundle\Entity\BillReport;
use Fulldon\IntersaBundle\Entity\MarketingCronTask;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\DonateurBundle\Entity\MotifAbo;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Prelevement;
use Fulldon\DonateurBundle\Entity\Don;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\DependencyInjection\ContainerAware;

class GlobalHelper extends ContainerAware {

    public function __construct(EntityManager $em, $seuil_rf) {
        $this->em = $em;
        $this->seuilRf = $seuil_rf;
    }

    public function getXmlFiles($dir) {

        $repSaisie = $this->em->getRepository('FulldonIntersaBundle:Saisie');
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

    public function getModMsgLog($obj, $quoi) {
        $msg = $quoi . '[' . $obj->getId() . '] : Les éléments suivants : ';
        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $attr = array();
        $changeset = $uow->getEntityChangeSet($obj);
        foreach ($changeset as $key => $changes) {
            if ($changes[0] != $changes[1]) {
                foreach (array(
            '\Fulldon\DonateurBundle\Entity\MotifAbo',
            '\Fulldon\DonateurBundle\Entity\MotifRejetPrelevement',
            '\Fulldon\DonateurBundle\Entity\MotifDisableDon',
            '\Fulldon\DonateurBundle\Entity\MotifPnd',
                ) as $class) {
                    if ($changes[1] instanceof $class) {
                        $changes[1] = $changes[1]->getName();
                    }
                    if ($changes[0] instanceof $class) {
                        $changes[0] = $changes[0]->getName();
                    }
                }
                //$msg = $changes[1]->getNom();
                if (!is_object($changes[0]) && !is_object($changes[1]))
                    $attr[] = $key . '([A:' . $changes[0] . '];[N:' . $changes[1] . '])';
                else
                    $attr[] = $key;
            }
        }
        $msg .= implode(',', $attr) . ' ont été modifiés';
        return $msg;
    }

    public function getAddMsgLog($obj, $quoi) {

        $msg = 'Création d\'un nouveau ' . $quoi . '[' . $obj->getId() . ']';

        if ($obj instanceof \Fulldon\DonateurBundle\Entity\Pnd) {
            $msg = $msg . ' [Motif : ' . $obj->getMotif()->getName() . ']';
        }

        return $msg;
    }

    public function canIgenerateRf(Don $don, Donateur $donateur, $type = 1) {


        $montant = 0;
        $curdate = new \DateTime();
        $curdate->sub(new \DateInterval('P1Y'));
        $repDon = $this->em->getRepository('FulldonDonateurBundle:Don');
//        if($don->getIspa()) {
//            $prelevementRep = $this->em->getRepository('FulldonDonateurBundle:Prelevement');
//            $prelevements = $prelevementRep->createQueryBuilder('p')
//                ->select(' SUM(p.montant) as somme, a.id as abo_id ')
//                ->join('p.abo','a')
//                ->where('p.datePrelevement LIKE :date')
//                ->setParameter('date', '%'.$curdate->format('Y').'%')
//                ->andwhere('p.rejet = 0')
//                ->andwhere('p.abo = :abo')
//                ->setParameter('abo', $don->getAbonnement())
//                ->groupBy('p.abo')
//                ->getQuery()
//                ->getSingleResult();
//            $montant = $prelevements['somme'];
//        } else {
        $montant = $don->getMontant();
//         var_dump($montant);
//        die('$montant');

//        }

//        var_dump($type);
//        die('type');
        //$tt = $don->getCause()->getRf() && $donateur->getAllowRf() /* && !$donateur->getAllowAnnualRf() */ && $montant >= $this->seuilRf && count($don->getRfs()) == 0 && $don->getTransaction()->getStatut()->getCode() == Vars\DonVars::_CODE_DON_VALIDE_;
//        var_dump(Vars\DonVars::_CODE_DON_VALIDE_);
//        die('_CODE_DON_VALIDE_');
        if ($type == 1) {
            return $don->getCause()->getRf() && $donateur->getAllowRf() /* && !$donateur->getAllowAnnualRf() */ && $montant >= $this->seuilRf && count($don->getRfs()) == 0 && $don->getTransaction()->getStatut()->getCode() == Vars\DonVars::_CODE_DON_VALIDE_;
        
//            return true ;            
            
            
        } elseif ($type == 2) {
            /* $cummule = $repDon->createQueryBuilder('d')
              ->select(' SUM(d.montant) as somme, d.id as don_id ')
              ->join('d.cause','c')
              ->where('d.date_fiscale LIKE :date')
              ->andwhere('d.removed = 0 and d.user = :user')
              ->setParameter('date', '%'.$curdate->format('Y').'%')
              ->setParameter('user', $donateur->getUser())
              ->andWhere('c.rf = true')
              ->groupBy('d.user')
              ->getQuery()
              ->getSingleResult();
              $montant =$cummule['somme'];
              return $donateur->getAllowRf() && $donateur->getAllowAnnualRf() && $montant >= $this->seuilRf ; */
        } else {
            $prelevementRep = $this->em->getRepository('FulldonDonateurBundle:Prelevement');

            $pr = $prelevementRep->createQueryBuilder('p')
                    ->select(' SUM(p.montant) as somme')
                    ->join('p.abo', 'a')
                    ->where('p.datePrelevement LIKE :date AND p.abo = :abo')
                    ->andwhere('p.rejet = 0')
                    ->setParameter('date', '%' . $curdate->format('Y') . '%')
                    ->setParameter('abo', $don->getAbonnement())
                    ->getQuery()
                    ->getOneOrNullResult();
            $montant = $pr['somme'];
            // $this->seuilRf  === 5
            // Vars\DonVars::_CODE_DON_VALIDE_ === don_valide
//             var_dump($this->seuilRf);
//            die('$this->seuilRf');



            $rfofyear = true;
            foreach ($don->getRfs() as $rf) {
                if ($rf->getCreatedAt()->format('Y') == date('Y') or $don->getDateFiscale()->format('Y') >= date('Y')) {
                    $rfofyear = false;
                    // true or false = true
                    //$rf->getCreatedAt()->format('Y') = 2017 
                    // $don->getDateFiscale()->format('Y') = 2015
                    // date('Y') = 2017
                }
            }


//            $tt = $don->getCause()->getRf() && $donateur->getAllowRf() && $montant >= $this->seuilRf && $rfofyear && $don->getTransaction()->getStatut()->getCode() == Vars\DonVars::_CODE_DON_VALIDE_;
//            var_dump($tt); // $tt false si $rfofyear = false //  $tt true si $rfofyear = true 
//            die('$tt');


            return $don->getCause()->getRf() && $donateur->getAllowRf() && $montant >= $this->seuilRf && $rfofyear && $don->getTransaction()->getStatut()->getCode() == Vars\DonVars::_CODE_DON_VALIDE_;
        }
    }

    public function age($date) {
        return (int) ((time() - strtotime($date)) / 3600 / 24 / 365);
    }

    public function getGlobalStats() {

        $repStat = $this->em->getRepository('FulldonIntersaBundle:Stat');
        $repPnd = $this->em->getRepository('FulldonDonateurBundle:Pnd');
        $repMotifPnd = $this->em->getRepository('FulldonDonateurBundle:MotifPnd');
        $repDonateur = $this->em->getRepository('FulldonDonateurBundle:Donateur');
        $repDon = $this->em->getRepository('FulldonDonateurBundle:Don');
        $repPrelevement = $this->em->getRepository('FulldonDonateurBundle:Prelevement');
        $repEntity = $this->em->getRepository('FulldonIntersaBundle:Entity');
        $repEntity = $this->em->getRepository('FulldonIntersaBundle:Entity');

        $don_cb = 0;
        $don_pa = 0;
        $don_espece = 0;
        $don_bc = 0;
        $don_cs = 0;
        $don_paypal = 0;
        $don_virement = 0;
        $mdon_cb = 0;
        $mdon_pa = 0;
        $mdon_espece = 0;
        $mdon_bc = 0;
        $mdon_cs = 0;
        $mdon_paypal = 0;
        $mdon_virement = 0;
        $pre_rejet = 0;
        $pre_accept = 0;
        $donateur_rnbemails = 0;
        $donateur_rtfixe = 0;
        $donateur_rtmobile = 0;
        $donateur_actif = 0;
        $donateur_anbemails = 0;
        $donateur_atfixe = 0;
        $donateur_atmobile = 0;
        $nb_12mois = 0;
        $nb_24mois = 0;
        $nb_36mois = 0;
        $donateur_removed = 0;
        $rsm = new ResultSetMapping();
        $year = date("Y");
        $day = date("d");
        $month = date("m");
        $rsm->addScalarResult('comptage', 'comptage');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('mois', 'mois');
        $rsm->addScalarResult('nom_entity', 'nom_entity');
        $rsm->addScalarResult('cpt', 'cpt');
        // Nombre  de donateurs actifs 0/12 mois.

        $sql_12mois = "SELECT COUNT(d.id) as comptage
        FROM don as d
        WHERE (d.removed = false or d.removed =null)
        and created_at <= '" . $year . "-" . $month . "-" . $day . "' and  created_at >= '" . ($year - 1) . "-" . $month . "-" . $day . "'
        ";
        $query = $this->em->createNativeQuery($sql_12mois, $rsm);
        $nb_12mois = $query->getOneOrNullResult();

        // Nombre  de donateurs actifs 12/24 mois.

        $sql_24mois = "SELECT COUNT(d.id) as comptage
        FROM don as d
        WHERE (d.removed = false or d.removed =null)
        and created_at <= '" . ($year - 2) . "-" . $month . "-" . $day . "' and created_at >= '" . ($year - 1) . "-" . $month . "-" . $day . "'
        ";
        $query = $this->em->createNativeQuery($sql_24mois, $rsm);
        $nb_24mois = $query->getOneOrNullResult();

        // Nombre  de donateurs actifs 24/36 mois.

        $sql_36mois = "SELECT COUNT(d.id) as comptage
        FROM don as d
        WHERE d.removed = false
        and created_at <= '" . ($year - 3) . "-" . $month . "-" . $day . "' and  created_at >= '" . ($year - 2) . "-" . $month . "-" . $day . "'
        ";
        $query = $this->em->createNativeQuery($sql_36mois, $rsm);
        $nb_36mois = $query->getOneOrNullResult();
        //Nombre de donateurs par motif
        //IFNULL instead of COALESCE
        $disabled_donateur_query = $repDonateur->createQueryBuilder('d')
                ->select(' COALESCE(COUNT(d.id),0) as comptage, m.name')
                ->join('d.motif', 'm')
                ->where('d.removed = 0')
                ->groupBy('m.name');

        $disabled_donateur = $disabled_donateur_query->getQuery()->getResult();

        //Nombre de PA Actif sur les 12 derniers mois
        $sql_12mois_pa = "SELECT COUNT(DISTINCT p.abo_id) as comptage
        FROM prelevement as p
        WHERE (p.rejet = false or p.rejet =null)
        and date_prelevement <= '" . $year . "-01-01' and  date_prelevement >= '" . ($year - 1) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_12mois_pa, $rsm);
        $nb_12mois_pa = $query->getOneOrNullResult();

        // Nombre  PA actifs sur les 12/24 mois.

        $sql_24mois_pa = "SELECT COUNT(DISTINCT p.abo_id) as comptage
        FROM prelevement as p
        WHERE (p.rejet = false or p.rejet =null)
        and date_prelevement <= '" . ($year - 1) . "-01-01' and  date_prelevement >= '" . ($year - 2) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_24mois_pa, $rsm);
        $nb_24mois_pa = $query->getOneOrNullResult();

        // Stops depuis le début de l'année
        $sql_stops_pa = "SELECT COUNT(a.id) as comptage
        FROM abonnement as a
        WHERE a.actif = false
        and disabled_at >= '" . ($year) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_stops_pa, $rsm);
        $stops_pa = $query->getOneOrNullResult();

        $pndStats = $repPnd->createQueryBuilder('d')
                        ->select('count(d.id) as cpt, m.name')
                        ->join('d.motif', 'm')
                        ->groupBy('m.name')
                        ->getQuery()->getResult();
        $motifs = $repMotifPnd->createQueryBuilder('m')
                        ->select('m.name')
                        ->getQuery()->getResult();

        $donateurstat = $repDonateur->createQueryBuilder('d')
                        ->select('count(d.id) as cpt, d.removed, count(d.email) as nb_emails, count(d.telephoneMobile) as t_mobile, count(d.telephoneFixe) as t_fixe')
                        ->groupBy('d.removed')
                        ->getQuery()->getResult();

        foreach ($donateurstat as $ds) {
            if ($ds['removed'] == true) {
                $donateur_removed = $ds['cpt'];
                $donateur_rnbemails = $ds['nb_emails'];
                $donateur_rtfixe = $ds['t_fixe'];
                $donateur_rtmobile = $ds['t_mobile'];
            } else {
                $donateur_actif = $ds['cpt'];
                $donateur_anbemails = $ds['nb_emails'];
                $donateur_atfixe = $ds['t_fixe'];
                $donateur_atmobile = $ds['t_mobile'];
            }
        }


        $donstat = $repDon->createQueryBuilder('d')
                        ->select('count( d.id) as cpt, t.code')
                        ->join('d.type', 't')
                        ->join('d.modePaiement', 'm')
                        ->groupBy('t.code')
                        ->getQuery()->getResult();
        foreach ($donstat as $ds) {
            $ds['moyenne'] = 0;
            switch ($ds['code']) {
                case 'PA':
                    $don_pa = $ds['cpt'];
                    $mdon_pa = $ds['moyenne'];
                    break;
                case 'VIREMENT':
                    $don_virement = $ds['cpt'];
                    $mdon_virement = $ds['moyenne'];
                    break;
                case 'ESPECE':
                    $don_espece = $ds['cpt'];
                    $mdon_espece = $ds['moyenne'];
                    break;
                case 'CS':
                    $don_cs = $ds['cpt'];
                    $mdon_cs = $ds['moyenne'];
                    break;
                case 'BC':
                    $don_bc = $ds['cpt'];
                    $mdon_bc = $ds['moyenne'];
                    break;
                case 'PAYPAL':
                    $don_paypal = $ds['cpt'];
                    $mdon_paypal = $ds['moyenne'];
                    break;
                case 'INTERNET':
                    $don_cb = $ds['cpt'];
                    $mdon_cb = $ds['moyenne'];
                    break;
            }
        }
        //Spécial pa
        $don_pa_res = $repDon->createQueryBuilder('d')
                        ->select('count(d.id) as cpt, avg(d.montant) as moyenne')
                        ->where('d.ispa = 1')
                        ->getQuery()->getOneOrNullResult();
        $don_pa = $don_pa_res['cpt'];
        $mdon_pa = $don_pa_res['moyenne'];

        $abostat = $repDon->createQueryBuilder('d')
                        ->select('count(d.id) as cpt, a.actif, m.name')
                        ->join('d.abonnement', 'a')
                        ->LeftJoin('a.motif', 'm')
                        ->where('d.ispa IS NOT NULL')
                        ->groupBy(' m.name')
                        ->getQuery()->getResult();

        $pa_actif = $repDon->createQueryBuilder('d')
                        ->select('count(d.id) as cpt')
                        ->join('d.abonnement', 'a')
                        ->where('d.ispa IS NOT NULL and d.removed = false')
                        ->getQuery()->getOneOrNullResult();

        $prelevementstat = $repPrelevement->createQueryBuilder('p')
                        ->select('count(p.id) as cpt, p.rejet')
                        ->groupBy('p.rejet')
                        ->getQuery()->getResult();

        foreach ($prelevementstat as $p) {
            if ($p['rejet'] == false) {
                $pre_accept = $p['cpt'];
            } else {
                $pre_rejet = $p['cpt'];
            }
        }

        // mapping results to the message entity
        $rsm->addScalarResult('moyenne_age', 'moyenne_age');

        $sql_moyenne = "SELECT AVG(YEAR(CURDATE())-YEAR(datenaissance)) as moyenne_age
        FROM donateur";
        $query = $this->em->createNativeQuery($sql_moyenne, $rsm);
        $stats_moyenne_age = $query->getOneOrNullResult();
        $sql_new_donateurs_by_entity_preyear = "SELECT COUNT(distinct d.user_id) as cpt, DATE_FORMAT(d.created_at,'%m') as mois, E.name as nom_entity
                    FROM don as d
                    INNER JOIN cause as c ON d.cause_id = c.id
                    RIGHT JOIN entite as E ON c.entity_assoc = E.id
                    WHERE (d.removed = false or d.removed =null)
                    and d.created_at <= '" . $year . "-01-01' and  d.created_at >= '" . ($year - 1) . "-01-01'
                    GROUP BY mois,nom_entity ";

        $query = $this->em->createNativeQuery($sql_new_donateurs_by_entity_preyear, $rsm);
        $new_donateurs_by_entity_preyear = $query->getResult();

        $sql_new_donateurs_by_entity_curyear = "SELECT COUNT(distinct d.user_id) as cpt, DATE_FORMAT(d.created_at,'%m') as mois, E.name as nom_entity
                    FROM don as d
                    INNER JOIN cause as c ON d.cause_id = c.id
                    RIGHT JOIN entite as E ON c.entity_assoc = E.id
                    WHERE (d.removed = false or d.removed =null)
                    and d.created_at <= '" . ($year + 1) . "-01-01' and  d.created_at >= '" . ($year) . "-01-01'
                    GROUP BY mois,nom_entity ";

        $query = $this->em->createNativeQuery($sql_new_donateurs_by_entity_curyear, $rsm);
        $new_donateurs_by_entity_curyear = $query->getResult();

        $entities = $repEntity->findAll();

        $rsm->addScalarResult('nbsms', 'nbsms');
        $rsm->addScalarResult('nbemail', 'nbemail');
        $rsm->addScalarResult('tag', 'tag');
        // Nombre  de donateurs actifs 0/12 mois.

        $sql_marketing = "SELECT SUM(nb_sms) as nbsms, SUM(nb_email) as nbemail, tag
        FROM stat_com group by tag
        ";
        $query = $this->em->createNativeQuery($sql_marketing, $rsm);
        $marketing = $query->getResult();
        $data = array(
            'donateur_removed' => $donateur_removed,
            'donateur_actif' => $donateur_actif,
            'don_pa' => $don_pa,
            'don_cb' => $don_cb,
            'don_bc' => $don_bc,
            'don_paypal' => $don_paypal,
            'don_cs' => $don_cs,
            'don_espece' => $don_espece,
            'don_virement' => $don_virement,
            'mdon_pa' => round($mdon_pa),
            'mdon_cb' => round($mdon_cb),
            'mdon_bc' => round($mdon_bc),
            'mdon_paypal' => round($mdon_paypal),
            'mdon_cs' => round($mdon_cs),
            'mdon_espece' => round($mdon_espece),
            'mdon_virement' => round($mdon_virement),
            'abo' => $abostat,
            'pre_rejet' => $pre_rejet,
            'pre_accept' => $pre_accept,
            'donateur_emails' => $donateur_rnbemails + $donateur_anbemails,
            'donateur_tfixe' => $donateur_rtfixe + $donateur_atfixe,
            'donateur_tmobile' => $donateur_rtmobile + $donateur_atmobile,
            'moyenne_age' => round($stats_moyenne_age['moyenne_age']),
            'pnd' => $pndStats,
            'nb_12mois' => $nb_12mois['comptage'],
            'nb_24mois' => $nb_24mois['comptage'],
            'nb_36mois' => $nb_36mois['comptage'],
            'disabled_donateur' => $disabled_donateur,
            'nb_12mois_pa' => $nb_12mois_pa['comptage'],
            'nb_24mois_pa' => $nb_24mois_pa['comptage'],
            'stops_pa' => $stops_pa['comptage'],
            'tab_nouveau_donateur_curyear' => $new_donateurs_by_entity_curyear,
            'tab_nouveau_donateur_preyear' => $new_donateurs_by_entity_preyear,
            'entities' => $entities,
            'pa_actif' => $pa_actif['cpt'],
            'marketing' => $marketing
        );
        return $data;
    }

    public function getAdvancedGlobalStats($date_debut, $date_fin, $entity) {

        $repStat = $this->em->getRepository('FulldonIntersaBundle:Stat');
        $repPnd = $this->em->getRepository('FulldonDonateurBundle:Pnd');
        $repMotifPnd = $this->em->getRepository('FulldonDonateurBundle:MotifPnd');
        $repDonateur = $this->em->getRepository('FulldonDonateurBundle:Donateur');
        $repDon = $this->em->getRepository('FulldonDonateurBundle:Don');
        $repPrelevement = $this->em->getRepository('FulldonDonateurBundle:Prelevement');
        $repEntity = $this->em->getRepository('FulldonIntersaBundle:Entity');



        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('comptage', 'comptage');
        $rsm->addScalarResult('code', 'code');
        $rsm->addScalarResult('cible', 'cible');
        $rsm->addScalarResult('moyenne', 'moyenne');
        $rsm->addScalarResult('cumul', 'cumul');
        $rsm->addScalarResult('pros', 'pros');



        $sql = "
        SELECT SUM(g.comptage) comptage, g.code, g.cible , g.pros,  AVG (g.moyenne) as moyenne, sum(g.cumul_montant) as cumul
        FROM (
        SELECT COUNT(distinct d.id) as comptage,oc.code as code, oc.cible as cible, count(distinct p.id) as pros,
        IFNULL(SUM( distinct(SELECT SUM( pr.montant) from prelevement as pr where d.abo_id=pr.abo_id and pr.rejet = 0)),0)+
        IFNULL(SUM( distinct (SELECT dd.montant from don as dd where dd.id=d.id and dd.abo_id is null)),0)
        as cumul_montant, AVG(d.montant) as moyenne
        FROM don as d
        INNER JOIN transaction as t ON d.transaction_id = t.id
        INNER JOIN cause as c ON d.cause_id = c.id
        INNER JOIN entite as e ON e.id = c.entity_assoc
        LEFT JOIN prospection as p on p.cause_id = c.id
        INNER JOIN code_occasion oc ON c.code_occasion = oc.id
        WHERE (d.removed = false or d.removed =null)
        AND t.status_id = 3
        AND e.code = '" . $entity . "'
        AND DATE_FORMAT(d.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $date_debut . "' and  DATE_FORMAT(d.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $date_fin . "'
        GROUP BY (d.id)

        ) as g
        group by (g.code)

        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();


        $data = array(
            'result' => $result,
        );
        return $data;
    }

    public function getDayBillStats($today = null) {
        if (is_null($today)) {
            $today = (new \DateTime())->format('Y-m-d');
        } else {
            $curdate = \DateTime::createFromFormat('d/m/Y', $today);
            $today = $curdate->format('Y-m-d');
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('nb', 'nb');
        // get total donateur
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        WHERE typelog_id =  " . LogVar::_LOG_TYPE_INFO_DONATEUR_ . "
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbNewDonateur = $result['nb'];
        //Get total dons ponctuels
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        INNER JOIN don as d ON d.id = l.don_id
        WHERE  typelog_id =  " . LogVar::_LOG_TYPE_INFO_DON_ . " AND d.ispa = 0
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbDonPonctuels = $result['nb'];

        //Get total dons prelevement
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        INNER JOIN don as d ON d.id = l.don_id
        WHERE typelog_id =  " . LogVar::_LOG_TYPE_INFO_DON_ . " AND d.ispa = 1
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbPa = $result['nb'];

        //Get total dons cs
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        INNER JOIN don as d ON d.id = l.don_id
        INNER JOIN type_don as t ON d.type_id = t.id
        WHERE  typelog_id =  " . LogVar::_LOG_TYPE_INFO_DON_ . " AND d.ispa = 1 AND t.code = 'CS'
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbCs = $result['nb'];

        //Get total dons BC
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        INNER JOIN don as d ON d.id = l.don_id
        INNER JOIN type_don as t ON d.type_id = t.id
        WHERE typelog_id =  " . LogVar::_LOG_TYPE_INFO_DON_ . " AND d.ispa = 1 AND t.code = 'BC'
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbBc = $result['nb'];

        //Get total dons ESPECE
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        INNER JOIN don as d ON d.id = l.don_id
        INNER JOIN type_don as t ON d.type_id = t.id
        WHERE typelog_id =  " . LogVar::_LOG_TYPE_INFO_DON_ . " AND d.ispa = 1 AND t.code = 'ESPECE'
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbEs = $result['nb'];

        //Get total dons Maj donateur modification
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        WHERE typelog_id =  " . LogVar::_DONATEUR_MOD_ . "
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbDonateurMod = $result['nb'];

        //Get total dons Maj dons modification
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        WHERE typelog_id =  " . LogVar::_DON_MOD_ . "
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbDonMod = $result['nb'];

        //Get total topage
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        WHERE typelog_id =  " . LogVar::_LOG_TYPE_INFO_PND_ . "
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $topage = $result['nb'];

        //Get total topage
        $sql = "
        SELECT sum(nb_email) as nb
        FROM stat_com as s
        WHERE DATE_FORMAT(s.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(s.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbEmail = $result['nb'];
        //Get total Rf
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        WHERE typelog_id =  " . LogVar::_LOG_TYPE_INFO_RF_ . "
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbRf = $result['nb'];

        //Get total duplicata
        $sql = "
        SELECT count(*) as nb
        FROM log as l
        WHERE typelog_id =  " . LogVar::_DUPLICATA_ADD_ . "
        AND DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $today . " 00:00:00' and  DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $today . " 23:59:59'
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        $nbDuplicata = $result['nb'];

        $billReport = new BillReport();

        $billReport->setNbBc($nbBc);
        $billReport->setNbCs($nbCs);
        $billReport->setNbDonPa($nbPa);
        $billReport->setNbDonPonc($nbDonPonctuels);
        $billReport->setNbDuplicata($nbDuplicata);
        $billReport->setNbEmail($nbEmail);
        $billReport->setNbMajDon($nbDonMod);
        $billReport->setNbTopage($topage);
        $billReport->getNbEspece($nbEs);
        $billReport->getNbRf($nbRf);
        $billReport->setNbNewDonateur($nbNewDonateur);
        $billReport->setNbMajDonateur($nbDonateurMod);
        $billReport->setCreatedAt(\DateTime::createFromFormat('Y-m-d', $today));

        $this->em->persist($billReport);
        $this->em->flush();
    }

    public function getAdvancedBillStats($date_debut, $date_fin) {

        $repLog = $this->em->getRepository('FulldonIntersaBundle:Log');
        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('nb_new_donateur', 'nb_new_donateur');
        $rsm->addScalarResult('nb_don_ponctuel', 'nb_don_ponctuel');
        $rsm->addScalarResult('nb_don_prelevement', 'nb_don_prelevement');
        $rsm->addScalarResult('nb_cs', 'nb_cs');
        $rsm->addScalarResult('nb_espece', 'nb_espece');
        $rsm->addScalarResult('nb_topage', 'nb_topage');
        $rsm->addScalarResult('nb_email', 'nb_email');
        $rsm->addScalarResult('nb_duplicata', 'nb_duplicata');
        $rsm->addScalarResult('nb_rf', 'nb_rf');
        $rsm->addScalarResult('nb_bc', 'nb_bc');
        $rsm->addScalarResult('maj_donateur', 'maj_donateur');
        $rsm->addScalarResult('maj_don', 'maj_don');
        $rsm->addScalarResult('created_at', 'created_at');

        $sql = " SELECT * FROM bill_report b
        WHERE  DATE_FORMAT(b.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $date_debut . "' and  DATE_FORMAT(b.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $date_fin . "'
        order by created_at ASC
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();


        $data = array(
            'result' => $result,
        );
        return $data;
    }

    public function getAdvancedOperationStats($code_occasion, $entity) {

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('comptage', 'comptage');
        $rsm->addScalarResult('code', 'code');
        $rsm->addScalarResult('cible', 'cible');
        $rsm->addScalarResult('moyenne', 'moyenne');
        $rsm->addScalarResult('cumul', 'cumul');
        $rsm->addScalarResult('pros', 'pros');



        $sql = "
        SELECT SUM(g.comptage) comptage, g.code, g.cible , g.pros,  AVG (g.moyenne) as moyenne, sum(g.cumul_montant) as cumul
        FROM (
        SELECT COUNT(distinct d.id) as comptage,c.code as code, c.cible as cible, count(distinct p.id) as pros,
        IFNULL(SUM( distinct(SELECT SUM( pr.montant) from prelevement as pr where d.abo_id=pr.abo_id  and pr.rejet = 0)),0)+
        IFNULL(SUM( distinct (SELECT dd.montant from don as dd where dd.id=d.id and dd.abo_id is null)),0)
        as cumul_montant, AVG(d.montant) as moyenne
        FROM don as d
        INNER JOIN transaction as t ON d.transaction_id = t.id
        INNER JOIN cause as c ON d.cause_id = c.id
        INNER JOIN entite as e ON e.id = c.entity_assoc
        LEFT JOIN prospection as p on p.cause_id = c.id
        INNER JOIN code_occasion oc ON c.code_occasion = oc.id
        WHERE (d.removed = false or d.removed =null)
        AND t.status_id = 3
        AND e.code = '" . $entity . "'
        AND oc.code = '" . $code_occasion . "'
        GROUP BY (d.id)
        Having cumul_montant > 0
        ) as g
        group by (g.code)
        ";

        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();


        $data = array(
            'result' => $result,
        );
        return $data;
    }

    public function getAdvancedOperationByDateStats($code_occasion, $entity, $date_debut, $date_fin) {




        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('comptage', 'comptage');
        $rsm->addScalarResult('code', 'code');
        $rsm->addScalarResult('cible', 'cible');
        $rsm->addScalarResult('moyenne', 'moyenne');
        $rsm->addScalarResult('cumul', 'cumul');
        $rsm->addScalarResult('pros', 'pros');



        $sql = "
        SELECT SUM(g.comptage) comptage, g.code, g.cible , g.pros,  AVG (g.moyenne) as moyenne, sum(g.cumul_montant) as cumul
        FROM (
        SELECT COUNT(distinct d.id) as comptage,c.code as code, c.cible as cible, count(distinct p.id) as pros,
        IFNULL(SUM( distinct(SELECT SUM( pr.montant) from prelevement as pr where d.abo_id=pr.abo_id  and pr.rejet = 0)),0)+
        IFNULL(SUM( distinct (SELECT dd.montant from don as dd where dd.id=d.id and dd.abo_id is null)),0)
        as cumul_montant, AVG(d.montant) as moyenne
        FROM don as d
        INNER JOIN transaction as t ON d.transaction_id = t.id
        INNER JOIN cause as c ON d.cause_id = c.id
        INNER JOIN entite as e ON e.id = c.entity_assoc
        LEFT JOIN prospection as p on p.cause_id = c.id
        INNER JOIN code_occasion oc ON c.code_occasion = oc.id
        WHERE (d.removed = false or d.removed =null)
        AND t.status_id = 3
        AND e.code = '" . $entity . "'
        AND oc.code = '" . $code_occasion . "'
        AND DATE_FORMAT(d.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $date_debut . "' and  DATE_FORMAT(d.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $date_fin . "'
        GROUP BY (d.id)
        Having cumul_montant > 0
        ) as g
        group by (g.code)
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();


        $data = array(
            'result' => $result,
        );
        return $data;
    }

    public function getAdvancedAttritionStats($code_occasion, $entity) {


        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('code', 'code');
        $rsm->addScalarResult('cible', 'cible');
        $rsm->addScalarResult('nb0', 'nb0');
        $rsm->addScalarResult('nb5', 'nb5');
        $rsm->addScalarResult('nb11', 'nb11');
        $rsm->addScalarResult('pros', 'pros');


        $sql = "
            SELECT g.code, g.cible, count(distinct p.id) as pros,g.nb0, g.nb5, g.nb11
        FROM (
            SELECT c.code as code, c.cible as cible, c.id as cause_id,

        IFNULL(count((SELECT count(distinct pr.abo_id) from prelevement as pr where pr.rejet = false and d.abo_id=pr.abo_id having count(pr.id) = 0)),0) as nb0,
        IFNULL(count((SELECT count(distinct pr.abo_id) from prelevement as pr where pr.rejet = false and d.abo_id=pr.abo_id having count(pr.id) <= 5)),0) as nb5,
        IFNULL(count((SELECT count(distinct pr.abo_id) from prelevement as pr where pr.rejet = false and d.abo_id=pr.abo_id having count(pr.id) <= 11)),0) as nb11

        FROM don as d
        INNER JOIN cause as c ON d.cause_id = c.id
        INNER JOIN entite as e ON e.id = c.entity_assoc
        INNER JOIN code_occasion oc ON c.code_occasion = oc.id
                INNER JOIN abonnement as a on d.abo_id = a.id
        WHERE
        e.code = '" . $entity . "'
        AND oc.code = '" . $code_occasion . "'
        AND d.ispa = true
        AND a.actif = false

        GROUP BY code
        ) as g
        LEFT JOIN prospection as p on g.cause_id = p.cause_id GROUP BY code
        ";


        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();


        $data = array(
            'result' => $result,
        );
        return $data;
    }

    public function getCumulatifStats($date_debut, $date_fin, $entity = null) {



        $repDon = $this->em->getRepository('FulldonDonateurBundle:Don');
        $data = array();
        $data['result'] = array();

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('code', 'code');
        $rsm->addScalarResult('cible', 'cible');

        $sql = "
        SELECT oc.code as code, oc.cible as cible, oc.name as name
        FROM don as d
        INNER JOIN cause as c ON d.cause_id = c.id
        INNER JOIN entite as e ON e.id = c.entity_assoc
        INNER JOIN code_occasion oc ON c.code_occasion = oc.id
        WHERE (d.removed = false or d.removed =null)
        AND e.code = '" . $entity . "'
        AND DATE_FORMAT(d.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $date_debut . "' and  DATE_FORMAT(d.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $date_fin . "'
        GROUP BY (oc.code)
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $occas = $query->getResult();

        $global_cumul = 0;
        $global_avg = 0;
        $global_quantity = 0;
        foreach ($occas as $oc) {
            $all_activities = $this->getAdvancedOperationByDateStats($oc['code'], $entity, $date_debut, $date_fin);
            $sub_global_cumul = 0;
            $sub_global_avg = 0;
            $sub_global_quanitity = 0;

            foreach ($all_activities['result'] as $aa) {
                $sub_global_cumul += $aa['cumul'];
                $sub_global_avg += $aa['moyenne'];
                $sub_global_quanitity += $aa['comptage'];
            }
            if (count($all_activities['result']) > 0) {
                $sub_global_avg = $sub_global_avg / count($all_activities['result']);
            } else {
                $sub_global_avg = 0;
            }

            $data['result'][] = array(
                'content' => $all_activities['result'],
                'cumul' => $sub_global_cumul,
                'avg' => $sub_global_avg,
                'quantity' => $sub_global_quanitity,
                'occasion' => $oc
                    ,
            );
            $global_cumul += $sub_global_cumul;
            $global_avg += $sub_global_avg;
            $global_quantity += $sub_global_quanitity;
        }
        $data['global_cumul'] = $global_cumul;
        if (count($occas) > 0) {
            $data['global_avg'] = $global_avg / count($occas);
        } else {
            $data['global_avg'] = 0;
        }

        $data['global_quantity'] = $global_quantity;

        return $data;
    }

    // daystat
    public function getDayStat($date_debut, $date_fin, $entity) {





        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('comptage', 'comptage');
        $rsm->addScalarResult('type', 'type');
        $rsm->addScalarResult('cumul', 'cumul');


        $sql = "

        SELECT COUNT(distinct d.id) as comptage,t.code as type,
        IFNULL(SUM( distinct (SELECT dd.montant from don as dd where dd.id=d.id )),0)
        as cumul
        FROM don as d
        INNER JOIN type_don as t ON d.type_id = t.id
        INNER JOIN cause as c ON d.cause_id = c.id
        INNER JOIN entite as e ON e.id = c.entity_assoc
        WHERE (d.removed = false or d.removed =null)
        AND e.code = '" . $entity . "'
        AND DATE_FORMAT(d.created_at,'%Y-%m-%d %H:%i:%s') >= '" . $date_debut . "' and  DATE_FORMAT(d.created_at,'%Y-%m-%d %H:%i:%s') <= '" . $date_fin . "'
        GROUP BY (t.code)

        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $par_type = $query->getResult();




//        $donRep = $this->em->getRepository('FulldonDonateurBundle:Don') ;
//        $dons= $donRep->createQueryBuilder('d')
//            ->select('d.*')
//            ->join('d.cause','c')
//            ->join('c.codeAnalytique' ,'ca')
//            ->join('c.codeOccasion','co')
//            ->join('d.abonnement', 'a')
//            ->where('d.removed = false or d.removed = null')
//            ->andwhere('d.ispa = true');
//        $dons = $dons->andwhere('d.createdAt <= :fin')
//            ->setParameter('fin', $date_fin);
//        $dons = $dons->andwhere('d.createdAt >= :debut')
//            ->setParameter('debut', $date_debut);
//
//        $new_pa = $dons->getQuery()->getResult();
//
//        $grosDon= $donRep->createQueryBuilder('d')
//            ->select('d.*')
//            ->join('d.cause','c')
//            ->join('c.codeAnalytique' ,'ca')
//            ->join('c.codeOccasion','co')
//            ->where('d.removed = false or d.removed = null')
//            ->andwhere('d.createdAt <= :fin')
//            ->setParameter('fin', $date_fin)
//            ->andwhere('d.createdAt >= :debut')
//            ->setParameter('debut', $date_debut)
//            ->andwhere('d.montant >= 400')
//            ->getQuery()
//            ->getResult();

        $data = array(
            'par_type' => $par_type,
                //'new_pa'   => $new_pa,
                //'gros_dons'=> $grosDon
        );

        return $data;
    }

    public function getUniqueRefDonateur() {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('max_id', 'max_id');

        $sql = "SELECT MAX(d.id) as max_id
        FROM donateur as d
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        return $result['max_id'] + 1;
    }

    public function getStatDonateur() {

        $donateur_rnbemails = 0;
        $donateur_rtfixe = 0;
        $donateur_rtmobile = 0;
        $donateur_actif = 0;
        $donateur_anbemails = 0;
        $donateur_atfixe = 0;
        $donateur_atmobile = 0;
        $nb_12mois = 0;
        $nb_24mois = 0;
        $nb_36mois = 0;
        $donateur_removed = 0;
        $rsm = new ResultSetMapping();
        $year = date("Y");
        $rsm->addScalarResult('comptage', 'comptage');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('mois', 'mois');
        $rsm->addScalarResult('nom_entity', 'nom_entity');
        $rsm->addScalarResult('cpt', 'cpt');
        // Nombre  de donateurs actifs 0/12 mois.

        $sql_12mois = "SELECT COUNT(d.id) as comptage
        FROM don as d
        WHERE (d.removed = false or d.removed =null)
        and created_at <= '" . $year . "-01-01' and  created_at >= '" . ($year - 1) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_12mois, $rsm);
        $nb_12mois = $query->getOneOrNullResult();

        // Nombre  de donateurs actifs 12/24 mois.

        $sql_24mois = "SELECT COUNT(d.id) as comptage
        FROM don as d
        WHERE (d.removed = false or d.removed =null)
        and created_at <= '" . ($year - 2) . "-01-01' and created_at >= '" . ($year - 1) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_24mois, $rsm);
        $nb_24mois = $query->getOneOrNullResult();

        // Nombre  de donateurs actifs 24/36 mois.

        $sql_36mois = "SELECT COUNT(d.id) as comptage
        FROM don as d
        WHERE d.removed = false
        and created_at <= '" . ($year - 3) . "-01-01' and  created_at >= '" . ($year - 2) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_36mois, $rsm);
        $nb_36mois = $query->getOneOrNullResult();
        //Nombre de donateurs par motif


        $sql_disabled_donateur = "SELECT IFNULL(COUNT(d.id),0) as comptage, mdd.name
        FROM donateur as d
        RIGHT JOIN motif_disable_donateur as mdd ON d.motif_donateur_id = mdd.id
        WHERE d.removed = true
        GROUP BY (mdd.name)
        ";
        $query = $this->em->createNativeQuery($sql_disabled_donateur, $rsm);
        $disabled_donateur = $query->getResult();

        $repDonateur = $this->em->getRepository('FulldonDonateurBundle:Donateur');
        $donateurstat = $repDonateur->createQueryBuilder('d')
                        ->select('count(d.id) as cpt, d.removed, count(d.email) as nb_emails, count(d.telephoneMobile) as t_mobile, count(d.telephoneFixe) as t_fixe')
                        ->groupBy('d.removed')
                        ->getQuery()->getResult();
        foreach ($donateurstat as $ds) {
            if ($ds['removed'] == true) {
                $donateur_removed = $ds['cpt'];
                $donateur_rnbemails = $ds['nb_emails'];
                $donateur_rtfixe = $ds['t_fixe'];
                $donateur_rtmobile = $ds['t_mobile'];
            } else {
                $donateur_actif = $ds['cpt'];
                $donateur_anbemails = $ds['nb_emails'];
                $donateur_atfixe = $ds['t_fixe'];
                $donateur_atmobile = $ds['t_mobile'];
            }
        }
        // mapping results to the message entity
        $rsm->addScalarResult('moyenne_age', 'moyenne_age');

        $sql_moyenne = "SELECT AVG(YEAR(CURDATE())-YEAR(datenaissance)) as moyenne_age
        FROM donateur";
        $query = $this->em->createNativeQuery($sql_moyenne, $rsm);
        $stats_moyenne_age = $query->getOneOrNullResult();
        $data = array(
            'donateur_removed' => $donateur_removed,
            'donateur_actif' => $donateur_actif,
            'donateur_emails' => $donateur_rnbemails + $donateur_anbemails,
            'donateur_tfixe' => $donateur_rtfixe + $donateur_atfixe,
            'donateur_tmobile' => $donateur_rtmobile + $donateur_atmobile,
            'moyenne_age' => round($stats_moyenne_age['moyenne_age']),
            'nb_12mois' => $nb_12mois['comptage'],
            'nb_24mois' => $nb_24mois['comptage'],
            'nb_36mois' => $nb_36mois['comptage'],
            'disabled_donateur' => $disabled_donateur,
        );
        return $data;
    }

    public function getStatNewDonateur() {
        $repEntity = $this->em->getRepository('FulldonIntersaBundle:Entity');
        $rsm = new ResultSetMapping();
        $year = date("Y");
        $rsm->addScalarResult('comptage', 'comptage');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('mois', 'mois');
        $rsm->addScalarResult('nom_entity', 'nom_entity');
        $rsm->addScalarResult('cpt', 'cpt');

        $sql_new_donateurs_by_entity_preyear = "SELECT COUNT(distinct d.user_id) as cpt, DATE_FORMAT(d.created_at,'%m') as mois, E.name as nom_entity
                    FROM don as d
                    INNER JOIN cause as c ON d.cause_id = c.id
                    RIGHT JOIN entite as E ON c.entity_assoc = E.id
                    WHERE (d.removed = false or d.removed =null)
                    and d.created_at <= '" . $year . "-01-01' and  d.created_at >= '" . ($year - 1) . "-01-01'
                    GROUP BY mois,nom_entity ";

        $query = $this->em->createNativeQuery($sql_new_donateurs_by_entity_preyear, $rsm);
        $new_donateurs_by_entity_preyear = $query->getResult();

        $sql_new_donateurs_by_entity_curyear = "SELECT COUNT(distinct d.user_id) as cpt, DATE_FORMAT(d.created_at,'%m') as mois, E.name as nom_entity
                    FROM don as d
                    INNER JOIN cause as c ON d.cause_id = c.id
                    RIGHT JOIN entite as E ON c.entity_assoc = E.id
                    WHERE (d.removed = false or d.removed =null)
                    and d.created_at <= '" . ($year + 1) . "-01-01' and  d.created_at >= '" . ($year) . "-01-01'
                    GROUP BY mois,nom_entity ";

        $query = $this->em->createNativeQuery($sql_new_donateurs_by_entity_curyear, $rsm);
        $new_donateurs_by_entity_curyear = $query->getResult();

        $entities = $repEntity->findAll();
        $data = array(
            'tab_nouveau_donateur_curyear' => $new_donateurs_by_entity_curyear,
            'tab_nouveau_donateur_preyear' => $new_donateurs_by_entity_preyear,
            'entities' => $entities,
        );
        return $data;
    }

    public function getStatDonByMode($date_debut, $date_fin) {
        $repDon = $this->em->getRepository('FulldonDonateurBundle:Don');
        $donstat = $repDon->createQueryBuilder('d')
                        ->select('count( d.id) as cpt, t.code')
                        ->join('d.type', 't')
                        ->groupBy('t.code')
                        ->getQuery()->getResult();
        foreach ($donstat as $ds) {
            switch ($ds['code']) {
                case 'PA':
                    $don_pa = $ds['cpt'];
                    break;
                case 'VIREMENT':
                    $don_virement = $ds['cpt'];

                    break;
                case 'ESPECE':
                    $don_espece = $ds['cpt'];

                    break;
                case 'CS':
                    $don_cs = $ds['cpt'];

                    break;
                case 'BC':
                    $don_bc = $ds['cpt'];

                    break;
                case 'PAYPAL':
                    $don_paypal = $ds['cpt'];

                    break;
                case 'INTERNET':
                    $don_cb = $ds['cpt'];

                    break;
            }
        }
        $data = array(
            'don_pa' => $don_pa,
            'don_cb' => $don_cb,
            'don_bc' => $don_bc,
            'don_paypal' => $don_paypal,
            'don_cs' => $don_cs,
            'don_espece' => $don_espece,
            'don_virement' => $don_virement,
        );
        return $data;
    }

    public function getStatPA($date_debut, $date_fin) {

        $repDon = $this->em->getRepository('FulldonDonateurBundle:Don');
        $repPrelevement = $this->em->getRepository('FulldonDonateurBundle:Prelevement');

        $rsm = new ResultSetMapping();
        $year = date("Y");
        $rsm->addScalarResult('comptage', 'comptage');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('mois', 'mois');
        $rsm->addScalarResult('nom_entity', 'nom_entity');
        $rsm->addScalarResult('cpt', 'cpt');

        //Nombre de PA Actif sur les 12 derniers mois
        $sql_12mois_pa = "SELECT COUNT(DISTINCT p.abo_id) as comptage
        FROM prelevement as p
        WHERE (p.rejet = false or p.rejet =null)
        and date_prelevement <= '" . $year . "-01-01' and  date_prelevement >= '" . ($year - 1) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_12mois_pa, $rsm);
        $nb_12mois_pa = $query->getOneOrNullResult();

        // Nombre  PA actifs sur les 12/24 mois.

        $sql_24mois_pa = "SELECT COUNT(DISTINCT p.abo_id) as comptage
        FROM prelevement as p
        WHERE (p.rejet = false or p.rejet =null)
        and date_prelevement <= '" . ($year - 1) . "-01-01' and  date_prelevement >= '" . ($year - 2) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_24mois_pa, $rsm);
        $nb_24mois_pa = $query->getOneOrNullResult();

        // Stops depuis le début de l'année
        $sql_stops_pa = "SELECT COUNT(a.id) as comptage
        FROM abonnement as a
        WHERE a.actif = false
        and disabled_at >= '" . ($year) . "-01-01'
        ";
        $query = $this->em->createNativeQuery($sql_stops_pa, $rsm);
        $stops_pa = $query->getOneOrNullResult();

        $abostat = $repDon->createQueryBuilder('d')
                        ->select('count(d.id) as cpt, a.actif, m.name')
                        ->join('d.abonnement', 'a')
                        ->LeftJoin('a.motif', 'm')
                        ->where('d.ispa IS NOT NULL')
                        ->groupBy(' m.name')
                        ->getQuery()->getResult();

        $pa_actif = $repDon->createQueryBuilder('d')
                        ->select('count(d.id) as cpt')
                        ->join('d.abonnement', 'a')
                        ->where('d.ispa IS NOT NULL and d.removed = false')
                        ->getQuery()->getOneOrNullResult();

        $prelevementstat = $repPrelevement->createQueryBuilder('p')
                        ->select('count(p.id) as cpt, p.rejet')
                        ->groupBy('p.rejet')
                        ->getQuery()->getResult();

        foreach ($prelevementstat as $p) {
            if ($p['rejet'] == false) {
                $pre_accept = $p['cpt'];
            } else {
                $pre_rejet = $p['cpt'];
            }
        }


        $data = array(
            'abo' => $abostat,
            'pre_rejet' => $pre_rejet,
            'pre_accept' => $pre_accept,
            'nb_12mois_pa' => $nb_12mois_pa['comptage'],
            'nb_24mois_pa' => $nb_24mois_pa['comptage'],
            'stops_pa' => $stops_pa['comptage'],
            'pa_actif' => $pa_actif['cpt']
        );
        return $data;
    }

    public function getStatPnd($date_debut, $date_fin) {
        $repPnd = $this->em->getRepository('FulldonDonateurBundle:Pnd');
        $pndStats = $repPnd->createQueryBuilder('d')
                        ->select('count(d.id) as cpt, m.name')
                        ->join('d.motif', 'm')
                        ->groupBy('m.name')
                        ->getQuery()->getResult();
        $data = array(
            'pnd' => $pndStats,
        );
        return $data;
    }

    public function updateCronTask(MarketingCronTask $cron) {
        $cron->setAppFolder($this->container->getParameter('folder_app'));
        $this->em->persist($cron);
    }

    public function getDonateurResult($request, $offset = 1, $batchSize = 1000) {


        $memo_search = array();
        $params = null;
        $donateur = new Donateur();
        $dem = $this->em;
        $repCat = $dem->getRepository('FulldonDonateurBundle:CategoryDonateur');
        $coldisplay = array();
        $withcat = $request->get('withcat');
        $withemail = $request->get('withemail');
        $withtel = $request->get('withtel');
        $statutDonateur = $request->get('statut_donateur');
        $sortelement = $request->get('sortelement');
        $sortdirection = $request->get('sortdirection');
        $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
        $pre_params = $resultform;
        $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
        $columns = $request->get('columns', 'list[]=numdonateur&list[]=nom&list[]=prenom&list[]=nomentreprise&list[]=statut');
        $restCols = array(
            'refDonateur' => 'numdonateur',
            'nom' => 'nom',
            'prenom' => 'prenom',
            'nomEntreprise' => 'nomentreprise',
            'isoville' => 'ville',
            'isopays' => 'pays',
            'zipcode' => 'zipcode',
            'removed' => 'statut',
            'dateNaissance' => 'birthday',
            'email' => 'email',
            'telephoneMobile' => 'telmobile',
            'telephoneFixe' => 'telfixe',
            'createdAt' => 'createdat',
            'cat',
            'adresse');
        $page = $request->get('page', 1);
        if ($request->getMethod() == 'GET' && count($pre_params) > 0) {



            $app = $this->container->getParameter('elastic_db_name');
            $donateurs = $this->container->get('fos_elastica.finder.' . $app . '.donateur');
            $elasticaQuery = new \Elastica\Query();

            $query_part = new \Elastica\Query\Bool();
            $filters = new \Elastica\Filter\Bool();
            if (isset($resultform['removed'])) {
                ($resultform['removed']) ? $resultform['removed'] = true : $resultform['removed'] = false;
            }
            if ($withcat == 'true') {
                $nestedRfQuery = new \Elastica\Filter\Nested();
                $nestedRfQuery->setPath('categories');
                $nestedRfQuery->setFilter(new \Elastica\Filter\Exists('categories'));

                //adding the parameters to the main query
                $filters->addMust($nestedRfQuery);
            } elseif ($withcat == 'false') {
                $nestedRfQuery = new \Elastica\Filter\Nested();
                $nestedRfQuery->setPath('categories');
                $nestedRfQuery->setFilter(new \Elastica\Filter\Exists('categories'));

                //adding the parameters to the main query
                $filters->addMustNot($nestedRfQuery);
            }
            if ($withemail == 'true') {
                $filters->addMust(
                        new \Elastica\Filter\Exists('email')
                );
            } elseif ($withemail == 'false') {
                $filters->addMust(
                        new \Elastica\Filter\Missing('email')
                );
            }
            if ($withtel == 'true') {
                $filters->addMust(
                        new \Elastica\Filter\Exists('telephoneMobile')
                );
            } elseif ($withtel == 'false') {
                $filters->addMust(
                        new \Elastica\Filter\Missing('telephoneMobile')
                );
            }
            if ($statutDonateur == 'on') {
                $query_part->addMust(
                        new \Elastica\Query\Match('removed', false)
                );
            } elseif ($statutDonateur == 'off') {
                $query_part->addMust(
                        new \Elastica\Query\Match('removed', true)
                );
            }

            foreach ($resultform as $key => $value) {
                if (!empty($value)) {
                    if (in_array($key, array('isoville', 'isopays', 'civilite', 'refDonateur', 'allowRf', 'removed'))) {
                        $query_part->addMust(
                                new \Elastica\Query\Match($key, $value)
                        );
                    } elseif (in_array($key, array('nom', 'prenom', 'email', 'adresse1', 'adresse2', 'adresse3', 'adresse3', 'zipcode', 'nomEntreprise'))) {
                        $query_part->addMust(
                                new \Elastica\Query\MatchPhrasePrefix($key, $value)
                        );
                    } elseif (in_array($key, array('zipcode'))) {
                        $query_part->addMust(
                                new \Elastica\Query\Prefix($key, $value)
                        );
                    } elseif (in_array($key, array('dateNaissance'))) {
                        $value = $value . ' 00:00:00';
                        $d = \DateTime::createFromFormat('d/m/Y H:i:s', $value);
                        $mydate = $d->format('c');
                        $filters->addMust(
                                new \Elastica\Filter\Range($key, array(
                            'gte' => $mydate,
                            "lte" => $mydate
                                ))
                        );
                    } elseif (in_array($key, array('categories'))) {

                        $filtersQuery = new \Elastica\Query\Bool();
                        $catQuery = new \Elastica\Query\Terms();
                        $catQuery->setTerms('categories.id', $value);
                        $catQuery->setMinimumMatch(1);
                        $filtersQuery->addMust($catQuery);
                        $nestedRfQuery = new \Elastica\Query\Nested();
                        $nestedRfQuery->setPath('categories');
                        $nestedRfQuery->setQuery($filtersQuery);

                        //adding the parameters to the main query
                        $query_part->addMust($nestedRfQuery);
                    }
                    $memo_search[$key] = $value;
                }
            }
            $query = new \Elastica\Query\Filtered($query_part, $filters);
            //$elasticaQuery->setQuery($query_part);
            $finalQuery = new \Elastica\Query($query);
            if (!empty($sortelement) && !empty($sortdirection)) {

                $finalQuery->setSort(array(array_search($sortelement, $restCols) => array('order' => $sortdirection)));
            } else {
                $finalQuery->setSort(array('id' => array('order' => 'desc')));
            }

            $result = $donateurs->findPaginated($finalQuery);
            $total_donateur = $result->getNbResults();
//            var_dump($total_donateur);
//            die;
            $donateur_per_page = $this->container->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
            $last_page = ceil($total_donateur / $donateur_per_page);
            //$result->setMaxPerPage(20);
            $result->setCurrentPage($page);
            $currentPage = $result->getCurrentPageResults();

            unset($pre_params['page']);
            $url = array();
            foreach ($pre_params as $key => $ele) {
                if ($key == 'categories') {
                    if (is_array($ele)) {
                        foreach ($ele as $v) {
                            $url[] = "fulldon_donateurbundle_donateursearchtype[$key][]=$v";
                        }
                    }
                } else {
                    $url[] = "fulldon_donateurbundle_donateursearchtype[$key]=$ele";
                }
            }
            $params = implode('&', $url);
            // Adding columns to params
            $params .= '&columns=' . urlencode($columns) . "&sortelement=$sortelement&sortdirection=$sortdirection";
            $params .= '&withemail=' . $withemail . '&withtel=' . $withtel . '&withcat=' . $withcat . '&statut_donateur=' . $statutDonateur;
        }// end if
        $mycol = explode('&', $columns);
        foreach ($mycol as $col) {
            $coldisplay[] = substr($col, 7, strlen($col));
        }

        foreach ($coldisplay as $tab) {
            $index = array_search($tab, $restCols);
            unset($restCols[$index]);
        }
        $result = $donateurs->findPaginated($finalQuery);
        $total_donateur = $result->getNbResults();
        $result->setMaxPerPage($batchSize);
        $last_page = ceil($total_donateur / $batchSize);
        $result->setCurrentPage($offset);
        $currentPage = $result->getCurrentPageResults();


        return array('result' => $result, 'nboffset' => $last_page, 'coldisplay' => $coldisplay);
    }

    public function getDonsResult($request, $offset = 1, $batchSize = 1000) {
        $db = $this->em;
        $params = null;
        $total_dons = 0;
        $pre_params = $request->query->all();
        $modeRep = $db->getRepository('FulldonDonateurBundle:ModePaiement');
        $tellmeall = true;
        $sortelement = $request->get('sortelement');
        $sortdirection = $request->get('sortdirection');
        $coldisplay = array();
        $columns = $request->get('columns', 'list[]=numdon&list[]=amount&list[]=nom&list[]=prenom&list[]=codeactivite&list[]=statut&list[]=modepay&list[]=createdat&list[]=typedon');

        $restCols = array(
            'id' => 'numdon',
            'dateFiscale' => 'datefiscale',
            'montant' => 'amount',
            'nom',
            'nomentreprise',
            'prenom',
            'cause.code' => 'codeactivite',
            'createdAt' => 'createdat',
            'transaction.statut.id' => 'statut',
            'modePaiement.codeSolution' => 'modepay',
            'ispa' => 'typedon',
            'rfs.id' => 'rfs',
            'lot' => 'lot',
            'cause.codeOccasion.code' => 'codeoccasion',
            'cause.codeOccasion.codeCompagne.code' => 'codecampagne'
        );
        if ($request->getMethod() == 'GET' && count($pre_params) > 0) {

            $montant_don = $request->get('montant_don');
            $id_don = $request->get('id_don');
            $montant_choice = $request->get('montant_choice');
            $cause = $request->get('cause');
            $num_cheque = $request->get('num_cheque');
            $date_debut = $request->get('date_debut');
            $date_fin = $request->get('date_fin');
            $type_don = $request->get('type_don');
            $iban = $request->get('iban');
            $date_annule_fin = $request->get('date_annule_fin');
            $date_annule_debut = $request->get('date_annule_debut');
            $mode_paiement = $request->get('mode_paiement');
            $cause = $request->get('cause');
            $code_occasion = $request->get('code_occasion');
            $code_campagne = $request->get('code_campagne');
            $num_rf = $request->get('num_rf');
            $lot = $request->get('lot_don');
            $date_stop_fin = $request->get('date_stop_fin');
            $date_stop_debut = $request->get('date_stop_debut');
            $d_actif = $request->get('d_actif');
            $d_inactif = $request->get('d_inactif');
            $is_rf = $request->get('is_rf');
            $baseQuery = false;


            $app = $this->container->getParameter('elastic_db_name');
            $dons = $this->container->get('fos_elastica.finder.' . $app . '.don');
            $elasticaQuery = new \Elastica\Query();

            $query_part = new \Elastica\Query\Bool();
            $filters = new \Elastica\Filter\Bool();



            switch ($montant_choice) {
                case 'inf':
                    $operator = 'lte';
                    break;
                case 'sup':
                    $operator = 'gte';
                    break;
                case 'eq':
                    $operator = '=';
                    break;
                default :
                    break;
            }
            if (isset($montant_don) && !is_null($montant_don) && $montant_don != "") {
                if ($operator == '=') {
                    $baseQuery = true;
                    $query_part->addMust(
                            new \Elastica\Query\Match('montant', $montant_don)
                    );
                } else {
                    $filters->addMust(
                            new \Elastica\Filter\Range('montant', array(
                        $operator => $montant_don
                            ))
                    );
                }
            }
            if (isset($id_don) && !empty($id_don)) {

                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('id', $id_don)
                );
            }
            if (isset($type_don) && !empty($type_don)) {
                $baseQuery = true;
                $bol = 'false';
                if ($type_don == 'regulier') {
                    $bol = 'true';
                }


                $query_part->addMust(
                        new \Elastica\Query\Term(array('ispa' => $bol))
                );
            }
            if (isset($d_actif) && !empty($d_actif)) {
                $baseQuery = true;
                if ($d_actif == "on")
                    $var = 'false';
                else
                    $var = 'true';
                $query_part->addMust(
                        new \Elastica\Query\Term(array('removed' => $var))
                );
            }

            if (isset($d_inactif) && !empty($d_inactif)) {
                $baseQuery = true;
                if ($d_inactif == "on")
                    $var = 'true';
                else
                    $var = 'false';
                $query_part->addMust(
                        new \Elastica\Query\Term(array('removed' => $var))
                );
            }


            if (isset($cause) && !empty($cause)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('cause.code', $cause)
                );
            }

            if (isset($code_occasion) && !empty($code_occasion)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('cause.codeOccasion.code', $code_occasion)
                );
            }

            if (isset($code_campagne) && !empty($code_campagne)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('cause.codeOccasion.codeCompagne.code', $code_campagne)
                );
            }

            if (isset($lot) && !empty($lot)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('lot', $lot)
                );
            }
            if (isset($date_debut) && !empty($date_debut)) {
                $date_debut = $date_debut . ' 00:00:00';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('dateFiscale', array(
                    'gte' => $mydate
                        ))
                );
            }

            if (isset($date_fin) && !empty($date_fin)) {

                $date_fin = $date_fin . ' 23:59:59';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('dateFiscale', array(
                    'lte' => $mydate
                        ))
                );
            }
            if (isset($date_annule_debut) && !empty($date_annule_debut)) {
                $date_annule_debut = $date_annule_debut . ' 00:00:00';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_annule_debut);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('removedAt', array(
                    'gte' => $mydate
                        ))
                );
            }
            if (isset($date_annule_fin) && !empty($date_annule_fin)) {
                $date_annule_fin = $date_annule_fin . ' 23:59:59';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_annule_fin);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('removedAt', array(
                    'lte' => $mydate
                        ))
                );
            }

            if (isset($date_stop_debut) && !empty($date_stop_debut)) {
                $date_stop_debut = $date_stop_debut . ' 00:00:00';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_stop_debut);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('abonnement.disabledAt', array(
                    'gte' => $mydate
                        ))
                );
            }
            if (isset($date_stop_fin) && !empty($date_stop_fin)) {
                $date_stop_fin = $date_stop_fin . ' 23:59:59';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_stop_fin);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('abonnement.disabledAt', array(
                    'lte' => $mydate
                        ))
                );
            }

            if (isset($iban) && !empty($iban)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\MatchPhrasePrefix('abonnement.iban', $iban)
                );
            }
            if (isset($num_rf) && !empty($num_rf)) {
                $baseQuery = true;
                $filtersQuery = new \Elastica\Query\Bool();
                $rfQuery = new \Elastica\Query\Term();
                $rfQuery->setTerm('rfs.id', $num_rf);
                $filtersQuery->addMust($rfQuery);
                $nestedRfQuery = new \Elastica\Query\Nested();
                $nestedRfQuery->setPath('rfs');
                $nestedRfQuery->setQuery($filtersQuery);

                //adding the parameters to the main query
                $query_part->addMust($nestedRfQuery);
            }

            if (isset($is_rf) && !empty($is_rf)) {
                $baseQuery = true;
                $filtersQuery = new \Elastica\Query\Bool();
                $rfQuery = new \Elastica\Query\Term();
                $rfQuery->setTerm('rfs.id', '00000000');
                $filtersQuery->addMustNot($rfQuery);
                $nestedRfQuery = new \Elastica\Query\Nested();
                $nestedRfQuery->setPath('rfs');
                $nestedRfQuery->setQuery($filtersQuery);

//adding the parameters to the main query
                $query_part->addMust($nestedRfQuery);
            }

            if (isset($num_cheque) && !empty($num_cheque)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\MatchPhrasePrefix('transaction.cheque.numeroCheque', $num_cheque)
                );
            }

            if (isset($mode_paiement) && !empty($mode_paiement)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('modePaiement.codeSolution', $mode_paiement)
                );
            }
            if (!$baseQuery) {
                $query_part = new \Elastica\Query\MatchAll();
            }

            $query = new \Elastica\Query\Filtered($query_part, $filters);
            $finalQuery = new \Elastica\Query($query);
            if (!empty($sortelement) && !empty($sortdirection)) {
                $index = array_search($sortelement, $restCols);
                if (!is_string($index)) {
                    $index = 'id';
                }
                $finalQuery->setSort(array($index => array('order' => $sortdirection)));
            } else {
                $finalQuery->setSort(array('id' => array('order' => 'desc')));
            }

            $mycol = explode('&', $columns);
            foreach ($mycol as $col) {
                $coldisplay[] = substr($col, 7, strlen($col));
            }


            array_push($coldisplay, "rfs", "datefiscale");
//            array_push($coldisplay, "datefiscale");
//            var_dump($coldisplay);
//            die('$coldisplay');

            $result = $dons->findPaginated($finalQuery);

            $total_dons = $result->getNbResults();
            $result->setMaxPerPage($batchSize);
            $last_page = ceil($total_dons / $batchSize);
            $result->setCurrentPage($offset);
            $currentPage = $result->getCurrentPageResults();
            if (count($currentPage) == 0) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
            }
        }
//        var_dump($result);
//        die('$result');
        return array('result' => $result, 'nboffset' => $last_page, 'coldisplay' => $coldisplay);
    }

    function dqlDate($date, $format = 'd/m/Y') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d->format('Y-m-d');
    }

    function getLeftTime() {
        $accountRep = $this->em->getRepository('FulldonIntersaBundle:Account');
        $account = $accountRep->find(1);

        $dStart = new \DateTime();
        $dEnd = $account->getCreatedAt()->add(new \DateInterval('P15D'));
        $dDiff = $dStart->diff($dEnd);
        return $dDiff->days;
    }

    public function isNewEmail($email) {
        // Check if it exists first
        $db = $this->em;
        $repUsers = $db->getRepository('FulldonDonateurBundle:Donateur');
        $query = $repUsers->createQueryBuilder('d')
                ->select('COUNT(d)')
                ->where("d.email = :email ")
                ->setParameter('email', $email)
                ->getQuery();

        if ($query->getSingleScalarResult() > 0) {
            return false;
        }

        return true;
    }

    public function changeStatus(Array $dons) {
        $db = $this->em;
        foreach ($dons as $key => $don) {
            // Si le don a une statut
            $code_statut = 'don_annule';
            if ($don->getTransaction()->getStatut()->getCode() == 'attente') {
                $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                $statut = $repStatut->findOneBy(array('code' => $code_statut));
                $don->getTransaction()->setStatut($statut);
                $db->persist($don);
                $db->flush();
            }
        }
    }

}
