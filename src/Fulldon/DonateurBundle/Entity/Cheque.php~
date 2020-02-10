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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\ChequeRepository")
 * @ORM\Table(name="cheque", indexes={@ORM\Index(name="search_cheque_idx", columns={"num_cheque"})})
 *
 */
class Cheque
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected  $id;
    /**
     * @ORM\Column(name="num_cheque", type="string", length=255, nullable=true)
     */
    protected $numeroCheque;
    /**
     * @ORM\Column(name="date_cheque", type="date", nullable=true)
     */
    protected $dateCheque;
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
     * Set numeroCheque
     *
     * @param string $numeroCheque
     * @return Cheque
     */
    public function setNumeroCheque($numeroCheque)
    {
        $this->numeroCheque = $numeroCheque;

        return $this;
    }

    /**
     * Get numeroCheque
     *
     * @return string 
     */
    public function getNumeroCheque()
    {
        return $this->numeroCheque;
    }

    /**
     * Set dateCheque
     *
     * @param \DateTime $dateCheque
     * @return Cheque
     */
    public function setDateCheque($dateCheque)
    {
        $this->dateCheque = $dateCheque;

        return $this;
    }

    /**
     * Get dateCheque
     *
     * @return \DateTime 
     */
    public function getDateCheque()
    {
        return $this->dateCheque;
    }

    /**
     * Set nomBanque
     *
     * @param string $nomBanque
     * @return Cheque
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
