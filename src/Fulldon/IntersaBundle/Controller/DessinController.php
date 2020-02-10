<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fulldon\IntersaBundle\Entity\DessinEnregistrement;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class DessinController extends Controller {

    public function addDessinAction(Request $request) {
        $db = $this->getDoctrine()->getManager();
        $newDE = new DessinEnregistrement();
//        $request = Request::createFromGlobals();
        if ($request->getMethod() == 'GET') {


            $title_de = $request->query->get('title_de');
            $description_de = $request->query->get('description_de');
            $columns_de = $request->query->get('columns_de');
//            return $JsonResponse = new JsonResponse(array('tab1'=>$title_de,'tab2'=>$description_de,'tab3'=>$columns_de));
            //JsonResponse
            if (!empty($title_de)) {
                $newDE->setTitle($title_de);
                $newDE->setDescription($description_de);
                $newDE->setColumns($columns_de);
                $db->persist($newDE);
                $db->flush();
                //$this->get('session')->getFlashBag()->add('success', 'Dessin enregistrée avec succée');
                return $Response = new Response('success');
//                return $JsonResponse = new JsonResponse(array('tab'=>$columns_de));
            } else {
                //$this->get('session')->getFlashBag()->add('erreur', 'Erreur de sauvegarde du dessin');
                return $Response = new Response('erreur');
            }
        }
    }

    public function GetAllDessin() {

        $query = "SELECT * FROM coline_en_re_full_db.dessin_enregistrement";

        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function paginationDessinAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
//        $request = $this->get('request');
        $page = 1;
        $datax = $request->query->all();
        $page = $datax["param"];
        $query2 = $this->GetAllDessin();

        $adapter = new ArrayAdapter($query2);
        $pagerfanta = new Pagerfanta($adapter);
        $maxPerPage = 5; //20
        $current_page = $page;

        $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
        $pagerfanta->setCurrentPage($current_page); // 1 by default
        $data = $pagerfanta->getCurrentPageResults();
        $last_page = $pagerfanta->getNbPages();
        $coldisplay_ligne_de_enregister = array(array('title' => 'Titre'),
            array('descrip' => 'Description'), array('createdat' => 'Date de creation'), array('btn_de_add' => ' '), array('btn_de_delete' => ' '));
        $view = $this->renderView('FulldonIntersaBundle:Donateurs:ligne_de_enregister.html.twig', array(
            'data' => $data,
            'coldisplay_ligne_de_enregister' => $coldisplay_ligne_de_enregister,
            'pager_dessin' => $pagerfanta,
            'current_page_dessin' => $current_page,
            'last_page_dessin' => $last_page
        ));

        $response = new Response($view);
        return $response;
    }

    public function getAllDessinAction(Request $request) {
        $db = $this->getDoctrine()->getManager();
        $repDessin = $db->getRepository('FulldonIntersaBundle:DessinEnregistrement');
        $page = 1;
        $query2 = $this->GetAllDessin();
        $adapter = new ArrayAdapter($query2);
        $pagerfanta = new Pagerfanta($adapter);
        $maxPerPage = 5; //20
        $current_page = $page;
        $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
        $pagerfanta->setCurrentPage($current_page); // 1 by default
        $data = $pagerfanta->getCurrentPageResults();
        $last_page = $pagerfanta->getNbPages();
        $coldisplay_ligne_de_enregister = array(array('title' => 'Titre'),
            array('descrip' => 'Description'), array('createdat' => 'Date de creation'), array('btn_de_add' => ' '), array('btn_de_delete' => ' '));
        $view = $this->renderView('FulldonIntersaBundle:Donateurs:ligne_de_enregister.html.twig', array(
            'data' => $data,
            'coldisplay_ligne_de_enregister' => $coldisplay_ligne_de_enregister,
            'pager_dessin' => $pagerfanta,
            'current_page_dessin' => $current_page,
            'last_page_dessin' => $last_page
        ));

        $response = new Response($view);
        return $response;
    }

    public function findDessinByAction(Request $request) {


        $data = $request->query->all();
        $id_dessin = $data['param'];


        $db = $this->getDoctrine()->getManager();
        $repDessin = $db->getRepository('FulldonIntersaBundle:DessinEnregistrement');
        $Dessin = $repDessin->find($id_dessin);
//        return $JsonResponse = new JsonResponse(array('tab'=>$Dessin->getColumns()));
        $columns = $Dessin->getColumns();
        $explode = explode('list[]=', $columns);
        $string = '';
        $array_to_json = array();
        $k = 0;
        $array_to_json2 = array();
        $k2 = 0;

        foreach ($explode as $key => $value) {

            if ($key != 0) {
                $string = $value;
                $string = str_replace('&', '', $string);
                $array_to_json[$k][$string] = $string;
                $k++;
            }
        }

        foreach ($array_to_json as $key => $value) {

            if (!is_object($value)) {
                $array_to_json2[$k2] = $value;
                $k2++;
            }
        }



        return $JsonResponse = new JsonResponse(array('tab' => $array_to_json2));




//         return $Response = new Response($Dessin->getColumns());
    }

    public function deleteDessinAction(Request $request) {
        //if ($request->isXmlHttpRequest()) {
        $datax = $request->query->all();
        $id = $datax["param"];
//            return $JsonResponse = new JsonResponse(array('$id'=>$id));
        $etat = '';
        $db = $this->getDoctrine()->getManager();
        $repDessin = $db->getRepository('FulldonIntersaBundle:DessinEnregistrement');
        if ($id > 0) {
            $dessin = $repDessin->find($id);
            if (is_object($dessin)) {
                $db->remove($dessin);
                $db->flush();
                return $Response = new Response('success');
            } else {
                return $Response = new Response('erreur');
            }
        }
        //}
        //return $this->redirect($this->generateUrl('elastic_donateur'));
    }

}
