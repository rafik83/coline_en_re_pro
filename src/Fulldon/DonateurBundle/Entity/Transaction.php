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

use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\TransactionRepository")
 * @ORM\Table(name="transaction")
 */
class Transaction
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected  $id;
    /**
     * @ORM\ManyToOne(targetEntity="StatutPaiement")
     * @ORM\JoinColumn(nullable=false, name="status_id" )
     */
    protected $statut;
    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;
    /**
     * @ORM\OneToOne(targetEntity="Cheque", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true,name="cheque_id");
     */
    protected $cheque;
    /**
     * @ORM\OneToOne(targetEntity="Virement", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="virement_id");
     */
    protected $virement;
    /**
     * @ORM\OneToOne(targetEntity="JMS\Payment\CoreBundle\Entity\PaymentInstruction")
     * @ORM\JoinColumn(nullable=true);
     */
    private $paymentInstruction;



    public function __construct()
    {
        $this->createdAt = new \Datetime();
    }

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Transaction
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }


    /**
     * Set cheque
     *
     * @param \Fulldon\DonateurBundle\Entity\Cheque $cheque
     * @return Transaction
     */
    public function setCheque(\Fulldon\DonateurBundle\Entity\Cheque $cheque = null)
    {
        $this->cheque = $cheque;

        return $this;
    }

    /**
     * Get cheque
     *
     * @return \Fulldon\DonateurBundle\Entity\Cheque 
     */
    public function getCheque()
    {
        return $this->cheque;
    }

    /**
     * Set statut
     *
     * @param \Fulldon\DonateurBundle\Entity\StatutPaiement $statut
     * @return Transaction
     */
    public function setStatut(\Fulldon\DonateurBundle\Entity\StatutPaiement $statut)
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut
     *
     * @return \Fulldon\DonateurBundle\Entity\StatutPaiement 
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Set virement
     *
     * @param \Fulldon\DonateurBundle\Entity\Virement $virement
     * @return Transaction
     */
    public function setVirement(\Fulldon\DonateurBundle\Entity\Virement $virement = null)
    {
        $this->virement = $virement;

        return $this;
    }

    /**
     * Get virement
     *
     * @return \Fulldon\DonateurBundle\Entity\Virement 
     */
    public function getVirement()
    {
        return $this->virement;
    }

    /**
     * Set paymentInstruction
     *
     * @param \JMS\Payment\CoreBundle\Entity\PaymentInstruction $paymentInstruction
     * @return Transaction
     */
    public function setPaymentInstruction(\JMS\Payment\CoreBundle\Entity\PaymentInstruction $paymentInstruction = null)
    {
        $this->paymentInstruction = $paymentInstruction;

        return $this;
    }

    /**
     * Get paymentInstruction
     *
     * @return \JMS\Payment\CoreBundle\Entity\PaymentInstruction 
     */
    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }
}
