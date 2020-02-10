<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\DonateurBundle\Form\PndType;
use Fulldon\IntersaBundle\Entity\Account;
use Fulldon\IntersaBundle\Entity\ConfAvance;
use Fulldon\IntersaBundle\Entity\Solde;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\IntersaBundle\Form\ConfAvanceType;
use Fulldon\IntersaBundle\Form\SoldeType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Doublon;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\DonateurBundle\Form\DonateurSearchType;
use Fulldon\DonateurBundle\Entity\Pnd;
use Fulldon\IntersaBundle\Form\IntersaDonateurType;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Fulldon\IntersaBundle\Entity\TypeDon;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Vars;
use Fulldon\IntersaBundle\Entity\CourrierAttente;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Yaml\Dumper;
#payment
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use Symfony\Component\Validator\Constraints\DateTime;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class UpgradeController extends Controller
{
    private $init = array();

    /**
     * @Secure(roles="ROLE_INTERSA_N1, ROLE_ASSOC_N1")
     */

    public function indexAction()
    {
        return $this->render('FulldonIntersaBundle:Upgrade:index.html.twig');
    }

    public function confirmAction($level)
    {
        $db = $this->getDoctrine()->getManager();
        $repAccount = $db->getRepository('FulldonIntersaBundle:Account');
        $account = $repAccount->find(1);
        $request = Request::createFromGlobals();
        if ($request->getMethod() == 'POST') {
            // Sauvegarder l'adresse
            $adresse = $request->request->get('adresse');
            $zipcode = $request->request->get('zipcode');
            $ville = $request->request->get('ville');
            $pays = $request->request->get('pays');
            $account->setAdresse($adresse);
            $account->setZipcode($zipcode);
            $account->setVille($ville);
            $account->setPays($pays);
            $db->flush();
            // Redireger vers la page de paiement

        }
        switch ($level) {
            case 1 :
                $price = 10;
                $product = "Abonnement basique";
                $description = "Abonnement basique durant 1 an ";
                break;
            case 2 :
                $price = 100;
                $product = "Abonnement standard";
                $description = "Abonnement standard durant 1 an ";
                break;
            case 3 :
                $price = 400;
                $product = "Abonnement premium";
                $description = "Abonnement premium durant 1 an ";
                break;
            case 4 :
                $price = -1;
                $product = "Abonnement illimité";
                $description = "Abonnement illimité durant 1 an ";
                break;
        }
        return $this->render('FulldonIntersaBundle:Upgrade:confirm.html.twig',
            array(
                'price' => $price,
                'product' => $product,
                'desc' => $description,
                'level' => $level
            ));
    }

    public function facturesAction()
    {
        return $this->render('FulldonIntersaBundle:Upgrade:factures.html.twig');
    }

    public function confAvanceAction()
    {
        $db = $this->getDoctrine()->getManager();
        $confAvanceRep = $db->getRepository('FulldonIntersaBundle:ConfAvance');
        $conf = $confAvanceRep->find(1);
        if (!is_object($conf)) {
            $conf = new ConfAvance();
        }
        $form = $this->createForm(new ConfAvanceType(), $conf);
        $request = Request::createFromGlobals();
        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $db->persist($conf);
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'Mise à jour de la configuration avec succès');
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'Problème survenu lors de la mise à jour de la configuration');
            }
        }
        return $this->render('FulldonIntersaBundle:Upgrade:conf_avance.html.twig', array('form' => $form->createView()));
    }

    public function shopAction()
    {
        $db = $this->getDoctrine()->getManager();
        $solde = new Solde();
        $form = $this->createForm(new SoldeType(), $solde);
        $request = Request::createFromGlobals();
        if ($request->getMethod() == 'POST') {
            // Sauvegarder l'adresse

            // Redireger vers la page de paiement

        }
        return $this->render('FulldonIntersaBundle:Upgrade:shop.html.twig', array('form' => $form->createView()));
    }

    public function nextStepAction($secret)
    {
        $db = $this->getDoctrine()->getManager();
        $request = Request::createFromGlobals();
        $accountRep = $db->getRepository('FulldonIntersaBundle:Account');
        $account = $accountRep->findOneBy(array('secretCode' => $secret, 'used' => false, 'id' => 1));
        if (is_object($account)) {
            if ($request->getMethod() == 'POST') {
                $login = $request->request->get('login');
                $plain_password = $request->request->get('plain_password');
                $confirm_password = $request->request->get('confirm_password');
                if (!empty($login) && ($plain_password == $confirm_password) && $this->isNewUser($login)) {
                    $user = new User();

                    $repRole = $db->getRepository('FulldonSecurityBundle:Role');
                    $role = $repRole->findOneBy(array('role' => 'ROLE_TRIAL'));
                    $user->addRole($role);
                    $user->setIsActive(true);
                    $user->setSalt(uniqid(mt_rand()));
                    // Set encrypted password
                    $encoder = $this->container->get('security.encoder_factory')
                        ->getEncoder($user);
                    $password = $encoder->encodePassword($plain_password, $user->getSalt());

                    $user->setUsername($login);
                    $user->setPassword($password);
                    $db->persist($user);
                    $account->setUsed(1);
                    $db->flush();
                    $this->get('session')->getFlashBag()->add('info', 'Création de l\'utilisateur avec succès ');
                    return $this->redirect($this->generateUrl('fulldon_intersa_homepage'));
                } else {
                    if ($plain_password != $confirm_password) {
                        $this->get('session')->getFlashBag()->add('erreur', 'les deux mots de passe fournis ne sont pas identiques');
                    } else {
                        $this->get('session')->getFlashBag()->add('erreur', 'Le nom d\'utilisateur choisi est déjà prit');
                    }
                    // message + redirect

                }


            }
            return $this->render('FulldonSecurityBundle:Upgrade:next_step.html.twig', array('secret' => $secret));
        } else {
            return $this->render('FulldonSecurityBundle:Upgrade:next_step_refused.html.twig');
        }

    }

    public function isNewUser($login)
    {
        // Check if it exists first
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User');
        $query = $repUsers->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->where("u.username = :username ")
            ->setParameter('username', $login)
            ->getQuery();

        if ($query->getSingleScalarResult() > 0) {
            return false;
        }

        return true;
    }
}
