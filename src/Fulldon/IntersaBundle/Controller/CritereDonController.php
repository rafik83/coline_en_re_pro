<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\DonateurBundle\Form\PndType;
use Fulldon\IntersaBundle\Event\LogVar;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\FoyerDonateur;
use Fulldon\DonateurBundle\Entity\Doublon;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\DonateurBundle\Form\DonateurSearchType;
use Fulldon\DonateurBundle\Entity\Pnd;
use Fulldon\IntersaBundle\Form\IntersaDonateurType;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundFle\Event\StatVar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Fulldon\IntersaBundle\Entity\TypeDon;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Vars;
use Fulldon\IntersaBundle\Entity\CourrierAttente;
use Symfony\Component\Intl\Intl;
use Fulldon\IntersaBundle\Entity\SauvgardeDon;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class CritereDonController extends Controller {

    //put your code here
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();
        $repSauvgardeDon = $em->getRepository('FulldonIntersaBundle:SauvgardeDon');
        $getAllSauvgardeDon = $repSauvgardeDon->findAll();


        $current_page = 1;
        $maxPerPage = 5;
        $adapter = new ArrayAdapter($repSauvgardeDon->findAll());
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
//        $total_pages = $pagerfanta->getNbResults();
//        $last_page = ceil($total_pages / $maxPerPage);
//
//        $maxPerPage = $pagerfanta->getMaxPerPage();
        $pagerfanta->setCurrentPage($current_page); // 1 by default
//        $current_page = $pagerfanta->getCurrentPage();
        $SauvgardeDon = $pagerfanta->getCurrentPageResults();

        $data = null;
        $data2 = null;

        $colDispaly = array(array('numdon' => '#REF'),
            array('nom' => 'Nom'),
            array('prenom' => 'Prénom'),
            array('statut' => 'Statut'),
            array('modepaiement' => 'Mode de paiement'),
            array('typedon' => 'Type de don'),
            //array('lot' => 'Lot'), 
            array('rfs' => 'Reçus fiscaux'),
            //array('createdat' => 'Date de création'),
            array('datefiscale' => 'Date fiscale'),
            array('codeactivite' => 'Code d\'activité'),
            array('codeoccasion' => 'Code occasion'),
            array('amount' => 'Montant'),
            array('codecompagne' => 'Code campagne'));
        return $this->render('FulldonIntersaBundle:Critere:dons/index.html.twig', array(
                    'sgdons' => $SauvgardeDon,
                    'data' => $data,
                    'data2' => $data2,
//                    'suvgdona' => $SauvgardeDonateur,
                    'col_display' => $colDispaly,
                    'pager' => $pagerfanta
//                    'last_page' => $last_page,
//                    'previous_page' => $previous_page,
//                    'current_page' => $page,
//                    'page' => $page,
//                    'next_page' => $next_page,
//                    'total_critere' => $total_critere,
        ));
    }

    public function addAction($section) {





        $db = $this->getDoctrine()->getManager();
        $sauvgarde_don = $db->getRepository('FulldonIntersaBundle:SauvgardeDon');
        $newSauvgarde = new SauvgardeDon();
        $request = Request::createFromGlobals();
//        var_dump($request);


        $route = null;
        if ($section == 'dons') {
            $route = 'elastic_don';
        }
//        var_dump($request->getMethod());
//        die('ici');
        if ($request->getMethod() == 'POST') {

            $title = $request->request->get('title');
//             var_dump($title);
//            die('$title');

            $description = $request->request->get('description');
//            var_dump($description);
//             die('$description');
            $url = $request->request->get('url');
//            var_dump($url);
//            die('$url');
            if (!empty($title)) {
                $existesauvgarde = $sauvgarde_don->findOneBy(array('title' => $title));
                if ($existesauvgarde) {
                    $existesauvgarde->setTitle($title);
                    $existesauvgarde->setDescription($description);
                    $existesauvgarde->setUrl($url);
                    $existesauvgarde->setSection($section);
                    $existesauvgarde->setCreatedAt(new \DateTime("now"));
                    $db->persist($existesauvgarde);
                    $db->flush();
                    // Redirection
                    $this->get('session')->getFlashBag()->add('success', 'Recherche Modifier dans vos favoris');
                    return $this->redirect($this->generateUrl($route) . '?' . $url);
                }
                if (!$existesauvgarde) {
                    $newSauvgarde->setTitle($title);
                    $newSauvgarde->setDescription($description);
                    $newSauvgarde->setUrl($url);
                    $newSauvgarde->setSection($section);
                    $newSauvgarde->setCreatedAt(new \DateTime("now"));
                    $db->persist($newSauvgarde);
                    $db->flush();
                    // Redirection
                    $this->get('session')->getFlashBag()->add('success', 'Recherche enregistrée dans vos favoris');
                    return $this->redirect($this->generateUrl($route) . '?' . $url);
                }
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'Impossible de sauvegarder cette recherche');
                return $this->redirect($this->generateUrl($route) . '?' . $url);
            }
        }
    }

    public function searchPaginateAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $repSauvgardeDon = $em->getRepository('FulldonIntersaBundle:SauvgardeDon');
        $getAllSauvgardeDonr = $repSauvgardeDon->findAll();
        $data = $request->query->all();
        $page = $data["param"];

        $current_page = $page;
        $maxPerPage = 5;
        $adapter = new ArrayAdapter($repSauvgardeDon->findAll());
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
        $pagerfanta->setCurrentPage($current_page); // 1 by default
        $SauvgardeDon = $pagerfanta->getCurrentPageResults();
