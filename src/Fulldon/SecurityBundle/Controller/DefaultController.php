<?php

namespace Fulldon\SecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FulldonSecurityBundle:Default:index.html.twig');
    }
    public function getVillesAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repPays = $db->getRepository('FulldonDonateurBundle:Pays') ;
        $repVilles = $db->getRepository('FulldonDonateurBundle:Ville') ;
        $pays = $repPays->find($id);
        $villes = $repVilles->findBy(array('pays'=>$pays));
        $response = new Response();
        $villes_content = array();
        foreach($villes as $ville) {


            $villes_content[] = array('id'=> $ville->getId(),'name'=>$ville->getName());
        }
        $response->setContent(json_encode($villes_content));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
}
