<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\DonateurBundle\Entity\MotifRejetPrelevement;
use Fulldon\DonateurBundle\Entity\Prelevement;
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
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\Term;
use Pagerfanta\Adapter\ElasticaAdapter;
use FOS\ElasticaBundle\Repository;

class DonsController extends Controller {

    const _BC_ = 'BC';
    const _CS_ = 'CS';
    const _ESPECES_ = 'ESPECES';
    const _PA_ = 'PA';
    const _VIREMENT_ = 'VIREMENT';
    const _INTERNET_ = 'INTERNET';

    public function sommePrelevementByAbo($abo) {

        $em = $this->getDoctrine()->getManager();
        $prelevementRep = $em->getRepository('FulldonDonateurBundle:Prelevement');
        $prelevements = $prelevementRep->createQueryBuilder('p')
                ->select('SUM(p.montant) as somme, p.id ')
                ->join('p.abo', 'a')
//                ->select('a.id ')
//                    ->where('p.abo_id =:aboid')
//                    ->setParameter('aboid', $abo)
                ->andwhere('p.rejet = 0')
                ->andwhere('p.abo = :abo')
                ->setParameter('abo', $abo)
//                ->groupBy('p.abo_id')
                ->getQuery()
                ->getSingleResult();

//        var_dump($prelevements['somme']);
//        die('$prelevements');
        return $prelevements['somme'];
    }

    public function superSearchAction2() {

//        die('here');
        $request = Request::createFromGlobals();



        $last_page = 0;
        $next_page = 0;
        $previous_page = 0;
        $memo_search = array();
        $params = null;
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $currentPage = 0;
        $total_dons = 0;
        $pre_params = $request->query->all();
//        $pre_params = $request->request->all();
        $modeRep = $db->getRepository('FulldonDonateurBundle:ModePaiement');
        $favoris = $repFavoris->findBy(array('section' => 'dons'));
        $tellmeall = true;
        $sortelement = $request->get('sortelement');
        $sortdirection = $request->get('sortdirection');
        $coldisplay = array();
        $modes = $modeRep->findAll();
        $page = $request->get('page', 1);
        $result2 = null;
        $array_abo_id = array();
        $sum_montant_prev = array();
        $abo_id = -1;
//        var_dump($page);
//        die('last page');
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
        if ($request->getMethod() == 'GET' && count($pre_params) > 0) {  //    if ($request->getMethod() == 'GET' && count($pre_params) > 0)
//            die('here');
            $montant_don = $request->get('montant_don');
            $memo_search['montant_don'] = $montant_don;
            $id_don = $request->get('id_don');
            $memo_search['id_don'] = $id_don;
            $montant_choice = $request->get('montant_choice');
            $memo_search['montant_choice'] = $montant_choice;
            $cause = $request->get('cause');
            $memo_search['montant_choice'] = $montant_choice;
            $num_cheque = $request->get('num_cheque');
            $memo_search['num_cheque'] = $num_cheque;
            $date_debut = $request->get('date_debut');
            $memo_search['date_debut'] = $date_debut;
            $date_fin = $request->get('date_fin');
            $memo_search['date_fin'] = $date_fin;
            $type_don = $request->get('type_don');
            $memo_search['type_don'] = $type_don;
            $iban = $request->get('iban');
            $memo_search['iban'] = $iban;
            $date_annule_fin = $request->get('date_annule_fin');
            $memo_search['date_annule_fin'] = $date_annule_fin;
            $date_annule_debut = $request->get('date_annule_debut');
            $memo_search['date_annule_debut'] = $date_annule_debut;
            $mode_paiement = $request->get('mode_paiement');
            $memo_search['mode_paiement'] = $mode_paiement;
            $cause = $request->get('cause');
            $memo_search['cause'] = $cause;
            $code_occasion = $request->get('code_occasion');
            $memo_search['code_occasion'] = $code_occasion;
            $code_campagne = $request->get('code_campagne');
            $memo_search['code_campagne'] = $code_campagne;
            $num_rf = $request->get('num_rf');
            $memo_search['num_rf'] = $num_rf;
            $lot = $request->get('lot_don');
            $memo_search['lot_don'] = $lot;
            $date_stop_fin = $request->get('date_stop_fin');
            $memo_search['date_stop_fin'] = $date_stop_fin;
            $date_stop_debut = $request->get('date_stop_debut');
            $memo_search['date_stop_debut'] = $date_stop_debut;
            $d_actif = $request->get('d_actif');
            $memo_search['d_actif'] = $d_actif;
            $d_inactif = $request->get('d_inactif');
            $memo_search['d_inactif'] = $d_inactif;
            $is_rf = $request->get('is_rf');
            $memo_search['is_rf'] = $is_rf;
            $baseQuery = false;
            $abo_id = null;
            $array_abo_id = array();
            $indexe_array_abo_id = 0;
            $baseQuery = false;

            $app = $this->container->getParameter('elastic_db_name');
            $dons = $this->get('fos_elastica.finder.' . $app . '.don');
//            var_dump($dons);
//            die('don');
            //$dons->findPaginated($finalQuery);
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





//             var_dump('$query');
//            var_dump($query);
//            die('ici');

            if (!empty($sortelement) && !empty($sortdirection)) {
                $index = array_search($sortelement, $restCols);
                if (!is_string($index)) {
                    $index = 'id';
                }
                $finalQuery->setSort(array($index => array('order' => $sortdirection)));
            } else {
                $finalQuery->setSort(array('id' => array('order' => 'desc')));
            }

//            var_dump($finalQuery);
//            die('$finalQuery');
            $result = $dons->findPaginated($finalQuery);
//             var_dump($page);
//            die('$page');
//              var_dump($result);
//            die('$result');
            $total_dons = $result->getNbResults();

            //$result->setMaxPerPage(20);
            $donateur_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
//            $donateur_per_page = 20;

            $result->setMaxPerPage($donateur_per_page);
            $last_page = ceil($total_dons / $donateur_per_page);
//            if ($page == 0) {
//                $page = 1 ;
//            }
            // $last_page = 65;
//            var_dump($page);
//            die('$page');
            $result->setCurrentPage($page);
            $result2 = $result;
//            var_dump($page);

            $currentPage = $result->getCurrentPageResults();
//             var_dump($result);
//            die('$result');
            if (count($currentPage) == 0) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
            }
            unset($pre_params['page']);
            $url = array();
            foreach ($pre_params as $key => $ele) {
                $url[] = "$key=" . urlencode($ele);
            }
            $params = implode('&', $url);
            // Adding columns to params
        }
        $mycol = explode('&', $columns);
        foreach ($mycol as $col) {
            $coldisplay[] = substr($col, 7, strlen($col));
        }

