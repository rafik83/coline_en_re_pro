<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\IntersaBundle\Service;
use Doctrine\ORM\EntityManager;
use Fulldon\IntersaBundle\Entity\BillReport;
use Fulldon\IntersaBundle\Entity\MarketingCronTask;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\DonateurBundle\Entity\MotifAbo;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Prelevement;
use Fulldon\DonateurBundle\Entity\Don;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\DependencyInjection\ContainerAware;

class StatNote extends ContainerAware{


    public function __construct(EntityManager $em)
    {
        $this->em = $em;

    }
    public function getPianiste($code)
    {

        $repCause = $this->em->getRepository('FulldonDonateurBundle:Cause');
        $cause = $repCause->findOneBy(array("code" => $code));
        $artiste = $cause->getArtiste();
        $myartiste = '-';
        if(is_object($artiste)) {
            $myartiste = $artiste->getPrenom().' '.$artiste->getNom();
        }


        return $myartiste;

    }
    public function getDate($code)
    {
        $repCause = $this->em->getRepository('FulldonDonateurBundle:Cause');
        $cause = $repCause->findOneBy(array("code" => $code));

        $exDate  = explode('_',$cause->getCode());
        if(count(explode('/',$exDate[0])) == 3) {
            return $exDate[0];
        } else {
            return 'Non définie';
        }

    }
    public function getHote($code)
    {
        $repCause = $this->em->getRepository('FulldonDonateurBundle:Cause');
        $cause = $repCause->findOneBy(array("code" => $code));

        $hote = $cause->getHote();
        $myhote = '-';
        if(is_object($hote)) {
            $myhote = $hote->getPrenom().' '.$hote->getNom();
        }


        return $myhote;
    }
    public function getMoyenne($code)
    {
        $repCause = $this->em->getRepository('FulldonDonateurBundle:Cause');
        $cause = $repCause->findOneBy(array("code" => $code));

        $repDon = $this->em->getRepository('FulldonDonateurBundle:Don');
        $repNote = $this->em->getRepository('CustomFulldonExtDonateurBundle:Note');
        $dons = $repDon->findBy(array("cause" => $cause));
        $gmoyenne = 0;
        $gsomme = 0;
        $gcpt = 0;


        foreach($dons  as $don) {
            $psomme = 0;
            $pmoyenne = 0;
            $ecpt = 0;
            $pcpt = 0;
            $notes = $repNote->findOneBy(array('don' => $don));

            if(is_object($notes)) {
            for($i = 1 ; $i <= 10; $i++) {
                $method = "getQ{$i}";
                $q{$i} = $notes->$method();
                if($q{$i} > 0) {
                    $psomme += $q{$i};
                    $pcpt++;
                }
                if($q{$i} == 10) {
                    $ecpt++;
                }
            }
            // calcul de la moyenne
            if($psomme > 0 && $pcpt > 0)  {
                $pmoyenne = $psomme/$pcpt;
                if($ecpt != 10) {
                    $gcpt++;
                    $gsomme += $pmoyenne;

                }
            }
            }

        }

        if($gsomme > 0 && $gcpt > 0)  {
            $gmoyenne = $gsomme/$gcpt;
        } else {
            $gmoyenne = 0;
        }

        if($gmoyenne > 0 ) {
            return round($gmoyenne,2);
        } else {
            return 'Note non fournie';
        }
    }

    public function getMoyenneWithTen($code)
    {
        $repCause = $this->em->getRepository('FulldonDonateurBundle:Cause');
        $cause = $repCause->findOneBy(array("code" => $code));

        $repDon = $this->em->getRepository('FulldonDonateurBundle:Don');
        $repNote = $this->em->getRepository('CustomFulldonExtDonateurBundle:Note');
        $dons = $repDon->findBy(array("cause" => $cause));
        $gmoyenne = 0;
        $gsomme = 0;
        $gcpt = 0;



        foreach($dons  as $don) {
            $psomme = 0;
            $pmoyenne = 0;
            $ecpt = 0;
            $pcpt = 0;
            $notes = $repNote->findOneBy(array('don' => $don));

            if(is_object($notes)) {
                for($i = 1 ; $i <= 10; $i++) {
                    $method = "getQ{$i}";
                    $q{$i} = $notes->$method();
                    if($q{$i} > 0) {

                        $psomme += $q{$i};
                        $pcpt++;
                    }

                }
                // calcul de la moyenne
                if($psomme > 0 && $pcpt > 0)  {
                    $pmoyenne = $psomme/$pcpt;
                        $gcpt++;
                        $gsomme += $pmoyenne;


                }
            }

        }

        if($gsomme > 0 && $gcpt > 0)  {
            $gmoyenne = $gsomme/$gcpt;
        } else {
            $gmoyenne = 0;
        }

        if($gmoyenne > 0 ) {
            return round($gmoyenne,2);
        } else {
            return 'Note non fournie';
        }
    }
}