<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\IntersaBundle\Service;

use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Rf;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Intl\Intl;
use Hip\MandrillBundle\Message;
use Hip\MandrillBundle\Dispatcher;
use Fulldon\IntersaBundle\Entity\StatCom;
use Fulldon\IntersaBundle\Vars\Email;

class EmailServices extends ContainerAware{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    public function sendEmail($emails,$html,$objet, $tag, $file, $ext)
    {

//// Mandrill
//        $dispatcher = $this->container->get('hip_mandrill.dispatcher');
//        $filename = $this->container->getParameter('folder_app').'_'. date('d_m_Y').'_'.uniqid(mt_rand());
//        $message = new Message();
//        $message = $message
//            ->setFromEmail($this->container->getParameter('mailer_sender'))
//            ->setFromName($this->container->getParameter('assoc_name'));
//
//        foreach($emails as $email) {
//            $message = $message
//                ->addTo($email);
//        }
//
//        $message = $message
//            ->setSubject($objet)
//            ->setHtml($html)
//            ->setSubaccount($this->container->getParameter('hip_mandrill.default.subaccount'));
//        if(!is_null($file)) {
//            $attachment = file_get_contents($file);
//            $attachment_encoded = base64_encode($attachment);
//            $message = $message
//                ->addAttachmentFromPath($file,'application/'.$ext,$filename.'.'.$ext);
//        }
//        $message = $message
//            ->addTag($tag);
//
//
//        $dispatcher->send($message);
//
//        unset($message);

        //Swiftmail


        $mail = \Swift_Message::newInstance()
            ->setSubject($objet)
            ->setFrom($this->container->getParameter('mailer_sender'))
            ->setTo($emails)
            ->setBody($html)
            ->setContentType('text/html');

        if(!is_null($file)) {
            $attachment = file_get_contents($file);
            $filename = $this->container->getParameter('folder_app').'_'. date('d_m_Y').'_'.uniqid(mt_rand());
            if($ext == 'xslx') {
                $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            } else {
                $mimetype = 'application/'.$ext;
            }
            $mail->attach(\Swift_Attachment::newInstance($attachment,$filename.'.'.$ext,$mimetype));
        }
        $this->container->get('mailer')->getTransport()->start();
        $this->container->get('mailer')->send($mail);
        $this->container->get('mailer')->getTransport()->stop();

        // stats
        $statRep  = $this->em->getRepository('FulldonIntersaBundle:StatCom');
        $statobjByTag = $statRep->findOneBy(array('tag'=> $tag ));
        if(is_object($statobjByTag)) {
            $statobjByTag->setNbEmail($statobjByTag->getNbEmail()+1);
            $this->em->flush();
        } else {
            $statobjByTag = new StatCom();
            $statobjByTag->setNbEmail(1);
            $statobjByTag->setNbSms(0);
            $statobjByTag->setTag($tag);
            $this->em->persist($statobjByTag);
            $this->em->flush();
        }
    }
    public function sendNewAccount($email,$html)
    {
        $tag = $this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_notification');
        $objet = Email::_OBJET_NEW_ACCOUNT_;
        $this->sendEmail(array($email), $html,$objet,$tag,null,null);

    }
    public function sendNewPassword($email,$html)
    {
        $tag = $this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_new_password');
        $objet = Email::_OBJET_MDP_FORGOT_;
        $this->sendEmail(array($email), $html,$objet,$tag,null,null);

    }
    public function sendInfosDonation($emails,$html)
    {
        $tag = $this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_new_password');
        $objet ='['.$this->container->getParameter('assoc_name').']'.'Nouveau don en ligne !';
        $this->sendEmail($emails, $html,$objet,$tag,null,null);

    }
    public function sendConfirmDonation($email, $html)
    {
        $tag = $this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_confirm_don');
        $objet = Email::_OBJET_CONFIRM_DON_.' '.$this->container->getParameter('assoc_name');
        $this->sendEmail(array($email), $html,$objet,$tag,null,null);
    }
    public function sendRf($email, $html, $file)
    {
        $tag = $this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_rf');
        $objet = Email::_OBJET_RF_;
        $this->sendEmail(array($email), $html,$objet,$tag,$file,'pdf');
    }

    public function sendEmarketing($email,$message_email,$objet, $tag)
    {
        $this->sendEmail(array($email), $message_email,$objet,$tag,null,null);

    }
    public function sendAutomatique($emails, $html, $file, $objet,$ext='pdf')
    {
        $tag = $this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_rf');
//        var_dump($tag);
        $this->sendEmail($emails, $html,$objet,$tag,$file,$ext);
    }

}