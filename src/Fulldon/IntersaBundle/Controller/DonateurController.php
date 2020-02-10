<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\DonateurBundle\Form\PndType;
use Fulldon\IntersaBundle\Event\LogVar;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\DonateurBundle\Entity\Pays;
use Fulldon\DonateurBundle\Entity\Ville;
use Fulldon\DonateurBundle\Entity\CategoryDonateur;
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
use Fulldon\IntersaBundle\Form\DonCumuleSearchType;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class DonateurController extends Controller {

    private $init = array();

    public function preExecute() {
        $dem = $this->getDoctrine()->getManager();
//        $user = $this->get('security.context')->getToken()->getUser();

        $persoRep = $dem->getRepository('FulldonIntersaBundle:Personnalisation');
        $perso = $persoRep->find(1);

        $this->init['perso'] = $perso;
    }

    public function modify_url($mod) {
        $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $query = explode("&", $_SERVER['QUERY_STRING']);
        if (!$_SERVER['QUERY_STRING']) {
            $queryStart = "?";
        } else {
            $queryStart = "&";
        }
        // modify/delete data 
//    foreach($query as $q) 
//    { 
//        list($key, $value) = explode("=", $q); 
//        if(array_key_exists($key, $mod)) 
//        { 
//            if($mod[$key]) 
//            { 
//                $url = preg_replace('/'.$key.'='.$value.'/', $key.'='.$mod[$key], $url); 
//            } 
//            else 
//            { 
//                $url = preg_replace('/&?'.$key.'='.$value.'/', '', $url); 
//            } 
//        } 
//    } 
        // add new data 
        foreach ($mod as $key => $value) {
            if ($value && !preg_match('/' . $key . '=/', $url)) {
                $url .= $queryStart . $key . '=' . $value;
            }
        }
        return $url;
    }

    public function fetchAllEntreprise() {
        $query = "SELECT id,nom_entreprise  FROM coline_en_re_full_db.donateur
where nom_entreprise is not null  and nom_entreprise !='' ";
        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function fetchAllZipCode() {
        $query = "SELECT id,zipcode  FROM coline_en_re_full_db.donateur
where zipcode is not null and zipcode !='' ";
        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function fetchAllAdresse3() {
        $query = "SELECT id, adresse3 FROM coline_en_re_full_db.donateur
where adresse3 is not null and adresse3 !='' ";
        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function fetchAllTelFixe() {
        $query = "SELECT id, telephone_fixe  FROM coline_en_re_full_db.donateur
where telephone_fixe is not null and telephone_fixe !='' ";
        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getArrayOfEntreprise() {

        $fetch = $this->fetchAllEntreprise();
        $info = array();
        foreach ($fetch as $key => $value) {
            $info[$key] = $value['id'];
            $info[$key] = $value['nom_entreprise'];
        }

        return $info;
    }

    public function getArrayOfZipCode() {

        $fetch = $this->fetchAllZipCode();
        $info = array();
        foreach ($fetch as $key => $value) {
            $info[$key] = $value['id'];
            $info[$key] = $value['zipcode'];
        }

        return $info;
    }

    public function getArrayOfAdresse3() {

        $fetch = $this->fetchAllAdresse3();
        $info = array();
        foreach ($fetch as $key => $value) {
            $info[$key] = $value['id'];
            $info[$key] = $value['adresse3'];
        }

        return $info;
    }

    public function getArrayOfTelFixe() {

        $fetch = $this->fetchAllTelFixe();
        $info = array();
        foreach ($fetch as $key => $value) {
            $info[$key] = $value['id'];
            $info[$key] = $value['telephone_fixe'];
        }

        return $info;
    }

    /**
     * @return Response
     */
    public function superSearchAction() {


        $request = $this->get('request');

//        die('superSearchAction');
        if ($request->getMethod() == 'GET') {
            $don = new Don();
            $form_cumule_don = $this->createForm(new DonCumuleSearchType(), $don);
        }



        $request = Request::createFromGlobals();
        $last_page = 0;
        $page = 0;
        $next_page = 0;
        $previous_page = 0;
        $memo_search = array();
        $params = null;
        $donateur = new Donateur();
        $dem = $this->getDoctrine()->getManager();
        $repFavoris = $dem->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $repCat = $dem->getRepository('FulldonDonateurBundle:CategoryDonateur');
        $favoris = $repFavoris->findBy(array('section' => 'donateurs'));
        $form = $this->createForm(new DonateurSearchType(), $donateur);
        $currentPage = 0;
        $coldisplay = array();
        $total_donateur = 0;
        $total_don = 0;
        $result2 = null;
        $withcat = $request->get('withcat');

        $memo_search['withcat'] = $withcat;
        $withemail = $request->get('withemail');
//         var_dump($withemail);
//        die('$withemail');
        $memo_search['withemail'] = $withemail;
        $withtel = $request->get('withtel');
        $memo_search['withtel'] = $withtel;
        $statutDonateur = $request->get('statut_donateur');
//        var_dump($statutDonateur);
//        die('$statutDonateur');
        $memo_search['statut_donateur'] = $statutDonateur;
        $sortelement = $request->get('sortelement');
        $sortdirection = $request->get('sortdirection');
        $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
        $pre_params = $resultform;
        $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
        $baseQuery = false;
//        var_dump($request);
//        var_dump($request->get('fulldon_donateurbundle_donateursearchtype'));
//        DIE('ICI');
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
            'adresse', 'cumuldon');
        $page = $request->get('page', 1);

        $data = array();
        $cumul = 0.00;
        $date = date("Y-m-d H:i:s");


        if ($request->getMethod() == 'GET' && count($pre_params) > 0) {

//            var_dump($resultform['isopays']);
//            var_dump($resultform);
//            die('iso');

            $donateur->setZipcode($resultform['zipcode']);
            $donateur->setNom($resultform['nom']);
            $donateur->setPrenom($resultform['prenom']);
            if (isset($resultform['email'])) {
                $donateur->setEmail($resultform['email']);
            }
            $donateur->setCivilite($resultform['civilite']);
            $donateur->setAdresse1($resultform['adresse1']);
            $donateur->setAdresse2($resultform['adresse2']);
            $donateur->setAdresse3($resultform['adresse3']);
            $donateur->setAdresse4($resultform['adresse4']);
            $donateur->setNomEntreprise($resultform['nomEntreprise']);
            $donateur->setRefDonateur($resultform['refDonateur']);

            if ($resultform['isopays'] == '') {
//                $donateur->setIsopays('FR');
                $donateur->setIsopays('');
            } else {
                $donateur->setIsopays($resultform['isopays']);
            }


            $donateur->setIsoville($resultform['isoville']);
            if (isset($resultform['categories'])) {



                foreach ($resultform['categories'] as $id) {
//                    var_dump($resultform['categories']);
//                    die('-2');
                    $donateur->addCategory($repCat->find($id));
                }
            }
//            die('-3');
            $form = $this->createForm(new DonateurSearchType(), $donateur);

            $app = $this->container->getParameter('elastic_db_name'); // $app= 'coline_en_re'


            $donateurs = $this->get('fos_elastica.finder.' . $app . '.donateur');

            $elasticaQuery = new \Elastica\Query();

            $query_part = new \Elastica\Query\Bool();
            $filters = new \Elastica\Filter\Bool();
//            var_dump($resultform['removed']);
//             var_dump($resultform);
//            die('$resultform');

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




//            var_dump($statutDonateur);
//            die;


            if (empty($statutDonateur)) {
//                die('empty');
                $query_part->addMust(
                        new \Elastica\Query\Match('removed', false)
                );
            } else {


                if ($statutDonateur == 'on') {
//                    die('on');
                    $query_part->addMust(
                            new \Elastica\Query\Match('removed', false)
                    );
                } elseif ($statutDonateur == 'off') {

//                    die('off');
                    $query_part->addMust(
                            new \Elastica\Query\Match('removed', true)
                    );
                }
            }





//            var_dump('isopays');
//            var_dump($resultform['isopays']);


            foreach ($resultform as $key => $value) {
                if (!empty($value)) {
                    if (in_array($key, array('isoville', 'isopays', 'civilite', 'refDonateur', 'allowRf', 'removed'))) {
                        // enter 
//                        die('1');
                        $query_part->addMust(
                                new \Elastica\Query\Match($key, $value)
                        );
                    } elseif (in_array($key, array('nom', 'prenom', 'email', 'adresse1', 'adresse2', 'adresse3', 'adresse3', 'zipcode', 'nomEntreprise'))) {
                        // no enter
                        $query_part->addMust(
                                new \Elastica\Query\MatchPhrasePrefix($key, $value)
                        );
                    } elseif (in_array($key, array('zipcode'))) {
                        // non enter
                        $query_part->addMust(
                                new \Elastica\Query\Prefix($key, $value)
                        );
                    } elseif (in_array($key, array('dateNaissance'))) {
                        // no enter
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
                        // no enter
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
//            die('fin foreach');
//            if (!$baseQuery) {
//                $query_part = new \Elastica\Query\MatchAll();
//            }
            $query = new \Elastica\Query\Filtered($query_part, $filters);
//             var_dump($query);
//            die('$query');
            //$elasticaQuery->setQuery($query_part);
            $finalQuery = new \Elastica\Query($query);

//            var_dump($finalQuery);
//            die('$finalQuery');
            if (!empty($sortelement) && !empty($sortdirection)) {

                $finalQuery->setSort(array(array_search($sortelement, $restCols) => array('order' => $sortdirection)));
            } else {
                $finalQuery->setSort(array('id' => array('order' => 'desc')));
            }

            if ($page == 0) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
                return $this->redirect($this->generateUrl('elastic_donateur'));
            }
            $result = $donateurs->findPaginated($finalQuery);

//             var_dump($result);
//            die('$result');
            $total_donateur = $result->getNbResults();
//            var_dump($total_donateur);
//            die('$total_donateur');
            $donateur_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
            $result->setMaxPerPage($donateur_per_page);
            $last_page = ceil($total_donateur / $donateur_per_page);

            $result->setCurrentPage($page);
            $result2 = $result;
//            var_dump($result2);
//            die('result2');
            $currentPage = $result->getCurrentPageResults();


            if (count($currentPage) == 0) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
            }
            unset($pre_params['page']);
            $url = array();
            foreach ($pre_params as $key => $ele) {
                if ($key == 'categories') {
                    foreach ($ele as $v) {
                        $url[] = "fulldon_donateurbundle_donateursearchtype[$key][]=$v";
                    }
                } else {
                    $url[] = "fulldon_donateurbundle_donateursearchtype[$key]=$ele";
                }
            }
            $params = implode('&', $url);
            // Adding columns to params
            $params .= '&columns=' . urlencode($columns) . "&sortelement=$sortelement&sortdirection=$sortdirection";
            $params .= '&withemail=' . $withemail . '&withtel=' . $withtel . '&withcat=' . $withcat . '&statut_donateur=' . $statutDonateur;
        }
        $mycol = explode('&', $columns);
        foreach ($mycol as $col) {
            $coldisplay[] = substr($col, 7, strlen($col));
        }

        foreach ($coldisplay as $tab) {
            $index = array_search($tab, $restCols);
            unset($restCols[$index]);
        }


        $pays = $this->getArrayOfPays();
        $villes = $this->getArrayOfVille();
        $categories = $this->getArrayOfCategorie();
        $status = $this->getArrayOfStaut();


        $entreprises = $this->getArrayOfEntreprise();
        $all_telfixe = $this->getArrayOfTelFixe();
        $all_adresses = $this->getArrayOfAdresse3();
        $all_zipcodes = $this->getArrayOfZipCode();

        $coldisplay_ligne_cumule_don = array(array('numdonateur' => '#REF'), array('nom' => 'Nom'), array('prenom' => 'Prénom'), array('email_donateur' => 'E-mail Donateur'), array('statut' => 'Statut'),
            array('cumul_don' => 'Cumul Don'), array('createdat' => 'Date Dernier Don'), array('modepay' => 'Mode de paiement'), array('nomentreprise' => 'Nom d\'entreprise'));

        // $datax = $this->getCumulDernierDonBy($id);
//        $date = $datax[0]['dernier_don'];
//        $cumul = $datax[0]['cumul'];
//entreprise_dropdawn*+ getArrayOfEntreprise
//
//telfixe_dropdawn* + getArrayOfTelFixe
//
//adresse_dropdawn* +  getArrayOfAdresse3
//
//codepostale_dropdawn*+  getArrayOfZipCode
        //elasticsearch_test.html.twig  elasticsearch.html.twig
//        die('1');
        $calcul_cumul_donateur = array();
        $index_calcul = 0;
        if ($currentPage) {

            foreach ($currentPage as $key => $value) {
                $calcul_cumul_donateur[$index_calcul]['donateur_id'] = $value->getId();
                $dataxx = $this->DonateurCumulDon($value->getId());
                $cumulxx = $dataxx[0]['cumul'];
                if ($cumulxx == null) {
                    $cumulxx = 0;
                }
//                var_dump($value);
//                die('icici');
                $calcul_cumul_donateur[$index_calcul]['cumul'] = $cumulxx;
                $calcul_cumul_donateur[$index_calcul]['user_id'] = $value->getUser()->getId();
                $index_calcul ++;
            }
        }



//        $coldisplay_ligne_de_enregister = array(array('numdonateur' => 'Numéro du donateur'), array('nom' => 'Nom'), 
//            array('prenom' => 'Prénom'),array('nomentreprise' => 'Nom d\'entreprise'),array('statut' => 'Statut'),
//            array('ville' => 'Ville'), array('pays' => 'Pays'),array('code_pstal' => 'Code Postal'),
//            array('date_naissance' => 'Date de naissance'),array('email' => 'Email'),array('tel_mobil' => 'Téléphone mobile'),
//            array('tel_fix' => 'Téléphone fixe'),array('createdat' => 'Date de Creation'),array('cat' => 'Catégories'),
//            array('adresse' => 'Adresse'),array('cumul_don' => 'Cumul des dons'));

        $pager_dessin = NULL;
        $current_page_dessin = 0;
        $last_page_dessin = 0;
        $coldisplay_ligne_de_enregister = array(array('title' => 'Titre'),
            array('descrip' => 'Description'), array('createdat' => 'Date de creation'), array('btn_de_add' => ' '), array('btn_de_delete' => ' '));
        return $this->render('FulldonIntersaBundle:Donateurs:elasticsearch.html.twig', array('form' => $form->createView(),
                    'result' => $currentPage,
                    'result2' => $result2,
                    'memo_search' => $memo_search,
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_donateur' => $total_donateur,
                    'params' => $params,
                    'form_cumule_don' => $form_cumule_don->createView(),
                    'favoris' => $favoris,
                    'col_display' => $coldisplay,
                    'columns' => $columns,
                    'rest_cols' => $restCols,
                    'sortelement' => $sortelement,
                    'sortdirection' => $sortdirection,
                    'pays' => $pays,
                    'villes' => $villes,
                    'categories' => $categories,
                    'status' => $status,
                    'entreprises' => $entreprises,
                    'allTelFixe' => $all_telfixe,
                    'allAdresses' => $all_adresses,
                    'allZipCodes' => $all_zipcodes,
                    'coldisplay_ligne_cumule_don' => $coldisplay_ligne_cumule_don,
                    'coldisplay_ligne_de_enregister' => $coldisplay_ligne_de_enregister,
                    'data' => $data,
                    'cumul' => $cumul, //$id_dessin
                    'date' => $date,
                    'calcul_cumul_donateur' => $calcul_cumul_donateur
//                    'pager_dessin' => $pager_dessin,
//                    'current_page_dessin' => $current_page_dessin,
//                    'last_page_dessin' => $last_page_dessin
        ));
    }

    public function DonateurCumulDon($donateur_id) {

        $query = "SELECT d.id as don_id,d.user_id,dt.id as donateur_id, SUM(d.montant) AS cumul,MAX(d.created_at) AS dernier_don,dt.removed as statut_donateur    FROM coline_en_re_full_db.don d

LEFT JOIN coline_en_re_full_db.donateur dt ON  d.user_id=dt.user_id

WHERE dt.removed=0 AND d.removed=0 

and dt.id = '" . $donateur_id . "' ";

        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function GetCumulDon($montant_min, $montant_max, $date_min, $date_max, $email_donateur) {

//        $montant_min = 20.00;
//        $montant_max = 40.00;
//        $date_min = '2016-06-15'; //'15/06/2016';
//        $date_max = '2017-10-16'; //'16/10/2017';
//        


        if ($montant_min == 0) {
            $montant_min = NULL;
        }

        if ($montant_max == 0) {
            $montant_max = NULL;
        }
        if ($date_min == 0) {
            $date_min = NULL;
        }
        if ($date_max == 0) {
            $date_max = NULL;
        }



//            $JsonResponse = new JsonResponse(array("tab" => array($email_donateur)));
//            return $JsonResponse;
        $query = "SELECT d.id as don_id,d.user_id,dt.id as donateur_id,dt.nom as nom,dt.prenom as prenom,dt.email as email_donateur,sp.code as statut_don, SUM(d.montant) AS cumul,MAX(d.created_at) AS dernier_don,dt.removed as statut_donateur,mp.libelle as mode_paiement,dt.nom_entreprise as entreprise   FROM coline_en_re_full_db.don d
LEFT JOIN coline_en_re_full_db.donateur dt ON dt.id=d.user_id
LEFT JOIN coline_en_re_full_db.mode_paiement mp on d.mode_id=mp.id
LEFT JOIN coline_en_re_full_db.transaction tr on d.transaction_id = tr.id

LEFT JOIN coline_en_re_full_db.statut_paiement sp on  tr.status_id = sp.id

WHERE dt.removed=0 AND d.removed=0 ";

        if (!is_null($email_donateur)) {

            $query = "SELECT d.id as don_id,d.user_id,dt.id as donateur_id,dt.nom as nom,dt.prenom as prenom,dt.email as email_donateur,sp.code as statut_don, SUM(d.montant) AS cumul,MAX(d.created_at) AS dernier_don,dt.removed as statut_donateur,mp.libelle as mode_paiement,dt.nom_entreprise as entreprise   FROM coline_en_re_full_db.don d
LEFT JOIN coline_en_re_full_db.donateur dt ON dt.id=d.user_id
LEFT JOIN coline_en_re_full_db.mode_paiement mp on d.mode_id=mp.id
LEFT JOIN coline_en_re_full_db.transaction tr on d.transaction_id = tr.id

LEFT JOIN coline_en_re_full_db.statut_paiement sp on  tr.status_id = sp.id

WHERE dt.removed=0 AND d.removed=0  AND dt.email = '" . $email_donateur . "' ";
        }


        if (!is_null($date_min)) {

            $query .= ' ';
            $query .= " AND d.created_at >= '" . $date_min . "' ";
        }



        if (!is_null($date_max)) {

            $query .= ' ';
            $query .= " AND d.created_at <= '" . $date_max . "' ";
        }
        $query .= ' ';
        $query .= " GROUP BY user_id ";



        if (!is_null($montant_min) && is_null($montant_max)) {

            $query .= ' ';
            $query .= " HAVING cumul >= '" . $montant_min . "' ";
        }

        if (is_null($montant_min) && !is_null($montant_max)) {

            $query .= ' ';
            $query .= " HAVING cumul <= '" . $montant_max . "' ";
        }

        if (!is_null($montant_min) && !is_null($montant_max)) {

            $query .= ' ';
            $query .= " HAVING cumul >= '" . $montant_min . "'  AND  cumul <= '" . $montant_max . "' ";
        }


//        var_dump($query);
//        die('icicici');


        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return Response
     */
    public function cumuleDonAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request');
        $page = 1;
        $date_max_cumule = 0;
        $date_min_cumule = 0;
        $date1 = 0;
        $date2 = 0;
        $email_donateur = "";
//        $MtMinDon = 0;
//        $MtMaxDon = 0;
//        $DateMinDon = date("Y-m-d H:i:s");
//        $DateMaxDon = date("Y-m-d H:i:s");

        if ($request->getMethod() == 'GET') {

            $datax = $request->query->all();


            $page = $datax["param"];
            //data: "param=" + page + "&param1=" + montant_min + "&param2=" + montant_max + "&param3=" + date_min + "&param4=" + date_max,
            $MtMinDon = $datax["param1"];
            $MtMaxDon = $datax["param2"];
            $DateMinDon = $datax["param3"];
            $DateMaxDon = $datax["param4"];
            $email_donateur = $datax["param5"];

//            $JsonResponse = new JsonResponse(array("tab" => $MtMinDon, "tab2" => $MtMaxDon, "tab3" => $DateMinDon, "tab4" => $DateMaxDon,));
//            $JsonResponse = new JsonResponse(array("tab" => $email_donateur));
//            return $JsonResponse;
            if (($MtMinDon == '') || ($MtMinDon == NULL)) {
                $MtMinDon = 0;
            }
            if (($MtMaxDon == '') || ($MtMaxDon == NULL)) {
                $MtMaxDon = 0;
            }


            if (($DateMinDon != NULL) || ($DateMinDon != '')) {
                $explode = explode('/', $DateMinDon);
                $day_min = $explode[0];
                $month_min = $explode[1];
                $year_min = $explode[2];
                $date_min_cumule = $year_min . '-' . $month_min . '-' . $day_min . ' 00:00:00';
                $date1 = $year_min . '-' . $month_min . '-' . $day_min;
            } else {
                $DateMinDon = 0;
                $date_min_cumule = 0;
                $date1 = 0;
            }


            if (($DateMaxDon != NULL) || ($DateMaxDon != '')) {
                $explode2 = explode('/', $DateMaxDon);
                $day_max = $explode2[0];
                $month_max = $explode2[1];
                $year_max = $explode2[2];
                $date_max_cumule = $year_max . '-' . $month_max . '-' . $day_max . ' 23:59:59';
                $date2 = $year_max . '-' . $month_max . '-' . $day_max;
            } else {
                $DateMaxDon = 0;
                $date_max_cumule = 0;
                $date2 = 0;
            }


//            $JsonResponse = new JsonResponse(array("tab" => $datax));
//            return $JsonResponse;
//            $JsonResponse = new JsonResponse(array("tab" => $MtMinDon,"tab1" => $MtMaxDon,
//                
//               "tab2" => $DateMinDon,"tab3" => $DateMaxDon,"tab4" => $page));
//            return $JsonResponse;
        }
        if ($request->getMethod() == 'POST') {
            $don = new Don();
            $form = $this->createForm(new DonCumuleSearchType(), $don);
            $form->handleRequest($request); //  bindRequest
            $MtMinDon = $form->get('MtMinDon')->getData();
            $MtMaxDon = $form->get('MtMaxDon')->getData();
            $DateMinDon = $form->get('DateMinDon')->getData();
            $DateMaxDon = $form->get('DateMaxDon')->getData();
            $email_donateur = $form->get('emailDonateur')->getData();
            if (isset($DateMinDon) && !is_null($DateMinDon) && $DateMinDon != "") {
                $explode = explode('/', $DateMinDon);
                $day_min = $explode[0];
                $month_min = $explode[1];
                $year_min = $explode[2];
                $date_min_cumule = $year_min . '-' . $month_min . '-' . $day_min . ' 00:00:00';
                $date1 = $year_min . '-' . $month_min . '-' . $day_min;
//                $DateMinDon = $year_min . '-' . $month_min . '-' . $day_min . ' 00:00:00';
            }

            if (isset($DateMaxDon) && !is_null($DateMaxDon) && $DateMaxDon != "") {
                $explode2 = explode('/', $DateMaxDon);
                $day_max = $explode2[0];
                $month_max = $explode2[1];
                $year_max = $explode2[2];
                $date_max_cumule = $year_max . '-' . $month_max . '-' . $day_max . ' 23:59:59';
                $date2 = $year_max . '-' . $month_max . '-' . $day_max;
//                $DateMaxDon = $year_max . '-' . $month_max . '-' . $day_max . ' 23:59:59';
            }
        }





        if (is_null($MtMinDon)) {
            $MtMinDon = 0;
        }
        if (is_null($MtMaxDon)) {
            $MtMaxDon = 0;
        }

        if (is_null($DateMinDon)) {
            $date_min_cumule = 0; //date("Y-m-d H:i:s");
            $DateMinDon = 0;
            $date1 = 0;
        }
        if (is_null($DateMaxDon)) {
            $date_max_cumule = 0; //date("Y-m-d H:i:s");
            $DateMaxDon = 0;
            $date2 = 0;
        }
        if (is_null($email_donateur)) {
            $email_donateur = NULL;
        }
        $query2 = $this->GetCumulDon($MtMinDon, $MtMaxDon, $date_min_cumule, $date_max_cumule, $email_donateur);
//        $JsonResponse = new JsonResponse(array("tab" => $query2));
//        return $JsonResponse;
        $adapter = new ArrayAdapter($query2);
        $pagerfanta = new Pagerfanta($adapter);
        $maxPerPage = 20; //20
        $current_page = $page;
//        $current_page = $pagerfanta->getCurrentPage();
//        $JsonResponse = new JsonResponse(array("tab" => $current_page));
//            return $JsonResponse;




        $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
        $pagerfanta->setCurrentPage($current_page); // 1 by default
        $data = $pagerfanta->getCurrentPageResults();
//            $JsonResponse = new JsonResponse(array("tab" => $data));
//            return $JsonResponse;
        $last_page = $pagerfanta->getNbPages();

        $coldisplay_ligne_cumule_don = array(array('numdonateur' => '#REF'), array('nom' => 'Nom'), array('prenom' => 'Prénom'), array('email_donateur' => 'E-mail'), array('statut' => 'Statut'),
            array('cumul_don' => 'Cumul Don'), array('createdat' => 'Date Dernier Don'), array('btn_gestion' => ' '));

//        $JsonResponse = new JsonResponse(array("tab" => $coldisplay_ligne_cumule_don));
//        return $JsonResponse;
//          $JsonResponse = new JsonResponse(array("tab1" => $DateMinDon,"tab2" => $DateMaxDon));
//            return $JsonResponse;
        $view = $this->renderView('FulldonIntersaBundle:Donateurs:ligne_cumul_don.html.twig', array(
            'data' => $data,
            'coldisplay_ligne_cumule_don' => $coldisplay_ligne_cumule_don,
            'pager2' => $pagerfanta,
            'current_page' => $current_page,
            'last_page' => $last_page,
            'montant_min' => $MtMinDon,
            'montant_max' => $MtMaxDon,
//            'email_donateur' => $email_donateur,
            'date_min' => $DateMinDon,
            'date_max' => $DateMaxDon
        ));



        $response = new Response($view);
        return $response;






//            $JsonResponse = new JsonResponse(array("tab" => $query2));
//            return $JsonResponse;
    }

    /**
     * @return Response
     */
    public function cumuleDon2Action(Request $request) {



        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request');
        if ($request->getMethod() == 'POST') {
            $don = new Don();
            $form = $this->createForm(new DonCumuleSearchType(), $don);
            $form->handleRequest($request); //  bindRequest
            $MtMinDon = $form->get('MtMinDon')->getData();
            $MtMaxDon = $form->get('MtMaxDon')->getData();
            $DateMinDon = $form->get('DateMinDon')->getData();
            $DateMaxDon = $form->get('DateMaxDon')->getData();
            $refDonateur = $form->get('refDonateur')->getData();
//            var_dump($MtMinDon);
//            var_dump($MtMaxDon);
//            var_dump($DateMinDon);
//            var_dump($DateMaxDon);
//            var_dump($refDonateur);
//            die('$extra');
        }
        $baseQuery = false;
        $id_don = ' ';
        $type_don = null;
        $d_actif = null;
        $d_inactif = 'off';
        $cause = ' ';
        $code_occasion = ' ';
        $code_campagne = ' ';
        $lot = ' ';
        $date_debut = ' ';
        $date_fin = null;
        $date_annule_debut = ' ';
        $date_annule_fin = ' ';
        $date_stop_debut = ' ';
        $date_stop_fin = ' ';
        $iban = ' ';
        $num_rf = null;
        $is_rf = ' ';
        $num_cheque = ' ';
        $mode_paiement = ' ';
        $last_page_don = 0;
        $page = 1;
        $next_page_don = 0;
        $previous_page_don = 0;
        $params = null;
        $currentPage_don = 0;
        $result2_don = null;

        //$page = $request->get('page', 1);
//        var_dump($request->getMethod());
//        die('icici');
        if ($request->getMethod() == 'GET') {

            $datax = $request->query->all();
            $page = $datax["param"];
        }






        $app = $this->container->getParameter('elastic_db_name');
        $dons = $this->get('fos_elastica.finder.' . $app . '.don');
        $elasticaQuery = new \Elastica\Query();

        $query_part = new \Elastica\Query\Bool();
        $filters = new \Elastica\Filter\Bool();



        $operator1 = 'gte'; //superieur ou egale gte
        if (isset($MtMinDon) && !is_null($MtMinDon) && $MtMinDon != "") {
            $baseQuery = true;
            $filters->addMust(
                    new \Elastica\Filter\Range('montant', array(
                $operator1 => $MtMinDon
                    ))
            );
//            $query_part->addMust(
//                    new \Elastica\Query\Match('montant', $mt_min_cumule)
//            );
        }



        $operator2 = 'lte'; // infrieur ou egale lte
        if (isset($MtMaxDon) && !is_null($MtMaxDon) && $MtMaxDon != "") {
            $filters->addMust(
                    new \Elastica\Filter\Range('montant', array(
                $operator2 => $MtMaxDon
                    ))
            );
        }



//        $DateMinDon = ''; //'15/06/2016'; //'2016-06-15';
        if (isset($DateMinDon) && !empty($DateMinDon)) {
            $DateMinDon = $DateMinDon . ' 00:00:00';
            $d = \DateTime::createFromFormat('d/m/Y H:i:s', $DateMinDon);
            $mydate = $d->format('c');

            $filters->addMust(
                    new \Elastica\Filter\Range('createdAt', array(
                'gte' => $mydate
                    ))
            );
        }

//        $DateMaxDon = ''; //'16/10/2017'; //'2017-10-16';
        if (isset($DateMaxDon) && !empty($DateMaxDon)) {
            $DateMaxDon = $DateMaxDon . ' 23:59:59';
            $d = \DateTime::createFromFormat('d/m/Y H:i:s', $DateMaxDon);
            $mydate = $d->format('c');
            $filters->addMust(
                    new \Elastica\Filter\Range('createdAt', array(
                'lte' => $mydate
                    ))
            );
        }

//        $refDonateur = '229';
        if (isset($refDonateur) && !empty($refDonateur)) {

            $baseQuery = true;
            $query_part->addMust(
                    new \Elastica\Query\Match('user.id', $refDonateur)
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

        if (!$baseQuery) {
            $query_part = new \Elastica\Query\MatchAll();
        }

        $query = new \Elastica\Query\Filtered($query_part, $filters);



        $finalQuery = new \Elastica\Query($query);


        if ($page == 0) {
            $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
            return $this->redirect($this->generateUrl('elastic_donateur'));
        }

        $result = $dons->findPaginated($finalQuery);
        $total_dons = $result->getNbResults();



//        var_dump($total_dons);
//        die('$total_dons');


        $donateur_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $result->setMaxPerPage($donateur_per_page); //ici ja modifier
        $last_page_don = ceil($total_dons / $donateur_per_page);
        $result->setCurrentPage($page);
        $result2_don = $result;

        $currentPage_don = $result->getCurrentPageResults();
        if (count($currentPage_don) == 0) {
            $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
        }
//        $previous_page_don = 0;
//        $next_page_don = 0;
//        $coldisplay = array(0 => 'numdon', 1 => 'nom', 2 => 'prenom', 3 => 'statut', 4 => 'createdat', 5 => 'amount');


        $coldisplay = array(array('numdon' => '#REF'), array('nom' => 'Nom'), array('prenom' => 'Prénom'), array('statut' => 'Statut'),
            array('amount' => 'Montant'), array('createdat' => 'Date Dernier Don'), array('modepay' => 'Mode de paiement'), array('nomentreprise' => 'Nom d\'entreprise'));
        //  /cumule/don/donateurs
        //elasticsearch_test.html.twig  elasticsearch.html.twig
        $view = $this->renderView('FulldonIntersaBundle:Donateurs:ligne_search_don.html.twig', array(
            'result_cumul' => $currentPage_don,
            'result2' => $result2_don,
            'last_page' => $last_page_don,
            'previous_page' => $previous_page_don,
            'current_page' => $page,
            'next_page' => $next_page_don,
            'total_dons' => $total_dons,
            'col_display' => $coldisplay,
        ));

        $response = new Response($view);
        return $response;
    }

    public function getArrayOfPays() {
        $em = $this->getDoctrine()->getManager();
        $listepays = $em->getRepository('FulldonDonateurBundle:Pays')->findAll();
        $list = array();
        foreach ($listepays as $key => $pays) {
            $info = array();
            $info['Id'] = $pays->getId();
            $info['Name'] = $pays->getName();
            $list[$key] = $info;
        }
        return $list;
    }

    public function getArrayOfVille() {
        $em = $this->getDoctrine()->getManager();
        $listepays = $em->getRepository('FulldonDonateurBundle:Ville')->findAll();
        $list = array();
        foreach ($listepays as $key => $pays) {
            $info = array();
            $info['Id'] = $pays->getId();
            $info['Name'] = $pays->getName();
            $list[$key] = $info;
        }
        return $list;
    }

    public function getArrayOfCategorie() {
        $em = $this->getDoctrine()->getManager();
        $listepays = $em->getRepository('FulldonDonateurBundle:CategoryDonateur')->findAll();
        $list = array();
        foreach ($listepays as $key => $pays) {
            $info = array();
            $info['Id'] = $pays->getId();
            $info['Name'] = $pays->getName();
            $list[$key] = $info;
        }
        return $list;
    }

    public function getArrayOfStaut() {

        $list = array("0" => "Actif", "1" => "Désactivé");

        return $list;
    }

    public function modificationMasseAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $json = $this->getRequest()->getContent();
        $data = json_decode($json, true);

        //$codepostale = $data["ligne"][0]["codepostale"];
        $entreprise = $data["ligne"][0]["entreprise"];
        //$status = $data["ligne"][0]["status"];
        //$telfixe = $data["ligne"][0]["telfixe"]; //adresse
        // $adresse = $data["ligne"][0]["adresse"];
        //$VilleId = $data["ligne"][0]["ville_id"];

        $CategoriId = $data["ligne"][0]["categorise_id"];
        $PaysId = $data["ligne"][0]["pays_id"];
        $array_donateur_id = $data['tab_donateur_id'];
        $oldCategorieId = ""; //$data['tab_oldcategorie_id'];
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');
        $Categories = NULL;
        $OldCategoriesDelete = NULL;
        $Pays = NULL;
        //$Ville = NULL;
        $Donateur = NULL;
        //$IsoVille = NULL;
        $IsoPays = NULL;
        //$Categorie_inDonateur = NULL;
        $array_Old_Categorie = NULL;
        //$IsoVille = $VilleId;
//        $response = new JsonResponse(array("mytab" => $data));
//        return $response;

        if ($CategoriId != "") {
            $Categories = $em->getRepository('FulldonDonateurBundle:CategoryDonateur')->find($CategoriId);
        }

        if ($PaysId != "") {
            $Pays = $em->getRepository('FulldonDonateurBundle:Pays')->find($PaysId); //46 france
//             $Pays = $em->getRepository('FulldonDonateurBundle:Pays')->findOneBy(array('id' => $PaysId));
            if ($Pays) {
                $IsoPays = $Pays->getCode();
            }
        }
//        $bool= false;
        foreach ($array_donateur_id as $key => $value) {

//            $bool= true;
//            $response = new JsonResponse(array("mytab" => $bool));
//            return $response;
            $Donateur = $repDonateur->find($value);
            if ($Donateur) {
                if (count($data['tab_oldcategorie_id']) == 1) {
                    $oldCategorieId = $data['tab_oldcategorie_id'][0];
                    if ($oldCategorieId != "") {
                        $OldCategoriesDelete = $em->getRepository('FulldonDonateurBundle:CategoryDonateur')->find($oldCategorieId);
                        if ($OldCategoriesDelete) {
                            $Donateur->removeCategory($OldCategoriesDelete);
                        }
                    }
                } elseif (count($data['tab_oldcategorie_id']) > 1) {

                    $array_Old_Categorie = $data['tab_oldcategorie_id'];

                    foreach ($array_Old_Categorie as $key => $value) {
//                        $response = new JsonResponse(array("mytab" => var_dump($value)));
//                        return $response;
                        $OldCategoriesDelete = $em->getRepository('FulldonDonateurBundle:CategoryDonateur')->find($value);
                        if ($OldCategoriesDelete) {
                            $Donateur->removeCategory($OldCategoriesDelete);
                        }
                    }
                }

//                if ($codepostale != "") {
//                    $Donateur->setZipcode($codepostale);
//                }
                if ($entreprise != "") {
                    $Donateur->setNomEntreprise($entreprise);
                }
//                if ($status != "") {
//                    //$Donateur->setRemoved($status);
//                    if ($status == "Actif") {
//                        $Donateur->setRemoved(false);
//                    }
//                    if ($status == "Désactivé") {
//                        $Donateur->setRemoved(true);
//                    }
//                }
//                if ($telfixe != "") {
//                    $Donateur->setTelephoneFixe($telfixe);
//                }
//                if ($adresse != "") {
//                    $Donateur->setAdresse3($adresse);
//                }
//                if ($IsoVille != NULL) {
//                    $Donateur->setIsoville($IsoVille);
//                }

                if ($IsoPays != NULL) {
                    $Donateur->setIsopays($IsoPays);
                }
                if ($Pays != NULL) {
                    $Donateur->setPays($Pays);
                }


                if ($Categories) {
                    $Donateur->addCategory($Categories);
                } else {
                    if (count($data['tab_oldcategorie_id']) == 1) {
                        $oldCategorieId = $data['tab_oldcategorie_id'][0];
                        if ($oldCategorieId != "") {
                            $OldCategoriesDelete = $em->getRepository('FulldonDonateurBundle:CategoryDonateur')->find($oldCategorieId);
                            if ($OldCategoriesDelete) {
                                $Donateur->addCategory($OldCategoriesDelete);
                            }
                        }
                    } elseif (count($data['tab_oldcategorie_id']) > 1) {

                        $boul_diffrenet = false;
//                        $response = new JsonResponse(array("mytab" => "ici"));
//                        return $response;
                        $array_Old_Categorie = $data['tab_oldcategorie_id'];
                        for ($i = 0; $i < count($array_Old_Categorie); $i++) {
                            for ($j = $i + 1; $j < count($array_Old_Categorie); $j++) {
                                if ($array_Old_Categorie[$i] != $array_Old_Categorie[$j]) {
                                    $boul_diffrenet = true;
                                    $value = $array_Old_Categorie[$i];
                                    $OldCategoriesDelete = $em->getRepository('FulldonDonateurBundle:CategoryDonateur')->find($value);
                                    if ($OldCategoriesDelete) {
                                        $Donateur->addCategory($OldCategoriesDelete);
                                    }
                                }// en if
                            }// end for 1
                        }//end for2

                        if (!$boul_diffrenet) {
                            $value = $data['tab_oldcategorie_id'][0];
//                            $response = new JsonResponse(array("mytab" => var_dump($value)));
//                            return $response;
                            $OldCategoriesDelete = $em->getRepository('FulldonDonateurBundle:CategoryDonateur')->find($value);
                            if ($OldCategoriesDelete) {
                                $Donateur->addCategory($OldCategoriesDelete);
                            }
                        }
                    }//endelseif
                }//end else

                $em->persist($Donateur);
            }//end if donateur
        }// end foreach
        $em->flush();

        // 


        $response = new JsonResponse(array("mytab" => "modification en masse avec succée")); //"modification en masse avec succée"
        return $response;
    }

    public function indexAction() {

        $dem = $this->getDoctrine()->getManager();
        $repFavoris = $dem->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $donateurRep = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $pndRep = $dem->getRepository('FulldonDonateurBundle:Pnd');
        $donateur = new Donateur();
        $favoris = $repFavoris->findBy(array('section' => 'donateurs'));
        $form = $this->createForm(new DonateurSearchType(), $donateur);
        $errors = array();
        // On récupère la requête
        $request = $this->getRequest();
        //initialisation
        $ids_do_pnd = array();
        $where = array();
        $result = '';
        $tellmeall = true;
        $params = null;
        $last_page = 0;
        $page = 0;
        $next_page = 0;
        $total_donateur = 0;
        $previous_page = 0;
        $memo_search = array();
        $pre_params = null;
        $coldisplay = array();
        $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');

        $columns = $request->get('columns', 'list[]=numdonateur&list[]=nom&list[]=prenom&list[]=nomentreprise&list[]=statut');
        $restCols = array('numdonateur', 'nom', 'prenom', 'nomentreprise', 'ville', 'pays', 'zipcode', 'statut', 'birthday', 'email', 'telmobile', 'telfixe', 'cat', 'adresse');
        $pre_params = $resultform;
        if ($request->getMethod() == 'GET' && count($pre_params) > 0) {
            // On fait le lien Requête <-> Formulaire
            // Page
            $page = $request->get('page', 1);
            // On fait le lien Requête <-> Formulaire

            $donateur->setZipcode($resultform['zipcode']);
            $donateur->setNom($resultform['nom']);
            $donateur->setPrenom($resultform['prenom']);
            $donateur->setEmail($resultform['email']);
            $donateur->setCivilite($resultform['civilite']);
            $donateur->setAdresse1($resultform['adresse1']);
            $donateur->setAdresse2($resultform['adresse2']);
            $donateur->setAdresse3($resultform['adresse3']);
            $donateur->setAdresse4($resultform['adresse4']);
            $donateur->setNomEntreprise($resultform['nomEntreprise']);
            $donateur->setRefDonateur($resultform['refDonateur']);
            $donateur->setIsopays($resultform['isopays']);
            $donateur->setIsoville($resultform['isoville']);

            if (isset($resultform['categorie']) && !empty($resultform['categorie']))
                $donateur->setCategorie($dem->getRepository('FulldonDonateurBundle:CategoryDonateur')->find($resultform['categorie']));
            if (isset($resultform['removed']) && !empty($resultform['removed']))
                $donateur->setRemoved($resultform['removed'] ? true : false);
            if (isset($resultform['allowRf']) && !empty($resultform['allowRf']))
                $donateur->setAllowRf($resultform['allowRf'] ? true : false);
            $form = $this->createForm(new DonateurSearchType(), $donateur);


            foreach ($resultform as $key => $value) {
                if (!empty($value)) {
                    if (in_array($key, array('isoville', 'isopays', 'civilite', 'refDonateur', 'allowRf', 'removed', 'nomEntreprise'))) {
                        $where[] = array(' d.' . $key . ' = :' . $key . ' ', $key, $value);
                    } elseif (in_array($key, array('nom', 'prenom', 'email', 'adresse1', 'adresse2', 'adresse3', 'adresse3'))) {
                        $where[] = array(' d.' . $key . ' like :' . $key . ' ', $key, '%' . $value . '%');
                    } elseif (in_array($key, array('zipcode'))) {
                        $where[] = array(' d.' . $key . ' like :' . $key . ' ', $key, $value . '%');
                    } elseif (in_array($key, array('dateNaissance'))) {
                        $where[] = array(' d.' . $key . ' = :' . $key . ' ', $key, $this->dqlDate($value));
                    } elseif (in_array($key, array('categories'))) {
                        $where[] = array(' :' . $key . ' MEMBER OF d.' . $key . ' ', $key, $value);
                    }
                    $memo_search[$key] = $value;
                }
            }
            //@TODO ,'receptionMode' traitement for search
            $pnd = $request->get('pnd');
            if (!empty($pnd)) {
                $motif = null;
                $cpt = null;
                switch ($pnd) {
                    case 'npai1':
                        $motif = 'npai';
                        $cpt = 1;
                        break;
                    case 'npai2':
                        $motif = 'npai';
                        $cpt = 2;
                        break;
                    case 'npai3':
                        $motif = 'npai';
                        $cpt = 3;
                        break;
                    case 'refuse1':
                        $motif = 'refuse';
                        $cpt = 1;
                        break;
                    case 'refuse2':
                        $motif = 'refuse';
                        $cpt = 2;
                        break;
                    case 'refuse3':
                        $motif = 'refuse';
                        $cpt = 3;
                        break;
                    case 'decede':
                        $motif = 'decede';
                        $cpt = 1;
                        break;
                }
                // Use of Mysql
                if ($motif != null && $cpt != null) {
                    $rsm = new ResultSetMapping();
                    $year = date("Y");
                    $rsm->addScalarResult('id_donateur', 'id_donateur');
                    $rsm->addScalarResult('comptage', 'comptage');

                    // Nombre  de donateurs actifs 0/12 mois.

                    $dopnds = "SELECT COUNT(p.id) as comptage, p.donateur_id as id_donateur
                    FROM pnd as p
                    INNER JOIN motif_pnd mp ON mp.id = p.motif_id
                    WHERE mp.code ='" . $motif . "'
                    GROUP BY p.donateur_id
                    HAVING comptage = " . $cpt . "
                    ";
                    $query = $dem->createNativeQuery($dopnds, $rsm);
                    $result_ids = $query->getResult();
                    foreach ($result_ids as $ri) {
                        $ids_do_pnd[] = $ri['id_donateur'];
                    }
                }
            }
            if (count($where) > 0 || count($ids_do_pnd) >= 1) {
                $donateurs = $donateurRep->createQueryBuilder('d');
                foreach ($where as $key => $value) {
                    if ($key == 0) {
                        $donateurs = $donateurs->where($value[0])
                                ->setParameter($value[1], $value[2]);
                    } else {
                        $donateurs = $donateurs->andwhere($value[0])
                                ->setParameter($value[1], $value[2]);
                    }
                }
                if (count($ids_do_pnd) >= 1) {

                    $donateurs = $donateurs->where($donateurs->expr()->in('d.id', implode(',', $ids_do_pnd)));
                }


                $donateursTest = clone $donateurs;
                $preResult = $donateursTest
                        ->select('COUNT(d.id) as cpt')
                        ->getQuery()
                        ->getOneOrNullResult();


                if ($preResult['cpt'] > 10000) {
                    $this->get('session')->getFlashBag()->add('warning', $preResult['cpt'] . ' Résultats : Trop de résultats, veuillez SVP affiner vos recherches !');
                    $result = null;
                    $tellmeall = false;
                } else {
                    $total_donateur = $preResult['cpt'];
                    $donateur_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
                    $last_page = ceil($total_donateur / $donateur_per_page);
                    $previous_page = $page > 1 ? $page - 1 : 1;
                    $next_page = $page < $last_page ? $page + 1 : $last_page;
                    $result = $donateurs
                                    ->orderBy('d.id ', 'DESC')
                                    ->setFirstResult(($page * $donateur_per_page) - $donateur_per_page)
                                    ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
                }
            }


            if (!$result) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
            }
            unset($pre_params['page']);
            $url = array();
            foreach ($pre_params as $key => $ele) {
                $url[] = "fulldon_donateurbundle_donateursearchtype[$key]=$ele";
            }
            $params = implode('&', $url);
            // Adding columns to params
            $params .= '&columns=' . urlencode($columns);
        }
        $mycol = explode('&', $columns);
        foreach ($mycol as $col) {
            $coldisplay[] = substr($col, 7, strlen($col));
        }

        foreach ($coldisplay as $tab) {
            $index = array_search($tab, $restCols);
            unset($restCols[$index]);
        }

        $cumul = 0.00;
        $date = date("Y-m-d H:i:s");

        return $this->render('FulldonIntersaBundle:Donateurs:index.html.twig', array('form' => $form->createView(),
                    'result' => $result,
                    'memo_search' => $memo_search,
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_donateur' => $total_donateur,
                    'params' => $params,
                    'favoris' => $favoris,
                    'col_display' => $coldisplay,
                    'columns' => $columns,
                    'rest_cols' => $restCols,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function getListeFoyer() {
        $em = $this->getDoctrine()->getManager();
        $foyerchoice = $em->getRepository('FulldonDonateurBundle:FoyerDonateur')->findAll();
        $list = array();
        foreach ($foyerchoice as $key => $pays) {
            $info = array();
            $info['Id'] = $pays->getId();
            $info['Email'] = $pays->getEmail();
            $list[$key] = $info;
        }
        return $list;
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function addAction() {

//        die('ici');
        $ID_DONATEUR = null;
        $cumul = null;
        $date = null;
        $dem = $this->getDoctrine()->getManager();
        $donateurRep = $dem->getRepository('FulldonDonateurBundle:Donateur');
//        $existefoyer = $dem->getRepository('FulldonDonateurBundle:FoyerDonateur')->findAll();
        $listefoyer = $this->getListeFoyer();
        $countries = Intl::getRegionBundle()->getCountryNames();


//        foreach ($countries as $key => $value) {
//
//            var_dump($value);
//            die('$countries');
//        }
//        $villes = Intl::getRegionBundle()->getCountryNames();



        $current_user = $this->get('security.context')->getToken()->getUser();
        $data = array();
        $donateur = new Donateur;

        // format all the form
        $form = $this->createForm(new IntersaDonateurType($dem), $donateur, array(
            'cascade_validation' => true));
        $errors = array();
        // On récupère la requête
        $request = $this->getRequest();
        $form->handleRequest($request);
//        var_dump($request->getMethod());
//          die('handleRequest');
        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
//           die('$request->getMethod()');
            $allow_rf = $request->get('allow_rf');
            $data['allow_rf'] = $allow_rf;
            $allow_annual_rf = $request->get('allow_annual_rf', "false");
            $data['allow_annual_rf'] = $allow_annual_rf;
            if (isset($allow_annual_rf) || !empty($allow_annual_rf) || isset($allow_rf) || !empty($allow_rf)) {
                $error['erreur'] = 'Vous avez oublié de configurer la réception des reçus fiscaux.';
            }
//            var_dump($form->bind($request));
//            die('bind');
//            $form->bind($request);
//            var_dump($request);
//            die('req');
            if ($form->isValid() && count($errors) == 0) {
//                 die('valid');
//                $params = $this->getRequest()->request->all();
//                var_dump($params);
//                die('$params');


                $user = new User();
                $donateur->setRemoved(false);
                if ($donateur->getRemoved()) {
                    $user->setIsActive(false);
                } else {
                    $user->setIsActive(true);
                }

                $i = 0;
                $login = 'donateur' . $i;

                do {
                    $i++;
                    $login = 'donateur' . $i;
                    $plain_password = base64_encode('p@s' . $i);
                } while (!$this->isNewUser($login));


                $data = $form->getData();
                $extra = $form->get('extra')->getData();

                if ($extra) {
                    $email_foyer = (String) $extra;
                } else {
                    $email_foyer = '';
                }

                $email = $email_foyer; //(String) $extra;

                $existemail = $dem->getRepository('FulldonDonateurBundle:FoyerDonateur')->getFoyerByEmail($email);


// $existemail = $dem->getRepository('FulldonDonateurBundle:FoyerDonateur')->
                //findBy(array('email' => $donateur->getEmail()));
                $_array_existe_mail = array();
                $_array_existe_mail = $existemail;
                //create foyer
                $foyer = Null;
                if (count($_array_existe_mail) == 0 && $email != '') {
                    $foyer = new FoyerDonateur();
                    $foyer->setEmail($email);
                    $dem->persist($foyer);
                    $dem->flush();
                }


                $getcurrentfoyer = $dem->getRepository('FulldonDonateurBundle:FoyerDonateur')->
                        findBy(array('email' => $email));


                $idfoyer = 0;
                foreach ($getcurrentfoyer as $key => $value) {
//                    die('enter');
                    $idfoyer = $value->getId();
                }
//                var_dump($idfoyer);
//                die('$extra');
                if ($idfoyer != 0) {
                    $Foyer = $dem->getRepository('FulldonDonateurBundle:FoyerDonateur')->find($idfoyer);
                    $donateur->setFoyer($Foyer);
                }
                $donateur->setEmail($email);
                $user->setUsername($login);
//                
                $user->setPassword($plain_password);
                $donateur->setUser($user);
                // On enregistre notre objet $article dans la base de données
                $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                $donateur->getUser()->setIsActive(true);
                $repRole = $dem->getRepository('FulldonSecurityBundle:Role');
                $role_donateur = $repRole->findOneBy(array('role' => 'ROLE_DONATEUR'));
                $donateur->getUser()->addRole($role_donateur);
                // Set encrypted password
                $encoder = $this->container->get('security.encoder_factory')
                        ->getEncoder($donateur->getUser());
                $password = $encoder->encodePassword($donateur->getUser()->getPassword(), $donateur->getUser()->getSalt());
                $donateur->getUser()->setPassword($password);
                $refId = $this->get('fulldon.intersa.global')->getUniqueRefDonateur();
                $donateur->setRefDonateur($refId);
//                var_dump($refId);
//                var_dump($current_id_foyer);
//                die('ici3');
                //Gestion des RFs
                if ($allow_rf == "true") {
                    $donateur->setAllowRf(true);
                } else {
                    $donateur->setAllowRf(false);
                }
                //Fin de la gestion des RFs
                //Gestion des doublons
                $percent = 0;
                $pid = 0;
                $pdonateur = $donateurRep->findOneBy(array('nom' => $donateur->getNom(), 'prenom' => $donateur->getPrenom(), 'removed' => false));

                if ($pdonateur) {

                    $percent += 60;
                    $pid = $pdonateur->getId();
                    $pdonateur2 = $donateurRep->findOneBy(array('adresse3' => $donateur->getAdresse3(), 'id' => $pid));
                    if ($pdonateur2) {
                        $percent += 30;
                    }
                    //Création d'un doublon
                    $doublon = new Doublon();
                    $doublon->setDone(false);
                    $doublon->setDonateur1($pid);



                    //Sauvegarder le nouveau donateur
//                    var_dump($donateur);
//                    die;
                    $dem->persist($donateur);
                    $dem->flush();
                    $ID_DONATEUR = $donateur->getId();


//                    die('persist');
//                    $foyer->addDonateur($donateur);
//                    $dem->persist($foyer);
//                    $dem->flush();
                    //required to  retreive the donateur ID

                    $doublon->setDonateur2($donateur->getId());
                    $doublon->setPourcentage($percent);
                    $dem->persist($doublon);
                    $dem->flush();
                    $current_user = $this->get('security.context')->getToken()->getUser();
                    $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_DOUBLON_CREATED_);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(StatVar::CREATE, $event);
                    $this->get('session')->getFlashBag()->add('warning', 'Un doublon a été detecté il sera géreable dans la gestion des doublons ! ');
                } else {

                    $dem->persist($donateur);
                    $dem->flush();
                }

                //Log
                $msg = $this->get('log.helper')->getAddMsgLog($donateur, 'DONATEUR');
                $typeLog = $dem->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DONATEUR_);
                $role = $this->get('log.helper')->getRole($current_user);
                $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, null);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);


                //Génération du de l'original.
                $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->renderView(
                                'FulldonIntersaBundle:Donateurs:pdf/identifiants.html.twig', array(
                            'login' => $login,
                            'password' => $plain_password,
                            'donateur' => $donateur,
                            'init' => $this->init,
                                )
                        ), '/' . $this->container->getParameter('folder_app') . '/users/donateur_fiche_' . $donateur->getId() . '.pdf'
                );
                // Send the email
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
                $email = $donateur->getEmail();
                if ($is_email && $email and ! empty($email)) {
                    //Envoi Email   
                    $html = $this->renderView('FulldonIntersaBundle:Email:send_identifiants.html.twig', array(
                        'login' => $donateur->getUser()->getUsername(),
                        'password' => $plain_password,
                        'donateur' => $donateur,
                        'init' => $this->init,
                    ));
                    //$this->get('fulldon.intersa.email_servies')->sendNewAccount($email,$html);
                }
                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Le donateur est crée avec succès ');

                if ($ID_DONATEUR) {
                    $datax = $this->getCumulDernierDonBy($ID_DONATEUR);
                    $date = $datax[0]['dernier_don'];
                    $cumul = $datax[0]['cumul'];
                } else {
                    $cumul = 0.00;
                    $date = date("Y-m-d H:i:s");
                }




                return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $donateur->getId(),
                                    'cumul' => $cumul,
                                    'date' => $date)));


                // On redirige vers la page de visualisation de l'article nouvellement créé
                //return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $donateur->getId())));
            } else {
                // dans le cas de la non validité des informations on va stoquer les informations sur la variable form.error
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }
        // À ce stade :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

        return $this->render('FulldonIntersaBundle:Donateurs:add.html.twig', array('form' => $form->createView(), 'data' => $data,
                    'countries' => $countries, 'foyerschoice' => $listefoyer));
    }

//    public function getAllFoyerEmailAction() {
//        $listefoyer = $this->getListeFoyer();
//        $response = new JsonResponse(array("myarray" => $listefoyer));
//        return $response;
//    }

    public function getAllEmailDonateurAction(Request $request) {


        $em = $this->getDoctrine()->getManager();
        $data = $request->query->all();
        $email = $data['param'];
        $findDonateurByEmail = $em->getRepository('FulldonDonateurBundle:Donateur')->findBy(array('email' => $email));
        $_array_count_entity = array();
        $_array_count_entity = $findDonateurByEmail;
        $count = count($_array_count_entity);
        $response = new Response($count);
        return $response;
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function editAction($id) {

        $data2 = $this->getCumulDernierDonBy($id);
        if ($data2) {
            $date = $data2[0]['dernier_don'];
            $cumul = $data2[0]['cumul'];
        } else {
            $cumul = 0.00;
            $date = date("Y-m-d H:i:s");
        }
        $data = array();
        $current_user = $this->get('security.context')->getToken()->getUser();
        $dem = $this->getDoctrine()->getManager();
        $donateurRep = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->find($id);



        $dem = $this->getDoctrine()->getManager();
        // format all the form
        $form = $this->createForm(new IntersaDonateurType($dem), $donateur, array(
            'cascade_validation' => true));
        $errors = array();
        // On récupère la requête
        $request = $this->getRequest();

        if ($donateur->getAllowRf()) {
            $val = "true";
        } else {
            $val = "false";
        }
        $data['allow_rf'] = $val;
        //$data['curville'] = $donateur->getVille();
        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $allow_rf = $request->get('allow_rf');
            $data['allow_rf'] = $allow_rf;

            $form->bind($request);
            if ($form->isValid() && count($errors) == 0) {
                if ($donateur->getRemoved()) {

                    $donateur->getUser()->setIsActive(false);
                } else {
                    $donateur->getUser()->setIsActive(true);
                }
                //Gestion des RFs
                if ($allow_rf == "true") {
                    $donateur->setAllowRf(true);
                } else {
                    $donateur->setAllowRf(false);
                }

                $uow = $dem->getUnitOfWork();
                $uow->computeChangeSets();
                $attr = array();
                $changeset = $uow->getEntityChangeSet($donateur);
                if (array_key_exists('adresse1', $changeset) || array_key_exists('adresse2', $changeset) || array_key_exists('adresse3', $changeset) || array_key_exists('adresse4', $changeset) || array_key_exists('adresse5', $changeset)
                ) {
                    //Création d'un courrier en attente.
                    $courrierAttente = new CourrierAttente();
                    $courrierAttente->setDonateur($donateur);
                    $courrierAttente->setTypeTraitements($dem->getRepository('FulldonIntersaBundle:TypeTraitementCourrier')->findOneBy(array('code' => Vars\DonVars::_COURRIER_MAJ_ADRESSE_)));
                    $courrierAttente->setDone(false);
                    $dem->persist($courrierAttente);
                    $dem->flush();
                    // Fin des traitements des courriers en attente
                }

                //Log
                $current_user = $this->get('security.context')->getToken()->getUser();
                $msg = $this->get('log.helper')->getModMsgLog($donateur, 'DONATEUR');
                $typeLog = $dem->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_DONATEUR_MOD_);
                $role = $this->get('log.helper')->getRole($current_user);
                $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, null);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);

                $dem->persist($donateur);
                $dem->flush();
//                die('ici');
                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Le donateur est modifié avec succès ');


                // On redirige vers la page de visualisation de l'article nouvellement créé
                return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $donateur->getId(),
                                    'cumul' => $cumul,
                                    'date' => $date)));
            } else {
                // dans le cas de la non validité des informations on va stoquer les informations sur la variable form.error
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }



        // À ce stade :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau
//        die('editAction2');
        return $this->render('FulldonIntersaBundle:Donateurs:edit.html.twig', array(
                    'form' => $form->createView(),
                    'donateur' => $donateur,
                    'data' => $data,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function isNewUser($login) {
        // Check if it exists first
        $dem = $this->getDoctrine()->getManager();
        $repUsers = $dem->getRepository('FulldonSecurityBundle:User');
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

    public function getCumulDernierDonBy($donateur_id) {

        $query = "SELECT d.id as don_id,d.user_id,dt.id as donateur_id, SUM(d.montant) AS cumul,MAX(d.created_at) AS dernier_don,dt.removed as statut_donateur    FROM coline_en_re_full_db.don d

LEFT JOIN coline_en_re_full_db.donateur dt ON  d.user_id=dt.user_id

WHERE dt.removed=0 AND d.removed=0 

and dt.id = '" . $donateur_id . "'

group by user_id DESC ";

        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function gestionAction($id, $cumul, $date) {
//        $request = $this->getRequest();
//        var_dump($request->getMethod());
//        die('houni');

        $datax = $this->getCumulDernierDonBy($id);
        if ($datax) {
            $date = $datax[0]['dernier_don'];
            $cumul = $datax[0]['cumul'];
        } else {
            $cumul = 0.00;
            $date = date("Y-m-d H:i:s");
        }


        $dem = $this->getDoctrine()->getManager();
        $repDonateur = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $repPnd = $dem->getRepository('FulldonDonateurBundle:Pnd');
        $repMotifPnd = $dem->getRepository('FulldonDonateurBundle:MotifPnd');
        $repTypeDon = $dem->getRepository('FulldonIntersaBundle:TypeDon');
        $types = $repTypeDon->findAll();
        $donateur = $repDonateur->find($id);
        $pnd = new Pnd();
        $form = $this->createForm(new PndType(), $pnd);
        $request = $this->getRequest();
        $country = null;
        if (!is_null($donateur->getIsopays()))
            $country = Intl::getRegionBundle()->getCountryName($donateur->getIsopays());

        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
//            die('post + gestion action');
            $form->bind($request);
            if ($form->isValid()) {
                // combien de pnd
                $pnd->setRemoved(false);
                $pnds = $repPnd
                                ->createQueryBuilder('p')
                                ->select('IDENTITY (p.motif) as motif')
                                ->where('p.donateur = :donateur and p.removed = false')
                                ->setParameter('donateur', $donateur)
                                ->orderBy('p.createdAt', 'DESC')
                                ->getQuery()->getResult();
                $i = 1;
                foreach ($pnds as $key => $p) {
                    if (($i == $this->get('fulldon.custom_params')->getParam('seuil_pnd') && $p['motif'] == $pnd->getMotif()->getId()) && $key + 1 == $i || $pnd->getMotif()->getCode() == 'decede') {
                        // suppression virtuelle de toutes les lignes sur le pnd
                        $remPnds = $repPnd->findBy(array('removed' => false, 'donateur' => $donateur));
                        foreach ($remPnds as $rp) {
                            $rp->setRemoved(true);
                            $dem->persist($rp);
                            $dem->flush();
                        }
                        $donateur->setRemoved(true);
                        $dem->persist($donateur);
                        $dem->flush();
                        $pnd->setRemoved(true);
                        break;
                    } elseif ($p['motif'] == $pnd->getMotif()->getId()) {
                        $i++;
                    }
                }


                $pnd->setDonateur($donateur);
                $dem->persist($pnd);
                $dem->flush();
                //Log
                $current_user = $this->get('security.context')->getToken()->getUser();
                $msg = $this->get('fulldon.intersa.global')->getAddMsgLog($pnd, 'PND');
                $typeLog = $dem->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_PND_);
                // Log the user creation
                $event = HistoryLogEvent::constr1($current_user, $donateur, $typeLog, $msg);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);
                //Fin Log

                $this->get('session')->getFlashBag()->add('info', 'Le PND a bien été pris en compte');
            }
        }
        $pnds = $repPnd->createQueryBuilder('p')
                        ->where('p.donateur = :donateur')
                        ->setParameter('donateur', $donateur)
                        ->orderBy('p.createdAt', 'DESC')
                        ->getQuery()->getResult();

//        var_dump($cumul);
//        var_dump($date);
//        die('ici2');
        return $this->render('FulldonIntersaBundle:Donateurs:gestion.html.twig', array(
                    'donateur' => $donateur,
                    'form' => $form->createView(),
                    'pnds' => $pnds, 'types' => $types,
                    'country' => $country,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function gestionRefDonateurAction($id) {
        $dem = $this->getDoctrine()->getManager();
        $repDonateur = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $repPnd = $dem->getRepository('FulldonDonateurBundle:Pnd');
        $repMotifPnd = $dem->getRepository('FulldonDonateurBundle:MotifPnd');
        $repTypeDon = $dem->getRepository('FulldonIntersaBundle:TypeDon');
        $types = $repTypeDon->findAll();
        $donateur = $repDonateur->findOneBy(array('refDonateur' => $id));
        $pnd = new Pnd();
        $form = $this->createForm(new PndType(), $pnd);
        $request = $this->getRequest();

        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire

            $form->bind($request);
            if ($form->isValid()) {
                // combien de pnd
                $pnd->setRemoved(false);
                $pnds = $repPnd
                                ->createQueryBuilder('p')
                                ->select('IDENTITY (p.motif) as motif')
                                ->where('p.donateur = :donateur and p.removed = false')
                                ->setParameter('donateur', $donateur)
                                ->orderBy('p.createdAt', 'DESC')
                                ->getQuery()->getResult();
                $i = 1;
                foreach ($pnds as $key => $p) {
                    if (($i == $this->get('fulldon.custom_params')->getParam('seuil_pnd') && $p['motif'] == $pnd->getMotif()->getId()) && $key + 1 == $i || $pnd->getMotif()->getCode() == 'decede') {
                        // suppression virtuelle de toutes les lignes sur le pnd
                        $remPnds = $repPnd->findBy(array('removed' => false, 'donateur' => $donateur));
                        foreach ($remPnds as $rp) {
                            $rp->setRemoved(true);
                            $dem->persist($rp);
                            $dem->flush();
                        }
                        $donateur->setRemoved(true);
                        $dem->persist($donateur);
                        $dem->flush();
                        $pnd->setRemoved(true);
                        break;
                    } elseif ($p['motif'] == $pnd->getMotif()->getId()) {
                        $i++;
                    }
                }


                $pnd->setDonateur($donateur);
                $dem->persist($pnd);
                $dem->flush();
                //Log
                $current_user = $this->get('security.context')->getToken()->getUser();
                $msg = $this->get('fulldon.intersa.global')->getAddMsgLog($pnd, 'PND');
                $typeLog = $dem->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_PND_);
                // Log the user creation
                $event = HistoryLogEvent::constr1($current_user, $donateur, $typeLog, $msg);
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(LogVar::CREATE, $event);
                //Fin Log

                $this->get('session')->getFlashBag()->add('info', 'Le retour du courrier est signaler avec succès.');
            }
        }
        $pnds = $repPnd->createQueryBuilder('p')
                        ->where('p.donateur = :donateur')
                        ->setParameter('donateur', $donateur)
                        ->orderBy('p.createdAt', 'DESC')
                        ->getQuery()->getResult();

        return $this->render('FulldonIntersaBundle:Donateurs:gestion.html.twig', array('donateur' => $donateur, 'form' => $form->createView(), 'pnds' => $pnds, 'types' => $types));
    }

    public function historyAction($id, $page) {

        $datax = $this->getCumulDernierDonBy($id);
        $date = $datax[0]['dernier_don'];
        $cumul = $datax[0]['cumul'];
        $dem = $this->getDoctrine()->getManager();
        $repDon = $dem->getRepository('FulldonDonateurBundle:Don');
        $donateurRep = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->find($id);
        $dons = $repDon->findBy(array('user' => $donateur->getUser(), 'ispa' => false));
        $total_dons = count($dons);
        $dons_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_dons / $dons_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $dons = $this->getDoctrine()
                        ->getRepository('FulldonDonateurBundle:Don')
                        ->createQueryBuilder('p')
                        ->where('p.user = :id')
                        ->andwhere('p.ispa = false')
                        ->setParameter('id', $donateur->getUser()->getId())
                        ->orderBy('p.createdAt', 'DESC')
                        ->setFirstResult(($page * $dons_per_page) - $dons_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        return $this->render('FulldonIntersaBundle:Donateurs:history.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_dons' => $total_dons,
                    'dons' => $dons,
                    'donateur' => $donateur,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function historySollicitationsAction($id, $page) {
        $datax = $this->getCumulDernierDonBy($id);
        $date = $datax[0]['dernier_don'];
        $cumul = $datax[0]['cumul'];
        $dem = $this->getDoctrine()->getManager();
        $repProspection = $dem->getRepository('FulldonDonateurBundle:Prospection');
        $donateur = $dem->getRepository('FulldonDonateurBundle:Donateur')->find($id);
        $prospections = $repProspection->findBy(array('donateur' => $donateur));
        $total_pros = count($prospections);
        $pros_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_pros / $pros_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $prospections = $this->getDoctrine()
                        ->getRepository('FulldonDonateurBundle:Prospection')
                        ->createQueryBuilder('p')
                        ->where('p.donateur = :id')
                        ->setParameter('id', $donateur)
                        ->orderBy('p.createdAt', 'DESC')
                        ->setFirstResult(($page * $pros_per_page) - $pros_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        return $this->render('FulldonIntersaBundle:Donateurs:Prospection/history.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_pros' => $total_pros,
                    'prospections' => $prospections,
                    'donateur' => $donateur,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function historyCourrierAction($id, $page) {
        $datax = $this->getCumulDernierDonBy($id);
        $date = $datax[0]['dernier_don'];
        $cumul = $datax[0]['cumul'];
        $dem = $this->getDoctrine()->getManager();
        $repCourrier = $dem->getRepository('FulldonIntersaBundle:CourrierTraitement');
        $donateur = $dem->getRepository('FulldonDonateurBundle:Donateur')->find($id);
        $mails = $repCourrier->findBy(array('donateur' => $donateur));
        $total_mails = count($mails);
        $pros_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_mails / $pros_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $dons = $this->getDoctrine()
                        ->getRepository('FulldonIntersaBundle:CourrierTraitement')
                        ->createQueryBuilder('c')
                        ->where('c.donateur = :id')
                        ->setParameter('id', $donateur)
                        ->orderBy('c.createdAt', 'DESC')
                        ->setFirstResult(($page * $pros_per_page) - $pros_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        return $this->render('FulldonIntersaBundle:Donateurs:courrier/history.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_mails' => $total_mails,
                    'mails' => $mails,
                    'donateur' => $donateur,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function historyCourrierViewAction($id) {
        $datax = $this->getCumulDernierDonBy($id);
        $date = $datax[0]['dernier_don'];
        $cumul = $datax[0]['cumul'];
        $dem = $this->getDoctrine()->getManager();
        $repCourrier = $dem->getRepository('FulldonIntersaBundle:CourrierDoc');
        $traitement = $dem->getRepository('FulldonIntersaBundle:CourrierTraitement')->find($id);
        $courriers = $repCourrier->findBy(array('courrierTraitement' => $traitement));
        return $this->render('FulldonIntersaBundle:Donateurs:courrier/view.html.twig', array(
                    'courrier' => $courriers,
                    'traitement' => $traitement,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function historyActionAction($id) {
        $datax = $this->getCumulDernierDonBy($id);
        $date = $datax[0]['dernier_don'];
        $cumul = $datax[0]['cumul'];
        $dem = $this->getDoctrine()->getManager();
        $donateurRep = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->find($id);

        /* résultat  à afficher */
        $actions = $this->getDoctrine()
                        ->getRepository('FulldonIntersaBundle:Log')
                        ->createQueryBuilder('p')
                        ->where('p.donateur = :donateur')
                        ->setParameter('donateur', $donateur)
                        ->orderBy('p.createdAt', 'DESC')
                        ->getQuery()->getResult();

        return $this->render('FulldonIntersaBundle:Donateurs:history_action.html.twig', array(
                    'actions' => $actions,
                    'donateur' => $donateur,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function historyActionAjaxAction($id) {
        $dem = $this->getDoctrine()->getManager();
        $donateur = null;
        $actions = null;
        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $donateurRep = $dem->getRepository('FulldonDonateurBundle:Donateur');
            $donateur = $donateurRep->find($id);
            $infos_rf = $request->get('infos_rf');
            $infos_pnd = $request->get('infos_pnd');
            $infos_don = $request->get('infos_don');
            $infos_donateur = $request->get('infos_donateur');
            $maj_donateur = $request->get('maj_donateur');
            $maj_don = $request->get('maj_don');
            $infos_duplicata = $request->get('infos_duplicata');
            $infos_email = $request->get('infos_email');

            $typelogs = array();
            /* résultat  à afficher */
            $actions = $this->getDoctrine()
                    ->getRepository('FulldonIntersaBundle:Log')
                    ->createQueryBuilder('p')
                    ->innerJoin('FulldonIntersaBundle:TypeLog', 't')
                    ->where('p.donateur = :donateur')
                    ->setParameter('donateur', $donateur);
            if ($infos_rf) {
                $typelogs[] = LogVar::_LOG_TYPE_INFO_RF_;
            }
            if ($infos_pnd) {

                $typelogs[] = LogVar::_LOG_TYPE_INFO_PND_;
            }
            if ($infos_don) {
                $typelogs[] = LogVar::_LOG_TYPE_INFO_DON_;
            }
            if ($infos_donateur) {
                $typelogs[] = LogVar::_LOG_TYPE_INFO_DONATEUR_;
            }
            if ($maj_donateur) {
                $typelogs[] = LogVar::_DONATEUR_MOD_;
            }
            if ($maj_don) {
                $typelogs[] = LogVar::_DON_MOD_;
            }
            if ($infos_duplicata) {
                $typelogs[] = LogVar::_DUPLICATA_ADD_;
            }
            if ($infos_email) {
                $typelogs[] = LogVar::_EMAIL_SENT_;
            }
            $actions = $actions
                    ->andwhere('p.typeLog IN (:typelogs)')
                    ->setParameter('typelogs', $typelogs);


            $actions = $actions
                            ->orderBy('p.createdAt', 'DESC')
                            ->getQuery()->getResult();
        }


        return $this->render('FulldonIntersaBundle:Donateurs:ajax/history_action.html.twig', array('actions' => $actions, 'donateur' => $donateur));
    }

    public function historyPaAction($id, $page) {
        $datax = $this->getCumulDernierDonBy($id);
        $date = $datax[0]['dernier_don'];
        $cumul = $datax[0]['cumul'];
        $dem = $this->getDoctrine()->getManager();
        $repDon = $dem->getRepository('FulldonDonateurBundle:Don');
        $donateurRep = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->find($id);
        $dons = $repDon->findBy(array('user' => $donateur->getUser(), 'ispa' => true));
        $total_dons = count($dons);
        $dons_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_dons / $dons_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $dons = $this->getDoctrine()
                        ->getRepository('FulldonDonateurBundle:Don')
                        ->createQueryBuilder('p')
                        ->where('p.user = :id')
                        ->andwhere('p.ispa = true')
                        ->setParameter('id', $donateur->getUser()->getId())
                        ->orderBy('p.createdAt', 'DESC')
                        ->setFirstResult(($page * $dons_per_page) - $dons_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();

        return $this->render('FulldonIntersaBundle:Donateurs:history_pa.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_dons' => $total_dons,
                    'dons' => $dons,
                    'donateur' => $donateur,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    function dqlDate($date, $format = 'd/m/Y') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d->format('Y-m-d');
    }

    public function disableDonateurAction($id) {
        //id prelevement
        $datax = $this->getCumulDernierDonBy($id);
        $date = $datax[0]['dernier_don'];
        $cumul = $datax[0]['cumul'];
        $request = $this->getRequest();
        $motif = $request->get('motif');
        $dem = $this->getDoctrine()->getManager();
        if ($request->getMethod() == 'POST' && isset($motif) && !empty($motif)) {
            $repDonateur = $dem->getRepository('FulldonDonateurBundle:Donateur');
            $donateur = $repDonateur->find($id);
            $donateur->setRemoved(true);
            $motif = $dem->getRepository('FulldonDonateurBundle:MotifDisableDonateur')->find($motif);
            $donateur->setMotif($motif);
            $donateur->setRemovedAt(new \DateTime());

            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg = $this->get('log.helper')->getModMsgLog($donateur, 'DONATEUR');
            $typeLog = $dem->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_DONATEUR_MOD_);
            $role = $this->get('log.helper')->getRole($current_user);
            $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, null);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);

            //Fin Log
            $dem->persist($donateur);
            $dem->flush();
            //redirect
            $this->get('session')->getFlashBag()->add('info', 'Le donateur #' . $id . ' est maintenant désactivé');
        } else {
            $this->get('session')->getFlashBag()->add('erreur', 'Une erreur est survenue lors de la désactivation du donateur ');
        }


        return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $id,
                            'cumul' => $cumul,
                            'date' => $date)));
        //return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $id)));
    }

    public function disableAjaxDonateurAction($id) {
        $dem = $this->getDoctrine()->getManager();
        $repDonateur = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $repDonateur->find($id);
        $repDons = $dem->getRepository('FulldonDonateurBundle:Don');
        $dons = $repDons->findBy(array('user' => $donateur->getUser()));

        if (count($dons) >= 1) {
            $this->get('session')->getFlashBag()->add('warning', 'Attention ce donateur a fait ' . count($dons) . ' dons.');
        }
        $repMotif = $dem->getRepository('FulldonDonateurBundle:MotifDisableDonateur');
        $motifs = $repMotif->findAll();
        return $this->render('FulldonIntersaBundle:Donateurs/ajax:disable_donateur.html.twig', array('donateur' => $donateur, 'donateur_id' => $id, 'motifs' => $motifs));
    }

    public function enableDonateurAction($id) {
        //id prelevement
        $datax = $this->getCumulDernierDonBy($id);
        $date = $datax[0]['dernier_don'];
        $cumul = $datax[0]['cumul'];
        $request = $this->getRequest();
        $dem = $this->getDoctrine()->getManager();
        if ($request->getMethod() == 'POST') {
            $repDonateur = $dem->getRepository('FulldonDonateurBundle:Donateur');
            $donateur = $repDonateur->find($id);
            $donateur->setMotif(null);
            $donateur->setRemovedAt(null);
            $donateur->setRemoved(false);
            //Log
            $current_user = $this->get('security.context')->getToken()->getUser();
            $msg = $this->get('log.helper')->getModMsgLog($donateur, 'DONATEUR');
            $typeLog = $dem->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_DONATEUR_MOD_);
            $role = $this->get('log.helper')->getRole($current_user);
            $event = HistoryLogEvent::mainConstr($current_user, $donateur, $typeLog, $msg, $role, null);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(LogVar::CREATE, $event);
            //Fin Log
            $dem->persist($donateur);
            $dem->flush();
            //redirect
            $this->get('session')->getFlashBag()->add('info', 'Le donateur #' . $id . ' est maintenant activé');
        } else {
            $this->get('session')->getFlashBag()->add('erreur', 'Une erreur est survenue lors de la réactivation du donateur ');
        }



        return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $id,
                            'cumul' => $cumul,
                            'date' => $date)));


        //return $this->redirect($this->generateUrl('intersa_donateur_gestion', array('id' => $id)));
    }

    public function enableAjaxDonateurAction($id) {
        $dem = $this->getDoctrine()->getManager();
        $repDonateur = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $repDonateur->find($id);
        return $this->render('FulldonIntersaBundle:Donateurs/ajax:enable_donateur.html.twig', array('donateur' => $donateur, 'donateur_id' => $id));
    }

    public function uploadCsvFileAction() {
        $dem = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $repProspection = $dem->getRepository('FulldonIntersaBundle:UploadProspection');
        $prospections = $repProspection->findAll();
        return $this->render('FulldonIntersaBundle:Donateurs:Prospection/index.html.twig', array(
                    'prospections' => $prospections,
        ));
    }

    public function statsAction($id) {

        $user = $this->get('security.context')->getToken()->getUser();
        $dem = $this->getDoctrine()->getManager();
        $repDon = $dem->getRepository('FulldonDonateurBundle:Don');
        $donateurRep = $dem->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->find($id);
        $dons_pa = $repDon->findBy(array('user' => $donateur->getUser(), 'ispa' => true));
        $total_dons_pa = count($dons_pa);
        $dons_po = $repDon->findBy(array('user' => $donateur->getUser(), 'ispa' => false));
        $total_dons_po = count($dons_po);

        $result = $total_dons_po . '|' . $total_dons_pa;

        return $this->render('FulldonIntersaBundle:Donateurs:stats.html.twig', array('result' => $result));
    }

}