//        $total_pages = $pagerfanta->getNbResults();
//        $last_page = ceil($total_pages / $maxPerPage);
//        $current_page = $pagerfanta->getCurrentPage();
//        $maxPerPage = $pagerfanta->getMaxPerPage();

        $view = $this->renderView('FulldonIntersaBundle:Critere:dons/ligne_index_critere.html.twig', array(
            'sgdons' => $SauvgardeDon,
            'pager' => $pagerfanta,
//            'last_page' => $last_page,
//            'current_page' => $current_page,
        ));
        $response = new Response($view);
        return $response;
    }

    public function paginateLigneDonateurAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $repSauvgardeDon = $em->getRepository('FulldonIntersaBundle:SauvgardeDon');
        $data = $request->query->all();
        $id_don = $data["param2"];
        $param_page = $data["param"];
//        $response = new Response($id_sauvgarde_donateur);
//        return $response;
        $entity = $repSauvgardeDon->find($id_don);
        if ($entity) {
//            $test = "fulldon_donateurbundle_donateursearchtype[refDonateur]=11762&fulldon_donateurbundle_donateursearchtype[civilite]=&fulldon_donateurbundle_donateursearchtype[nom]=&fulldon_donateurbundle_donateursearchtype[prenom]=&fulldon_donateurbundle_donateursearchtype[dateNaissance]=&fulldon_donateurbundle_donateursearchtype[nomEntreprise]=&fulldon_donateurbundle_donateursearchtype[email]=&fulldon_donateurbundle_donateursearchtype[isopays]=FR&fulldon_donateurbundle_donateursearchtype[zipcode]=75005&fulldon_donateurbundle_donateursearchtype[isoville]=&fulldon_donateurbundle_donateursearchtype[adresse3]=&fulldon_donateurbundle_donateursearchtype[adresse1]=&fulldon_donateurbundle_donateursearchtype[adresse2]=&fulldon_donateurbundle_donateursearchtype[adresse4]=&fulldon_donateurbundle_donateursearchtype[_token]=8sqALvc9j40DeANYNz675Cp5zT4293STpH3erK3yT7c&columns=list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Demail%26list%5B%5D%3Dzipcode%26list%5B%5D%3Dville&sortelement=&sortdirection=&withemail=true&withtel=&withcat=&statut_donateur=on";
            $request = Request::create($this->container->get('router')->generate('intersa_critere_recherche_donateur') . '?' . $entity->getUrl());
            $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
//            $JsonResponse = new JsonResponse(array("tab"=>$resultform));
//           return $JsonResponse ;

            $_array_filter = array();

           

//            $query = $repSauvgardeDonateur->findAll();
            $query = $this->GetDonOrderBy($_array_filter);
            $query2 = $this->GetDonGrouBy($_array_filter);
//            $JsonResponse = new JsonResponse(array("tab" => $query));
//            return $JsonResponse;
            $adapter = new ArrayAdapter($query);
            $pagerfanta = new Pagerfanta($adapter);
            $maxPerPage = 20;
            $current_page = $param_page;
            $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($current_page); // 1 by default
            $data = $pagerfanta->getCurrentPageResults();


            $adapter2 = new ArrayAdapter($query2);
            $pagerfanta2 = new Pagerfanta($adapter2);
            $maxPerPage2 = 20;
            $current_page2 = $param_page;
            $pagerfanta2->setMaxPerPage($maxPerPage2); // 10 by default
            $pagerfanta2->setCurrentPage($current_page2); // 1 by default
            $data2 = $pagerfanta2->getCurrentPageResults();
            $current_page2 = $pagerfanta2->getCurrentPage();
            $last_page = $pagerfanta2->getNbPages();
//            $JsonResponse = new JsonResponse(array("tab" => $pagerfanta->getCurrentPageResults()));
//            return $JsonResponse;











            $colDispaly = array(array('numdon' => '#REF'),
                array('nom' => 'Nom'),
                array('prenom' => 'Prénom'),
                array('statut' => 'Statut'),
                array('modepaiement' => 'Mode de paiement'),
                array('typedon' => 'Type de don'),
                //array('lot' => 'Lot'), 
                array('rfs' => 'Reçus fiscaux'),
                //array('createdat' => 'Date de création'),
                array('datefiscale' => 'Date fiscale'),
                array('codeactivite' => 'Code d\'activité'),
                array('codeoccasion' => 'Code occasion'),
                array('amount' => 'Montant'),
                array('codecompagne' => 'Code campagne'));

            $view = $this->renderView('FulldonIntersaBundle:Critere:dons/ligne_search_don.html.twig', array('data' => $data,
                'col_display' => $colDispaly,
                'pager' => $pagerfanta,
                'pager2' => $pagerfanta2,
                'data2' => $data2,
                'id_entity' => $id_don,
                'current_page' => $current_page2,
                'last_page' => $last_page,
            ));
            $response = new Response($view);
            return $response;
        } else {
            $this->createNotFoundException('erreur');
        }
    }

    public function GetDonOrderBy(array $param) {

        // $_array_filter['categories'] = $resultform['categories'];
        $array = $param;
        $bool_with_cat = false;
        $array_cat = array();
        $array_parameter = array();
        $index_parameter = 0;
        $tt = ""; //$array['categories'];
        $query_categorie = "";
        $query1 = "SELECT dnt.id as donateur_id,u.id as id_user,d.id as don_id,rf.id as recu_fiscale,dnt.nom,dnt.prenom ,

d.montant as montant_don,d.ispa as ispa,mp.libelle as mode_paiement,cause.code as cause_code,cause.date_debut_projet as date_debut , cause.date_fin_projet as date_fin,

s.libelle as satut_don,d.date_fiscale,p.id as prelevement_id,p.rejet,s.id as statut_id ,t.id as transaction_id,

d.abo_id as abo_id,d.mode_id,d.motif_disable_don_id,d.cause_id,d.user_id,

occ.id as id_occasion, occ.code as code_occassion,cmp.id as id_code_compagne,cmp.code as code_compagne,abo.iban as iban


FROM coline_en_re_full_db.don d


left join coline_en_re_full_db.rf rf

on d.id = rf.don_id



inner join coline_en_re_full_db.User u

on d.user_id = u.id

left join coline_en_re_full_db.donateur dnt

on u.id = dnt.user_id


left join coline_en_re_full_db.cause cause

on d.cause_id = cause.id



left join coline_en_re_full_db.code_occasion occ

on d.cause_id = occ.id


left join coline_en_re_full_db.code_compagne cmp

on occ.code_compagne= cmp.id



inner join coline_en_re_full_db.transaction t

on d.transaction_id = t.id

left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id

left join coline_en_re_full_db.abonnement abo
on d.abo_id =abo.id

        
left join coline_en_re_full_db.prelevement p

on d.abo_id = p.abo_id

inner join coline_en_re_full_db.statut_paiement s 

on t.status_id = s.id

WHERE   s.libelle = 'Don validé' and ( p.rejet is null or p.rejet != 1)";
        $query_fin = "Order By d.id DESC";
        // and occ.id is not null and cmp.id is not null and abo.iban is not null
        // ajouter cette condition dans les requette de filtrage du code occassion, etc...
        if (count($array) >= 1) {
            foreach ($array as $key => $value) {

                if ($key == 'cause') {
                    if ($value != NULL) {
                        $query_cause = "AND  cause.code ='" . $value . "'";
                        $query1 .= " " . $query_cause;
                    }
                } elseif ($key == 'num_rf') {
                    if ($value != NULL) {
                        $query_num_rf = "AND rf.id ='" . $value . "'";
                        $query1 .= " " . $query_num_rf;
                    }
                } elseif ($key == 'montant_don') {
                    if ($value != NULL) {
                        $query_montant_don = "AND d.montant = '" . $value . "'";
                        $query1 .= " " . $query_montant_don;
                    }
                } elseif ($key == 'montant_choice') {
                    if ($value != NULL) {
//                        $query_prenom = "AND dnt.prenom ='" . $value . "'";
//                        $query1 .= " " . $query_prenom;
                    }
                } elseif ($key == 'iban') {

                    if ($value != NULL) {
                        $queryiban = "AND abo.iban ='" . $value . "'";
                        $query1 .= " " . $queryiban;
                    }
                } elseif ($key == 'lot_don') {
                    if ($value != NULL) {
//                        $query_lot = "AND dnt.nom_entreprise ='" . $value . "'";
//                        $query1 .= " " . $query_lot;
                    }
                } elseif ($key == 'id_don') {
                    if ($value != NULL) {
                        $query_id_don = "AND d.id ='" . $value . "'";
                        $query1 .= " " . $query_id_don;
                    }
                } elseif ($key == 'type_don') {
                    if ($value != NULL) {
//                        $query_type_don = "AND dnt.iso_pays ='" . $value . "'";
//                        $query1 .= " " . $query_type_don;
                    }
                } elseif ($key == 'num_cheque') {
                    if ($value != NULL) {
//                        $query_zipcode = "AND dnt.zipcode ='" . $value . "'";
//                        $query1 .= " " . $query_zipcode;
                    }
                } elseif ($key == 'code_occasion') {
                    if ($value != NULL) {
                        $query_num_cheque = "AND occ.code ='" . $value . "'";
                        $query1 .= " " . $query_num_cheque;
                    }
                } elseif ($key == 'code_campagne') {

                    if ($value != NULL) {
                        $query_code_campagne = "AND cmp.code ='" . $value . "'";
                        $query1 .= " " . $query_code_campagne;
                    }
                } elseif ($key == 'd_actif') {
                    $query_don_actif = "AND d.removed = 0 ";
                    $query1 .= " " . $query_don_actif;
                } elseif ($key == 'd_inactif') {
                    $query_don_inactif = "AND d.removed = 1 ";
                    $query1 .= " " . $query_don_inactif;
                } elseif ($key == 'is_rf') {
//                    $query_is_rf = "AND dnt.adresse3 ='" . $value . "'";
//                    $query1 .= " " . $query_is_rf;
                } elseif ($key == 'date_debut') {
                    $query_date_debut = "AND cause.date_debut_projet <='" . $value . "'";
                    $query1 .= " " . $query_date_debut;
                } elseif ($key == 'date_fin') {
                    $query_date_date_fin = "AND cause.date_fin_projet >='" . $value . "'";
                    $query1 .= " " . $query_date_date_fin;
                }
            }// end foreach
//            $bool_with_cat = false;
        }

        if (count($array) == 0) {
            $query = $query1 . " " . $query_fin;
        }



//        $vv = "WHERE d.date_fiscale <='" . $datetime2->format("Y-m-d H:i:s") . "' AND d.date_fiscale >='" . $datetime1->format("Y-m-d H:i:s") . "' AND s.libelle = 'Don validé'
//        AND d.motif_disable_don_id is null  AND (p.rejet = 0 OR p.rejet is null)";


        $query = $query1 . " " . $query_fin;
        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
//        return $query;
    }

    public function GetDonGrouBy(array $param) {

        $array = $param;
        $bool_with_cat = false;
        $array_cat = array();
        $array_parameter = array();
        $index_parameter = 0;
        $tt = ""; //$array['categories'];
        $query_categorie = "";
        $query1 = "SELECT dnt.id as donateur_id,u.id as id_user,d.id as don_id,rf.id as recu_fiscale,dnt.nom,dnt.prenom ,

d.montant as montant_don,d.ispa as ispa,mp.libelle as mode_paiement,cause.code as cause_code,cause.date_debut_projet as date_debut , cause.date_fin_projet as date_fin,

s.libelle as satut_don,d.date_fiscale,p.id as prelevement_id,p.rejet,s.id as statut_id ,t.id as transaction_id,

d.abo_id as abo_id,d.mode_id,d.motif_disable_don_id,d.cause_id,d.user_id,

occ.id as id_occasion, occ.code as code_occassion,cmp.id as id_code_compagne,cmp.code as code_compagne,abo.iban as iban


FROM coline_en_re_full_db.don d


left join coline_en_re_full_db.rf rf

on d.id = rf.don_id



inner join coline_en_re_full_db.User u

on d.user_id = u.id

left join coline_en_re_full_db.donateur dnt

on u.id = dnt.user_id


left join coline_en_re_full_db.cause cause

on d.cause_id = cause.id



left join coline_en_re_full_db.code_occasion occ

on d.cause_id = occ.id


left join coline_en_re_full_db.code_compagne cmp

on occ.code_compagne= cmp.id



inner join coline_en_re_full_db.transaction t

on d.transaction_id = t.id

left join coline_en_re_full_db.mode_paiement mp

on d.mode_id = mp.id

left join coline_en_re_full_db.abonnement abo
on d.abo_id =abo.id

        
left join coline_en_re_full_db.prelevement p

on d.abo_id = p.abo_id

inner join coline_en_re_full_db.statut_paiement s 

on t.status_id = s.id

WHERE   s.libelle = 'Don validé' and ( p.rejet is null or p.rejet != 1)";
        $query_fin = "Group By d.id DESC";
        // and occ.id is not null and cmp.id is not null and abo.iban is not null
        // ajouter cette condition dans les requette de filtrage du code occassion, etc...
        if (count($array) >= 1) {
            foreach ($array as $key => $value) {

                if ($key == 'cause') {
                    if ($value != NULL) {
                        $query_cause = "AND  cause.code ='" . $value . "'";
                        $query1 .= " " . $query_cause;
                    }
                } elseif ($key == 'num_rf') {
                    if ($value != NULL) {
                        $query_num_rf = "AND rf.id ='" . $value . "'";
                        $query1 .= " " . $query_num_rf;
                    }
                } elseif ($key == 'montant_don') {
                    if ($value != NULL) {
                        $query_montant_don = "AND d.montant = '" . $value . "'";
                        $query1 .= " " . $query_montant_don;
                    }
                } elseif ($key == 'montant_choice') {
                    if ($value != NULL) {
//                        $query_prenom = "AND dnt.prenom ='" . $value . "'";
//                        $query1 .= " " . $query_prenom;
                    }
                } elseif ($key == 'iban') {

                    if ($value != NULL) {
                        $queryiban = "AND abo.iban ='" . $value . "'";
                        $query1 .= " " . $queryiban;
                    }
                } elseif ($key == 'lot_don') {
                    if ($value != NULL) {
//                        $query_lot = "AND dnt.nom_entreprise ='" . $value . "'";
//                        $query1 .= " " . $query_lot;
                    }
                } elseif ($key == 'id_don') {
                    if ($value != NULL) {
                        $query_id_don = "AND d.id ='" . $value . "'";
                        $query1 .= " " . $query_id_don;
                    }
                } elseif ($key == 'type_don') {
                    if ($value != NULL) {
//                        $query_type_don = "AND dnt.iso_pays ='" . $value . "'";
//                        $query1 .= " " . $query_type_don;
                    }
                } elseif ($key == 'num_cheque') {
                    if ($value != NULL) {
//                        $query_zipcode = "AND dnt.zipcode ='" . $value . "'";
//                        $query1 .= " " . $query_zipcode;
                    }
                } elseif ($key == 'code_occasion') {
                    if ($value != NULL) {
                        $query_num_cheque = "AND occ.code ='" . $value . "'";
                        $query1 .= " " . $query_num_cheque;
                    }
                } elseif ($key == 'code_campagne') {

                    if ($value != NULL) {
                        $query_code_campagne = "AND cmp.code ='" . $value . "'";
                        $query1 .= " " . $query_code_campagne;
                    }
                } elseif ($key == 'd_actif') {
                    $query_don_actif = "AND d.removed = 0 ";
                    $query1 .= " " . $query_don_actif;
                } elseif ($key == 'd_inactif') {
                    $query_don_inactif = "AND d.removed = 1 ";
                    $query1 .= " " . $query_don_inactif;
                } elseif ($key == 'is_rf') {
//                    $query_is_rf = "AND dnt.adresse3 ='" . $value . "'";
//                    $query1 .= " " . $query_is_rf;
                } elseif ($key == 'date_debut') {
                    $query_date_debut = "AND cause.date_debut_projet <='" . $value . "'";
                    $query1 .= " " . $query_date_debut;
                } elseif ($key == 'date_fin') {
                    $query_date_date_fin = "AND cause.date_fin_projet >='" . $value . "'";
                    $query1 .= " " . $query_date_date_fin;
                }
            }// end foreach
//            $bool_with_cat = false;
        }

        if (count($array) == 0) {
            $query = $query1 . " " . $query_fin;
        }



//        $vv = "WHERE d.date_fiscale <='" . $datetime2->format("Y-m-d H:i:s") . "' AND d.date_fiscale >='" . $datetime1->format("Y-m-d H:i:s") . "' AND s.libelle = 'Don validé'
//        AND d.motif_disable_don_id is null  AND (p.rejet = 0 OR p.rejet is null)";


        $query = $query1 . " " . $query_fin;
        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function superSearchAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $repSauvgardeDon = $em->getRepository('FulldonIntersaBundle:SauvgardeDon');
        $data = $request->query->all();
        $id_don = $data["id"];
//        $response = new Response($id_sauvgarde_donateur);
//        return $response;
        $entity = $repSauvgardeDon->find($id_don);
        if ($entity) {
//            $test = "fulldon_donateurbundle_donateursearchtype[refDonateur]=11762&fulldon_donateurbundle_donateursearchtype[civilite]=&fulldon_donateurbundle_donateursearchtype[nom]=&fulldon_donateurbundle_donateursearchtype[prenom]=&fulldon_donateurbundle_donateursearchtype[dateNaissance]=&fulldon_donateurbundle_donateursearchtype[nomEntreprise]=&fulldon_donateurbundle_donateursearchtype[email]=&fulldon_donateurbundle_donateursearchtype[isopays]=FR&fulldon_donateurbundle_donateursearchtype[zipcode]=75005&fulldon_donateurbundle_donateursearchtype[isoville]=&fulldon_donateurbundle_donateursearchtype[adresse3]=&fulldon_donateurbundle_donateursearchtype[adresse1]=&fulldon_donateurbundle_donateursearchtype[adresse2]=&fulldon_donateurbundle_donateursearchtype[adresse4]=&fulldon_donateurbundle_donateursearchtype[_token]=8sqALvc9j40DeANYNz675Cp5zT4293STpH3erK3yT7c&columns=list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Demail%26list%5B%5D%3Dzipcode%26list%5B%5D%3Dville&sortelement=&sortdirection=&withemail=true&withtel=&withcat=&statut_donateur=on";
//            $url_dona = $entity->getUrl();
            $request = Request::create($this->container->get('router')->generate('intersa_critere_recherche_donateur') . '?' . $entity->getUrl());
//            $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');

            $cause = $request->get('cause'); //+
            $montant_don = $request->get('montant_don'); //+

            $id_don = $request->get('id_don'); //+

            $montant_choice = $request->get('montant_choice'); //+

            $num_cheque = $request->get('num_cheque'); //+

            $date_debut = $request->get('date_debut'); //+

            $date_fin = $request->get('date_fin'); //+

            $type_don = $request->get('type_don'); //+

            $iban = $request->get('iban'); //+
//            $date_annule_fin = $request->get('date_annule_fin');
//
//            $date_annule_debut = $request->get('date_annule_debut');

            $mode_paiement = $request->get('mode_paiement'); //+

            $code_occasion = $request->get('code_occasion'); //+

            $code_campagne = $request->get('code_campagne'); //+

            $num_rf = $request->get('num_rf'); //+

            $lot = $request->get('lot_don'); //+
//            $date_stop_fin = $request->get('date_stop_fin');
//
//            $date_stop_debut = $request->get('date_stop_debut');

            $d_actif = $request->get('d_actif'); //+

            $d_inactif = $request->get('d_inactif'); //+

            $is_rf = $request->get('is_rf'); //+


            $_array_filter = array();
            $resultform = array();

            if (!empty($cause)) {
                $resultform['cause'] = $cause;
            }

            if (!empty($num_rf)) {
                $resultform['num_rf'] = $num_rf;
//                 $JsonResponse = new JsonResponse(array("tab" => $resultform));
//                return $JsonResponse;
            }

            if (!empty($montant_don)) {
                $resultform['montant_don'] = $montant_don;
            }

            if (!empty($montant_choice)) {
                $resultform['montant_choice'] = $montant_choice;
            }

            if (!empty($iban)) {
                $resultform['iban'] = $iban;
            }

            if (!empty($lot)) {
                $resultform['lot_don'] = $lot;
            }

            if (!empty($mode_paiement)) {
                $resultform['mode_paiement'] = $mode_paiement;
            }

            if (!empty($$id_don)) {
                $resultform['id_don'] = $id_don;
            }

            if (!empty($type_don)) {
                $resultform['type_don'] = $type_don;
            }

            if (!empty($num_cheque)) {
                $resultform['num_cheque'] = $num_cheque;
            }

            if (!empty($code_occasion)) {
                $resultform['code_occasion'] = $code_occasion;
            }

            if (!empty($code_campagne)) {
                $resultform['code_campagne'] = $code_campagne;
            }


            if (!empty($d_actif)) {
                $resultform['d_actif'] = $d_actif;
            }

            if (!empty($d_inactif)) {
                $resultform['d_inactif'] = $d_inactif;
            }

            if (!empty($is_rf)) {
                $resultform['is_rf'] = $is_rf;
            }

            if (!empty($date_debut)) {
                $resultform['date_debut'] = $date_debut;
//                $JsonResponse = new JsonResponse(array("tab" => $resultform));
//                return $JsonResponse;
            }

            if (!empty($date_fin)) {
                $resultform['date_fin'] = $date_fin;
            }

//            $query = $repSauvgardeDonateur->findAll();
            $query = $this->GetDonOrderBy($resultform);
//            $JsonResponse = new JsonResponse(array("tab" => $query));
//            return $JsonResponse;


            $query2 = $this->GetDonGrouBy($resultform);
//             $JsonResponse = new JsonResponse(array("tab" => $query2));
//            return $JsonResponse;



            $adapter = new ArrayAdapter($query);
            $pagerfanta = new Pagerfanta($adapter);
            $maxPerPage = 20;
            $current_page = 1;
            $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($current_page); // 1 by default
            $data = $pagerfanta->getCurrentPageResults();


            $adapter2 = new ArrayAdapter($query2);
            $pagerfanta2 = new Pagerfanta($adapter2);
            $maxPerPage2 = 20;
            $current_page2 = 1;
            $pagerfanta2->setMaxPerPage($maxPerPage2); // 10 by default
            $pagerfanta2->setCurrentPage($current_page); // 1 by default
            $data2 = $pagerfanta2->getCurrentPageResults();
            $current_page2 = $pagerfanta2->getCurrentPage();
            $last_page = $pagerfanta2->getNbPages();
//            $JsonResponse = new JsonResponse(array("tab" => $data2));
//            return $JsonResponse;

            $colDispaly = array(array('numdon' => '#REF'),
                array('nom' => 'Nom'),
                array('prenom' => 'Prénom'),
                array('statut' => 'Statut'),
                array('modepaiement' => 'Mode de paiement'),
                array('typedon' => 'Type de don'),
                //array('lot' => 'Lot'), 
                array('rfs' => 'Reçus fiscaux'),
                //array('createdat' => 'Date de création'),
                array('datefiscale' => 'Date fiscale'),
                array('codeactivite' => 'Code d\'activité'),
                array('codeoccasion' => 'Code occasion'),
                array('amount' => 'Montant'),
                array('codecompagne' => 'Code campagne'));


            $view = $this->renderView('FulldonIntersaBundle:Critere:dons/ligne_search_don.html.twig', array('data' => $data,
                'col_display' => $colDispaly,
//            'criterecherche' => $criterecherche,
                'pager' => $pagerfanta,
                'pager2' => $pagerfanta2, //
                'data2' => $data2,
                'id_entity' => $id_don,
                'current_page' => $current_page2,
                'last_page' => $last_page,
//            'last_page' => $last_page,
//            'previous_page' => $previous_page,
//            
//            'next_page' => $next_page,
//            'total_donateur' => $total_donateur,
//            'params' => $params,
            ));
            $response = new Response($view);
            return $response;
        } else {
            $this->createNotFoundException('erreur');
        }
    }

}
