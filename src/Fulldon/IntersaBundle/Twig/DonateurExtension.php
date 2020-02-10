<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulldon\IntersaBundle\Twig;
use Doctrine\ORM\EntityManager;

class DonateurExtension extends \Twig_Extension
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;

    }
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('donateur', array($this, 'donateurFilter')),
        );
    }

    public function donateurFilter($id_user)
    {

        $repDonateur = $this->em->getRepository('FulldonDonateurBundle:Donateur');
        return $repDonateur->findOneBy(array('user'=>$this->em->getRepository('FulldonSecurityBundle:User')->find($id_user)));
    }

    public function getName()
    {
        return 'fulldon_extension';
    }
}