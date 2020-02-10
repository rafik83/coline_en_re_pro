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

class StatNoteExtension extends \Twig_Extension
{
    private $container;
    public function __construct(EntityManager $em, $container)
    {
        $this->em = $em;
        $this->container = $container;
    }
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('get_pianiste', array($this, 'getPianiste')),
            new \Twig_SimpleFilter('get_date_concert', array($this, 'getDate')),
            new \Twig_SimpleFilter('get_hote', array($this, 'getHote')),
            new \Twig_SimpleFilter('get_moyenne', array($this, 'getMoyenne')),
            new \Twig_SimpleFilter('get_moyenne_ten', array($this, 'getMoyenneWithTen')),
        );
    }

    public function getPianiste($code)
    {
        return $this->container->get('fulldon.notes_servies')->getPianiste($code);
    }
    public function getDate($code)
    {
        return $this->container->get('fulldon.notes_servies')->getDate($code);
    }
    public function getHote($code)
    {
        return $this->container->get('fulldon.notes_servies')->getHote($code);
    }
    public function getMoyenne($code)
    {
        return $this->container->get('fulldon.notes_servies')->getMoyenne($code);
    }
    public function getMoyenneWithTen($code)
    {
        return $this->container->get('fulldon.notes_servies')->getMoyenneWithTen($code);
    }
    public function getName()
    {
        return 'fulldon_extension_notes';
    }
}