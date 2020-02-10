<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\DonateurBundle\Form\DonateurSearchType;
use Fulldon\IntersaBundle\Form\IntersaDonateurType;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Yaml\Parser;

class AnomalieController extends Controller {

    const _BC_ = 'BC';
    const _CS_ = 'CS';
    const _ESPECES_ = 'ESPECES';
    const _PA_ = 'PA';
    const _VIREMENT_ = 'VIREMENT';
    const _MIX_ = 'MIX';

    private $init = array();

    public function preExecute() {

        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $repSaisie = $db->getRepository('FulldonIntersaBundle:Saisie');
        $prod = $this->getDoctrine()
                        ->getRepository('FulldonIntersaBundle:Saisie')
                        ->createQueryBuilder('p')
                        ->select('count(p.id) as cpt')
                        ->where('p.done = FALSE')
                        ->getQuery()->getSingleResult();
        $data = $this->getDoctrine()
                        ->getRepository('FulldonIntersaBundle:Saisie')
                        ->createQueryBuilder('p')
                        ->select('count(p.id) as cpt')
                        ->where('p.user = :id')
                        ->andwhere('p.done = FALSE')
                        ->setParameter('id', $user->getId())
                        ->getQuery()->getSingleResult();
        $done = $this->getDoctrine()
                        ->getRepository('FulldonIntersaBundle:Saisie')
                        ->createQueryBuilder('p')
                        ->select('count(p.id) as cpt')
                        ->where('p.done = true')
                        ->andwhere('SUBSTRING(p.createdAt, 1, 10) = CURRENT_DATE()')
                        ->getQuery()->getSingleResult();

        $root = $this->container->getParameter('path_scan');

        $files = array('files' => array(), 'dirs' => array());
        $directories = array();
        $last_letter = $root[strlen($root) - 1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;
        $directories[] = $root;
        $depth = 0;
        $productivite = 0;

        while (sizeof($directories)) {
            $dir = array_pop($directories);

//            if ($handle = opendir($dir)) {
//                while (false !== ($file = readdir($handle)) && $depth < 1) {
//                    if ($file == '.' || $file == '..') {
//                        continue;
//                    }
//                    $filename = $file;
//                    $file = $dir . $file;
//                    if (is_dir($file)) {
//                        $directory_path = $file . DIRECTORY_SEPARATOR;
//                        array_push($directories, $directory_path);
//                        $files['dirs'][] = array('name' => $filename, 'path' => $directory_path);
//                    }
//                }
//                closedir($handle);
//            }
            $depth++;
        }
        $nbTotal = 0;
        foreach ($files['dirs'] as $dir) {
            $result = $this->get('fulldon.intersa.global')->getXmlFiles($dir['path']);
            $nbTotal += $result['nb'];
        }
        if ($prod['cpt'] > 0) {
            $productivite = ($done['cpt'] / $prod['cpt']) * 100;
        }

        $this->init['data'] = $data;
        $this->init['done'] = $done;
        $this->init['productivite'] = round($productivite);
        $this->init['nbtotal'] = $nbTotal;
    }

    public function indexAction($page) {
        $db = $this->getDoctrine()->getManager();
        $anomalieRep = $db->getRepository('FulldonIntersaBundle:Anomalie');
        $anomalies = $anomalieRep->findBy(array('corriger' => false));
        $total_anomalies = count($anomalies);
        $anomalies_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_anomalies / $anomalies_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $anomalies = $this->getDoctrine()
                        ->getRepository('FulldonIntersaBundle:Anomalie')
                        ->createQueryBuilder('p')
                        ->where('p.corriger = false')
                        ->orderBy('p.createdAt', 'DESC')
                        ->setFirstResult(($page * $anomalies_per_page) - $anomalies_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();
        return $this->render('FulldonIntersaBundle:Anomalie:index.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_anomalies' => $total_anomalies,
                    'anomalies' => $anomalies,
                    'init' => $this->init
        ));
        
        
        
        


        
        
        
        
        
        
        
        
        
    }

    public function viewAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repAnomalie = $db->getRepository('FulldonIntersaBundle:Anomalie');
        $anomalie = $repAnomalie->findOneBy(array('id' => $id, 'corriger' => false));
        $nom = $anomalie->getLot();
        $type = $anomalie->getType();

        $root = $this->container->getParameter('path_scan');
        $file = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $nom . '.XML';
        $donefile = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'DONE' . DIRECTORY_SEPARATOR . $nom . '.XML';
        $sequence = $anomalie->getSequence();
        $front = false;
        $data = array();
        if (file_exists($file) || file_exists($donefile)) {
            if (file_exists($donefile)) {
                $file = $donefile;
            }
            $xml = simplexml_load_file($file);

            foreach ($xml->Batch->Page as $page) {
                switch ($type) {
                    case self::_BC_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $sequence % 2 != 0) {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                        } elseif ($page->Fields->SequenceNumber == $sequence + 1 && $sequence % 2 != 0) {
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                        }

                        break;
                    case self::_MIX_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $sequence % 2 != 0) {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                        } elseif ($page->Fields->SequenceNumber == $sequence + 1 && $sequence % 2 != 0) {
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                        }

                        break;
                    case self::_CS_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence) {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                        }
                        break;
                    case self::_ESPECES_:
                    case self::_VIREMENT_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $front == false) {
                            $front = true;
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                        }
                        break;
                    case self::_PA_:
                        $patharr = explode('\\', $page['Path']);
                        $originimg = end($patharr);
                        if ($page->Fields->SequenceNumber == $sequence && $front == false) {
                            //Je commence le traitement
                            $data['image'][] = $nom . '_' . $page->Fields->SequenceNumber;
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $this->render('FulldonIntersaBundle:Anomalie:view.html.twig', array('anomalie' => $anomalie, 'init' => $this->init, 'data' => $data));
    }

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1")
     */
    public function correctAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repAnomalie = $db->getRepository('FulldonIntersaBundle:Anomalie');
        $anomalie = $repAnomalie->findOneBy(array('id' => $id, 'corriger' => false));
        $anomalie->setCorriger(true);
        $db->persist($anomalie);
        $db->flush();
        $current_user = $this->get('security.context')->getToken()->getUser();
        // Log the user creation
        $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_ANOMALIE_CORRECTION_);
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(StatVar::CREATE, $event);
        // redirection vers la page de la gestion des anomalies
        $this->get('session')->getFlashBag()->add('info', 'L\'anomlie #' . $anomalie->getId() . ' est maintenant flaguée comme abondonée ');
        return $this->redirect($this->generateUrl('intersa_saisie_anomalie', array('page' => 1)));
    }

}
