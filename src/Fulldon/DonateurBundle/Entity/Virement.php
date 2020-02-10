<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\DonateurBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Table(name="virement")
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\VilleRepository")
 */
class Virement
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="iban", type="string", length=100, nullable=true)
     */
    private $iban;
    /**
     * @ORM\Column(name="bic", type="string", length=100, nullable=true)
     */
    private $bic;
    /**
     * @ORM\Column(name="nom_banque", type="string", length=255, nullable=true)
     */
    protected $nomBanque;

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
     * Set iban
     *
     * @param string $iban
     * @return Virement
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban
     *
     * @return string 
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set bic
     *
     * @param string $bic
     * @return Virement
     */
    public function setBic($bic)
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Get bic
     *
     * @return string 
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Set nomBanque
     *
     * @param string $nomBanque
     * @return Virement
     */
    public function setNomBanque($nomBanque)
    {
        $this->nomBanque = $nomBanque;

        return $this;
    }

    /**
     * Get nomBanque
     *
     * @return string 
     */
    public function getNomBanque()
    {
        return $this->nomBanque;
    }
}
