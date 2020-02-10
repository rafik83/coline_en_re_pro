<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\DonateurBundle\Entity\CategoryDonateur;
use Fulldon\DonateurBundle\Entity\DepenseCause;
use Fulldon\IntersaBundle\Form\CatType;
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

class TablesController extends Controller
{
    const _BC_ = 'BC';
    const _CS_ = 'CS';
    const _ESPECES_ = 'ESPECES';
    const _PA_ = 'PA';
    const _VIREMENT_ = 'VIREMENT';

    public function listCauseAction($page)
    {
        $db = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $occas = $request->get('idoccas');
        $search = $request->get('codeact');
        $occasion = null;

        if(isset($occas) && !empty($occas)) {

            $repElement = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
            $occasion = $repElement->find($occas);
            if(is_object($occasion)){
                $tableRep = $db->getRepository('FulldonDonateurBundle:Cause') ;
                $elements = $tableRep->findBy(array('codeOccasion'=>$occasion));
            } else {
                $this->get('session')->getFlashBag()->add('warning', 'Le code occasion sélectionné n\'s aucun code d\'activité associé ');
                return $this->redirect($this->generateUrl('intersa_table_element',array('page'=>1,'table'=>'occasion')));
            }

        } elseif(isset($search) && !empty($search)) {
            $tableRep = $db->getRepository('FulldonDonateurBundle:Cause') ;
            $elements= $tableRep->createQueryBuilder('c')
                ->where('c.code LIKE :code')
                ->setParameter('code','%'.$search.'%')
                ->getQuery()->getResult();


        } else {

            $tableRep = $db->getRepository('FulldonDonateurBundle:Cause') ;
            $elements = $tableRep->findAll();

        }
        $total_elements    = count($elements);
        $elements_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page         = ceil($total_elements / $elements_per_page);
        $previous_page     = $page > 1 ? $page - 1 : 1;
        $next_page         = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher*/
        $elements = $this->getDoctrine()
            ->getRepository('FulldonDonateurBundle:Cause')
            ->createQueryBuilder('t');

        if(isset($occas) && !empty($occas)) {
        $elements = $elements->where('t.codeOccasion = :occas')
                    ->setParameter('occas',$occasion);
        }elseif(isset($search) && !empty($search)) {
            $elements= $elements->where('t.code LIKE :code')
                ->setParameter('code','%'.$search.'%');
        }

        $elements =
            $elements
            ->orderBy('t.id','DESC')
            ->setFirstResult(($page * $elements_per_page) - $elements_per_page)
            ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();

        return $this->render('FulldonIntersaBundle:Table:list_cause.html.twig', array(
            'last_page' => $last_page,
            'previous_page' => $previous_page,
            'current_page' => $page,
            'next_page' => $next_page,
            'total_elements' => $total_elements,
            'elements' => $elements,
            'occasion'=>$occasion,
            'search'=>$search,
        ));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function newCauseAction()
    {
        $options = array ('artistes' => $this->container->getParameter('artistes'), 'hotes' =>  $this->container->getParameter('hotes'));
        $class = new Cause;
        $db = $this->getDoctrine()->getManager();
        $form = $this->createForm(new CauseType($options), $class);
        // On récupère la requête
        $request = $this->getRequest();
        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $form->bind($request);
            if ($form->isValid()) {
                $visible_do = $request->get('visible_do');
                $actif = $request->get('actif');
                $rf = $request->get('rf');
                if($actif == "true") {
                    $class->setActif(true);
                } else {
                    $class->setActif(false);
                }

                if($visible_do == "true") {
                    $class->setVisibleOnDonateur(true);
                } else {
                    $class->setVisibleOnDonateur(false);
                }

                if($rf == "true") {
                    $class->setRf(true);
                } else {
                    $class->setRf(false);
                }

                $db->persist($class);
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'Création du code d\'activité avec succès ! ');
                return $this->redirect($this->generateUrl('intersa_table_cause',array('page'=>1)));
            }
        }
        return $this->render('FulldonIntersaBundle:Table:new_cause.html.twig', array('form' => $form->createView()));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function editCauseAction($id)
    {
        $options = array ('artistes' => $this->container->getParameter('artistes'), 'hotes' =>  $this->container->getParameter('hotes'));
        $db = $this->getDoctrine()->getManager();
        $repTable = $db->getRepository('FulldonDonateurBundle:Cause') ;
        //Extract the chosen user
        $class = $repTable->find($id);
        // On utiliser le ArticleEditType
        $form = $this->createForm(new CauseType($options), $class);

        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $visible_do = $request->get('visible_do');
                $actif = $request->get('actif');
                $rf = $request->get('rf');
                if($actif == "true") {
                    $class->setActif(true);
                } else {
                    $class->setActif(false);
                }

                if($visible_do == "true") {
                    $class->setVisibleOnDonateur(true);
                } else {
                    $class->setVisibleOnDonateur(false);
                }

                if($rf == "true") {
                    $class->setRf(true);
                } else {
                    $class->setRf(false);
                }

                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Le code d\'activité a été modifié avec succès !');

