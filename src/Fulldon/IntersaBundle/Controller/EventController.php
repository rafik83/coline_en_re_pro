<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\DonateurBundle\Entity\DepenseCause;
use Fulldon\DonateurBundle\Entity\Event;
use Fulldon\DonateurBundle\Form\EventType;
use Fulldon\IntersaBundle\Form\DepenseCauseType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Cause;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\IntersaBundle\Form\CauseType;
use Fulldon\DonateurBundle\Entity\Periodicite;
use Fulldon\IntersaBundle\Form\PeriodiciteType;
use Fulldon\DonateurBundle\Entity\CodeAnalytique;
use Fulldon\IntersaBundle\Form\AnalytiqueType;
use Fulldon\DonateurBundle\Entity\CodeCompagne;
use Fulldon\IntersaBundle\Form\CompagneType;
use Fulldon\IntersaBundle\Entity\Entity;
use Fulldon\IntersaBundle\Form\EntityType;
use Fulldon\DonateurBundle\Entity\CodeOccasion;
use Fulldon\IntersaBundle\Form\OccasionType;
use Fulldon\IntersaBundle\Form\EditCauseType;
use Doctrine\ORM\DBALException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Yaml\Parser;

class EventController extends Controller
{


    public function listEventAction($page)
    {
        $db = $this->getDoctrine()->getManager();
        $request = $this->getRequest();

         $tableRep = $db->getRepository('FulldonDonateurBundle:Event') ;
         $elements = $tableRep->findAll();

        $total_elements    = count($elements);
        $elements_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page         = ceil($total_elements / $elements_per_page);
        $previous_page     = $page > 1 ? $page - 1 : 1;
        $next_page         = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher*/
        $elements = $this->getDoctrine()
            ->getRepository('FulldonDonateurBundle:Event')
            ->createQueryBuilder('e');


        $elements =
            $elements
            ->orderBy('e.id','DESC')
            ->setFirstResult(($page * $elements_per_page) - $elements_per_page)
            ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();

        return $this->render('FulldonIntersaBundle:Event:index.html.twig', array(
            'last_page' => $last_page,
            'previous_page' => $previous_page,
            'current_page' => $page,
            'next_page' => $next_page,
            'total_elements' => $total_elements,
            'elements' => $elements
        ));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function newEventAction()
    {
        $class = new Event();
        $db = $this->getDoctrine()->getManager();
        $form = $this->createForm(new EventType(), $class);
        // On récupère la requête
        $request = $this->getRequest();
        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $form->bind($request);
            if ($form->isValid()) {
                $actif = $request->get('actif');
                if($actif == "true") {
                    $class->setActif(true);
                } else {
                    $class->setActif(false);
                }


                $db->persist($class);
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'Création de l\'événement avec succès ! ');
                return $this->redirect($this->generateUrl('fulldon_bo_events',array('page'=>1)));
            }
        }
        return $this->render('FulldonIntersaBundle:Event:new_event.html.twig', array('form' => $form->createView()));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function editEventAction($id)
    {

        $db = $this->getDoctrine()->getManager();
        $repTable = $db->getRepository('FulldonDonateurBundle:Event') ;
        //Extract the chosen user
        $class = $repTable->find($id);
        // On utiliser le ArticleEditType
        $form = $this->createForm(new EventType(), $class);

        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $actif = $request->get('actif');
                $rf = $request->get('rf');

                if($actif == "true") {
                    $class->setActif(true);
                } else {
                    $class->setActif(false);
                }


                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'L\'événement a été modifié avec succès !');

                return $this->redirect($this->generateUrl('fulldon_bo_events',array('page'=>1)));
            }
        }
        return $this->render('FulldonIntersaBundle:Event:edit_event.html.twig', array('form' => $form->createView(), 'id'=> $id,'element'=>$class));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function deleteEventAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repEvent = $db->getRepository('FulldonDonateurBundle:Event') ;
        $event = $repEvent->find($id);

        if ($id > 0) {
            if(!is_null($event)) {
            try {
                $db->remove($event);
                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'L\'événement #'.$id.' a été supprimé avec succès !');
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->get('session')->getFlashBag()->add('erreur', 'La suppression de l\'événement  est intérdite !');
            }
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'L\'événement n\'existe pas');
            }
        }

        return $this->redirect($this->generateUrl('fulldon_bo_events',array('page'=>1)));
    }
    public function ajaxEventAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repEvent = $db->getRepository('FulldonDonateurBundle:Event') ;
        $event = $repEvent->find($id);

        return $this->render('FulldonIntersaBundle:Event/ajax:delete_event.html.twig', array('element' => $event));
    }

    public function enrollersEventAction($id)
    {
        $cumul = 0.00;
        $date = date("Y-m-d H:i:s");

        $db = $this->getDoctrine()->getManager();
        $repTable = $db->getRepository('FulldonDonateurBundle:Event') ;
        //Extract the chosen user
        $class = $repTable->find($id);

        $dons = $class->getDons();
        return $this->render('FulldonIntersaBundle:Event:enrollers_event.html.twig', array(
            'element'=> $class,
            'elements'=>$dons,
            'cumul'=>$cumul,
            'date'=>$date
                ));
    }
}
