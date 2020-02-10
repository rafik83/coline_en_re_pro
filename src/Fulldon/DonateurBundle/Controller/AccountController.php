<?php


namespace Fulldon\DonateurBundle\Controller;
use Doctrine\Tests\Common\DataFixtures\TestEntity\Role;
use Fulldon\DonateurBundle\Form\DonateurType;
use Fulldon\DonateurBundle\Form\DonateurEditType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\SecurityBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\DonateurBundle\Entity\Doublon;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Fulldon\IntersaBundle\Vars;



class AccountController extends Controller{

    const _PAYPAL_ = 3;
    const _CHEQUE_= 2;
    const _VIREMENT_ = 4;
    const _STATUT_ATTENTE_ = 1;
    const _STATUT_TRAITEMENT_PAIEMENT_ = 2;
    const _STATUT_DON_VALIDE_ = 3;
    const _STATUT_DON_ANNULE_ = 4;
    private $init = array();

    public function preExecute() {
        if($this->container->getParameter('donor_space') == 0) {
//            die('Espace donateur non disponible !');
        }
        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $data = array();
        if(is_object($user)) {
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
        $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);
        $data = $this->getDoctrine()
            ->getRepository('FulldonDonateurBundle:Rf')
            ->createQueryBuilder('r')
            ->join('r.don','d')
            ->join('d.transaction','t')
            ->join('t.statut','s')
            ->select('count(d.id) as cpt')
            ->where('d.user = :id and r.don is not null and t.statut =:statut and r.sended = 0')
            ->setParameter('id', $user->getId())
            ->setParameter('statut', $statut )
            ->getQuery()->getSingleResult();
        }

        $persoRep = $db->getRepository('FulldonIntersaBundle:Personnalisation') ;
        $perso = $persoRep->find(1);

