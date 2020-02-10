<?php

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\IntersaBundle\Form\SaisieType;
class StreetController extends Controller
{

    public function indexAction() {
        $data = array();
        $champs = array();
        $db = $this->getDoctrine()->getManager();
        $repPeriod = $db->getRepository('FulldonDonateurBundle:Periodicite');
        // if session exists  ?
        $user = $this->get('security.context')->getToken()->getUser();
        $donateur = new Donateur;
        $form = $this->createForm(new SaisieType(), $donateur, array(
            'cascade_validation' => true));
        $periodes = $repPeriod->findAll();
        $data = array(
            'nom' => '',
            'prenom' => '',
            'adresse1' =>'',
            'adresse2' =>'',
            'adresse3' =>'',
            'adresse4' =>'',
            'zipcode' =>'',
            'ville' =>''
        );
        $request = $this->getRequest();
        // Tester les donnée
        if ($request->getMethod() == 'POST') {

            // Data contrôl
            $bic = $request->get('bic');
            $iban = $request->get('iban');
            $date_first_pa = $request->get('date_first_pa');
            $periodicite = $request->get('periodicite');
            $nom_banque = $request->get('nom_banque');
            $champs['nom_banque'] = $nom_banque;
            $champs['bic'] = $bic;
            $champs['iban'] = $iban;
            $champs['date_first_pa'] = $date_first_pa;
            $champs['date_next_pa'] = $date_first_pa;
            $champs['periodicite'] = $periodicite;
            $date_fin_pa = $request->get('date_fin_pa');
            $champs['date_fin_pa'] = $date_fin_pa;
            if(isset($date_fin_pa) && !empty($date_fin_pa) && !$this->validateDate($date_fin_pa)) {

                $errors['error_date_fin_pa'] = ' La date du fin de l\'engagement n\'est pas valide : jj/mm/aaaa' ;
            }
            if(!isset($bic) || empty($bic)) {

                $errors['error_bic'] = ' Veuillez spécifier un BIC valide' ;
            }
            if(!isset($iban) || empty($iban)) {

                $errors['error_iban'] = ' Veuillez spécifier un IBAN valide' ;
            }
            if(!isset($date_first_pa) || empty($date_first_pa) || !$this->validateDate($date_first_pa)) {

                $errors['error_date_first_pa'] = ' La date du premier prélevement n\'est pas valide : jj/mm/aaaa' ;
            }
            if(!isset($nom_banque) || empty($nom_banque) ) {

                $errors['error_nom_banque'] = 'Veuillez spécifier le nom de la banque' ;
            }
        }
        // prepeare form
        return $this->render("FulldonIntersaBundle:Street:index.html.twig", array(

            'data'=>$data,
            'form' => $form->createView(),
            'champs'=>$champs,
            'periodes'=>$periodes
        ));

    }


}
