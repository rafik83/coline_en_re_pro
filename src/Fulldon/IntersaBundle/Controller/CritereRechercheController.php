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
use Fulldon\IntersaBundle\Entity\SauvgardeDonateur;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class CritereRechercheController extends Controller {

    //put your code here
    public function indexAction() {


        $em = $this->getDoctrine()->getManager();
        $repSauvgardeDonateur = $em->getRepository('FulldonIntersaBundle:SauvgardeDonateur');
        $getAllSauvgardeDonateur = $repSauvgardeDonateur->findAll();


        $current_page = 1;
        $maxPerPage = 5;
        $adapter = new ArrayAdapter($repSauvgardeDonateur->findAll());
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
//        $total_pages = $pagerfanta->getNbResults();
//        $last_page = ceil($total_pages / $maxPerPage);
//
//        $maxPerPage = $pagerfanta->getMaxPerPage();
        $pagerfanta->setCurrentPage($current_page); // 1 by default
//        $current_page = $pagerfanta->getCurrentPage();
        $SauvgardeDonateur = $pagerfanta->getCurrentPageResults();

        $data = null;
        $data2 = null;
        $colDispaly = array('numdonateur' => '#REF', 'nom' => 'Nom', 'prenom' => 'Prénom', 'statut' => 'Statut',
            'nomentreprise' => 'Nom d\'entreprise', 'email' => 'Email', 'birthday' => 'Date de naissance',
            'telmobile' => 'Téléphone mobile', 'telfixe' => 'Téléphone fixe', 'cat' => 'Catégories',
            'adresse' => 'Adresse', 'ville' => 'Ville', 'pays' => 'Pays', 'zipcode' => 'Code postal', 'createdat' => 'Date Creation');

        return $this->render('FulldonIntersaBundle:Critere:donateur/index.html.twig', array(
                    'sgdonateurs' => $SauvgardeDonateur,
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
        $sauvgarde_donateur = $db->getRepository('FulldonIntersaBundle:SauvgardeDonateur');
        $newSauvgarde = new SauvgardeDonateur();
        $request = Request::createFromGlobals();
//        var_dump($request);


        $route = null;
        if ($section == 'donateurs') {
            $route = 'elastic_donateur';
        }
//        elseif ($section == 'dons') {
//            $route = 'elastic_don';
//        }
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
                $existesauvgarde = $sauvgarde_donateur->findOneBy(array('title' => $title));
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
        $repSauvgardeDonateur = $em->getRepository('FulldonIntersaBundle:SauvgardeDonateur');
        $getAllSauvgardeDonateur = $repSauvgardeDonateur->findAll();
        $data = $request->query->all();
        $page = $data["param"];

        $current_page = $page;
        $maxPerPage = 5;
        $adapter = new ArrayAdapter($repSauvgardeDonateur->findAll());
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
        $pagerfanta->setCurrentPage($current_page); // 1 by default
        $SauvgardeDonateur = $pagerfanta->getCurrentPageResults();
//        $total_pages = $pagerfanta->getNbResults();
//        $last_page = ceil($total_pages / $maxPerPage);
//        $current_page = $pagerfanta->getCurrentPage();
//        $maxPerPage = $pagerfanta->getMaxPerPage();

        $view = $this->renderView('FulldonIntersaBundle:Critere:donateur/ligne_index_critere.html.twig', array(
            'sgdonateurs' => $SauvgardeDonateur,
            'pager' => $pagerfanta,
//            'last_page' => $last_page,
//            'current_page' => $current_page,
        ));
        $response = new Response($view);
        return $response;
    }

    public function paginateLigneDonateurAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $repSauvgardeDonateur = $em->getRepository('FulldonIntersaBundle:SauvgardeDonateur');
        $data = $request->query->all();
        $id_donateur = $data["param2"];
        $param_page = $data["param"];
//        $response = new Response($id_sauvgarde_donateur);
//        return $response;
        $entity = $repSauvgardeDonateur->find($id_donateur);
        if ($entity) {
//            $test = "fulldon_donateurbundle_donateursearchtype[refDonateur]=11762&fulldon_donateurbundle_donateursearchtype[civilite]=&fulldon_donateurbundle_donateursearchtype[nom]=&fulldon_donateurbundle_donateursearchtype[prenom]=&fulldon_donateurbundle_donateursearchtype[dateNaissance]=&fulldon_donateurbundle_donateursearchtype[nomEntreprise]=&fulldon_donateurbundle_donateursearchtype[email]=&fulldon_donateurbundle_donateursearchtype[isopays]=FR&fulldon_donateurbundle_donateursearchtype[zipcode]=75005&fulldon_donateurbundle_donateursearchtype[isoville]=&fulldon_donateurbundle_donateursearchtype[adresse3]=&fulldon_donateurbundle_donateursearchtype[adresse1]=&fulldon_donateurbundle_donateursearchtype[adresse2]=&fulldon_donateurbundle_donateursearchtype[adresse4]=&fulldon_donateurbundle_donateursearchtype[_token]=8sqALvc9j40DeANYNz675Cp5zT4293STpH3erK3yT7c&columns=list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Demail%26list%5B%5D%3Dzipcode%26list%5B%5D%3Dville&sortelement=&sortdirection=&withemail=true&withtel=&withcat=&statut_donateur=on";
            $url_dona = $entity->getUrl();
            $request = Request::create($this->container->get('router')->generate('intersa_critere_recherche_donateur') . '?' . $url_dona);
            $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
//            $JsonResponse = new JsonResponse(array("tab"=>$resultform));
//           return $JsonResponse ;

            $_array_filter = array();

            if (!empty($resultform['refDonateur'])) {
                $ref_donateur = $resultform['refDonateur'];
                $_array_filter['refDonateur'] = $ref_donateur;
            }

            if (!empty($resultform['civilite'])) {
                $civilite = $resultform['civilite'];
                $_array_filter['civilite'] = $civilite;
            }
            if (!empty($resultform['nom'])) {
                $nom = $resultform['nom'];
                $_array_filter['nom'] = $nom;
            }
            if (!empty($resultform['prenom'])) {
                $prenom = $resultform['prenom'];
                $_array_filter['prenom'] = $prenom;
            }
            if (!empty($resultform['dateNaissance'])) {
                $dateNaissance = $resultform['dateNaissance'];
                $_array_filter['dateNaissance'] = $dateNaissance;
            }
            if (!empty($resultform['nomEntreprise'])) {
                $nomEntreprise = $resultform['nomEntreprise'];
                $_array_filter['nomEntreprise'] = $nomEntreprise;
            }
            if (!empty($resultform['email'])) {
                $email = $resultform['email'];
                $_array_filter['email'] = $email;
            }
            if (!empty($resultform['isopays'])) {
                $isopays = $resultform['isopays'];
                $_array_filter['isopays'] = $isopays;
            }
            if (!empty($resultform['zipcode'])) {
                $zipcode = $resultform['zipcode'];
                $_array_filter['zipcode'] = $zipcode;
            }
            if (!empty($resultform['isoville'])) {
                $isoville = $resultform['isoville'];
                $_array_filter['isoville'] = $isoville;
            }
            if (!empty($resultform['adresse3'])) {
                $adresse3 = $resultform['adresse3'];
                $_array_filter['adresse3'] = $adresse3;
            }
            if (!empty($resultform['telephoneFixe'])) {
                $_array_filter['telephoneFixe'] = $resultform['telephoneFixe'];
            }
            if (!empty($resultform['telephoneMobile'])) {
                $_array_filter['telephoneMobile'] = $resultform['telephoneMobile'];
            }

            if (!empty($resultform['categories'])) {
                $_array_filter['categories'] = $resultform['categories'];
//                $JsonResponse = new JsonResponse(array("tab" => $_array_filter['categories']));
//                return $JsonResponse;
            }

//            $query = $repSauvgardeDonateur->findAll();
            $query = $this->GetDonateurOrderBy($_array_filter);
            $query2 = $this->GetDonateurGrouBy($_array_filter);
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
            $colDispaly = array(array('numdonateur' => '#REF'), array('nom' => 'Nom'), array('prenom' => 'Prénom'), array('statut' => 'Statut'),
                array('nomentreprise' => 'Nom d\'entreprise'), array('email' => 'Email'), array('birthday' => 'Date de naissance'),
                array('telmobile' => 'Téléphone mobile'), array('telfixe' => 'Téléphone fixe'), array('cat' => 'Catégories'),
                array('adresse' => 'Adresse'), array('ville' => 'Ville'), array('pays' => 'Pays'), array('zipcode' => 'Code postal'), array('createdat' => 'Date Creation'));

            $view = $this->renderView('FulldonIntersaBundle:Critere:donateur/ligne_search_donateur.html.twig', array('data' => $data,
                'col_display' => $colDispaly,
                'pager' => $pagerfanta,
                'pager2' => $pagerfanta2,
                'data2' => $data2,
                'id_entity' => $id_donateur,
                'current_page' => $current_page2,
                'last_page' => $last_page,
            ));
            $response = new Response($view);
            return $response;
        } else {
            $this->createNotFoundException('erreur');
        }
    }

    public function GetDonateurOrderBy(array $param) {

        // $_array_filter['categories'] = $resultform['categories'];
        $array = $param;
        $bool_with_cat = false;
        $array_cat = array();
        $array_parameter = array();
        $index_parameter = 0;
        $tt = ""; //$array['categories'];
        $query_categorie = "";
        $query1 = "SELECT dnt.id as id,dnt.ref_donateur as ref_donateur,dnt.nom as nom,
                

dnt.prenom as prenom ,dnt.removed as removed ,dnt.nom_entreprise as nom_entreprise,dnt.email as email ,

dnt.datenaissance as datenaissance ,dnt.adresse3 as adresse3,dnt.iso_ville as iso_ville,dnt.iso_pays as iso_pays ,

 dnt.zipcode as zipcode ,dnt.created_at as created_at ,dnt.telephone_fixe as telephone_fixe,dnt.telephone_mobile as telephone_mobile,

cat.id as cat_id,cat.name as name 


FROM coline_en_re_full_db.donateur dnt

left join coline_en_re_full_db.donateur_categorydonateur dnt_cat

on dnt.id = dnt_cat.donateur_id

left join coline_en_re_full_db.cat_donateur cat
on dnt_cat.categorydonateur_id = cat.id

where dnt.removed = 0";
        $query_fin = "Order By dnt.ref_donateur DESC";
        if (count($array) >= 1) {
            foreach ($array as $key => $value) {

                if ($key == 'refDonateur') {
                    if ($value != NULL) {
                        $query_ref = "AND  dnt.ref_donateur ='" . $value . "'";
                        $query1 .= " " . $query_ref;
                    }
                } elseif ($key == 'civilite') {
                    if ($value != NULL) {
                        $query_civilite = "AND dnt.civilite ='" . $value . "'";
                        $query1 .= " " . $query_civilite;
                    }
                } elseif ($key == 'nom') {
                    if ($value != NULL) {
                        $query_nom = "AND dnt.nom ='" . $value . "'";
                        $query1 .= " " . $query_nom;
                    }
                } elseif ($key == 'prenom') {
                    if ($value != NULL) {
                        $query_prenom = "AND dnt.prenom ='" . $value . "'";
                        $query1 .= " " . $query_prenom;
                    }
                } elseif ($key == 'dateNaissance') {

                    if ($value != NULL) {
                        $querydate_naissance = "AND dnt.datenaissance ='" . $value . "'";
                        $query1 .= " " . $querydate_naissance;
                    }
                } elseif ($key == 'nomEntreprise') {
                    if ($value != NULL) {
                        $query_entreprise = "AND dnt.nom_entreprise ='" . $value . "'";
                        $query1 .= " " . $query_entreprise;
                    }
                } elseif ($key == 'email') {
                    if ($value != NULL) {
                        $query_email = "AND dnt.email ='" . $value . "'";
                        $query1 .= " " . $query_email;
                    }
                } elseif ($key == 'isopays') {
                    if ($value != NULL) {
                        $query_isopays = "AND dnt.iso_pays ='" . $value . "'";
                        $query1 .= " " . $query_isopays;
                    }
                } elseif ($key == 'zipcode') {
                    if ($value != NULL) {
                        $query_zipcode = "AND dnt.zipcode ='" . $value . "'";
                        $query1 .= " " . $query_zipcode;
                    }
                } elseif ($key == 'isoville') {
                    if ($value != NULL) {
                        $query_isoville = "AND dnt.iso_ville ='" . $value . "'";
                        $query1 .= " " . $query_isoville;
                    }
                } elseif ($key == 'adresse3') {

                    if ($value != NULL) {
                        $query_adresse3 = "AND dnt.adresse3 ='" . $value . "'";
                        $query1 .= " " . $query_adresse3;
                    }
                } elseif ($key == 'categories') {
                    $bool_with_cat = true;
                    foreach ($value as $key2 => $value2) {
                        $array_cat[$key2] = $value2;
                    }
                }
            }// end foreach
//            $bool_with_cat = false;
            if ($bool_with_cat) {
                if (count($array_cat) > 1) {
                    $lenght = 0;
                    $query_categorie .= "and (";
                    $query_categorie .= " ";
                    foreach ($array_cat as $key => $value) {
                        $query_parameter = "cat.id ='" . $value . "'";
                        $array_parameter[$index_parameter] = $query_parameter;
                        $index_parameter ++;
                    }
                    $lenght = count($array_parameter);

                    for ($j = 0; $j < count($array_parameter); $j++) {
                        $query_categorie .= $array_parameter[$j];
                        if ($lenght - 1 > $j) {
                            $query_categorie .= "or";
                            $query_categorie .= " ";
                        }
                    }
                }

                if (count($array_cat) == 1) {
                    $query_categorie .= "and (";
                    $query_categorie .= " ";
                    foreach ($array_cat as $key => $value) {
                        $query_parameter = "cat.id ='" . $value . "'";
                        $array_parameter[$index_parameter] = $query_parameter;
                    }//end
                    $query_categorie .= $array_parameter[$index_parameter];
                }
                $query_categorie .= " ";
                $query_categorie .= ")";
                $query_categorie .= "  ";
                $query1 .= " " . $query_categorie;
//                $tt = $query ;
            }
        }

        if (count($array) == 0) {
            $query = $query1 . " " . $query_fin;
        }



        $query = $query1 . " " . $query_fin;
        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
//        return $query;
    }

    public function GetDonateurGrouBy(array $param) {

        // $_array_filter['categories'] = $resultform['categories'];
        $array = $param;
        $bool_with_cat = false;
        $array_cat = array();
        $array_parameter = array();
        $index_parameter = 0;
        $tt = ""; //$array['categories'];
        $query_categorie = "";
        $query1 = "SELECT dnt.id as id,dnt.ref_donateur as ref_donateur,dnt.nom as nom,
                

dnt.prenom as prenom ,dnt.removed as removed ,dnt.nom_entreprise as nom_entreprise,dnt.email as email ,

dnt.datenaissance as datenaissance ,dnt.adresse3 as adresse3,dnt.iso_ville as iso_ville,dnt.iso_pays as iso_pays ,

 dnt.zipcode as zipcode ,dnt.created_at as created_at ,dnt.telephone_fixe as telephone_fixe,dnt.telephone_mobile as telephone_mobile,

cat.id as cat_id,cat.name as name 


FROM coline_en_re_full_db.donateur dnt

left join coline_en_re_full_db.donateur_categorydonateur dnt_cat

on dnt.id = dnt_cat.donateur_id

left join coline_en_re_full_db.cat_donateur cat
on dnt_cat.categorydonateur_id = cat.id

where dnt.removed = 0";
        $query_fin = "Group By dnt.ref_donateur DESC";
        if (count($array) >= 1) {
            foreach ($array as $key => $value) {

                if ($key == 'refDonateur') {
                    if ($value != NULL) {
                        $query_ref = "AND  dnt.ref_donateur ='" . $value . "'";
                        $query1 .= " " . $query_ref;
                    }
                } elseif ($key == 'civilite') {
                    if ($value != NULL) {
                        $query_civilite = "AND dnt.civilite ='" . $value . "'";
                        $query1 .= " " . $query_civilite;
                    }
                } elseif ($key == 'nom') {
                    if ($value != NULL) {
                        $query_nom = "AND dnt.nom ='" . $value . "'";
                        $query1 .= " " . $query_nom;
                    }
                } elseif ($key == 'prenom') {
                    if ($value != NULL) {
                        $query_prenom = "AND dnt.prenom ='" . $value . "'";
                        $query1 .= " " . $query_prenom;
                    }
                } elseif ($key == 'dateNaissance') {

                    if ($value != NULL) {
                        $querydate_naissance = "AND dnt.datenaissance ='" . $value . "'";
                        $query1 .= " " . $querydate_naissance;
                    }
                } elseif ($key == 'nomEntreprise') {
                    if ($value != NULL) {
                        $query_entreprise = "AND dnt.nom_entreprise ='" . $value . "'";
                        $query1 .= " " . $query_entreprise;
                    }
                } elseif ($key == 'email') {
                    if ($value != NULL) {
                        $query_email = "AND dnt.email ='" . $value . "'";
                        $query1 .= " " . $query_email;
                    }
                } elseif ($key == 'isopays') {
                    if ($value != NULL) {
                        $query_isopays = "AND dnt.iso_pays ='" . $value . "'";
                        $query1 .= " " . $query_isopays;
                    }
                } elseif ($key == 'zipcode') {
                    if ($value != NULL) {
                        $query_zipcode = "AND dnt.zipcode ='" . $value . "'";
                        $query1 .= " " . $query_zipcode;
                    }
                } elseif ($key == 'isoville') {
                    if ($value != NULL) {
                        $query_isoville = "AND dnt.iso_ville ='" . $value . "'";
                        $query1 .= " " . $query_isoville;
                    }
                } elseif ($key == 'adresse3') {

                    if ($value != NULL) {
                        $query_adresse3 = "AND dnt.adresse3 ='" . $value . "'";
                        $query1 .= " " . $query_adresse3;
                    }
                } elseif ($key == 'categories') {
                    $bool_with_cat = true;
                    foreach ($value as $key2 => $value2) {
                        $array_cat[$key2] = $value2;
                    }
                }
            }// end foreach
//            $bool_with_cat = false;
            if ($bool_with_cat) {
                if (count($array_cat) > 1) {
                    $lenght = 0;
                    $query_categorie .= "and (";
                    $query_categorie .= " ";
                    foreach ($array_cat as $key => $value) {
                        $query_parameter = "cat.id ='" . $value . "'";
                        $array_parameter[$index_parameter] = $query_parameter;
                        $index_parameter ++;
                    }
                    $lenght = count($array_parameter);

                    for ($j = 0; $j < count($array_parameter); $j++) {
                        $query_categorie .= $array_parameter[$j];
                        if ($lenght - 1 > $j) {
                            $query_categorie .= "or";
                            $query_categorie .= " ";
                        }
                    }
                }

                if (count($array_cat) == 1) {
                    $query_categorie .= "and (";
                    $query_categorie .= " ";
                    foreach ($array_cat as $key => $value) {
                        $query_parameter = "cat.id ='" . $value . "'";
                        $array_parameter[$index_parameter] = $query_parameter;
                    }//end
                    $query_categorie .= $array_parameter[$index_parameter];
                }
                $query_categorie .= " ";
                $query_categorie .= ")";
                $query_categorie .= "  ";
                $query1 .= " " . $query_categorie;
//                $tt = $query ;
            }
        }

        if (count($array) == 0) {
            $query = $query1 . " " . $query_fin;
        }



        $query = $query1 . " " . $query_fin;
        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
//        return $query;
    }

    public function superSearchAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $repSauvgardeDonateur = $em->getRepository('FulldonIntersaBundle:SauvgardeDonateur');
        $data = $request->query->all();
        $id_sauvgarde_donateur = $data["id"];
//        $response = new Response($id_sauvgarde_donateur);
//        return $response;
        $entity = $repSauvgardeDonateur->find($id_sauvgarde_donateur);
        if ($entity) {
//            $test = "fulldon_donateurbundle_donateursearchtype[refDonateur]=11762&fulldon_donateurbundle_donateursearchtype[civilite]=&fulldon_donateurbundle_donateursearchtype[nom]=&fulldon_donateurbundle_donateursearchtype[prenom]=&fulldon_donateurbundle_donateursearchtype[dateNaissance]=&fulldon_donateurbundle_donateursearchtype[nomEntreprise]=&fulldon_donateurbundle_donateursearchtype[email]=&fulldon_donateurbundle_donateursearchtype[isopays]=FR&fulldon_donateurbundle_donateursearchtype[zipcode]=75005&fulldon_donateurbundle_donateursearchtype[isoville]=&fulldon_donateurbundle_donateursearchtype[adresse3]=&fulldon_donateurbundle_donateursearchtype[adresse1]=&fulldon_donateurbundle_donateursearchtype[adresse2]=&fulldon_donateurbundle_donateursearchtype[adresse4]=&fulldon_donateurbundle_donateursearchtype[_token]=8sqALvc9j40DeANYNz675Cp5zT4293STpH3erK3yT7c&columns=list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Demail%26list%5B%5D%3Dzipcode%26list%5B%5D%3Dville&sortelement=&sortdirection=&withemail=true&withtel=&withcat=&statut_donateur=on";
            $url_dona = $entity->getUrl();
            $request = Request::create($this->container->get('router')->generate('intersa_critere_recherche_donateur') . '?' . $url_dona);
            $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
//            $JsonResponse = new JsonResponse(array("tab"=>$resultform));
//           return $JsonResponse ;

            $_array_filter = array();

            if (!empty($resultform['refDonateur'])) {
                $ref_donateur = $resultform['refDonateur'];
                $_array_filter['refDonateur'] = $ref_donateur;
            }

            if (!empty($resultform['civilite'])) {
                $civilite = $resultform['civilite'];
                $_array_filter['civilite'] = $civilite;
            }
            if (!empty($resultform['nom'])) {
                $nom = $resultform['nom'];
                $_array_filter['nom'] = $nom;
            }
            if (!empty($resultform['prenom'])) {
                $prenom = $resultform['prenom'];
                $_array_filter['prenom'] = $prenom;
            }
            if (!empty($resultform['dateNaissance'])) {
                $dateNaissance = $resultform['dateNaissance'];
                $_array_filter['dateNaissance'] = $dateNaissance;
            }
            if (!empty($resultform['nomEntreprise'])) {
                $nomEntreprise = $resultform['nomEntreprise'];
                $_array_filter['nomEntreprise'] = $nomEntreprise;
            }
            if (!empty($resultform['email'])) {
                $email = $resultform['email'];
                $_array_filter['email'] = $email;
            }
            if (!empty($resultform['isopays'])) {
                $isopays = $resultform['isopays'];
                $_array_filter['isopays'] = $isopays;
            }
            if (!empty($resultform['zipcode'])) {
                $zipcode = $resultform['zipcode'];
                $_array_filter['zipcode'] = $zipcode;
            }
            if (!empty($resultform['isoville'])) {
                $isoville = $resultform['isoville'];
                $_array_filter['isoville'] = $isoville;
            }
            if (!empty($resultform['adresse3'])) {
                $adresse3 = $resultform['adresse3'];
                $_array_filter['adresse3'] = $adresse3;
            }

//            if (!empty($resultform['telephoneFixe'])) {
//                $_array_filter['telephoneFixe'] = $resultform['telephoneFixe'];
//            }
//            if (!empty($resultform['telephoneMobile'])) {
//                $_array_filter['telephoneMobile'] = $resultform['telephoneMobile'];
//            }
            if (!empty($resultform['categories'])) {
                $_array_filter['categories'] = $resultform['categories'];
//                $JsonResponse = new JsonResponse(array("tab" => $_array_filter['categories']));
//                return $JsonResponse;
            }

//            $query = $repSauvgardeDonateur->findAll();
            $query = $this->GetDonateurOrderBy($_array_filter);
//            $JsonResponse = new JsonResponse(array("tab" => $query));
//            return $JsonResponse;


            $query2 = $this->GetDonateurGrouBy($_array_filter);
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
            $colDispaly = array(array('numdonateur' => '#REF'), array('nom' => 'Nom'), array('prenom' => 'Prénom'), array('statut' => 'Statut'),
                array('nomentreprise' => 'Nom d\'entreprise'), array('email' => 'Email'), array('birthday' => 'Date de naissance'),
                array('telmobile' => 'Téléphone mobile'), array('telfixe' => 'Téléphone fixe'), array('cat' => 'Catégories'),
                array('adresse' => 'Adresse'), array('ville' => 'Ville'), array('pays' => 'Pays'), array('zipcode' => 'Code postal'), array('createdat' => 'Date Creation'));

            $view = $this->renderView('FulldonIntersaBundle:Critere:donateur/ligne_search_donateur.html.twig', array('data' => $data,
                'col_display' => $colDispaly,
//            'criterecherche' => $criterecherche,
                'pager' => $pagerfanta,
                'pager2' => $pagerfanta2, //
                'data2' => $data2,
                'id_entity' => $id_sauvgarde_donateur,
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

    public function superSearchAction2(Request $request) {


        $data = $request->query->all();
        $id_critere_rechereche = $data['id'];
        $em = $this->getDoctrine()->getManager();
        $repsauvgardedonateur = $em->getRepository('FulldonIntersaBundle:SauvgardeDonateur');
        $entity = $repsauvgardedonateur->find($id_critere_rechereche);
        if ($entity) {


            $request = Request::create($this->container->get('router')->generate('elastic_donateur') . '?' . $entity->getUrl());

//            var_dump($request);
//            die('eedo');
//            $request = Request::create($this->container->get('router')->generate('elastic_donateur') . '?' . $favObj->getUrl());



            $_array_fileds = $entity->getFieldDonateur();
            $refdonateur = $_array_fileds['refDonateur'];
            $nom = $_array_fileds['nom'];
            $prenom = $_array_fileds['prenom'];
            $nomEntreprise = $_array_fileds['nomEntreprise'];
            $statut_don = $_array_fileds['statut_donateur'];
            $email = $_array_fileds['email'];
            $isopays = $_array_fileds['isopays'];
            $isoville = $_array_fileds['isoville'];
            $zipcode = $_array_fileds['zipcode'];
            $adresse3 = $_array_fileds['adresse3'];
            $telephoneMobile = $_array_fileds['telephoneMobile'];
            $civilite = $_array_fileds['civilite'];
            $removed = $_array_fileds['removed'];
            $pays_id = $_array_fileds['pays_id'];
            $ville_id = $_array_fileds['ville_id'];
            $boolcategorie = $_array_fileds['with_categorie'];

            $boolemail = $_array_fileds['with_email'];

            $booltel = $_array_fileds['with_tel'];
            $categories = $_array_fileds['categories'];

            $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
            $resultform['refDonateur'] = $refdonateur;
            $resultform['civilite'] = $civilite;
            $resultform['nom'] = $nom;
            $resultform['prenom'] = $prenom;
            $resultform['dateNaissance'] = '';
            $resultform['nomEntreprise'] = $nomEntreprise;
            $resultform['email'] = $email;
            $resultform['isopays'] = $isopays;
            $resultform['zipcode'] = $zipcode;
            $resultform['isoville'] = $isoville;
            $resultform['adresse3'] = $adresse3;
//            $array_categori = array(0 => 1, 1 => 7);
//            $array_categori = $categories;
//            var_dump($array_categori);
//            die;
//            $resultform['categories'] = $array_categori;
//            $resultform['categories.id'] = $categories;
            $pre_params = $resultform;
//            $withemail = $boolemail;
//            $withtel = $booltel;
//            $withcat = $boocategorie;
            //on,off
            //$withemail = $email;


            if (isset($boolemail)) {
                $withemail = 'true';
            } else {
                $withemail = 'false';
            }

            if (isset($booltel)) {
                $withtel = 'true';
            } else {
                $withtel = 'false';
            }

            if (isset($statut_don)) {
                $statutDonateur = 'on';
            } else {
                $statutDonateur = 'off';
            }

            if (isset($boocategorie)) {
                $withcat = 'true';
            } else {
                $withcat = 'false';
            }


//            $withemail = $QUERY_String->get('isoville');
//            var_dump($pre_params);
//            die('ici');
        }





//        $request = Request::createFromGlobals();dresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
//        $test = 'fulldon_donateurbundle_donateursearchtype[refDonateur]=&fulldon_donateurbundle_donateursearchtype[civilite]=&fulldon_donateurbundle_donateursearchtype[nom]=&fulldon_donateurbundle_donateursearchtype[prenom]=&fulldon_donateurbundle_donateursearchtype[dateNaissance]=&fulldon_donateurbundle_donateursearchtype[nomEntreprise]=&fulldon_donateurbundle_donateursearchtype[email]=&fulldon_donateurbundle_donateursearchtype[isopays]=FR&fulldon_donateurbundle_donateursearchtype[zipcode]=75&fulldon_donateurbundle_donateursearchtype[isoville]=&fulldon_donateurbundle_donateursearchtype[adresse3]=&fulldon_donateurbundle_donateursearchtype[adresse1]=&fulldon_donateurbundle_donateursearchtype[adresse2]=&fulldon_donateurbundle_donateursearchtype[adresse4]=&fulldon_donateurbundle_donateursearchtype[_token]=8sqALvc9j40DeANYNz675Cp5zT4293STpH3erK3yT7c&columns=list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Demail%26list%5B%5D%3Dzipcode%26list%5B%5D%3Dville&sortelement=&sortdirection=&withemail=true&withtel=&withcat=&statut_donateur=on';


        $test = "fulldon_donateurbundle_donateursearchtype[refDonateur]=&fulldon_donateurbundle_donateursearchtype[civilite]=&fulldon_donateurbundle_donateursearchtype[nom]=&fulldon_donateurbundle_donateursearchtype[prenom]=&fulldon_donateurbundle_donateursearchtype[dateNaissance]=&fulldon_donateurbundle_donateursearchtype[nomEntreprise]=&fulldon_donateurbundle_donateursearchtype[email]=&fulldon_donateurbundle_donateursearchtype[isopays]=FR&fulldon_donateurbundle_donateursearchtype[zipcode]=75&fulldon_donateurbundle_donateursearchtype[isoville]=&fulldon_donateurbundle_donateursearchtype[adresse3]=&fulldon_donateurbundle_donateursearchtype[adresse1]=&fulldon_donateurbundle_donateursearchtype[adresse2]=&fulldon_donateurbundle_donateursearchtype[adresse4]=&fulldon_donateurbundle_donateursearchtype[_token]=8sqALvc9j40DeANYNz675Cp5zT4293STpH3erK3yT7c&columns=list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Demail%26list%5B%5D%3Dzipcode%26list%5B%5D%3Dville&sortelement=&sortdirection=&withemail=true&withtel=&withcat=&statut_donateur=on";
        $request = Request::create($this->container->get('router')->generate('intersa_critere_recherche_donateur') . '?' . $test);
        $request = Request::create($test);

//        var_dump($request);
//        die('$request');
//        $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
//        var_dump($resultform);
//        die('$resultform');
//        $columns = $request->get('columns', 'list[]=numdonateur&list[]=nom&list[]=prenom&list[]=nomentreprise&list[]=statut');
//        var_dump($columns);
//        die('$columns');
//        $request2 = Request::createFromGlobals();
//        $QUERY_String = $request2->server->get('QUERY_STRING');
//        $header = 'http://colinepromai.dev/app_dev.php/intersa/critere/recherche/donateur?';
//        $tt = 'fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
////        $request = $header . $QUERY_String;
//        $request = $header . $tt;
////        $tt = Request::create($this->container->get('router')->generate('elastic_donateur') . '?');
//        var_dump($request);
//        die('$request');
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
        $result2 = null;

//        $withcat = $request->get('withcat');
//        $memo_search['withcat'] = $withcat;
//        $withemail = $request->get('withemail');
//        $memo_search['withemail'] = $withemail;
//        $withtel = $request->get('withtel');
//        $memo_search['withtel'] = $withtel;
//        $statutDonateur = $request->get('statut_donateur');
//        $memo_search['statut_donateur'] = $statutDonateur;
//        $sortelement = $request->get('sortelement');
        $sortelement = '';
//        $sortdirection = $request->get('sortdirection');
        $sortdirection = '';
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


//        $page = $request->get('page', 1);
        $page = 1;




        if ($request->getMethod() == 'GET' && count($pre_params) > 0) {

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
            $donateur->setIsopays($resultform['isopays']);
            $donateur->setIsoville($resultform['isoville']);
//            var_dump($resultform['isoville']);
//                 die('-2');
            if (isset($resultform['categories'])) {

                foreach ($resultform['categories'] as $id) {
//                    var_dump($resultform['categories']);
//                    die('-2');
                    $donateur->addCategory($repCat->find($id));
                }
            }
//            die('3');
            $form = $this->createForm(new DonateurSearchType(), $donateur);
            $app = $this->container->getParameter('elastic_db_name');
            $donateurs = $this->get('fos_elastica.finder.' . $app . '.donateur');
            $elasticaQuery = new \Elastica\Query();

            $query_part = new \Elastica\Query\Bool();
            $filters = new \Elastica\Filter\Bool();
            if (isset($resultform['removed'])) {
                ($resultform['removed']) ? $resultform['removed'] = true : $resultform['removed'] = false;
//                die('0');
            }
            if ($withcat == 'true') {
                $nestedRfQuery = new \Elastica\Filter\Nested();
                $nestedRfQuery->setPath('categories');
                $nestedRfQuery->setFilter(new \Elastica\Filter\Exists('categories'));
//                die('-1');
                //adding the parameters to the main query
                $filters->addMust($nestedRfQuery);
            } elseif ($withcat == 'false') {
                $nestedRfQuery = new \Elastica\Filter\Nested();
                $nestedRfQuery->setPath('categories');
                $nestedRfQuery->setFilter(new \Elastica\Filter\Exists('categories'));
//                var_dump($resultform);
//                 die('-2');
                //adding the parameters to the main query
                $filters->addMustNot($nestedRfQuery);
            }
            if ($withemail == 'true') {
                $filters->addMust(
                        new \Elastica\Filter\Exists('email')
                );
//                 die('-3');
            } elseif ($withemail == 'false') {
                $filters->addMust(
                        new \Elastica\Filter\Missing('email')
                );
//                 die('-4');
            }
            if ($withtel == 'true') {
                $filters->addMust(
                        new \Elastica\Filter\Exists('telephoneMobile')
                );
//                 die('-5');
            } elseif ($withtel == 'false') {
                $filters->addMust(
                        new \Elastica\Filter\Missing('telephoneMobile')
                );
//                die('-5');
            }
            if ($statutDonateur == 'on') {
                $query_part->addMust(
                        new \Elastica\Query\Match('removed', false)
                );
//                 die('-6');
            } elseif ($statutDonateur == 'off') {
                $query_part->addMust(
                        new \Elastica\Query\Match('removed', true)
                );
//                 die('-7');
            }

//            var_dump($resultform);



            foreach ($resultform as $key => $value) {
                if (!empty($value)) {
                    if (in_array($key, array('isoville', 'isopays', 'civilite', 'refDonateur', 'allowRf', 'removed'))) {

                        $query_part->addMust(
                                new \Elastica\Query\Match($key, $value)
                        );
//                        die('11');
                    } elseif (in_array($key, array('nom', 'prenom', 'email', 'adresse1', 'adresse2', 'adresse3', 'adresse3', 'zipcode', 'nomEntreprise'))) {
//                        die('2');

                        $query_part->addMust(
                                new \Elastica\Query\MatchPhrasePrefix($key, $value)
                        );
                    } elseif (in_array($key, array('zipcode'))) {
//                        die('3');

                        $query_part->addMust(
                                new \Elastica\Query\Prefix($key, $value)
                        );
                    } elseif (in_array($key, array('dateNaissance'))) {
//                        die('4');

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


                        // $catQuery->setTerms('categories.id', $value);
                        $catQuery->setTerms('categories.id', array(0 => '1', 1 => '7'));


                        $catQuery->setMinimumMatch(1);
                        $filtersQuery->addMust($catQuery);
                        $nestedRfQuery = new \Elastica\Query\Nested();
                        $nestedRfQuery->setPath('categories');
                        $nestedRfQuery->setQuery($filtersQuery);

                        //adding the parameters to the main query
                        $query_part->addMust($nestedRfQuery);
//                                                die('5');
                    }
//                    $memo_search[$key] = $value;
                }
            }
            $query = new \Elastica\Query\Filtered($query_part, $filters);
//            var_dump($query);
//            die('$query');
            //$elasticaQuery->setQuery($query_part);
            $finalQuery = new \Elastica\Query($query);
//            var_dump($finalQuery);
//            die('$finalQuery');
//            var_dump($typePrets);
//            die('$typePrets');
//            var_dump($finalQuery);
            if (!empty($sortelement) && !empty($sortdirection)) {

                $finalQuery->setSort(array(array_search($sortelement, $restCols) => array('order' => $sortdirection)));
            } else {
                $finalQuery->setSort(array('id' => array('order' => 'desc')));
            }


            if ($page == 0) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
                return $this->redirect($this->generateUrl('elastic_donateur'));
            }
//            $result = $donateurs->findPaginated($finalQuery);
////            var_dump($result);
//            $total_donateur = $result->getNbResults();
//            $donateur_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
//            $result->setMaxPerPage($donateur_per_page);
//            $last_page = ceil($total_donateur / $donateur_per_page);
//
//            $result->setCurrentPage($page);
////            die('setCurrentPage');
//            $result2 = $result;
//            die('result2');
            $result = $donateurs->findPaginated($finalQuery);










            $total_donateur = $result->getNbResults();
//            var_dump($total_donateur);
//            die('$total_donateur');
            $donateur_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
            $result->setMaxPerPage($donateur_per_page);
            $last_page = ceil($total_donateur / $donateur_per_page);

            $result->setCurrentPage($page);
            $result2 = $result;



            $currentPage = $result->getCurrentPageResults();
//            var_dump($currentPage);
//            die('$currentPage');




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
//        die('$mycol ');
        foreach ($mycol as $col) {
            $coldisplay[] = substr($col, 7, strlen($col));
        }
//        die('foreach1 ');

        foreach ($coldisplay as $tab) {
            $index = array_search($tab, $restCols);
            unset($restCols[$index]);
        }
//        die('foreach2 ');



        $response = new Response('herrreee');
        return $response;

//            die('ici');
//        return $this->render('FulldonIntersaBundle:Critere:elasticsearch.html.html.twig', array('result' => $currentPage,
//                    'result2' => $result2,
//                    'memo_search' => $memo_search,
//                    'last_page' => $last_page,
//                    'previous_page' => $previous_page,
//                    'current_page' => $page,
//                    'next_page' => $next_page,
//                    'total_donateur' => $total_donateur,
//                    'params' => $params,
//                    'favoris' => $favoris,
//                    'col_display' => $coldisplay,
//                    'columns' => $columns,
//                    'rest_cols' => $restCols,
//                    'sortelement' => $sortelement,
//                    'sortdirection' => $sortdirection
//        ));
    }

}