        $this->init['data']= $data;
        $this->init['perso']= $perso;
    }

    public function registerAction()
    {
        $data = array();
        // Si le visiteur est déjà identifié, on le redirige vers l'accueil
        if ($this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fulldon_donateur_accueil'));
        }

        $donateur = new Donateur;
        $user = new User;
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $receptionRep = $db->getRepository('FulldonDonateurBundle:ReceptionMode');
        foreach(array(1,2,3) as $a) {
            $donateur->addReceptionMode($receptionRep->find($a));
        }

        // format all the form
        $form = $this->createForm(new DonateurType(), $donateur, array(
            'cascade_validation' => true));
        $errors = array();
        // On récupère la requête
        $request = $this->getRequest();
        $plain_password = null ;


        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $allow_rf = $request->get('allow_rf');
            $data['allow_rf']=$allow_rf;
            $form->bind($request);
            // On vérifie que les valeurs entrées sont correctes
            if($donateur->getUser()->getPassword() != $request->get('confirm_pass')) {

                $errors['error_user_pass'] = ' Les deux mots de passe ne se ressemblent pas ! ' ;
            }
            if ($form->isValid() && count($errors) == 0) {
                    // On enregistre notre objet $article dans la base de données
                    $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                    $donateur->getUser()->setIsActive(true);
                    $plain_password = $donateur->getUser()->getPassword();

                    $repRole = $db->getRepository('FulldonSecurityBundle:Role');
                    $role_donateur = $repRole->findOneBy(array('role' => 'ROLE_DONATEUR'));
                    $donateur->getUser()->addRole($role_donateur);
                    // Set encrypted password
                    $encoder = $this->container->get('security.encoder_factory')
                        ->getEncoder($donateur->getUser());
                    $password = $encoder->encodePassword($donateur->getUser()->getPassword(), $donateur->getUser()->getSalt());
                    $donateur->getUser()->setPassword($password);
                    $donateur->setRemoved(false);

                    //Gestion des RFs
                    if($allow_rf ==  "true") {
                        $donateur->setAllowRf(true);
                    } else {
                        $donateur->setAllowRf(false);
                    }

                    //Fin de la gestion des RFs

                    $refId =  $this->get('fulldon.intersa.global')->getUniqueRefDonateur();
                    $donateur->setRefDonateur($refId);


                    //Gestion des doublons
                    $percent = 0;
                    $pid = 0;
                    $pdonateur = $donateurRep->findOneBy(array('nom'=>$donateur->getNom(),'prenom'=>$donateur->getPrenom(),'removed'=>false));
                    if($pdonateur) {
                        $percent += 60;
                        $pid = $pdonateur->getId();
                        $pdonateur2 = $donateurRep->findOneBy(array('adresse3'=>$donateur->getAdresse3(),'id'=>$pid));
                        if($pdonateur2) {
                            $percent += 30;
                        }
                        //Création d'un doublon
                        $doublon = new Doublon();
                        $doublon->setDone(false);
                        $doublon->setDonateur1($pid);


                        //Sauvegarder le nouveau donateur
                        $db->persist($donateur);
                        $db->flush();
                        //required to  retreive the donateur ID

                        $doublon->setDonateur2($donateur->getId());
                        $doublon->setPourcentage($percent);
                        $db->persist($doublon);
                        $db->flush();
                        //$current_user = $this->get('security.context')->getToken()->getUser();
                        $event = HistoryStatEvent::constr1($pdonateur->getUser(),StatVar::_STAT_TYPE_DOUBLON_CREATED_);
                        $dispatcher =$this->get('event_dispatcher');
                        $dispatcher->dispatch( StatVar::CREATE  , $event);

                    } else {
                        $db->persist($donateur);
                        $db->flush();
                    }

                    //Log
                    $msg =$this->get('fulldon.intersa.global')->getAddMsgLog($donateur,'DONATEUR');
                    $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DONATEUR_);
                    // Log the user creation
                    $event =  HistoryLogEvent::constr1($donateur->getUser(),$donateur,$typeLog,$msg);
                    $dispatcher =$this->get('event_dispatcher');
                    $dispatcher->dispatch( LogVar::CREATE  , $event);

                    //Génération du de l'original.
                    $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->renderView(
                            'FulldonIntersaBundle:Donateurs:pdf/identifiants.html.twig',
                            array(
                                'login' => $donateur->getUser()->getUsername(),
                                'password' => $plain_password,
                                'donateur' => $donateur,
                                'init'=>$this->init,

                            )
                        ),
                        '/'.$this->container->getParameter('folder_app').'/users/donateur_fiche_' . $donateur->getId() . '.pdf'
                    );
                    // Send the email
                    $is_email = false;
                    $is_sms = false;
                    //Traitement des modes de récéption
                    $modes = $donateur->getReceptionMode();
                    foreach($modes as $mode) {
                        switch($mode->getCode())
                        {
                            case Vars\DonVars::_COM_COURRIER_ :
                                $is_courrier = true;
                                break;
                            case Vars\DonVars::_COM_EMAIL_:
                                $is_email = true;
                                break;
                            case Vars\DonVars::_COM_SMS_:
                                $is_sms = true;
                                break;
                        }
                    }
                    $email = $donateur->getEmail();
                    // sending emails through mandrill
                    if(isset($email) && !empty($email)) {
                    $html =  $this->renderView( 'FulldonIntersaBundle:Email:send_identifiants.html.twig', array(
                        'login' => $donateur->getUser()->getUsername(),
                        'password' => $plain_password,
                        'donateur' => $donateur,
                        'init'=>$this->init,
                    ));
                    $this->get('fulldon.intersa.email_servies')->sendNewAccount($email,$html);
                    }
                    // On définit un message flash
                    $this->get('session')->getFlashBag()->add('info', 'Votre compte est crée avec succès ');

                    // On redirige vers la page de visualisation de l'article nouvellement créé
                    return $this->redirect($this->generateUrl('login_donateur'));

            } else {
                    // dans le cas de la non validité des informations on va stoquer les informations sur la variable form.error
                    foreach($errors as $key => $error) {
                        $this->get('session')->getFlashBag()->add($key, $error);
                    }
            }
        }
        // À ce stade :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

        return $this->render('FulldonDonateurBundle:Account:register.html.twig', array('form' => $form->createView(), 'init'=>$this->init,'data'=>$data));
    }
    public function editAction()
    {
        $data = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur') ;
        $donateur = $donateurRep->findOneBy(array('user'=>$user->getId()));
        $form = $this->createForm(new DonateurEditType(), $donateur, array(
            'cascade_validation' => true));
        $errors = array();
        // On récupère la requête
        $request = $this->getRequest();

        if($donateur->getAllowRf()) {
            $val ="true";
        } else {
            $val ="false";
        }
        $data['allow_rf']=$val;
        $data['curville'] = $donateur->getVille();

        $oldPassword = $donateur->getUser()->getPassword();

        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire

            $form->bind($request);
            $allow_rf = $request->get('allow_rf');
            $data['allow_rf']=$allow_rf;

            // On vérifie que les valeurs entrées sont correctes
            if($donateur->getUser()->getPassword() != $request->get('confirm_pass')) {

                $errors['error_user_pass'] = ' Les deux mots de passe ne se ressemblent pas ! ' ;
            }
            if ($form->isValid() && count($errors) == 0) {
                $password = $donateur->getUser()->getPassword();
                if( !empty($password) ) {
                $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                // password management
                $encoder = $this->container->get('security.encoder_factory')
                    ->getEncoder($donateur->getUser());
                $password = $encoder->encodePassword($donateur->getUser()->getPassword(), $donateur->getUser()->getSalt());
                $donateur->getUser()->setPassword($password);
                } else {
                    $donateur->getUser()->setPassword($oldPassword);
                }
                $donateur->setRemoved(false);

                //Gestion des RFs
                if($allow_rf ==  "true") {
                    $donateur->setAllowRf(true);
                } else {
                    $donateur->setAllowRf(false);
                }

                //Log
                $current_user = $this->get('security.context')->getToken()->getUser();
                $msg =$this->get('fulldon.intersa.global')->getModMsgLog($donateur,'DONATEUR');
                $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DONATEUR_);
                // Log the user creation
                $event =HistoryLogEvent::constr1($current_user,$donateur,$typeLog,$msg);
                $dispatcher =$this->get('event_dispatcher');
                $dispatcher->dispatch( LogVar::CREATE  , $event);
                $db->persist($donateur);
                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Votre compte est modifié avec succès');

                // On redirige vers la page de visualisation de l'article nouvellement créé
                return $this->redirect($this->generateUrl('donateur_account_edit'));

            } else {
                // dans le cas de la non validité des informations on va stoquer les informations sur la variable form.error
                foreach($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }
        // À ce stade :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

        return $this->render('FulldonDonateurBundle:Account:edit.html.twig', array('form' => $form->createView(), 'init'=>$this->init,'data'=>$data));
    }

    public function isNewUser(User $user)
    {
        // Check if it exists first
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User') ;
        $query = $repUsers->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->where("u.username = :username ")
            ->setParameter('username',$user->getUsername())
            ->getQuery();

        if ($query->getSingleScalarResult()>0)
        {
            return false;
        }

        return true;
    }
    public function mdpOublieDonateurAction(){
        $donateur = new Donateur;
        $user = new User;
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        // format all the form
        $errors = array();
        // On récupère la requête
        $request = $this->getRequest();


        // On vérifie qu'elle est de type POST et On vérifie les informations du donateur
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $email = $request->get('_email');
            $data['email']=$email;
            // On vérifie que les valeurs entrées sont correctes
            if(!isset($email) or empty($email) ) {

                $errors['error_email_donateur'] = ' Veuillez fournir un email correct ! ' ;
            }
            $donateur = $donateurRep->findOneBy(array('email'=>$email));
            if(!is_object($donateur)) {
                $errors['error_email_exist_donateur'] = ' l\'email fourni n\'existe pas ! ' ;
            }


            if ( count($errors) == 0) {
                // On enregistre notre objet $article dans la base de données
                $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                $donateur->getUser()->setIsActive(true);

                // Set encrypted password
                $encoder = $this->container->get('security.encoder_factory')
                    ->getEncoder($donateur->getUser());
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $count = mb_strlen($chars);
                $length = 8;
                for ($i = 0, $result = ''; $i < $length; $i++) {
                    $index = rand(0, $count - 1);
                    $result .= mb_substr($chars, $index, 1);
                }

                $new_pass =  $result;
                $password = $encoder->encodePassword($new_pass, $donateur->getUser()->getSalt());
                $donateur->getUser()->setPassword($password);
                $db->persist($donateur);
                $db->flush();
                // Send the email
                $is_email = false;
                $is_sms = false;
                //Traitement des modes de récéption
                $modes = $donateur->getReceptionMode();
                foreach($modes as $mode) {
                    switch($mode->getCode())
                    {
                        case Vars\DonVars::_COM_COURRIER_ :
                            $is_courrier = true;
                            break;
                        case Vars\DonVars::_COM_EMAIL_:
                            $is_email = true;
                            break;
                        case Vars\DonVars::_COM_SMS_:
                            $is_sms = true;
                            break;
                    }
                }
                $email = $donateur->getEmail();

                $assoc = $this->container->getParameter('assoc_name');
                $link = $this->container->getParameter('url_site');
                if($is_email && $email and !empty($email)) {
                    $html = $this->renderView( 'FulldonSecurityBundle:Security:Email/mdp_oublie.html.twig', array('new_pass' => $new_pass, 'donateur' => $donateur, 'assoc'=>$assoc, 'link'=>$link, 'init' => $this->init));
                    $this->get('fulldon.intersa.email_servies')->sendNewPassword($email,$html);

                }
                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Votre mot de passe vient d\'être changé et envoyé à votre email');
                return $this->redirect($this->generateUrl('login_donateur'));
            } else {
                foreach($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add($key, $error);
                }
            }
        }

        return $this->render('FulldonDonateurBundle:Account:mdp_oublie.html.twig',array('init'=>$this->init));

    }
} 