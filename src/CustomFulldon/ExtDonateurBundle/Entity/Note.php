<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace CustomFulldon\ExtDonateurBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="CustomFulldon\ExtDonateurBundle\Entity\NoteRepository")
 * @ORM\Table(name="note")
 *
 */
class Note
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected  $id;
    /**
     * @ORM\Column(name="is_note", type="boolean", nullable=false)
     */
    protected $isNote;
    /**
     * @ORM\Column(name="q_1", type="integer", nullable=true)
     */
    protected $q1;
    /**
     * @ORM\Column(name="q_2", type="integer", nullable=true)
     */
    protected $q2;
    /**
     * @ORM\Column(name="q_3", type="integer", nullable=true)
     */
    protected $q3;
    /**
     * @ORM\Column(name="q_4", type="integer", nullable=true)
     */
    protected $q4;
    /**
     * @ORM\Column(name="q_5", type="integer", nullable=true)
     */
    protected $q5;
    /**
     * @ORM\Column(name="q_6", type="integer", nullable=true)
     */
    protected $q6;
    /**
     * @ORM\Column(name="q_7", type="integer", nullable=true)
     */
    protected $q7;
    /**
     * @ORM\Column(name="q_8", type="integer", nullable=true)
     */
    protected $q8;
    /**
     * @ORM\Column(name="q_9", type="integer", nullable=true)
     */
    protected $q9;
    /**
     * @ORM\Column(name="q_10", type="integer", nullable=true)
     */
    protected $q10;

    /**
     * @ORM\OneToOne(targetEntity="Fulldon\DonateurBundle\Entity\Don")
     * @ORM\JoinColumn(nullable=true,name="don_id");
     */
    protected $don;


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
     * Set q1
     *
     * @param integer $q1
     * @return Note
     */
    public function setQ1($q1)
    {
        $this->q1 = $q1;

        return $this;
    }

    /**
     * Get q1
     *
     * @return integer 
     */
    public function getQ1()
    {
        return $this->q1;
    }

    /**
     * Set q2
     *
     * @param integer $q2
     * @return Note
     */
    public function setQ2($q2)
    {
        $this->q2 = $q2;

        return $this;
    }

    /**
     * Get q2
     *
     * @return integer 
     */
    public function getQ2()
    {
        return $this->q2;
    }

    /**
     * Set q3
     *
     * @param integer $q3
     * @return Note
     */
    public function setQ3($q3)
    {
        $this->q3 = $q3;

        return $this;
    }

    /**
     * Get q3
     *
     * @return integer 
     */
    public function getQ3()
    {
        return $this->q3;
    }

    /**
     * Set q4
     *
     * @param integer $q4
     * @return Note
     */
    public function setQ4($q4)
    {
        $this->q4 = $q4;

        return $this;
    }

    /**
     * Get q4
     *
     * @return integer 
     */
    public function getQ4()
    {
        return $this->q4;
    }

    /**
     * Set q5
     *
     * @param integer $q5
     * @return Note
     */
    public function setQ5($q5)
    {
        $this->q5 = $q5;

        return $this;
    }

    /**
     * Get q5
     *
     * @return integer 
     */
    public function getQ5()
    {
        return $this->q5;
    }

    /**
     * Set q6
     *
     * @param integer $q6
     * @return Note
     */
    public function setQ6($q6)
    {
        $this->q6 = $q6;

        return $this;
    }

    /**
     * Get q6
     *
     * @return integer 
     */
    public function getQ6()
    {
        return $this->q6;
    }

    /**
     * Set q7
     *
     * @param integer $q7
     * @return Note
     */
    public function setQ7($q7)
    {
        $this->q7 = $q7;

        return $this;
    }

    /**
     * Get q7
     *
     * @return integer 
     */
    public function getQ7()
    {
        return $this->q7;
    }

    /**
     * Set q8
     *
     * @param integer $q8
     * @return Note
     */
    public function setQ8($q8)
    {
        $this->q8 = $q8;

        return $this;
    }

    /**
     * Get q8
     *
     * @return integer 
     */
    public function getQ8()
    {
        return $this->q8;
    }

    /**
     * Set q9
     *
     * @param integer $q9
     * @return Note
     */
    public function setQ9($q9)
    {
        $this->q9 = $q9;

        return $this;
    }

    /**
     * Get q9
     *
     * @return integer 
     */
    public function getQ9()
    {
        return $this->q9;
    }

    /**
     * Set q10
     *
     * @param integer $q10
     * @return Note
     */
    public function setQ10($q10)
    {
        $this->q10 = $q10;

        return $this;
    }

    /**
     * Get q10
     *
     * @return integer 
     */
    public function getQ10()
    {
        return $this->q10;
    }

 

    /**
     * Set isNote
     *
     * @param boolean $isNote
     * @return Note
     */
    public function setIsNote($isNote)
    {
        $this->isNote = $isNote;

        return $this;
    }

    /**
     * Get isNote
     *
     * @return boolean 
     */
    public function getIsNote()
    {
        return $this->isNote;
    }

    /**
     * Set don
     *
     * @param \Fulldon\DonateurBundle\Entity\Don $don
     * @return Note
     */
    public function setDon(\Fulldon\DonateurBundle\Entity\Don $don = null)
    {
        $this->don = $don;

        return $this;
    }

    /**
     * Get don
     *
     * @return \Fulldon\DonateurBundle\Entity\Don 
     */
    public function getDon()
    {
        return $this->don;
    }
}
