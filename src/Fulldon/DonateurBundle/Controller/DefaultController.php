<?php

namespace Fulldon\DonateurBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    const _PAYPAL_ = 3;
    const _CHEQUE_= 2;
    const _VIREMENT_ = 4;
    const _STATUT_ATTENTE_ = 1;
    const _STATUT_TRAITEMENT_PAIEMENT_ = 2;
    const _STATUT_DON_VALIDE_ = 3;
    const _STATUT_DON_ANNULE_ = 4;
    private $init = array();

    public function preExecute() {
        if($this->container->getParameter('donor_space') == 0) {
//            die('Espace donateur non disponible !');
        }
        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
        $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);
        $data = $this->getDoctrine()
            ->getRepository('FulldonDonateurBundle:Rf')
            ->createQueryBuilder('r')
            ->join('r.don','d')
            ->join('d.transaction','t')
            ->join('t.statut','s')
            ->select('count(d.id) as cpt')
            ->where('d.user = :id and r.don is not null and t.statut =:statut and r.sended = 0')
            ->setParameter('id', $user->getId())
            ->setParameter('statut', $statut )
            ->getQuery()->getSingleResult();
        $persoRep = $db->getRepository('FulldonIntersaBundle:Personnalisation') ;
        $perso = $persoRep->find(1);

        $this->init['data']= $data;
        $this->init['perso']= $perso;
    }

    public function indexAction()
    {
        return $this->render('FulldonDonateurBundle:Donateur:index.html.twig',array('init'=>$this->init));
    }
}