        foreach ($coldisplay as $tab) {
            $index = array_search($tab, $restCols);
            unset($restCols[$index]);
        }

        $dons_prelevemeent = $db->getRepository('FulldonDonateurBundle:Don')->findBy(array('modePaiement' => 6));
        foreach ($dons_prelevemeent as $key => $don) {
//            $abo_id = $don->getAbonnement()->getId();
//            var_dump($abo_id);
//            die('$abo_id');
//            $array_abo_id[$abo_id] = $don->getAbonnement();
            $montant = 0;
            $montant = $this->sommePrelevementByAbo($don->getAbonnement());
            $sum_montant_prev[$key]['somme'] = $montant;
            $sum_montant_prev[$key]['abo_id'] = $don->getAbonnement()->getId();
//            var_dump( $sum_montant_prev);
//            die('ananan ');
//                $indexe_array_abo_id ++;
        }
//        var_dump($sum_montant_prev);
//        die('ananan ');



        return $this->render('FulldonIntersaBundle:Dons:elasticsearch2.html.twig', array(
                    'result' => $currentPage,
                    'result2' => $result2,
                    'sumprelevement' => $sum_montant_prev,
                    'modes' => $modes,
                    'memo_search' => $memo_search,
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_dons' => $total_dons,
                    'params' => $params,
                    'favoris' => $favoris,
                    'col_display' => $coldisplay,
                    'columns' => $columns,
                    'rest_cols' => $restCols,
                    'sortelement' => $sortelement,
                    'sortdirection' => $sortdirection
        ));
    }

    public function superSearchAction() {

//        die('here');
        $request = Request::createFromGlobals();

//        var_dump($request);
//          die('here');

        $last_page = 0;
        $next_page = 0;
        $previous_page = 0;
        $memo_search = array();
        $params = null;
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $currentPage = 0;
        $total_dons = 0;
        $pre_params = $request->query->all();
//        $pre_params = $request->request->all();
        $modeRep = $db->getRepository('FulldonDonateurBundle:ModePaiement');
        $favoris = $repFavoris->findBy(array('section' => 'dons'));
        $tellmeall = true;
        $sortelement = $request->get('sortelement');
        $sortdirection = $request->get('sortdirection');
        $coldisplay = array();
        $modes = $modeRep->findAll();
        $page = $request->get('page', 1);
        $result2 = null;
        $array_abo_id = array();
        $sum_montant_prev = array();
        $abo_id = -1;
//        var_dump($page);
//        die('last page');
        $columns = $request->get('columns', 'list[]=numdon&list[]=rfs&list[]=amount&list[]=nom&list[]=prenom&list[]=codeactivite&list[]=statut&list[]=modepay&list[]=createdat&list[]=typedon&list[]=datefiscale');
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
        if ($request->getMethod() == 'GET' && count($pre_params) > 0) {  //    if ($request->getMethod() == 'GET' && count($pre_params) > 0)
//            die('here');
            $montant_don = $request->get('montant_don');
            $memo_search['montant_don'] = $montant_don;
            $id_don = $request->get('id_don');
            $memo_search['id_don'] = $id_don;
            $montant_choice = $request->get('montant_choice');
            $memo_search['montant_choice'] = $montant_choice;
            $cause = $request->get('cause');
//            var_dump($cause);
//            die('cause');
            $memo_search['montant_choice'] = $montant_choice;
            $num_cheque = $request->get('num_cheque');
            $memo_search['num_cheque'] = $num_cheque;
            $date_debut = $request->get('date_debut');
            $memo_search['date_debut'] = $date_debut;
            $date_fin = $request->get('date_fin');
            $memo_search['date_fin'] = $date_fin;
            $type_don = $request->get('type_don');
            $memo_search['type_don'] = $type_don;
            $iban = $request->get('iban');
            $memo_search['iban'] = $iban;
            $date_annule_fin = $request->get('date_annule_fin');
            $memo_search['date_annule_fin'] = $date_annule_fin;
            $date_annule_debut = $request->get('date_annule_debut');
            $memo_search['date_annule_debut'] = $date_annule_debut;
            $mode_paiement = $request->get('mode_paiement');
            $memo_search['mode_paiement'] = $mode_paiement;
            $cause = $request->get('cause');
            $memo_search['cause'] = $cause;
            $code_occasion = $request->get('code_occasion');
            $memo_search['code_occasion'] = $code_occasion;
            $code_campagne = $request->get('code_campagne');
            $memo_search['code_campagne'] = $code_campagne;
            $num_rf = $request->get('num_rf');
            $memo_search['num_rf'] = $num_rf;
            $lot = $request->get('lot_don');
            $memo_search['lot_don'] = $lot;
            $date_stop_fin = $request->get('date_stop_fin');
            $memo_search['date_stop_fin'] = $date_stop_fin;
            $date_stop_debut = $request->get('date_stop_debut');
            $memo_search['date_stop_debut'] = $date_stop_debut;
            $d_actif = $request->get('d_actif');
            $memo_search['d_actif'] = $d_actif;
            $d_inactif = $request->get('d_inactif');
            $memo_search['d_inactif'] = $d_inactif;
            $is_rf = $request->get('is_rf');
            $memo_search['is_rf'] = $is_rf;
            $baseQuery = false;
            $abo_id = null;
            $array_abo_id = array();
            $indexe_array_abo_id = 0;
//            $baseQuery = false;

            $app = $this->container->getParameter('elastic_db_name');
            $dons = $this->get('fos_elastica.finder.' . $app . '.don');
//            var_dump($dons);//$dons->findPaginated($finalQuery);
//            die('icici');
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
//            $montant_don = 20.00 ;
            if (isset($montant_don) && !is_null($montant_don) && $montant_don != "") {
                if ($operator == '=') {
                    $baseQuery = true;
                    $query_part->addMust(
                            new \Elastica\Query\Match('montant', $montant_don)
                    );
//                    var_dump($query_part);
//                    die('if $montant_don');
                } else {
                    $filters->addMust(
                            new \Elastica\Filter\Range('montant', array(
                        $operator => $montant_don
                            ))
                    );

//                    var_dump($query_part);
//                    die('else montant');
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
//                $query1 = new \Elastica\Query\Match();
//                $query1->setFieldQuery('cause.code', $cause);
//                $query1->setFieldFuzziness('cause.code', 0.7);
//                $query1->setFieldMinimumShouldMatch('cause.code', '80%');
//                $baseQuery2 = $query1;
//                
//                $filtered = new \Elastica\Query\Filtered($baseQuery2);
//                
//                $query3 = \Elastica\Query::create($filtered);
//                $dons->find($query3);
//                 var_dump($dons->find($query3));
//                die('rafik');
//                $query = $request->attributes->get('query');
//                   $finder = $this->get('fos_elastica.finder.app.entries');
//                $finder = $this->get('fos_elastica.finder.' . $app . '.don');
//                $nameQuery = new \Elastica_Query_Text();
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
//            var_dump($dons->find($finalQuery));
//            die('boussaccoufinal');
//            

            if (!empty($sortelement) && !empty($sortdirection)) {
                $index = array_search($sortelement, $restCols);
                if (!is_string($index)) {
                    $index = 'id';
                }
                $finalQuery->setSort(array($index => array('order' => $sortdirection)));
            } else {
                $finalQuery->setSort(array('id' => array('order' => 'desc')));
            }

            $result = $dons->findPaginated($finalQuery);
//            var_dump($result);
//            die('$result');
//              var_dump($result);
//            die('$result');
            $total_dons = $result->getNbResults();

//            $result->setMaxPerPage(20);
            $donateur_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
            $result->setMaxPerPage($donateur_per_page); //ici ja modifier
            $last_page = ceil($total_dons / $donateur_per_page);
            $result->setCurrentPage($page);
            $result2 = $result;
//      ;

            $currentPage = $result->getCurrentPageResults();

//            var_dump($currentPage);
//            die('ici');
//           

            if (count($currentPage) == 0) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
            }
            unset($pre_params['page']);
            $url = array();
            foreach ($pre_params as $key => $ele) {
                $url[] = "$key=" . urlencode($ele);
            }
            $params = implode('&', $url);
            // Adding columns to params
        }
        $mycol = explode('&', $columns);
        
        foreach ($mycol as $col) {
            $coldisplay[] = substr($col, 7, strlen($col));
        }

//        var_dump($coldisplay);
//        die('icici');
        foreach ($coldisplay as $tab) {
            $index = array_search($tab, $restCols);
            unset($restCols[$index]);
        }

        $dons_prelevemeent = $db->getRepository('FulldonDonateurBundle:Don')->findBy(array('modePaiement' => 6));
        foreach ($dons_prelevemeent as $key => $don) {
//            $abo_id = $don->getAbonnement()->getId();
//            var_dump($abo_id);
//            die('$abo_id');
//            $array_abo_id[$abo_id] = $don->getAbonnement();
            $montant = 0;
            $montant = $this->sommePrelevementByAbo($don->getAbonnement());
            $sum_montant_prev[$key]['somme'] = $montant;
            $sum_montant_prev[$key]['abo_id'] = $don->getAbonnement()->getId();
//            var_dump( $sum_montant_prev);
//            die('ananan ');
//                $indexe_array_abo_id ++;
        }
        return $this->render('FulldonIntersaBundle:Dons:elasticsearch.html.twig', array(
                    'result' => $currentPage,
                    'result2' => $result2,
                    'sumprelevement' => $sum_montant_prev,
                    'modes' => $modes,
                    'memo_search' => $memo_search,
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_dons' => $total_dons,
                    'params' => $params,
                    'favoris' => $favoris,
                    'col_display' => $coldisplay,
                    'columns' => $columns,
                    'rest_cols' => $restCols,
                    'sortelement' => $sortelement,
                    'sortdirection' => $sortdirection
        ));
    }

    public function indexAction() {
        $db = $this->getDoctrine()->getManager();
        $donRep = $db->getRepository('FulldonDonateurBundle:Don');
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $causeRep = $db->getRepository('FulldonDonateurBundle:Cause');
        $occasionRep = $db->getRepository('FulldonDonateurBundle:CodeOccasion');
        $campagneRep = $db->getRepository('FulldonDonateurBundle:CodeCompagne');
        $chequeRep = $db->getRepository('FulldonDonateurBundle:Cheque');
        $modeRep = $db->getRepository('FulldonDonateurBundle:ModePaiement');
        $virementRep = $db->getRepository('FulldonDonateurBundle:Virement');
        $rfRep = $db->getRepository('FulldonDonateurBundle:Rf');
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $favoris = $repFavoris->findBy(array('section' => 'dons'));
        $tellmeall = true;
        $modes = $modeRep->findAll();
        $errors = array();
        $memo_search = array();
        // On récupère la requête
        $request = $this->getRequest();
        //initialisation
        $where = array();
        $result = '';
        // Pagination
        $params = null;
        $last_page = 0;
        $page = 0;
        $next_page = 0;
        $total_dons = 0;
        $previous_page = 0;
        $pre_params = $request->query->all();
        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'GET' && count($pre_params) > 0) {
            // On fait le lien Requête <-> Formulaire
            // Page
            $page = $request->get('page', 1);

            $montant_don = $request->get('montant_don');
            $memo_search['montant_don'] = $montant_don;
            $id_don = $request->get('id_don');
            $memo_search['id_don'] = $id_don;
            $montant_choice = $request->get('montant_choice');
            $memo_search['montant_choice'] = $montant_choice;
            $cause = $request->get('cause');
            $memo_search['montant_choice'] = $montant_choice;
            $num_cheque = $request->get('num_cheque');
            $memo_search['num_cheque'] = $num_cheque;
            $date_debut = $request->get('date_debut');
            $memo_search['date_debut'] = $date_debut;
            $date_fin = $request->get('date_fin');
            $memo_search['date_fin'] = $date_fin;
            $type_don = $request->get('type_don');
            $memo_search['type_don'] = $type_don;
            $iban = $request->get('iban');
            $memo_search['iban'] = $iban;
            $date_annule_fin = $request->get('date_annule_fin');
            $memo_search['date_annule_fin'] = $date_annule_fin;
            $date_annule_debut = $request->get('date_annule_debut');
            $memo_search['date_annule_debut'] = $date_annule_debut;
            $mode_paiement = $request->get('mode_paiement');
            $memo_search['mode_paiement'] = $mode_paiement;
            $cause = $request->get('cause');
            $memo_search['cause'] = $cause;
            $code_occasion = $request->get('code_occasion');
            $memo_search['code_occasion'] = $code_occasion;
            $code_campagne = $request->get('code_campagne');
            $memo_search['code_campagne'] = $code_campagne;

            $num_rf = $request->get('num_rf');
            $memo_search['num_rf'] = $num_rf;
            $lot = $request->get('lot_don');
            $memo_search['lot_don'] = $lot;
            $date_stop_fin = $request->get('date_stop_fin');
            $memo_search['date_stop_fin'] = $date_stop_fin;
            $date_stop_debut = $request->get('date_stop_debut');
            $memo_search['date_stop_debut'] = $date_stop_debut;
            $d_actif = $request->get('d_actif');
            $memo_search['d_actif'] = $d_actif;
            $d_inactif = $request->get('d_inactif');
            $memo_search['d_inactif'] = $d_inactif;

            $op = '=';
            switch ($montant_choice) {
                case 'inf':
                    $op = '<=';
                    break;
                case 'sup':
                    $op = '>=';
                    break;
                default :
                    break;
            }
            $isResult = false;
            $don = $donRep->createQueryBuilder('d')
                    ->join('d.transaction', 't')
                    ->join('d.cause', 'c')
                    ->join('c.codeOccasion', 'co')
                    ->LeftJoin('d.abonnement', 'a');
            if (isset($type_don) && !empty($type_don)) {
                $isResult = true;
                $bol = false;
                if ($type_don == 'regulier') {
                    $bol = true;
                }
                $don = $don->andwhere('d.ispa = :bol')
                        ->setParameter(':bol', $bol);
            }

            if (isset($montant_don) && !is_null($montant_don) && $montant_don != "") {
                $isResult = true;
                $don = $don->andwhere('d.montant ' . $op . ' :montant')
                        ->setParameter(':montant', $montant_don);
            }

            if (isset($id_don) && !empty($id_don)) {
                $isResult = true;
                $don = $don->andwhere('d.id = :id')
                        ->setParameter('id', $id_don);
            }

            if (isset($d_actif) && !empty($d_actif)) {
                $isResult = true;
                if ($d_actif == "on")
                    $var = false;
                else
                    $var = true;
                $don = $don->andwhere('d.removed = :var')
                        ->setParameter('var', $var);
            }

            if (isset($d_inactif) && !empty($d_inactif)) {
                $isResult = true;
                if ($d_inactif == "on")
                    $var = true;
                else
                    $var = false;
                $don = $don->andwhere('d.removed = :var')
                        ->setParameter('var', $var);
            }

            if (isset($id_don) && !empty($id_don)) {
                $isResult = true;
                $don = $don->andwhere('d.id = :id')
                        ->setParameter('id', $id_don);
            }

            if (isset($cause) && !empty($cause)) {
                $isResult = true;
                $macause = $causeRep->findOneBy(array('code' => $cause));
                $don = $don->andwhere('d.cause = :cause')
                        ->setParameter(':cause', $macause);
            }

            if (isset($code_occasion) && !empty($code_occasion)) {
                $isResult = true;
                $occasion = $occasionRep->findOneBy(array('code' => $code_occasion));
                $don = $don->andwhere('c.codeOccasion = :occasion')
                        ->setParameter(':occasion', $occasion);
            }

            if (isset($code_campagne) && !empty($code_campagne)) {
                $isResult = true;
                $campagne = $campagneRep->findOneBy(array('code' => $code_campagne));
                $don = $don->andwhere('co.codeCompagne = :campagne')
                        ->setParameter(':campagne', $campagne);
            }

            if (isset($lot) && !empty($lot)) {
                $isResult = true;
                $don = $don->andwhere('d.lot = :lot')
                        ->setParameter(':lot', $lot);
            }
            if (isset($date_debut) && !empty($date_debut)) {
                $isResult = true;
                $don = $don->andwhere('d.date_fiscale >= :debut')
                        ->setParameter(':debut', $this->dqlDate($date_debut) . ' 00:00:00');
            }

            if (isset($date_fin) && !empty($date_fin)) {
                $isResult = true;
                $don = $don->andwhere('d.date_fiscale <= :fin')
                        ->setParameter(':fin', $this->dqlDate($date_fin) . ' 23:59:59');
            }
            if (isset($date_annule_debut) && !empty($date_annule_debut)) {
                $isResult = true;
                $don = $don->andwhere('d.removedAt >= :debut')
                        ->setParameter('debut', $this->dqlDate($date_annule_debut));
            }
            if (isset($date_annule_fin) && !empty($date_annule_fin)) {
                $isResult = true;
                $don = $don->andwhere('d.removedAt <= :fin')
                        ->setParameter('fin', $this->dqlDate($date_annule_fin));
            }

            if (isset($date_stop_debut) && !empty($date_stop_debut)) {
                $isResult = true;
                $don = $don->andwhere('d.abonnement is not null and a.actif = false and a.disabledAt >= :debut')
                        ->setParameter('debut', $this->dqlDate($date_stop_debut));
            }
            if (isset($date_stop_fin) && !empty($date_stop_fin)) {
                $isResult = true;
                $don = $don->andwhere('d.abonnement is not null and a.actif = false and a.disabledAt <= :fin')
                        ->setParameter('fin', $this->dqlDate($date_stop_fin));
            }

            if (isset($iban) && !empty($iban)) {

                $don = $don->andwhere('d.abonnement is not null and a.actif = true and a.iban = :iban')
                        ->setParameter('iban', $iban);
            }
            if (isset($num_rf) && !empty($num_rf)) {
                $rf = $rfRep->find($num_rf);
                if (is_object($rf)) {
                    $isResult = true;
                    $don = $don->andwhere('d.id = :id')
                            ->setParameter('id', $rf->getDon()->getId());
                }
            }

            if (isset($num_cheque) && !empty($num_cheque)) {
                $moncheque = $chequeRep->createQueryBuilder('c')->where('c.numeroCheque = :num')
                                ->setParameter(':num', $num_cheque)
                                ->getQuery()->getOneOrNullResult();
                if (is_object($moncheque)) {
                    $isResult = true;
                    $don = $don->andwhere('t.cheque = :cheque')
                            ->setParameter(':cheque', $moncheque);
                }
            }

            if (isset($mode_paiement) && !empty($mode_paiement)) {
                $isResult = true;
                $monmode = $modeRep->findOneBy(array('codeSolution' => $mode_paiement));
                $don = $don->andwhere('d.modePaiement = :mode')
                        ->setParameter(':mode', $monmode);
            }
            if ($isResult) {
                $donTest = clone $don;
                $preResult = $donTest
                        ->select('COUNT(d.id) as cpt')
                        ->getQuery()
                        ->getOneOrNullResult();
                if ($preResult['cpt'] > 10000) {
                    $this->get('session')->getFlashBag()->add('warning', $preResult['cpt'] . ' Résultats : Trop de résultats, veuillez SVP affiner vos recherches !');
                    $result = null;
                    $tellmeall = false;
                } else {
                    $total_dons = $preResult['cpt'];
                    $dons_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
                    $last_page = ceil($total_dons / $dons_per_page);
                    $previous_page = $page > 1 ? $page - 1 : 1;
                    $next_page = $page < $last_page ? $page + 1 : $last_page;
                    $result = $don
                                    ->orderBy('d.id ', 'DESC')
                                    ->setFirstResult(($page * $dons_per_page) - $dons_per_page)
                                    ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
                }
            }
            if (!$result && $tellmeall) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
            }


            unset($pre_params['page']);
            $url = array();
            foreach ($pre_params as $key => $ele) {
                $url[] = "$key=$ele";
            }
            $params = implode('&', $url);
        }
        return $this->render('FulldonIntersaBundle:Dons:index.html.twig', array(
                    'result' => $result,
                    'modes' => $modes,
                    'memo_search' => $memo_search,
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_dons' => $total_dons,
                    'params' => $params,
                    'favoris' => $favoris
        ));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function editAction($id) {
        $errors = array();
        $data = array();
        $type = null;
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $repPeriod = $db->getRepository('FulldonDonateurBundle:Periodicite');
        $don = $repDon->find($id);
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
                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
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

                        if (!isset($nom_banque) || empty($nom_banque)) {

                            $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                        }
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

                $db->persist($don);
                $db->flush();
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
                case self::_VIREMENT_:


                    $data['image'][] = $nom . '_' . $sequence;


                    break;
                case self::_PA_:

                    //Je commence le traitement
                    $data['image'][] = $nom . '_' . $sequence;

                    break;
                default:
                    break;
            }
        }

        //$mode =  $don->getModePaiement()->getCodeSolution();

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

