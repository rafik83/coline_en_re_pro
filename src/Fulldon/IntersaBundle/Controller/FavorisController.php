<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fulldon\IntersaBundle\Entity\RechercheFavoris;

class FavorisController extends Controller {

    public function addAction($section) {
       
        
          
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $newRfavoris = new RechercheFavoris();
        $request = Request::createFromGlobals();
//        var_dump($request);
        
        $route = null;
        if ($section == 'donateurs') {
            $route = 'elastic_donateur';
        } elseif ($section == 'dons') {
            $route = 'elastic_don';
        }

        if ($request->getMethod() == 'POST') {
            $title = $request->request->get('title');

            $description = $request->request->get('description');
//            var_dump($description);
            $url = $request->request->get('url');
//            die('enter ');
//            var_dump($url);
//            die('$url');
            if (!empty($title)) {
                $existefavoris = $repFavoris->findOneBy(array('title' => $title));
                if ($existefavoris) {
                    $existefavoris->setTitle($title);
                    $existefavoris->setDescription($description);
                    $existefavoris->setUrl($url);
                    $existefavoris->setSection($section);
                    $db->persist($existefavoris);
                    $db->flush();
                    // Redirection
                    $this->get('session')->getFlashBag()->add('success', 'Recherche Modifier dans vos favoris');
                    return $this->redirect($this->generateUrl($route) . '?' . $url);
                }
                if (!$existefavoris) {
                    $newRfavoris->setTitle($title);
                    $newRfavoris->setDescription($description);
                    $newRfavoris->setUrl($url);
                    $newRfavoris->setSection($section);
                    $db->persist($newRfavoris);
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

    public function deleteAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        if ($id > 0) {
            $favoris = $repFavoris->find($id);
            if (is_object($favoris)) {
                try {
                    $db->remove($favoris);
                    $db->flush();

                    // On définit un message flash
                    $this->get('session')->getFlashBag()->add('info', 'La recherche a été supprimée avec succès !');
                } catch (\Doctrine\DBAL\DBALException $e) {
                    $this->get('session')->getFlashBag()->add('erreur', 'La suppression de cette recherche est intérdite !');
                }
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'La recherche spécifiée n\'existe pas');
            }
        }

        return $this->redirect($this->generateUrl('elastic_donateur'));
    }

}
