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

use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Rf;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Intl\Intl;

class GlobalVars extends ContainerAware{


    public function __construct(EntityManager $em)
    {
        $this->em = $em;

    }
    public function getParam($param) {
        //chercher dans la base de donnée , si c'est pas trouvé,
        $corresp = array(

            'seuil_rf'=>'getSeuilRf',
            'seuil_pnd'=>'getSeuilPnd',
            'assoc_iban'=>'getAssocIban',
            'assoc_bic'=>'getAssocBic',
            'assoc_sepa'=>'getAssocSepa',
            'assoc_banque_name' => 'getAssocBanqueName',
            'prelevement_jour'=>'getJourPrelevement',
            'subscription_cb'=>'getSubscriptionCb',
            'subscription_online'=>'getSubscriptionOline',
            'twitter_id'=>'getTwitterId',
            'facebook_id'=>'getFacebookId',
            'google_analytics'=>'getGoogleAnalytics',
            'max_articles_on_listepage' => 'getMaxPerPage'
        );
        //Variable initial
        $confAvanceRep = $this->em->getRepository('FulldonIntersaBundle:ConfAvance') ;
        $conf = $confAvanceRep->find(1);
        if(is_object($conf)) {
            $var = $conf->$corresp[$param]();
            if(isset($var) && !empty($var)) {
                return $var;
            }
        }
        return $this->container->getParameter($param);
    }
}