//        var_dump($id);
//        die('showAction');
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $repPrelevement = $db->getRepository('FulldonDonateurBundle:Prelevement');
        $user = $this->get('security.context')->getToken()->getUser();
        $don = $repDon->findOneBy(array('id' => $id));
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
                    'don' => $don,
                    'donateur' => $donateur,
                    'prelevements' => $pr,
                    'motifs' => $motifs,
                    'eclates' => $eclates,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function validationAction() {
        $request = $this->getRequest();
        $id = $request->get('don_id');
        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            $db = $this->getDoctrine()->getManager();
            $repDon = $db->getRepository('FulldonDonateurBundle:Don');
            $don = $repDon->findOneBy(array('id' => $id));
            return $this->render('FulldonIntersaBundle:Dons:validation.html.twig', array('don' => $don));
        }
        return $this->render('FulldonIntersaBundle:Dons:validation.html.twig');
    }

    public function tValidAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $don = $repDon->findOneBy(array('id' => $id));
        $request = $this->getRequest();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->findOneBy(array('user' => $don->getUser()->getId()));

        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            $code_statut = $request->get('code_statut');
            $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
            $statut = $repStatut->findOneBy(array('code' => $code_statut));
            $don->getTransaction()->setStatut($statut);
            $db->persist($don);
            $db->flush();
            $this->get('session')->getFlashBag()->add('info', 'Le statut du don #' . $id . ' a été changé avec succès');

            // On redirige vers la page de visualisation de l'article nouvellement créé
            return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $id)));
        }
        if (isset($id)) {

            $repSta = $db->getRepository('FulldonDonateurBundle:StatutPaiement');

            $stats = $repSta->findAll();
            return $this->render('FulldonIntersaBundle:Dons:tvalid.html.twig', array('don' => $don, 'stats' => $stats));
        }
        return $this->render('FulldonIntersaBundle:Dons:tvalid.html.twig');
    }

    public function prelevementAction() {
        $errors = array();
        $db = $this->getDoctrine()->getManager();
        $hpDon = $db->getRepository('FulldonDonateurBundle:HistoriquePrelevement');
        $historique = $hpDon->findAll();
        // On récupère la requête
        $request = $this->getRequest();
        //initialisation
        $where = array();
        $result = '';
        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $date = $request->get('date_pa');
            $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');

            $repDon = $db->getRepository('FulldonDonateurBundle:Don');
            $dons = $repDon->createQueryBuilder('d');
            $dons->join('d.abonnement', 'a')
                    ->where('a.date_next_pa = :date')
                    ->setParameter('date', $this->dqlDate($date));
            $result = $dons
                    ->getQuery()
                    ->getResult();
            var_dump($result);
            die;
        }
        return $this->render('FulldonIntersaBundle:Dons:prelevement.html.twig', array('historique' => $historique, 'result' => $result));
    }

    function dqlDate($date, $format = 'd/m/Y') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d->format('Y-m-d');
    }

    function validateDate($date, $format = 'd/m/Y') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public function checkCodeActivite($code) {
        // Check if it exists first
        $db = $this->getDoctrine()->getManager();
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $query = $repCause->findOneBy(array('code' => $code));

        if (is_null($query)) {
            return false;
        }

        return true;
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function stopperAbonnementAction() {
        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {

            $db = $this->getDoctrine()->getManager();
            $id = $request->get('don_id');
            $motif = $request->get('motif');
            $don = $db->getRepository('FulldonDonateurBundle:Don')->find($id);

            $don->getAbonnement()->setActif(false);
            $motif = $db->getRepository('FulldonDonateurBundle:MotifAbo')->find($motif);
            $don->getAbonnement()->setMotif($motif);
            $don->getAbonnement()->setDisabledAt(new \DateTime());
            $donateur = $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser()));
            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg = $this->get('fulldon.intersa.global')->getModMsgLog($don->getAbonnement(), 'ABONNEMENT');
            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
            // Log the user creation
            $event = HistoryLogEvent::constr1($current_user, $donateur, $typeLog, $msg);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);
            //Fin Log
            $db->persist($don);
            $db->flush();
            //Création d'un courrier en attente.
            $courrierAttente = new CourrierAttente();
            $courrierAttente->setDonateur($donateur);
            $courrierAttente->setDon($don);
            $courrierAttente->setTypeTraitements($db->getRepository('FulldonIntersaBundle:TypeTraitementCourrier')->findOneBy(array('code' => Vars\DonVars::_COURRIER_ARRET_PA_)));
            $courrierAttente->setDone(false);
            $db->persist($courrierAttente);
            $db->flush();
            $this->get('session')->getFlashBag()->add('info', 'Le don réguilier  #' . $id . ' est maintenant Stoppé');
        }
        return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $id)));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function activerAbonnementAction() {
        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $errors = array();
            $db = $this->getDoctrine()->getManager();
            $id = $request->get('don_id');
            $date = $request->get('date_next_pa');
            if (!$this->validateDate($date)) {
                $errors['error_date'] = 'La date de la réactivation de l\'abonnement devrait être sous la forme jj/mm/yyyy';
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
                //panique mode
                return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $id)));
            }
            $don = $db->getRepository('FulldonDonateurBundle:Don')->find($id);
            $don->getAbonnement()->setActif(true);
            $don->getAbonnement()->setDisabledAt(null);
            $don->getAbonnement()->setMotif(null);
            $don->getAbonnement()->setDateNextPa(\DateTime::createFromFormat('d/m/Y', $date));

            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg = $this->get('fulldon.intersa.global')->getModMsgLog($don->getAbonnement(), 'ABONNEMENT');
            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
            // Log the user creation
            $event = HistoryLogEvent::constr1($current_user, $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser())), $typeLog, $msg);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);
            //Fin Log

            $db->persist($don);
            $db->flush();
            $this->get('session')->getFlashBag()->add('info', 'Le don réguilier  #' . $id . ' est maintenant Réactiver');
        }

        return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $id)));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function rejetPrelevementAction($did, $id) {
        $request = $this->getRequest();
        $motif = $request->get('motif');
        $db = $this->getDoctrine()->getManager();
        if ($request->getMethod() == 'POST' && isset($motif) && !empty($motif)) {
            $repPrelevement = $db->getRepository('FulldonDonateurBundle:Prelevement');
            $prelevement = $repPrelevement->find($id);
            $don = $db->getRepository('FulldonDonateurBundle:Don')->find($did);
            $motif = $db->getRepository('FulldonDonateurBundle:MotifRejetPrelevement')->find($motif);
            $prelevement->setMotif($motif);
            $prelevement->setRejet(true);
            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg = $this->get('fulldon.intersa.global')->getModMsgLog($prelevement, 'REJET');
            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
            // Log the user creation
            $event = HistoryLogEvent::constr1($current_user, $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser())), $typeLog, $msg);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);
            //Fin Log
            $db->persist($prelevement);
            $db->flush();
            //redirect
            $this->get('session')->getFlashBag()->add('info', 'Le prélevement #' . $id . ' est maintenant rejeté');
        } else {
            $this->get('session')->getFlashBag()->add('erreur', 'Une erreur est survenue lors du rejet ');
        }
        // On redirige vers la page de visualisation de l'article nouvellement créé
        return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $did)));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function disableDonAction($id) {
        //id prelevement
        $request = $this->getRequest();
        $motif = $request->get('motif');
        $db = $this->getDoctrine()->getManager();
        if ($request->getMethod() == 'POST' && isset($motif) && !empty($motif)) {
            $repDon = $db->getRepository('FulldonDonateurBundle:Don');
            $don = $repDon->find($id);
            $don->setRemoved(true);
            $motif = $db->getRepository('FulldonDonateurBundle:MotifDisableDon')->find($motif);
            $don->setMotif($motif);
            $don->setRemovedAt(new \DateTime());
            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg = $this->get('fulldon.intersa.global')->getModMsgLog($don, 'DON');
            $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
            // Log the user creation
            $event = HistoryLogEvent::constr1($current_user, $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser())), $typeLog, $msg);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);
            //Fin Log
            $db->persist($don);
            $db->flush();
            //redirect
            $this->get('session')->getFlashBag()->add('info', 'Le don #' . $id . ' est maintenant annulé');
        } else {
            $this->get('session')->getFlashBag()->add('erreur', 'Une erreur est survenue lors de l\'annulation du don ');
        }
        return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $id)));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function rejetAjaxAction($did, $id) {

        $db = $this->getDoctrine()->getManager();
        $repPrelevement = $db->getRepository('FulldonDonateurBundle:Prelevement');
        $prelevement = $repPrelevement->find($id);
        $repMotif = $db->getRepository('FulldonDonateurBundle:MotifRejetPrelevement');
        $motifs = $repMotif->findAll();
        return $this->render('FulldonIntersaBundle:Dons/ajax:rejet_prelevement.html.twig', array('prelevement' => $prelevement, 'don_id' => $did, 'motifs' => $motifs));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function disableAjaxDonAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $don = $repDon->find($id);
        $repMotif = $db->getRepository('FulldonDonateurBundle:MotifDisableDon');
        $motifs = $repMotif->findAll();
        return $this->render('FulldonIntersaBundle:Dons/ajax:disable_don.html.twig', array('don' => $don, 'don_id' => $id, 'motifs' => $motifs));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1")
     */
    public function verifLotsAction($page) {
        $db = $this->getDoctrine()->getManager();
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $elements = $repSaisie->createQueryBuilder('s')
                ->where('s.done = true and s.rfDone = false ')
                ->getQuery()
                ->getResult();
        $verifTotal = $repSaisie->createQueryBuilder('s')
                ->select('count(s.id) as total')
                ->where('s.done = true and s.rfDone = false and s.verifDone = true')
                ->getQuery()
                ->getOneOrNullResult();
        $total_elements = count($elements);
        $elements_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_elements / $elements_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $elements = $repSaisie->createQueryBuilder('s')
                        ->where('s.done = true and s.rfDone = false ')// on affiche comme même les PAS
                        ->orderBy('s.id', 'DESC')
                        ->setFirstResult(($page * $elements_per_page) - $elements_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        return $this->render('FulldonIntersaBundle:Dons:verif_lots.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_elements' => $total_elements,
                    'elements' => $elements,
                    'verif_total' => $verifTotal['total']
        ));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1")
     */
    public function verifAllLotsAction() {
        $db = $this->getDoctrine()->getManager();

        $repElement = $db->getRepository('FulldonIntersaBundle:Saisie');
        $msg = 'Tous les lots ont été validées avec succès !';
        $msg_not_exist = 'Il n\'y a aucun lot disponible à vérifier';
        $msg_not_possible = 'La vérification des lots est intérdite !';
        $elements = $repElement->findBy(array('done' => true, 'rfDone' => false));

        if (!is_null($elements)) {
            try {
                foreach ($elements as $element) {
                    $element->setVerifDone(true);
                    $db->persist($element);
                    $db->flush();
                    // Log the user creation
                    $event = HistoryStatEvent::constr2(StatVar::_STAT_TYPE_LOT_VERIFIED_);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(StatVar::CREATE, $event);
                    //Fin Stat
                }
                $this->get('session')->getFlashBag()->add('info', $msg);
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->get('session')->getFlashBag()->add('erreur', $msg_not_possible);
            }
            // On définit un message flash
        } else {
            $this->get('session')->getFlashBag()->add('erreur', $msg_not_exist);
        }

        return $this->redirect($this->generateUrl('intersa_verif_lot', array('page' => 1)));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1")
     */
    public function verifLotsAjaxAction($lot) {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $repAnomalies = $db->getRepository('FulldonIntersaBundle:Anomalie');
        $anomalies = $repAnomalies->createQueryBuilder('a')
                ->where('a.lot = :lot and a.corriger = false')
                ->setParameter('lot', $lot)
                ->getQuery()
                ->getResult();
        $result = $repDon->createQueryBuilder('d')
                ->select(' SUM(d.montant) as somme, count(d.id) cpt ')
                ->where('d.lot = :lot')
                ->andwhere('d.removed = false')
                ->setParameter('lot', $lot)
                ->groupBy('d.lot')
                ->getQuery()
                ->getOneOrNullResult();
        return $this->render('FulldonIntersaBundle:Dons/ajax:verif_lots.html.twig', array('anomalies' => $anomalies, 'result' => $result));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1")
     */
    public function verifOkLotAction($lot) {
        $db = $this->getDoctrine()->getManager();

        $repElement = $db->getRepository('FulldonIntersaBundle:Saisie');
        $msg = 'La vérification du lot #' . $lot . ' a été validée avec succès !';
        $msg_not_exist = 'Le lot n\'existe pas';
        $msg_not_possible = 'La vérification de ce lot est intérdite !';
        $element = $repElement->findOneBy(array('lot' => $lot));

        if (is_object($element)) {
            try {
                $element->setVerifDone(true);
                $db->persist($element);
                $db->flush();
                // Log the user creation
                $event = HistoryStatEvent::constr2(StatVar::_STAT_TYPE_LOT_VERIFIED_);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(StatVar::CREATE, $event);
                //Fin Stat
                $this->get('session')->getFlashBag()->add('info', $msg);
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->get('session')->getFlashBag()->add('erreur', $msg_not_possible);
            }
            // On définit un message flash
        } else {
            $this->get('session')->getFlashBag()->add('erreur', $msg_not_exist);
        }

        return $this->redirect($this->generateUrl('intersa_verif_lot', array('page' => 1)));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function redirectAddDonAction($id) {
//       $request = $this->getRequest();
//       $type = $request->get('type');
//        var_dump($type);//PA
//        die('redirectAddDonAction');

        $cumul = 0.00;
        $date = date("Y-m-d H:i:s");
        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $type = $request->get('type');
            if (isset($type) && !empty($type)) {
                //redirect to the addDon
//                die('type + empty');
                return $this->redirect($this->generateUrl('intersa_dons_add', array('id' => $id, 'type' => $type)));
            } else {
//                die('else1');

                return $this->redirect($this->generateUrl('intersa_donateur_gestion', array(
                                    'id' => $id,
                                    'cumul' => $cumul,
                                    'date' => $date
                )));
            }
        } else {
//            die('else2');
            return $this->redirect($this->generateUrl('intersa_donateur_gestion', array(
                                'id' => $id,
                                'cumul' => $cumul,
                                'date' => $date
            )));
        }
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function addDonAction($id, $type) {

//        die('add_don + intersabundle');
        $errors = array();
        $db = $this->getDoctrine()->getManager();
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $repMode = $db->getRepository('FulldonDonateurBundle:ModePaiement');
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
        $donateur = $repDonateur->find($id);
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
                    $shouldDate = $this->get('fulldon.intersa.rf_service')->getStartDate();

                    $date_first_pa = $request->get('date_first_pa');
                    if ($date_first_pa < $shouldDate->format('d/m/Y')) {
                        $date_first_pa = $shouldDate->format('d/m/Y');
                    }
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
                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
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
                    }
                    break;
                case self::_VIREMENT_:

                    $nom_banque = $request->get('nom_banque');
                    $champs['nom_banque'] = $nom_banque;

                    if (!isset($nom_banque) || empty($nom_banque)) {

                        $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque';
                    }

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

                $cumul = 0.00;
                $date = date("Y-m-d H:i:s");
                $this->get('session')->getFlashBag()->add('info', 'Création du don effectuée avec succès ! ');
                return $this->redirect($this->generateUrl('intersa_donateur_gestion', array(
                                    'id' => $id,
                                    'cumul' => $cumul,
                                    'date' => $date
                )));
            } else {
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }


// switch mode de paiement
        // cheque
        // PA
        // ESPECE

        $cumul = 0.00;
        $date = date("Y-m-d H:i:s");
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

    private function checkBic($bic) {
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

    public function generateNextPaAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don');
        $don = $repDon->find($id);

        if ($don->getAbonnement()->getActif()) {
            $date_prelevement = $don->getAbonnement()->getDateNextPa();
            $curdate = new \DateTime();
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
            $periodicite = $don->getAbonnement()->getPeriodicite();
            $next = $don->getAbonnement()->getDateNextPa()->add(new \DateInterval('P' . $periodicite->getCode() . 'M'));
            $next_date = $next->format('d/m/Y');
            $don->getAbonnement()->setPreFirst(false);
            $don->getAbonnement()->setDateNextPa(\DateTime::createFromFormat('d/m/Y', $next_date));
            $db->persist($don);
            $db->flush();
            $this->get('session')->getFlashBag()->add('info', 'Le prélèvement demandé a été généré avec success ');
        } else {
            $this->get('session')->getFlashBag()->add('warning', 'Impossible de générer le prélèvement, veuillez activer l\'abonnement');
        }

        return $this->redirect($this->generateUrl('intersa_dons_view', array('id' => $id)));
    }

}
