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
use Symfony\Component\Intl\Intl;

class PaysExtension extends \Twig_Extension
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;

    }
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('getcountryname', array($this, 'paysFilter')),
        );
    }

    public function paysFilter($code)
    {
        $country = null;
        if(!is_null($code))
            $country = Intl::getRegionBundle()->getCountryName($code);
        return $country;
    }

    public function getName()
    {
        return 'fulldon_extension_pays';
    }
}