                return $this->redirect($this->generateUrl('intersa_table_cause',array('page'=>1)));
            }
        }
        return $this->render('FulldonIntersaBundle:Table:edit_cause.html.twig', array('form' => $form->createView(), 'id'=> $id,'class'=>$class));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function deleteCauseAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause') ;
        $cause = $repCause->find($id);

        if ($id > 0) {
            if(!is_null($cause)) {
            try {
                $db->remove($cause);
                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Le code d\'activité #'.$id.' a été supprimé avec succès !');
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->get('session')->getFlashBag()->add('erreur', 'La suppression de ce code d\'activité  est intérdite !');
            }
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'Le code d\'activité n\'existe pas');
            }
        }

        return $this->redirect($this->generateUrl('intersa_table_cause',array('page'=>1)));
    }
    public function ajaxCauseAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause') ;
        $cause = $repCause->find($id);

        return $this->render('FulldonIntersaBundle:Table/ajax:deletecause.html.twig', array('cause' => $cause));
    }

    public function listElementAction($page,$table)
    {
        $db = $this->getDoctrine()->getManager();
        $search = null;
        $request = $this->getRequest();
        if($request->getMethod() == 'GET') {
        $search = $request->get('codeocc');
        $bycause = $request->get('bycause');
        }
        $elements = null;
        switch($table)  {
            case 'cat' :
                $tableRep = $db->getRepository('FulldonDonateurBundle:CategoryDonateur') ;
                $htmlTwig = 'list_cat';
                $elements = $tableRep->findAll();
                break;
            case 'periodicite' :
                $tableRep = $db->getRepository('FulldonDonateurBundle:Periodicite') ;
                $htmlTwig = 'list_periodicite';
                $elements = $tableRep->findAll();
                break;
            case 'analytique' :
                $tableRep = $db->getRepository('FulldonDonateurBundle:CodeAnalytique') ;
                $htmlTwig = 'list_analytique';
                $elements = $tableRep->findAll();
                break;
            case 'compagne' :
                $tableRep = $db->getRepository('FulldonDonateurBundle:CodeCompagne') ;
                $htmlTwig = 'list_compagne';
                $elements = $tableRep->findAll();
                break;
            case 'occasion' :
                $tableRep = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
                $htmlTwig = 'list_occasion';
              if(isset($search) && !empty($search)) {
                    $elements= $tableRep->createQueryBuilder('c')
                        ->where('c.code LIKE :code')
                        ->setParameter('code','%'.$search.'%')
                        ->getQuery()->getResult();

                } else {
                    $elements = $tableRep->findAll();
                }
                break;
            case 'entity' :
                $tableRep = $db->getRepository('FulldonIntersaBundle:Entity') ;
                $htmlTwig = 'list_entity';
                $elements = $tableRep->findAll();
                break;
            case 'depense' :
                $tableRep = $db->getRepository('FulldonDonateurBundle:DepenseCause') ;
                $htmlTwig = 'list_depense';
                if(isset($bycause) && !empty($bycause) && is_object($obj_cause = $db->getRepository('FulldonDonateurBundle:Cause')->find($bycause))) {
                    $elements= $tableRep->createQueryBuilder('c')
                        ->where('c.cause = :cause')
                        ->setParameter('cause',$obj_cause)
                        ->getQuery()->getResult();

                } else {
                    $elements = $tableRep->findAll();
                }
                break;
        }

        $total_elements   = count($elements);
        $elements_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page         = ceil($total_elements / $elements_per_page);
        $previous_page     = $page > 1 ? $page - 1 : 1;
        $next_page         = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher*/
        $elements = $tableRep
            ->createQueryBuilder('t');
        if(isset($search) && !empty($search)) {
            $elements= $elements
                ->where('t.code LIKE :code')
                ->setParameter('code','%'.$search.'%');

        }
        if(isset($bycause) && !empty($bycause) && is_object($obj_cause = $db->getRepository('FulldonDonateurBundle:Cause')->find($bycause))) {
            $elements= $tableRep->createQueryBuilder('t')
                ->where('t.cause = :cause')
                ->setParameter('cause',$obj_cause);

        }

        $elements = $elements->orderBy('t.id','DESC')
            ->setFirstResult(($page * $elements_per_page) - $elements_per_page)
            ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        return $this->render('FulldonIntersaBundle:Table:'.$htmlTwig.'.html.twig', array(
            'last_page' => $last_page,
            'previous_page' => $previous_page,
            'current_page' => $page,
            'next_page' => $next_page,
            'total_elements' => $total_elements,
            'elements' => $elements,
            'table' => $table,
            'search' =>$search,
            'bycause' => $bycause
        ));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1,ROLE_TRIAL")
     */
    public function newElementAction($table)
    {
        $request = $this->getRequest();
        $db = $this->getDoctrine()->getManager();
        $url_ext = null;
        $bycause = $request->get('bycause');



        switch($table)  {
            case 'cat' :
                $class = new CategoryDonateur;
                $form = $this->createForm(new CatType(), $class);
                $msg = 'Création de la catégorie avec succès ! ';
                $htmlTwig = 'new_cat';
                break;
            case 'periodicite' :
                $class = new Periodicite;
                $form = $this->createForm(new PeriodiciteType(), $class);
                $msg = 'Création de la périodicitié avec succès ! ';
                $htmlTwig = 'new_periodicite';
                break;
            case 'analytique' :
                $class = new CodeAnalytique;
                $form = $this->createForm(new AnalytiqueType(), $class);
                $msg = 'Création du code analytique avec succès ! ';
                $htmlTwig = 'new_analytique';
                break;
            case 'compagne' :
                $class = new CodeCompagne;
                $form = $this->createForm(new CompagneType(), $class);
                $msg = 'Création du code compagne avec succès ! ';
                $htmlTwig = 'new_compagne';
                break;
            case 'occasion' :
                $class = new CodeOccasion;
                $form = $this->createForm(new OccasionType(), $class);
                $msg = 'Création du code occasion avec succès ! ';
                $htmlTwig = 'new_occasion';
                break;
            case 'entity' :
                $class = new Entity;
                $form = $this->createForm(new EntityType(), $class);
                $msg = 'Création de l\'entité avec succès ! ';
                $htmlTwig = 'new_entity';
                break;
            case 'depense' :
                $class = new DepenseCause();
                if(isset($bycause) && !empty($bycause) && is_object($obj_cause = $db->getRepository('FulldonDonateurBundle:Cause')->find($bycause))) {
                $class->setCause($obj_cause);
                $url_ext = '?bycause='.$class->getCause()->getId();
                }
                $form = $this->createForm(new DepenseCauseType(), $class);
                $msg = 'Création de la dépense ! ';
                $htmlTwig = 'new_depense';
                break;
        }

        // On récupère la requête
        $request = $this->getRequest();
        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $form->bind($request);
            if ($form->isValid()) {
                switch($table)  {
                    case 'periodicite' :
                        $visible_do = $request->get('visible_do');

                        if($visible_do == "true") {
                            $class->setVisibleOnDonateur(true);
                        } else {
                            $class->setVisibleOnDonateur(false);
                        }
                    break;
                }

                $db->persist($class);
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', $msg);
                return $this->redirect($this->generateUrl('intersa_table_element',array('table'=>$table ,'page'=>1)).$url_ext);
            }
        }
        return $this->render('FulldonIntersaBundle:Table:'.$htmlTwig.'.html.twig', array('form' => $form->createView(),'table'=>$table, 'url_ext' => $url_ext));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1, ROLE_TRIAL")
     */
    public function editElementAction($table,$id)
    {
        $request = $this->getRequest();
        $db = $this->getDoctrine()->getManager();
        $without_validation = false;
        $url_ext = null;
        $bycause = $request->get('bycause');
        switch($table)  {
            case 'cat' :
                $class = new CategoryDonateur();
                $repTable = $db->getRepository('FulldonDonateurBundle:CategoryDonateur') ;
                $class = $repTable->find($id);
                $form = $this->createForm(new CatType(), $class);
                $msg = 'La catégorie a été modifiée avec succès ! ';
                $htmlTwig = 'edit_cat';
                break;
            case 'periodicite' :
                $class = new Periodicite;
                $repTable = $db->getRepository('FulldonDonateurBundle:Periodicite') ;
                $class = $repTable->find($id);
                $form = $this->createForm(new PeriodiciteType(), $class);
                $msg = 'La priodicité a été modifiée avec succès ! ';
                $htmlTwig = 'edit_periodicite';
                $without_validation = true;
                break;
            case 'analytique' :
                $class = new CodeAnalytique;
                $repTable = $db->getRepository('FulldonDonateurBundle:CodeAnalytique') ;
                $class = $repTable->find($id);
                $form = $this->createForm(new AnalytiqueType(), $class);
                $msg = 'Le code analytique a été modifié avec succès ! ';
                $htmlTwig = 'edit_analytique';

                break;
            case 'compagne' :
                $class = new CodeCompagne;
                $repTable = $db->getRepository('FulldonDonateurBundle:CodeCompagne') ;
                $class = $repTable->find($id);
                $form = $this->createForm(new CompagneType(), $class);
                $msg = 'Le code compagne a été modifié avec succès ! ';
                $htmlTwig = 'edit_compagne';

                break;
            case 'occasion' :
                $class = new CodeOccasion;
                $repTable = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
                $class = $repTable->find($id);
                $form = $this->createForm(new OccasionType(), $class);
                $msg = 'Le code occasion a été modifié avec succès ! ';
                $htmlTwig = 'edit_occasion';

                break;
            case 'entity' :
                $class = new Entity;
                $repTable = $db->getRepository('FulldonIntersaBundle:Entity') ;
                $class = $repTable->find($id);
                $form = $this->createForm(new EntityType(), $class);
                $msg = 'L\'entité a été modifiée avec succès ! ';
                $htmlTwig = 'edit_entity';

                break;
            case 'depense' :
                $class = new DepenseCause();
                $repTable = $db->getRepository('FulldonDonateurBundle:DepenseCause') ;
                $class = $repTable->find($id);
                $form = $this->createForm(new DepenseCauseType(), $class);
                $msg = 'La dépense a été modifiée avec succès ! ';
                $htmlTwig = 'edit_depense';
                $url_ext = '?bycause='.$class->getCause()->getId();

                break;
        }
        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            //Bind
            switch($table)  {
                case 'analytique' :
                case 'compagne' :
                case 'entity' :
                case 'occasion' :
                case 'depense' :
                    $form->bind($request);
                    break;
            }
            //Fin bind
            if ($form->isValid() || $without_validation) {
                switch($table)  {
                    case 'periodicite' :
                        $visible_do = $request->get('visible_do');
                        if($visible_do == "true") {
                            $class->setVisibleOnDonateur(true);
                        } else {
                            $class->setVisibleOnDonateur(false);
                        }
                    break;
                }

                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', $msg);

                return $this->redirect($this->generateUrl('intersa_table_element',array('table'=>$table ,'page'=>1)));
            }
        }
        return $this->render('FulldonIntersaBundle:Table:'.$htmlTwig.'.html.twig', array('form' => $form->createView(), 'id'=> $id, 'table'=>$table,'class'=>$class, 'url_ext' => $url_ext));
    }
    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1,ROLE_TRIAL")
     */
    public function deleteElementAction($table, $id)
    {
        $db = $this->getDoctrine()->getManager();
        $can_remove = true;
        $url_ext = null;
        switch($table)  {
            case 'cat' :
                $repElement = $db->getRepository('FulldonDonateurBundle:CategoryDonateur') ;
                $msg = 'La catégorie #'.$id.' a été supprimée avec succès !';
                $msg_not_exist ='La catégorie n\'existe pas';
                $msg_not_possible=' La suppression de cette catégorie est intérdite !';
                if($repElement->find($id)->getCode() == 'ADHERENTS') {
                    $can_remove = false;
                }
                break;
            case 'periodicite' :
                $repElement = $db->getRepository('FulldonDonateurBundle:Periodicite') ;
                $msg = 'La périodicité #'.$id.' a été supprimée avec succès !';
                $msg_not_exist ='La périodicité n\'existe pas';
                $msg_not_possible=' La suppression de cette périodicité est intérdite !';
                break;
            case 'analytique' :
                $repElement = $db->getRepository('FulldonDonateurBundle:CodeAnalytique') ;
                $msg = 'Le code analytique #'.$id.' a été supprimé avec succès !';
                $msg_not_exist ='Le code analytique n\'existe pas';
                $msg_not_possible=' La suppression de ce code analytique est intérdite !';
                break;
            case 'compagne' :
                $repElement = $db->getRepository('FulldonDonateurBundle:CodeCompagne') ;
                $msg = 'Le code compagne #'.$id.' a été supprimé avec succès !';
                $msg_not_exist ='Le code compagne n\'existe pas';
                $msg_not_possible=' La suppression de ce code compagne est intérdite !';
                break;
            case 'occasion' :
                $repElement = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
                $msg = 'Le code occasion #'.$id.' a été supprimé avec succès !';
                $msg_not_exist ='Le code occasion n\'existe pas';
                $msg_not_possible=' La suppression de ce code occasion est intérdite !';
                break;
            case 'entity' :
                $repElement = $db->getRepository('FulldonIntersaBundle:Entity') ;
                $msg = 'L\'entité #'.$id.' a été supprimé avec succès !';
                $msg_not_exist ='L\'entité n\'existe pas';
                $msg_not_possible=' La suppression de l\'entité est intérdite !';
                break;
            case 'depense' :
                $repElement = $db->getRepository('FulldonDonateurBundle:DepenseCause') ;
                $msg = 'La dépense #'.$id.' a été supprimée avec succès !';
                $msg_not_exist ='La dépense n\'existe pas';
                $msg_not_possible=' La suppression de cette dépense est intérdite !';
                $element = $repElement->find($id);
                $url_ext = '?bycause='.$element->getCause()->getId();
                break;
        }

        $element = $repElement->find($id);
        if ($id > 0) {
            if(!is_null($element) && $can_remove) {
                try {
                    $db->remove($element);
                    $db->flush();
                    $this->get('session')->getFlashBag()->add('info', $msg);
                } catch (\Doctrine\DBAL\DBALException $e) {
                    $this->get('session')->getFlashBag()->add('erreur', $msg_not_possible);
                }
                // On définit un message flash

            }elseif(!$can_remove) {
                $this->get('session')->getFlashBag()->add('erreur', $msg_not_possible);
            } else {
                $this->get('session')->getFlashBag()->add('erreur', $msg_not_exist);
            }
        }

        return $this->redirect($this->generateUrl('intersa_table_element',array('table'=>$table,'page'=>1)).$url_ext);
    }
    public function ajaxElementAction($table, $id)
    {
        $db = $this->getDoctrine()->getManager();
        switch($table)  {
            case 'cat' :
                $htmlTwig = 'deletecat';
                $repElement = $db->getRepository('FulldonDonateurBundle:CategoryDonateur') ;
                break;
            case 'periodicite' :
                $htmlTwig = 'deleteperiodicite';
                $repElement = $db->getRepository('FulldonDonateurBundle:Periodicite') ;
                break;
            case 'analytique' :
                $htmlTwig = 'deleteanalytique';
                $repElement = $db->getRepository('FulldonDonateurBundle:CodeAnalytique') ;
                break;
            case 'compagne' :
                $htmlTwig = 'deletecompagne';
                $repElement = $db->getRepository('FulldonDonateurBundle:CodeCompagne') ;
                break;
            case 'occasion' :
                $htmlTwig = 'deleteoccasion';
                $repElement = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
                break;
            case 'entity' :
                $htmlTwig = 'deleteentity';
                $repElement = $db->getRepository('FulldonIntersaBundle:Entity') ;
                break;
            case 'depense' :
                $htmlTwig = 'deletedepense';
                $repElement = $db->getRepository('FulldonDonateurBundle:DepenseCause') ;
                break;
        }
        $element = $repElement->find($id);

        return $this->render('FulldonIntersaBundle:Table/ajax:'.$htmlTwig.'.html.twig', array('table' => $table, 'element' => $element));
    }
    public function viewOccasion($id)
    {
        $db = $this->getDoctrine()->getManager();

        $repElement = $db->getRepository('FulldonDonateurBundle:Occasion') ;
        $occasion = $repElement->find($id);
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause') ;
        $codes = $repCause->findBy(array('codeOccasion'=> $occasion));


        return $this->render('FulldonIntersaBundle:Table/view_occasion.html.twig', array('occasion' => $occasion, 'codes' => $codes));
    }
    public function statCompAction($id) {

        $db = $this->getDoctrine()->getManager();
        $repElement = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
        $repDepense = $db->getRepository('FulldonDonateurBundle:DepenseCause') ;
        $repCompagne = $db->getRepository('FulldonDonateurBundle:CodeCompagne') ;
        $occasion = $repElement->find($id);
        $compagne = $repCompagne->find($id);
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult( 'budget', 'budget');
        $rsm->addScalarResult( 'somme', 'somme');


        $sql = "        SELECT SUM(budget) as budget, SUM(somme) as somme
          From (select  sum(ifnull(montant, 0)) as somme, c.budget_previsionnel as budget  from depenses_cause
          right join cause as c on id_cause = c.id
          INNER JOIN code_occasion oc ON c.code_occasion = oc.id
          INNER JOIN code_compagne cc ON oc.code_compagne = cc.id
          where cc.id = $id
          group by c.id) as d
        ";
        $query =  $db->createNativeQuery($sql, $rsm);
        $stats = $query->getOneOrNullResult();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult( 'cumul', 'cumul');

        $sql = "
        SELECT sum(IFNULL(g.cumul_montant, 0)) as cumul
        FROM (
        SELECT
        IFNULL(SUM( distinct(SELECT SUM( pr.montant) from prelevement as pr where d.abo_id=pr.abo_id)),0)+
        IFNULL(SUM( distinct (SELECT dd.montant from don as dd where dd.id=d.id and dd.abo_id is null)),0)
        as cumul_montant
        FROM don as d

        INNER JOIN cause as c ON d.cause_id = c.id
        INNER JOIN code_occasion oc ON c.code_occasion = oc.id
        INNER JOIN code_compagne cc ON oc.code_compagne = cc.id
                WHERE (d.removed = false or d.removed =null) AND
           cc.id = $id
        GROUP BY (d.id)

        ) as g

        ";
        $query =  $db->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        return $this->render('FulldonIntersaBundle:Table:compagne_stat.html.twig', array(
            'budget' => $stats['budget'],
            'depenses' => $stats['somme'],
            'cummul_don' =>$result['cumul'],
            'compagne' => $compagne
        ));

    }
    public function statOccasAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repElement = $db->getRepository('FulldonDonateurBundle:CodeOccasion') ;
        $repDepense = $db->getRepository('FulldonDonateurBundle:DepenseCause') ;
        $occasion = $repElement->find($id);
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult( 'budget', 'budget');
        $rsm->addScalarResult( 'somme', 'somme');


        $sql = "        SELECT SUM(budget) as budget, SUM(somme) as somme
          From (select  sum(ifnull(montant, 0)) as somme, c.budget_previsionnel as budget  from depenses_cause
          right join cause as c on id_cause = c.id
          INNER JOIN code_occasion oc ON c.code_occasion = oc.id
          where oc.id = $id
          group by c.id) as d
        ";
        $query =  $db->createNativeQuery($sql, $rsm);
        $stats = $query->getOneOrNullResult();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult( 'cumul', 'cumul');

        $sql = "
        SELECT sum(IFNULL(g.cumul_montant, 0)) as cumul
        FROM (
        SELECT
        IFNULL(SUM( distinct(SELECT SUM( pr.montant) from prelevement as pr where d.abo_id=pr.abo_id)),0)+
        IFNULL(SUM( distinct (SELECT dd.montant from don as dd where dd.id=d.id and dd.abo_id is null)),0)
        as cumul_montant
        FROM don as d

        INNER JOIN cause as c ON d.cause_id = c.id
        INNER JOIN code_occasion oc ON c.code_occasion = oc.id
        WHERE (d.removed = false or d.removed =null)
        AND oc.id = '".$id."'
        GROUP BY (d.id)

        ) as g

        ";
        $query =  $db->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        return $this->render('FulldonIntersaBundle:Table:occasion_stat.html.twig', array(
            'budget' => $stats['budget'],
            'depenses' => $stats['somme'],
            'cummul_don' =>$result['cumul'],
            'occasion' => $occasion
        ));

    }
    public function statCauseAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repElement = $db->getRepository('FulldonDonateurBundle:Cause') ;
        $repDepense = $db->getRepository('FulldonDonateurBundle:DepenseCause') ;
        $cause = $repElement->find($id);
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult( 'somme', 'somme');


        $sql = " select  sum(ifnull(montant, 0)) as somme  from depenses_cause
          Where  id_cause = $id";

        $query =  $db->createNativeQuery($sql, $rsm);
        $stats = $query->getOneOrNullResult();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult( 'cumul', 'cumul');

        $sql = "
        SELECT sum(IFNULL(g.cumul_montant, 0)) as cumul
        FROM (
        SELECT
        IFNULL(SUM( distinct(SELECT SUM( pr.montant) from prelevement as pr where d.abo_id=pr.abo_id)),0)+
        IFNULL(SUM( distinct (SELECT dd.montant from don as dd where dd.id=d.id and dd.abo_id is null)),0)
        as cumul_montant
        FROM don as d

        INNER JOIN cause as c ON d.cause_id = c.id
        WHERE (d.removed = false or d.removed =null)
        AND c.id = '".$id."'
        GROUP BY (d.id)

        ) as g

        ";
        $query =  $db->createNativeQuery($sql, $rsm);
        $result = $query->getOneOrNullResult();
        return $this->render('FulldonIntersaBundle:Table:cause_stat.html.twig', array(
            'budget' => $cause->getBudgetPrevisionnel(),
            'depenses' => $stats['somme'],
            'cummul_don' =>$result['cumul'],
            'cause' => $cause
        ));

    }
}
