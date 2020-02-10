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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="biblio_image")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\BiblioImageRepository")
 */
class BiblioImage
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(name="image", type="string", length=255,nullable=false)
     * @Assert\File(
     *     maxSize = "2M",
     *     mimeTypes = {"image/jpeg", "image/gif", "image/png"},
     *     mimeTypesMessage = "Le fichier choisi ne correspond pas à un fichier valide",
     *     notFoundMessage = "Le fichier n'a pas été trouvé sur le disque",
     *     uploadErrorMessage = "Erreur dans l'upload du fichier"
     * )
     *
     */
    private $image;



    public function getFullImagePath() {
        return null === $this->image ? null : $this->getUploadRootDir().$this->image;
    }
    public function getFullHeaderPagePath() {
        return null === $this->headerPage ? null : $this->getUploadRootDir().$this->headerPage;
    }
    public function getFullFondPagePath() {
        return null === $this->fondPage ? null : $this->getUploadRootDir().$this->fondPage;
    }
    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir().$this->getId()."/";
    }
    public function getTmpUploadRootDir() {
        return __DIR__ .'/../../../../web/upload/';
    }
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function uploadImage() {
        if(null === $this->image || !is_object($this->image)) {
            return ;
        }
// générer un nom aléatoire et essayer de deviner l'extension (plus sécurisé)
        if(!$this->id) {
            $this->image->move($this->getTmpUploadRootDir(), $this->image->getClientOriginalName());
        } else {
            $this->image->move($this->getUploadRootDir(), $this->image->getClientOriginalName());
        }
        $this->setimage($this->image->getClientOriginalName());
    }

    /**
     * @ORM\PostPersist
     */
    public function moveImage()
    {
        if(null === $this->image) {
            return;
        }
        if(!is_dir($this->getUploadRootDir())){
            mkdir($this->getUploadRootDir());
        }
        copy($this->getTmpUploadRootDir().$this->image, $this->getFullImagePath());
        unlink($this->getTmpUploadRootDir().$this->image);
    }

    /**
     * @ORM\PreRemove()
     */
    public function removeImage()
    {
        @unlink($this->getFullImagePath());
        @rmdir($this->getUploadRootDir());
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
     * Set image
     *
     * @param string $image
     * @return BiblioImage
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }
}
