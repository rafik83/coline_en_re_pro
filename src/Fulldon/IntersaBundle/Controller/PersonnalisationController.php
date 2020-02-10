<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\IntersaBundle\Entity\PersoRfs;
use Fulldon\IntersaBundle\Form\PersoRfsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\IntersaBundle\Entity\Personnalisation;
use Fulldon\IntersaBundle\Form\PersonnalisationType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @PreAuthorize(" hasRole('ROLE_INTERSA_N1') or hasRole('ROLE_ASSOC_N1') or hasRole('ROLE_TRIAL')")
 */

class PersonnalisationController extends Controller
{
    public function indexAction()
    {

        $db = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $persoRep = $db->getRepository('FulldonIntersaBundle:Personnalisation') ;
        $perso = $persoRep->find(1);
        $old_img = null;
        $old_fond_page = null;
        $old_header_page = null;
        if($perso !== null){
        $old_img = $perso->getLogo();
        $old_fond_page = $perso->getFondPage();
        $old_header_page = $perso->getHeaderPage();
        } else {
            $perso = new Personnalisation();
        }
        $pval = $perso->getMontantP();
        if(!is_null($pval) && !empty($pval) ) {
            $vmp = explode(':',$pval);
        } else {
            $vmp = null;
        }

        $rval = $perso->getMontantR();
        if(!is_null($rval) && !empty($rval)) {
            $vmr = explode(':',$rval);
        } else {
            $vmr = null;
        }

        $adhrval = $perso->getMontantAR();
        if(!is_null($adhrval) && !empty($adhrval)) {
            $adhvmr = explode(':',$adhrval);
        } else {
            $adhvmr = null;
        }

        //$entity = $em->getRepository('CliniqueGynecoBundle:Article');
        $form   = $this->createForm(new PersonnalisationType(), $perso);
        if ($request->getMethod() == 'POST') {
        $form->bind($request);

        if ($form->isValid()) {
            //Gestion des montants ponctuels
            $amount_p = array();
            $max_mp = $request->get('max_mp');
            if($max_mp > 0) {
            for($i= 1; $i <= $max_mp; $i++)
            {
                $val = $request->get("input_mp_{$i}");
                echo $val;
                if(!is_null($val) && is_numeric($val)) {
                    $amount_p[] = $val;
                }
            }
            }
            $perso->setMontantP(implode(':', $amount_p));
            //Gestion des montants réguliers
            $amount_r = array();
            $max_mr = $request->get('max_mr');
            if($max_mr > 0) {
                for($i= 1; $i <= $max_mr; $i++)
                {
                    $val = $request->get("input_mr_{$i}");
                    echo $val;
                    if(!is_null($val) && is_numeric($val)) {
                        $amount_r[] = $val;
                    }
                }
            }
            $perso->setMontantR(implode(':', $amount_r));

            //Gestion des montants réguliers sur la pages des adherent
            $amount_ar = array();
            $max_adhmr = $request->get('max_adhmr');
            if($max_adhmr > 0) {
                for($i= 1; $i <= $max_adhmr; $i++)
                {
                    $val = $request->get("input_adhmr_{$i}");
                    echo $val;
                    if(!is_null($val) && is_numeric($val)) {
                        $amount_ar[] = $val;
                    }
                }
            }
            $perso->setMontantAR(implode(':', $amount_ar));
            //fin de gestion des montants
            $logo = $perso->getLogo();
            $fondPage = $perso->getFondPage();
            $headerPage = $perso->getHeaderPage();
            if(is_null($logo) && !is_object($logo)) {
                $perso->setLogo($old_img);
            }
            if(is_null($fondPage) && !is_object($fondPage)) {
                $perso->setFondPage($old_fond_page);
            }
            if(is_null($headerPage) && !is_object($headerPage)) {
                $perso->setHeaderPage($old_header_page);
            }
            $db->persist($perso);
            $db->flush();
            $this->get('session')->getFlashBag()->add('info', 'Modification effectuée avec succès. ');
            return $this->redirect($this->generateUrl('intersa_personnalisation'));

        }
        }
        return $this->render('FulldonIntersaBundle:Personnalisation:personnalisation.html.twig', array(
            'perso' => $perso,
            'form'   => $form->createView(),
            'vmp' => $vmp,
            'vmr' => $vmr,
            'adhvmr' => $adhvmr
        ));
    }
    public function persoRfsAction()
    {
        $db = $this->getDoctrine()->getManager();
        $entityRep = $db->getRepository('FulldonIntersaBundle:Entity');
        $entities =  $entityRep->findAll();
        return $this->render('FulldonIntersaBundle:Personnalisation:perso_rfs.html.twig', array('entities' => $entities));
    }
    public function persoFormRfsAction($code, $identity)
    {
        $db = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $repPersoRfs = $db->getRepository('FulldonIntersaBundle:PersoRfs');
        $entityRep = $db->getRepository('FulldonIntersaBundle:Entity');
        $myEntity =$entityRep->findOneBy(array('code'=>$identity));
        $objet = $repPersoRfs->findOneBy(array('code'=>$code, 'entity'=>$myEntity));
        $codes = array('rf', 'rf_pa', 'duplicata_rf', 'duplicata_rf_pa');
        if(!in_array($code, $codes) || !is_object($myEntity)) {

            $this->get('session')->getFlashBag()->add('error', 'Un problème est survenu lors de la création du document');
            return $this->redirect($this->generateUrl('intersa_perso_rf'));
        }
        if(!is_object($objet)) {
            $objet = new PersoRfs();
            $objet->setCode($code);
            $objet->setEntity($myEntity);
            $db->persist($objet);
            $db->flush();
        }
        $form = $this->createForm(new PersoRfsType(), $objet);
        $filetype = null;
        switch($code)
        {
            case 'rf': $filetype = 'Reçu fiscal [Original]';break;
            case 'rf_pa': $filetype = 'Reçu fiscal PA [Original]';break;
            case 'duplicata_rf': $filetype = 'Duplicata RF  ';break;
            case 'duplicata_rf_pa': $filetype = 'Duplicata RF PA ';break;
        }
        if ($request->getMethod() == 'POST') {
            $form->bind($request);

            if ($form->isValid()) {

                $db->persist($objet);
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'Modification effectuée avec succès. ');

            }
        }
        return $this->render('FulldonIntersaBundle:Personnalisation:perso_rfs_form.html.twig', array(
            'doc' => $objet,
            'filetype' => $filetype,
            'code' => $code,
            'form'   => $form->createView(),
            'identity' => $identity,
            'entite' => $myEntity
        ));
    }
}
