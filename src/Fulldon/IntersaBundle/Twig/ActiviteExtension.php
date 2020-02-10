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

class ActiviteExtension extends \Twig_Extension
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('get_activite', array($this, 'activiteFilter')),
            new \Twig_SimpleFilter('test_val', array($this, 'testValFilter')),
        );
    }

    public function activiteFilter($id)
    {
        $repCause = $this->em->getRepository('FulldonDonateurBundle:Cause');
        return $repCause->find($id);
    }

    /**
     * @param $val
     * @return int
     * Return 0 if the value is null
     */
    public function testValFilter($val)
    {
        if ( is_null( $val )) {
            return 0;
        } else {
            return $val;
        }
    }


    public function getName()
    {
        return 'fulldon_extension_activite';
    }
}