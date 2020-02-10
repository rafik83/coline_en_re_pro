<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Fulldon\IntersaBundle\Entity\CustomCronTask;
use Fulldon\IntersaBundle\Form\MarketingCronTaskType;
use Symfony\Component\HttpFoundation\Request;

class TaskController extends Controller {

    public function indexAction($page) {

//         die('index');
        $db = $this->getDoctrine()->getManager();
//          die('index');
        $request = $this->getRequest();
        $tableRep = $db->getRepository('FulldonIntersaBundle:CustomCronTask');
        $elements = $tableRep->findAll(); // les different exportation des dons(csv,pdf,exel...)
//        var_dump($elements);
//        die('element');
        $total_elements = count($elements);
        $elements_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_elements / $elements_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $elements = $this->getDoctrine()
                ->getRepository('FulldonIntersaBundle:CustomCronTask')
                ->createQueryBuilder('t');

        $elements = $elements
                        ->orderBy('t.id', 'DESC')
                        ->setFirstResult(($page * $elements_per_page) - $elements_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();

        return $this->render('FulldonIntersaBundle:Task:index.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_elements' => $total_elements,
                    'elements' => $elements,
        ));
    }

    public function newTaskAction() {

//        die('custom task');
        $class = new CustomCronTask;
//        die('custom task');
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $favorisDons = $repFavoris->findBy(array('section' => 'dons'));
        $errors = array();
        $display_error = false;
        // On récupère la requête
        $request = $this->getRequest();
        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {

            $action = $request->get('action');
            $fRechercheDons = $request->get('search_fav_dons');
            $memo_search['search_fav_dons'] = $fRechercheDons;
            $fRechercheDonateurs = $request->get('search_fav_donateurs');
            $memo_search['search_fav_donateurs'] = $fRechercheDonateurs;
            //Fin de traitement du test.

            if (count($errors) == 0) {
                foreach ($fRechercheDons as $r) {
                    $favObj = $repFavoris->find($r);
//                    var_dump($favObj);
//                    die('uuu');
                    if (is_object($favObj)) {
                        $class->addRecherch($favObj);
                    }
                }

                $class->setAction($action);
//                var_dump($action);
//                die;
                $class->setProgress(false);
                $db->persist($class);
                //Test mode
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'La tâche a été palnifiée avec succès');
                return $this->redirect($this->generateUrl('fulldon_bo_custom_tasks', array('page' => 1)));
            } elseif (count($errors) > 0) {
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add('error', $error);
                    $display_error = true;
                }
            }
        }
        return $this->render('FulldonIntersaBundle:Task:new_task.html.twig', array(
                    'display_error' => $display_error,
                    'class' => $class, 'favoris_dons' => $favorisDons
        ));
    }

    private function isset_notempty($val) {
        return isset($val) && !empty($val);
    }

}
