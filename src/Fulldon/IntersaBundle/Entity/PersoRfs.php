<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulldon\IntersaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="perso_rfs")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\PersoRfsRepository")
 */
class PersoRfs
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="template", type="text", nullable=true)
     */
    private $template;
    /**
     * @ORM\Column(name="code", type="string", length=255, nullable=false)
     */
    private $code;

    /**
     * @ORM\ManyToOne(targetEntity="Entity")
     * @ORM\JoinColumn( name="entity_id");
     */
    private $entity;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }



    /**
     * Set code
     *
     * @param string $code
     * @return PersoRfs
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set entity
     *
     * @param \Fulldon\IntersaBundle\Entity\Entity $entity
     * @return PersoRfs
     */
    public function setEntity(\Fulldon\IntersaBundle\Entity\Entity $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return \Fulldon\IntersaBundle\Entity\Entity 
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return PersoRfs
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
