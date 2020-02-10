<?php
namespace Fulldon\DonateurBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Fulldon\DonateurBundle\Entity\Abonnement;
use Fulldon\DonateurBundle\Entity\ReceptionMode;
use Fulldon\DonateurBundle\Entity\Transaction;
use Fulldon\DonateurBundle\Entity\Cheque;
use Fulldon\DonateurBundle\Entity\Virement;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\StatutPaiement;
use Fulldon\DonateurBundle\Entity\ModePaiement;
use Fulldon\DonateurBundle\Entity\Pays;
use Fulldon\DonateurBundle\Entity\Ville;
use Fulldon\DonateurBundle\Form\DonPonctuelType;
use Fulldon\DonateurBundle\Form\VirementType;
use Fulldon\DonateurBundle\Form\DonRegulierType;
use Fulldon\DonateurBundle\Form\DonType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\IntersaBundle\Entity\TypeDon;
#payment
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\DateTime;
use Fulldon\IntersaBundle\Vars;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Intl;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\DonateurBundle\Entity\Doublon;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Symfony\Component\HttpFoundation\Session\Session;
use Fulldon\IntersaBundle\Vars\Email;


class OnePageController extends Controller
{

    /** @DI\Inject("payment.plugin_controller") */
    private $ppc;

    /** @DI\Inject("payment.ogone") */
    private $ogone;

    /** @DI\Inject("payment.plugin.ogone_gateway") */
    private $pogone;

    /** @DI\Inject */
    private $router;

    private $init = array();

    public function preExecute()
    {
        if ($this->container->getParameter('donor_space') == 0) {
            throw $this->createNotFoundException('Espace donateur non disponible !');
        }
        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        if (is_object($user)) {

            $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
            $statut = $repStatut->find(Vars\DonVars::_STATUT_DON_VALIDE_);
            $data = $this->getDoctrine()
                ->getRepository('FulldonDonateurBundle:Rf')
                ->createQueryBuilder('r')
                ->join('r.don', 'd')
                ->join('d.transaction', 't')
                ->join('t.statut', 's')
                ->select('count(d.id) as cpt')
                ->where('d.user = :id and r.don is not null and t.statut =:statut and r.sended = 0')
                ->setParameter('id', $user->getId())
                ->setParameter('statut', $statut)
                ->getQuery()->getSingleResult();
            $this->init['data'] = $data;
        }


        $persoRep = $db->getRepository('FulldonIntersaBundle:Personnalisation');
        $perso = $persoRep->find(1);


        $this->init['perso'] = $perso;
    }

    function notAuthAction()
    {
        //Extract all countries
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $countries = Intl::getRegionBundle()->getCountryNames();
        $request = $this->getRequest();
        $errors = array();
        $display_error = false;
        $subscription = false;
        $data = array();
        $session = new Session();
        $db = $this->getDoctrine()->getManager();
        $sub_cb = $this->container->getParameter('subscription_cb');
        //test if it's a post method
        if ($request->getMethod() == 'POST') {
            //donor informations
            $email = $request->get('email');
            $civilite = $request->get('civilite');
            $prenom = $request->get('prenom');
            $nom = $request->get('nom');
            $adresse1 = $request->get('adresse1');
            $adresse2 = $request->get('adresse2');
            $zipcode = $request->get('zipcode');
            $ville = $request->get('ville');
            $pays = $request->get('pays');
            $iban = $request->get('iban');
            $bic = $request->get('bic');
            $telephone = $request->get('telephone');
            $indicatif = $request->get('indicatif');
            $form_token = $request->get('token');
            $raisonSociale = $request->get('raison_sociale');
            $company_check = $request->get('company_check');
            $currentLocalite = $request->get('current_localite');

            if ($form_token == $session->get('token')) {
                $data['email'] = $email;
                if (!isset($email) || empty($email)) {
                    $errors['email'] = 'Veuillez spécifier un email valide';
                }
                if(!$this->container->get('fulldon.intersa.global')->isNewEmail($email)) {
                    $errors['email'] = 'Cet email est déjà utilisé';
                }
                if(!isset($company_check) || empty($company_check)) {
                    $data['civilite'] = $civilite;
                    if (!isset($civilite) || empty($civilite)) {
                        $errors['civilite'] = 'La Civilité n\'est un fournie valide';
                    }
                    $data['prenom'] = $prenom;
                    if (!isset($prenom) || empty($prenom)) {
                        $errors['prenom'] = 'Veuillez spécifier un prénom valide';
                    }
                    $data['nom'] = $nom;
                    if (!isset($nom) || empty($nom)) {
                        $errors['nom'] = 'Veuillez spécifier un nom valide';
                    }
                } else {
                    if (!isset($raisonSociale) || empty($raisonSociale)) {
                        $errors['raison_sociale'] = 'Veuillez spécifier une raison sociale';
                    }
                }
                $data['adresse1'] = $adresse1;
                $data['adresse2'] = $adresse2;
                if (!isset($adresse1) || empty($adresse1)) {
                    $errors['adresse1'] = 'Veuillez spécifier un adresse valide';
                }
                $data['zipcode'] = $zipcode;
                if (!isset($zipcode) || empty($zipcode) || !is_numeric($zipcode)) {
                    $errors['zipcode'] = 'Veuillez spécifier un code postal valide';
                }
                $data['ville'] = $ville;
                if (!isset($ville) || empty($ville)) {
                    $errors['ville'] = 'Veuillez spécifier un ville valide';
                }
                $data['pays'] = $pays;
                if (!isset($pays) || empty($pays)) {
                    $errors['pays'] = 'Veuillez spécifier un pays valide';
                }

                if ($this->isset_notempty($ville) && $this->isset_notempty($zipcode) && $this->isset_notempty($pays) && $this->isset_notempty($adresse1) && $this->isset_notempty($currentLocalite)) {
                    if ($pays == 'FR') {
                        try {


                            $secondLink = 'https://prod2.dqe-software.com/ADR/?Pays=FRA&IDLocalite=' . $currentLocalite . '&Adresse=' . urlencode($adresse1) . '&Licence=INTERSA12';

                            $curl2 = curl_init();
                            curl_setopt($curl2, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($curl2, CURLOPT_SSL_VERIFYPEER, 0);
                            curl_setopt($curl2, CURLOPT_URL, $secondLink);
                            curl_setopt($curl2, CURLOPT_RETURNTRANSFER, true);

                            $return2 = curl_exec($curl2);
                            $step2 = json_decode($return2);

                            if (!isset($step2->{'1'})) {
                                $errors['adresse not valide'] = 'L\'adresse spécifiée n\'est pas valide';
                            }
                            curl_close($curl2);
                        } catch (\Exception $e) {

                        }
                    }
                }
                // amount information
                $full_montant_p = $request->get('full_montant_p');
                $full_montant_r = $request->get('full_montant_r');
                $montant_ponctuel = $request->get('montant_ponctuel');
                $montant_reguilier = $request->get('montant_reguilier');
                $data['full_montant_p'] = $full_montant_p;
                $data['full_montant_r'] = $full_montant_r;
                $data['montant_ponctuel'] = $montant_ponctuel;
                $data['montant_reguilier'] = $montant_reguilier;
                $data['raison_sociale'] = $raisonSociale;
                // Handle the amount

                if ($this->isset_notempty($montant_ponctuel) && !is_numeric($montant_ponctuel)) {
                    $errors['mp_v'] = 'Veuillez spécifier un montant valide';
                }
                if ($this->isset_notempty($montant_reguilier) && !is_numeric($montant_reguilier)) {
                    $errors['mp_r'] = 'Veuillez spécifier un montant valide2';
                }
                if (!$this->isset_notempty($full_montant_p) && !$this->isset_notempty($montant_ponctuel) && !$this->isset_notempty($full_montant_r) && !$this->isset_notempty($montant_reguilier)) {
                    // error il faut définir un montant
                    $errors['mp_1'] = 'Veuillez SVP spécifier le montant du don';
                }

                // payement information

                $cb_payment_method = $request->get('payment_method');
                $cheque_payment_method = $request->get('cheque');
                $pa_payment_method = $request->get('pa');
                $data['cb_payment_method'] = $cb_payment_method;
                $data['cheque_payment_method'] = $cheque_payment_method;
                $data['pa_payment_method'] = $pa_payment_method;
                // Handle the payment method

                if ($this->isset_notempty($cb_payment_method) && $this->isset_notempty($cheque_payment_method) && $this->isset_notempty($pa_payment_method)) {
                    // erreur use only one payment method
                    $errors['mp_1'] = 'Veuillez sélectionner uniquement une seule méthode de paiement';
                } elseif (!$this->isset_notempty($cb_payment_method) && !$this->isset_notempty($cheque_payment_method) && !$this->isset_notempty($pa_payment_method)) {
                    // at least one payment method
                    $errors['mp_2'] = 'Veuillez sélectionner au moins une méthode de paiement';
                } elseif ($this->isset_notempty($cheque_payment_method) && $this->isset_notempty($pa_payment_method)) {
                    // error only one other payment
                    $errors['mp_3'] = 'Il faut choisir soit prélevement automatique soit chèque ';
                }
                if ($this->isset_notempty($pa_payment_method)) {
                    if ($this->container->getParameter('subscription_online')) {
                        if (!$this->isset_notempty($telephone) || !$this->isset_notempty($bic) || !$this->isset_notempty($iban) || !$this->isset_notempty($indicatif)) {
                            $errors['prelev_'] = 'Veuillez SVP revérifier les informations bancaires du prélèvement.';
                        }
                    }
                }
                if (count($errors) == 0) {

                    $donateur = new Donateur;
                    // Création du donateur
                    $duser = new User();
                    $i = 0;

                    do {
                        $i++;
                        $login = 'donateur' . $i;
                        $plain_password = base64_encode('p@s' . $i);
                    } while (!$this->isNewUser($login));

                    $duser->setUsername($login);
                    $duser->setPassword($plain_password);
                    $donateur->setUser($duser);
                    // On enregistre notre objet $article dans la base de données
                    $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                    $donateur->getUser()->setIsActive(true);
                    $repRole = $db->getRepository('FulldonSecurityBundle:Role');
                    $role_donateur = $repRole->findOneBy(array('role' => 'ROLE_DONATEUR'));
                    $donateur->getUser()->addRole($role_donateur);
                    // Set encrypted password
                    $encoder = $this->container->get('security.encoder_factory')
                        ->getEncoder($donateur->getUser());
                    $password = $encoder->encodePassword($donateur->getUser()->getPassword(), $donateur->getUser()->getSalt());
                    $donateur->getUser()->setPassword($password);

                    //Pays & ville
                    $donateur->setIsopays($pays);
                    $donateur->setIsoville($ville);

                    $rm1 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'courrier'));
                    $rm2 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'email'));
                    $rm3 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'sms'));

                    $donateur->addReceptionMode($rm1);
                    $donateur->addReceptionMode($rm2);
                    $donateur->addReceptionMode($rm3);
                    $donateur->setEmail($email);
                    $donateur->setCivilite($civilite);
                    $donateur->setNom($nom);
                    $donateur->setPrenom($prenom);
                    $donateur->setAdresse3($adresse1);
                    $donateur->setAdresse4($adresse2);
                    $donateur->setZipcode($zipcode);
                    $donateur->setAllowRf(true);
                    $donateur->setRemoved(false);
                    if (isset($raisonSociale) && !empty($raisonSociale)) {
                        $donateur->setNomEntreprise($raisonSociale);
                    }
                    $refId = $this->get('fulldon.intersa.global')->getUniqueRefDonateur();
                    $donateur->setRefDonateur($refId);
                    //$donateur->setPays();
                    //$donateur->setVille();
                    //Gestion des doublons
                    $percent = 0;
                    $pid = 0;
                    $pdonateur = $donateurRep->findOneBy(array('nom' => $donateur->getNom(), 'prenom' => $donateur->getPrenom(), 'removed' => false));
                    if ($pdonateur) {
                        $percent += 60;
                        $pid = $pdonateur->getId();
                        $pdonateur2 = $donateurRep->findOneBy(array('adresse3' => $donateur->getAdresse3(), 'id' => $pid));
                        if ($pdonateur2) {
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
                        $current_user = $donateur->getUser();
                        $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_DOUBLON_CREATED_);
                        $dispatcher = $this->get('event_dispatcher');
                        $dispatcher->dispatch(StatVar::CREATE, $event);

                    } else {
                        $db->persist($donateur);
                        $db->flush();
                    }

                    //Génération du pdf
                    $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->renderView(
                            'FulldonIntersaBundle:Donateurs:pdf/identifiants.html.twig',
                            array(
                                'login' => $donateur->getUser()->getUsername(),
                                'password' => $plain_password,
                                'donateur' => $donateur,
                                'init' => $this->init,

                            )
                        ),
                        '/' . $this->container->getParameter('folder_app') . '/users/donateur_fiche_' . $donateur->getId() . '.pdf'
                    );
                    //Envoi Email
                    $email = $donateur->getEmail();
                    if (isset($email) && !empty($email)) {
                        $html = $this->renderView('FulldonIntersaBundle:Email:send_identifiants.html.twig', array(
                            'login' => $donateur->getUser()->getUsername(),
                            'password' => $plain_password,
                            'donateur' => $donateur,
                            'init' => $this->init,
                        ));
                        $this->get('fulldon.intersa.email_servies')->sendNewAccount($email, $html);
                    }

                    //creation of donation
                    $don = new Don;

                    if (!empty($full_montant_p) && isset($full_montant_p) && is_numeric($full_montant_p)) {
                        $montant = $full_montant_p;
                    } elseif (!empty($montant_ponctuel) && isset($montant_ponctuel)) {
                        $montant = $montant_ponctuel;
                    } elseif (!empty($full_montant_r) && isset($full_montant_r) && is_numeric($full_montant_r)) {
                        $montant = $full_montant_r;
                        $subscription = true;
                    } elseif (!empty($montant_reguilier) && isset($montant_reguilier)) {
                        $montant = $montant_reguilier;
                        $subscription = true;
                    }

                    // subscription ?
                    if ($subscription) {

                        $periodicite = 1;
                        $date_debut = $this->get('fulldon.intersa.rf_service')->getStartDate();

                        $abo = new Abonnement();
                        $abo->setActif(true);
                        $abo->setRumIndice(1);
                        $don->setAbonnement($abo);
                        $don->setIspa(true);
                        $don->setType($db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET')));
                        $don->getAbonnement()->setPeriodicite($db->getRepository('FulldonDonateurBundle:Periodicite')->find($periodicite));
                        $don->setIspa(true);
                        $don->setMontant($montant);
                        $don->setUser($donateur->getUser());
                        $don->setCause($db->getRepository('FulldonDonateurBundle:Cause')->findOneBy(array('code' => 'don_en_ligne')));
                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'PA'));
                        $don->setType($obj_type);
                        $transaction = new Transaction();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $don->getAbonnement()->setDateFirstPa($date_debut);
                        $don->getAbonnement()->setDateNextPa($date_debut);
                        $transaction->setStatut($statut);
                        $don->setTransaction($transaction);
                        if ($this->isset_notempty($cb_payment_method)) {
                            $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CB_MODE_)));
                        } elseif ($this->isset_notempty($pa_payment_method)) {
                            $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_PA_MODE_)));
                        }
                        $db->persist($don);
                        $db->flush();
                        $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                        $abo->setRum($rum);
                        $db->persist($abo);
                        $db->flush();
                        $html = $this->renderView('FulldonDonateurBundle:Emails:administrator.html.twig', array(
                            'donateur' => $donateur,
                            'don' => $don,
                        ));
                        $admins = explode(',', $this->container->getParameter('administrator_email'));
                        $this->get('fulldon.intersa.email_servies')->sendInfosDonation($admins, $html);


                        if ($this->isset_notempty($cb_payment_method) && $sub_cb) {
                            //redirect
                            $accepturl = 'http://' . $request->getHttpHost() . $this->generateUrl('payment_callback_anonymous');
                            $brand = 'CreditCard';
                            if ($request->getLocale() == 'fr') {
                                $lang = 'fr_FR';
                            } else {
                                $lang = 'en_GB';
                            }
                            if ($cb_payment_method == 'PAYPAL')
                                $brand = 'PAYPAL';
                            $date_debut = $this->get('fulldon.intersa.rf_service')->getStartDate();
                            $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                            $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                            $period_obj = $don->getAbonnement()->getPeriodicite();
                            $don->getTransaction()->setStatut($statut);
                            $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                            if (is_object($donateur->getPays())) {
                                $pays = $donateur->getPays()->getName();

                            }
                            if (is_object($donateur->getVille())) {
                                $ville = $donateur->getVille()->getName();
                            }
                            $options = array(
                                'amount' => $don->getMontant(),
                                'currency' => 'EUR',
                                'default_method' => 'ogone_gateway', // Optional
                                'predefined_data' => array(
                                    'ogone_gateway' => array(
                                        'SUB_AM' => $don->getMontant(),
                                        'SUB_AMOUNT' => $don->getMontant() * 100,
                                        'SUB_CUR' => 'EUR',
                                        'SUB_ENDDATE' => '',
                                        'SUB_PERIOD_MOMENT' => $recommanded_day,
                                        'SUB_PERIOD_NUMBER' => $periodicite,
                                        'SUB_PERIOD_UNIT' => 'm',
                                        'SUB_STATUS' => 1,
                                        'SUB_STARTDATE' => $date_debut->format('d/m/Y'),
                                        'SUB_ORDERID' => $don->getId(), // Optional
                                        'PM' => $brand, // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                                        'BRAND' => $cb_payment_method, // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                                        'CN' => $donateur->getNom(), // Optional
                                        'EMAIL' => $donateur->getEmail(), // Optional
                                        'OWNERZIP' => $donateur->getZipcode(), // Optional
                                        'OWNERADDRESS' => $donateur->getAdresse3(), // Optional
                                        'OWNERCTY' => $pays, // Optional
                                        'OWNERTOWN' => $ville, // Optional
                                        'OWNERTELNO' => '',
                                        'lang' => $lang, // 5 characters maximum, for e.g: fr_FR
                                        'SUBSCRIPTION_ID' => $don->getId(), // Optional, 30 characters maximum
                                        'ORDERID' => $don->getId(),
                                        'acceptUrl' => $accepturl,
                                    ),
                                ),
                            );

                            $this->ppc->addPlugin($this->pogone);
                            $this->ppc->createPaymentInstruction($instruction = $this->reverseTransform(null, $options));
                            $transaction = new Transaction();
                            $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                            $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                            $transaction->setStatut($statut);
                            $transaction->setPaymentInstruction($instruction);
                            $don->setTransaction($transaction);
                            $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                            $don->setType($obj_type);
                            $db->persist($don);
                            $db->flush($don);
                            $instruction = $don->getTransaction()->getPaymentInstruction();
                            if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
                                $payment = $this->ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
                            } else {
                                $payment = $pendingTransaction->getPayment();
                            }

                            $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
                            if (Result::STATUS_PENDING === $result->getStatus()) {
                                $ex = $result->getPluginException();

                                if ($ex instanceof ActionRequiredException) {
                                    $action = $ex->getAction();

                                    if ($action instanceof VisitUrl) {
                                        return new RedirectResponse($action->getUrl());
                                    }

                                    throw $ex;
                                }
                            } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                                throw new \RuntimeException('Transaction was not successful: ' . $result->getReasonCode());
                            }

                        } elseif ($this->isset_notempty($pa_payment_method)) {
                            // if we have the online subscription
                            if ($this->container->getParameter('subscription_online')) {
                                // Enregistrement de l'iban et bic
                                $iban = strtoupper(trim($iban));
                                $bic = strtoupper(trim($bic));
                                $don->getAbonnement()->setIban($iban);
                                $don->getAbonnement()->setBic($bic);
                                $db->flush();
                                $app_code = $this->container->getParameter('certeurope_certisms_app_code');
                                $identifiant = '0' . $telephone;
                                $url = $this->container->getParameter('certeurope_certisms_url');
                                $session = new Session();

                                // add access to sertisms
                                try {
                                    $client = new \SoapClient($this->container->getParameter('certeurope_certisms_wsdl'), array('trace' => 1));
                                    $a = array('codeApplication' => $app_code, 'indicaddtifRegional' => $indicatif, 'identifiant' => $identifiant, 'url' => $url, 'paramaters' => null);
                                    $response = $client->__soapCall('AddAcces', $a);
                                    $filename = uniqid(mt_rand()) . 'mandat_' . $don->getId() . '.pdf';
                                    if ($response->error == 0) {
                                        //Afficher la page de confirmation
                                        //Génération du pdf
                                        $this->get('knp_snappy.pdf')->generateFromHtml(
                                            $this->renderView(
                                                'FulldonDonateurBundle:OnePage:mandat_numerique.html.twig',
                                                array(
                                                    'don' => $don,
                                                    'donateur' => $donateur,
                                                    'telephone' => $telephone,
                                                    'indicatif' => $indicatif
                                                )
                                            ),
                                            '/' . $this->container->getParameter('folder_app') . '/users/' . $filename,
                                            array(
                                                'page-height' => 100,
                                                'page-width' => 200,
                                                'margin-top' => 0,
                                                'margin-bottom' => 0,
                                                'margin-left' => 0,
                                                'margin-right' => 0,
                                                'no-background' => false
                                            )
                                        );
                                        // Générer un PDF
                                        // Display confirmation page
                                        $session->set('don_id', $don->getId());
                                        return $this->render('FulldonDonateurBundle:OnePage:mandat_confirmation.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'mandat_filename' => $filename, 'mandat_telephone' => $telephone, 'indicatif' => $indicatif));
                                    } else {
                                        //Rediriger l'utilisateur vers la page courante avec un message d'erreur.
                                    }
                                } catch (\SoapFault $e) {

                                    throw new \Exception('Un problème technique est survenu, veuillez contacter l\'administrateur!');
                                }
                                //return array('response' => $response);
                            } else {
                                // redirect
                                $d2 = $this->get('fulldon.donateur.barecodes');
                                $string = $don->getId() . "|edon";
                                $d2->setStrToEncode($string);
                                //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                                $approx_size = 60;
                                // Et on trace le code barre avec comme argument la taille en pixels voulue
                                $filename = 'datamatrix' . '_' . $don->getId();
                                $d2->stroke($approx_size, $filename);
                                $assoc_name = $this->container->getParameter('assoc_name');
                                $this->get('session')->getFlashBag()->add('info', 'Votre prélèvement automatique est désormais en cours de traitement,merci pour votre générosité.');
                                return $this->render('FulldonDonateurBundle:Donateur:print/virement.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'datamatrix' => $filename, 'assoc_name' => $assoc_name));
                            }

                        }

                    } else {
                        $don->setType($db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET')));
                        $don->setIspa(false);
                        $don->setMontant($montant);
                        $don->setUser($donateur->getUser());
                        $don->setCause($db->getRepository('FulldonDonateurBundle:Cause')->findOneBy(array('code' => 'don_en_ligne')));
                        $transaction = new Transaction();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $transaction->setStatut($statut);
                        $don->setTransaction($transaction);
                        if ($this->isset_notempty($cb_payment_method)) {
                            $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CB_MODE_)));

                            $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                            $don->setType($obj_type);
                        } elseif ($this->isset_notempty($cheque_payment_method)) {
                            $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_)));
                            $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'BC'));
                            $don->setType($obj_type);
                        }
                        $db->persist($don);
                        $db->flush();
                        // Send an information email to the administrator.
                        $html = $this->renderView('FulldonDonateurBundle:Emails:administrator.html.twig', array(
                            'donateur' => $donateur,
                            'don' => $don,
                        ));
                        $admins = explode(',', $this->container->getParameter('administrator_email'));
                        $this->get('fulldon.intersa.email_servies')->sendInfosDonation($admins, $html);
                        $email = $donateur->getEmail();
                        if (isset($email) && !empty($email)) {
                            $html = $this->renderView('FulldonDonateurBundle:Emails:confirmation.html.twig', array(
                                'donateur' => $donateur,
                                'don' => $don,
                                'init' => $this->init,
                            ));
                            $this->get('fulldon.intersa.email_servies')->sendConfirmDonation($email, $html);
                        }

                        if ($this->isset_notempty($cb_payment_method)) {

                            //redirect
                            $accepturl = 'http://' . $request->getHttpHost() . $this->generateUrl('payment_callback_anonymous');
                            $brand = 'CreditCard';
                            if ($request->getLocale() == 'fr') {
                                $lang = 'fr_FR';
                            } else {
                                $lang = 'en_GB';
                            }
                            if ($cb_payment_method == 'PAYPAL')
                                $brand = 'PAYPAL';

                            if (is_object($donateur->getPays())) {
                                $pays = $donateur->getPays()->getName();
                            }

                            if (is_object($donateur->getVille())) {
                                $ville = $donateur->getVille()->getName();
                            }
                            $options = array(
                                'amount' => $don->getMontant(),
                                'currency' => 'EUR',
                                'default_method' => 'ogone_gateway', // Optional
                                'predefined_data' => array(
                                    'ogone_gateway' => array(
                                        'tp' => '', // Optional
                                        'PM' => $brand, // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                                        'BRAND' => $cb_payment_method, // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                                        'CN' => $donateur->getNom(), // Optional
                                        'EMAIL' => $donateur->getEmail(), // Optional
                                        'OWNERZIP' => $donateur->getZipcode(), // Optional
                                        'OWNERADDRESS' => $donateur->getAdresse3(), // Optional
                                        'OWNERCTY' => $ville, // Optional
                                        'OWNERTOWN' => $pays, // Optional
                                        'OWNERTELNO' => '',
                                        'lang' => $lang, // 5 characters maximum, for e.g: fr_FR
                                        'ORDERID' => $don->getId(), // Optional, 30 characters maximum
                                        'acceptUrl' => $accepturl,
                                    ),
                                ),
                            );


                            $this->ppc->addPlugin($this->pogone);
                            $this->ppc->createPaymentInstruction($instruction = $this->reverseTransform(null, $options));
                            $transaction = new Transaction();
                            $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                            $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                            $transaction->setStatut($statut);
                            $transaction->setPaymentInstruction($instruction);
                            $don->setTransaction($transaction);
                            $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                            $don->setType($obj_type);
                            $db->persist($don);
                            $db->flush($don);
                            $instruction = $don->getTransaction()->getPaymentInstruction();
                            if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
                                $payment = $this->ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
                            } else {
                                $payment = $pendingTransaction->getPayment();
                            }

                            $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
                            if (Result::STATUS_PENDING === $result->getStatus()) {
                                $ex = $result->getPluginException();

                                if ($ex instanceof ActionRequiredException) {
                                    $action = $ex->getAction();

                                    if ($action instanceof VisitUrl) {
                                        return new RedirectResponse($action->getUrl());
                                    }
                                    throw $ex;
                                }
                            } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                                throw new \RuntimeException('Transaction was not successful: ' . $result->getReasonCode());
                            }

                        } elseif ($this->isset_notempty($cheque_payment_method)) {
                            // redirect
                            $d2 = $this->get('fulldon.donateur.barecodes');
                            $transaction = $don->getTransaction();
                            $d2 = $this->get('fulldon.donateur.barecodes');
                            $string = $don->getId() . "|edon";
                            $d2->setStrToEncode($string);
                            //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                            $approx_size = 60;
                            // Et on trace le code barre avec comme argument la taille en pixels voulue
                            $filename = 'datamatrix' . '_' . $don->getId();
                            $d2->stroke($approx_size, $filename);
                            $assoc_name = $this->container->getParameter('assoc_name');

                            return $this->render('FulldonDonateurBundle:Donateur:Payment/cheque.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'datamatrix' => $filename, 'assoc_name' => $assoc_name));
                        }

                    }


                } else {
                    foreach ($errors as $key => $error) {
                        $this->get('session')->getFlashBag()->add('error', $error);
                        $display_error = true;
                    }
                }
            } else {
                // Alert security
                throw $this->createNotFoundException('Veuillez svp actualiser la page  !');
            }
        }
        //Security form
        $token = sha1(uniqid(rand(), TRUE));
        $session->set('token', $token);

        //check form values
        //redirect to the appropriate page
        return $this->render('FulldonDonateurBundle:OnePage:not_auth.html.twig', array('init' => $this->init, 'display_error' => $display_error, 'data' => $data, 'countries' => $countries, 'token' => $token, 'sub_cb' => $sub_cb));
    }

    function authAction()
    {
        //Extract all countries
        $countries = Intl::getRegionBundle()->getCountryNames();
        $request = $this->getRequest();
        $errors = array();
        $display_error = false;
        $subscription = false;
        $data = array();
        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->findOneBy(array('user' => $user->getId()));
        $sub_cb = $this->container->getParameter('subscription_cb');
        $isCompany = false;
        $companyName = $donateur->getNomEntreprise();
        if (!empty($companyName)) {
            $isCompany = true;
        }
        //test if it's a post method
        if ($request->getMethod() == 'POST') {


            // amount information
            $full_montant_p = $request->get('full_montant_p');
            $full_montant_r = $request->get('full_montant_r');
            $montant_ponctuel = $request->get('montant_ponctuel');
            $montant_reguilier = $request->get('montant_reguilier');
            $iban = $request->get('iban');
            $bic = $request->get('bic');
            $telephone = $request->get('telephone');
            $indicatif = $request->get('indicatif');
            $data['full_montant_p'] = $full_montant_p;
            $data['full_montant_r'] = $full_montant_r;
            $data['montant_ponctuel'] = $montant_ponctuel;
            $data['montant_reguilier'] = $montant_reguilier;
            // Handle the amount
            if ($this->isset_notempty($montant_ponctuel) && !is_numeric($montant_ponctuel)) {
                $errors['mp_v'] = 'Veuillez spécifier un montant valide';
            }
            if ($this->isset_notempty($montant_reguilier) && !is_numeric($montant_reguilier)) {
                $errors['mp_r'] = 'Veuillez spécifier un montant valide2';
            }
            if (!$this->isset_notempty($full_montant_p) && !$this->isset_notempty($montant_ponctuel) && !$this->isset_notempty($full_montant_r) && !$this->isset_notempty($montant_reguilier)) {
                // error il faut définir un montant
                $errors['mp_1'] = 'Veuillez SVP spécifier le montant du don';
            }

            // payement information

            $cb_payment_method = $request->get('payment_method');
            $cheque_payment_method = $request->get('cheque');
            $pa_payment_method = $request->get('pa');
            $data['cb_payment_method'] = $cb_payment_method;
            $data['cheque_payment_method'] = $cheque_payment_method;
            $data['pa_payment_method'] = $pa_payment_method;
            // Handle the payment method

            if ($this->isset_notempty($cb_payment_method) && $this->isset_notempty($cheque_payment_method) && $this->isset_notempty($pa_payment_method)) {
                // erreur use only one payment method
                $errors['mp_1'] = 'Veuillez uniquement une seule méthode de paiement';
            } elseif (!$this->isset_notempty($cb_payment_method) && !$this->isset_notempty($cheque_payment_method) && !$this->isset_notempty($pa_payment_method)) {
                // at least one payment method
                $errors['mp_2'] = 'Veuillez sélectionner au moins une méthode de paiement';
            } elseif ($this->isset_notempty($cheque_payment_method) && $this->isset_notempty($pa_payment_method)) {
                // error only one other payment
                $errors['mp_3'] = 'Il faut choisir soit prélevement automatique ou chèque ';
            }
            if ($this->isset_notempty($pa_payment_method)) {
                if ($this->container->getParameter('subscription_online')) {
                    if (!$this->isset_notempty($telephone) || !$this->isset_notempty($bic) || !$this->isset_notempty($iban) || !$this->isset_notempty($indicatif)) {
                        $errors['prelev_'] = 'Veuillez SVP revérifier les informations bancaires du prélèvement.';
                    }
                }
            }

            if (count($errors) == 0) {

                $user = $this->get('security.context')->getToken()->getUser();
                $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
                $donateur = $donateurRep->findOneBy(array('user' => $user->getId()));

                //creation of donation
                $don = new Don;

                if (!empty($full_montant_p) && isset($full_montant_p) && is_numeric($full_montant_p)) {
                    $montant = $full_montant_p;
                }
                if (!empty($montant_ponctuel) && isset($montant_ponctuel)) {
                    $montant = $montant_ponctuel;
                } elseif (!empty($full_montant_r) && isset($full_montant_r) && is_numeric($full_montant_r)) {
                    $montant = $full_montant_r;
                    $subscription = true;
                } elseif (!empty($montant_reguilier) && isset($montant_reguilier)) {
                    $montant = $montant_reguilier;
                    $subscription = true;
                }

                // subscription ?
                if ($subscription) {

                    $periodicite = 1;
                    $date_debut = $this->get('fulldon.intersa.rf_service')->getStartDate();
                    $abo = new Abonnement();
                    $abo->setActif(true);
                    $abo->setRumIndice(1);
                    $don->setAbonnement($abo);
                    $don->setIspa(true);
                    $don->getAbonnement()->setPeriodicite($db->getRepository('FulldonDonateurBundle:Periodicite')->find($periodicite));
                    $don->setIspa(true);
                    $don->setType($db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET')));
                    $don->setMontant($montant);
                    $don->setUser($donateur->getUser());
                    $don->setCause($db->getRepository('FulldonDonateurBundle:Cause')->findOneBy(array('code' => 'don_en_ligne')));
                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'PA'));
                    $don->setType($obj_type);
                    $transaction = new Transaction();
                    $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                    $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                    $don->getAbonnement()->setDateFirstPa($date_debut);
                    $don->getAbonnement()->setDateNextPa($date_debut);
                    $transaction->setStatut($statut);
                    $don->setTransaction($transaction);
                    if ($this->isset_notempty($cb_payment_method)) {
                        $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CB_MODE_)));
                    } elseif ($this->isset_notempty($pa_payment_method)) {
                        $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_PA_MODE_)));
                    }
                    $db->persist($don);
                    $db->flush();
                    $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                    $abo->setRum($rum);
                    $db->persist($abo);
                    $db->flush();


                    $html = $this->renderView('FulldonDonateurBundle:Emails:administrator.html.twig', array(
                        'donateur' => $donateur,
                        'don' => $don,
                    ));
                    $admins = explode(',', $this->container->getParameter('administrator_email'));
                    $this->get('fulldon.intersa.email_servies')->sendInfosDonation($admins, $html);

                    if ($this->isset_notempty($cb_payment_method) && $sub_cb) {
                        //redirect
                        $accepturl = 'http://' . $request->getHttpHost() . $this->generateUrl('payment_callback');
                        $brand = 'CreditCard';
                        if ($request->getLocale() == 'fr') {
                            $lang = 'fr_FR';
                        } else {
                            $lang = 'en_GB';
                        }
                        if ($cb_payment_method == 'PAYPAL')
                            $brand = 'PAYPAL';
                        $date_debut = $this->get('fulldon.intersa.rf_service')->getStartDate();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $period_obj = $don->getAbonnement()->getPeriodicite();
                        $don->getTransaction()->setStatut($statut);
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        if (is_object($donateur->getPays())) {
                            $pays = $donateur->getPays()->getName();

                        } else {
                            $pays = null;
                        }
                        if (is_object($donateur->getVille())) {
                            $ville = $donateur->getVille()->getName();
                        } else {
                            $ville = null;
                        }
                        $options = array(
                            'amount' => $don->getMontant(),
                            'currency' => 'EUR',
                            'default_method' => 'ogone_gateway', // Optional
                            'predefined_data' => array(
                                'ogone_gateway' => array(
                                    'SUB_AM' => $don->getMontant(),
                                    'SUB_AMOUNT' => $don->getMontant() * 100,
                                    'SUB_CUR' => 'EUR',
                                    'SUB_ENDDATE' => '',
                                    'SUB_PERIOD_MOMENT' => $recommanded_day,
                                    'SUB_PERIOD_NUMBER' => $periodicite,
                                    'SUB_PERIOD_UNIT' => 'm',
                                    'SUB_STATUS' => 1,
                                    'SUB_STARTDATE' => $date_debut->format('d/m/Y'),
                                    'SUB_ORDERID' => $don->getId(), // Optional
                                    'PM' => $brand, // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                                    'BRAND' => $cb_payment_method, // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                                    'CN' => $donateur->getNom(), // Optional
                                    'EMAIL' => $donateur->getEmail(), // Optional
                                    'OWNERZIP' => $donateur->getZipcode(), // Optional
                                    'OWNERADDRESS' => $donateur->getAdresse3(), // Optional
                                    'OWNERCTY' => $pays, // Optional
                                    'OWNERTOWN' => $ville, // Optional
                                    'OWNERTELNO' => '',
                                    'lang' => $lang, // 5 characters maximum, for e.g: fr_FR
                                    'SUBSCRIPTION_ID' => $don->getId(), // Optional, 30 characters maximum
                                    'ORDERID' => $don->getId(),
                                    'acceptUrl' => $accepturl,
                                ),
                            ),
                        );

                        $this->ppc->addPlugin($this->pogone);
                        $this->ppc->createPaymentInstruction($instruction = $this->reverseTransform(null, $options));
                        $transaction = new Transaction();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $transaction->setStatut($statut);
                        $transaction->setPaymentInstruction($instruction);
                        $don->setTransaction($transaction);
                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                        $don->setType($obj_type);
                        $db->persist($don);
                        $db->flush($don);
                        $instruction = $don->getTransaction()->getPaymentInstruction();
                        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
                            $payment = $this->ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
                        } else {
                            $payment = $pendingTransaction->getPayment();
                        }

                        $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
                        if (Result::STATUS_PENDING === $result->getStatus()) {
                            $ex = $result->getPluginException();

                            if ($ex instanceof ActionRequiredException) {
                                $action = $ex->getAction();

                                if ($action instanceof VisitUrl) {
                                    return new RedirectResponse($action->getUrl());
                                }

                                throw $ex;
                            }
                        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                            throw new \RuntimeException('Transaction was not successful: ' . $result->getReasonCode());
                        }

                    } elseif ($this->isset_notempty($pa_payment_method)) {
                        // if we have the online subscription
                        if ($this->container->getParameter('subscription_online')) {
                            // Enregistrement de l'iban et bic
                            $don->getAbonnement()->setIban($iban);
                            $don->getAbonnement()->setBic($bic);
                            $db->flush();
                            $app_code = $this->container->getParameter('certeurope_certisms_app_code');
                            $identifiant = '0' . $telephone;
                            $url = $this->container->getParameter('certeurope_certisms_url');
                            $session = new Session();

                            // add access to sertisms
                            try {
                                $client = new \SoapClient($this->container->getParameter('certeurope_certisms_wsdl'), array('trace' => 1));
                                $a = array('codeApplication' => $app_code, 'indicaddtifRegional' => $indicatif, 'identifiant' => $identifiant, 'url' => $url, 'paramaters' => null);
                                $response = $client->__soapCall('AddAcces', $a);
                                $filename = uniqid(mt_rand()) . 'mandat_' . $don->getId() . '.pdf';
                                if ($response->error == 0) {
                                    //Afficher la page de confirmation
                                    //Génération du pdf
                                    $this->get('knp_snappy.pdf')->generateFromHtml(
                                        $this->renderView(
                                            'FulldonDonateurBundle:OnePage:mandat_numerique.html.twig',
                                            array(
                                                'don' => $don,
                                                'donateur' => $donateur,
                                                'telephone' => $telephone,
                                                'indicatif' => $indicatif
                                            )
                                        ),
                                        '/' . $this->container->getParameter('folder_app') . '/users/' . $filename,
                                        array(
                                            'page-height' => 100,
                                            'page-width' => 200,
                                            'margin-top' => 0,
                                            'margin-bottom' => 0,
                                            'margin-left' => 0,
                                            'margin-right' => 0,
                                            'no-background' => false
                                        )
                                    );
                                    // Générer un PDF
                                    // Display confirmation page
                                    $session->set('don_id', $don->getId());
                                    return $this->render('FulldonDonateurBundle:OnePage:mandat_confirmation.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'mandat_filename' => $filename, 'mandat_telephone' => $telephone, 'indicatif' => $indicatif));
                                } else {
                                    //Rediriger l'utilisateur vers la page courante avec un message d'erreur.
                                }
                            } catch (\SoapFault $e) {
                                var_dump($e->getMessage(), $client->__getLastResponse());
                                throw new \Exception('Un problème technique est survenu, veuillez contacter l\'administrateur!');
                            }
                            //return array('response' => $response);

                        } else {
                            // redirect
                            $d2 = $this->get('fulldon.donateur.barecodes');
                            $string = $don->getId() . "|edon";
                            $d2->setStrToEncode($string);
                            //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                            $approx_size = 60;
                            // Et on trace le code barre avec comme argument la taille en pixels voulue
                            $filename = 'datamatrix' . '_' . $don->getId();
                            $d2->stroke($approx_size, $filename);
                            $assoc_name = $this->container->getParameter('assoc_name');
                            $this->get('session')->getFlashBag()->add('info', 'Votre prélèvement automatique est désormais en cours de traitement,merci pour votre générosité.');
                            return $this->render('FulldonDonateurBundle:Donateur:print/virement.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'datamatrix' => $filename, 'assoc_name' => $assoc_name));
                        }
                    }

                } else {

                    $don->setIspa(false);
                    $don->setMontant($montant);
                    $don->setType($db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET')));
                    $don->setUser($donateur->getUser());
                    $don->setCause($db->getRepository('FulldonDonateurBundle:Cause')->findOneBy(array('code' => 'don_en_ligne')));
                    $transaction = new Transaction();
                    $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                    $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                    $transaction->setStatut($statut);
                    $don->setTransaction($transaction);
                    if ($this->isset_notempty($cb_payment_method)) {
                        $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CB_MODE_)));

                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                        $don->setType($obj_type);
                    } elseif ($this->isset_notempty($cheque_payment_method)) {
                        $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CHEQUE_MODE_)));
                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'BC'));
                        $don->setType($obj_type);
                    }
                    $db->persist($don);
                    $db->flush();
                    // Send an information email to the administrator.
                    $html = $this->renderView('FulldonDonateurBundle:Emails:administrator.html.twig', array(
                        'donateur' => $donateur,
                        'don' => $don,
                    ));
                    $admins = explode(',', $this->container->getParameter('administrator_email'));
                    $this->get('fulldon.intersa.email_servies')->sendInfosDonation($admins, $html);
                    //Envoi Email
                    $email = $donateur->getEmail();
                    if (isset($email) && !empty($email)) {
                        $html = $this->renderView('FulldonDonateurBundle:Emails:confirmation.html.twig', array(
                            'donateur' => $donateur,
                            'don' => $don,
                            'init' => $this->init,
                        ));
                        $this->get('fulldon.intersa.email_servies')->sendConfirmDonation($email, $html);
                    }
                    if ($this->isset_notempty($cb_payment_method)) {

                        //redirect
                        $accepturl = 'http://' . $request->getHttpHost() . $this->generateUrl('payment_callback');
                        $brand = 'CreditCard';
                        if ($request->getLocale() == 'fr') {
                            $lang = 'fr_FR';
                        } else {
                            $lang = 'en_GB';
                        }
                        if ($cb_payment_method == 'PAYPAL')
                            $brand = 'PAYPAL';

                        if (is_object($donateur->getPays())) {
                            $pays = $donateur->getPays()->getName();
                        } else {
                            $pays = null;
                        }

                        if (is_object($donateur->getVille())) {
                            $ville = $donateur->getVille()->getName();
                        } else {
                            $ville = null;
                        }
                        $options = array(
                            'amount' => $don->getMontant(),
                            'currency' => 'EUR',
                            'default_method' => 'ogone_gateway', // Optional
                            'predefined_data' => array(
                                'ogone_gateway' => array(
                                    'tp' => '', // Optional
                                    'PM' => $brand, // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                                    'BRAND' => $cb_payment_method, // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                                    'CN' => $donateur->getNom(), // Optional
                                    'EMAIL' => $donateur->getEmail(), // Optional
                                    'OWNERZIP' => $donateur->getZipcode(), // Optional
                                    'OWNERADDRESS' => $donateur->getAdresse3(), // Optional
                                    'OWNERCTY' => $ville, // Optional
                                    'OWNERTOWN' => $pays, // Optional
                                    'OWNERTELNO' => '',
                                    'lang' => $lang, // 5 characters maximum, for e.g: fr_FR
                                    'ORDERID' => $don->getId(), // Optional, 30 characters maximum
                                    'acceptUrl' => $accepturl,
                                ),
                            ),
                        );


                        $this->ppc->addPlugin($this->pogone);
                        $this->ppc->createPaymentInstruction($instruction = $this->reverseTransform(null, $options));
                        $transaction = new Transaction();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $transaction->setStatut($statut);
                        $transaction->setPaymentInstruction($instruction);
                        $don->setTransaction($transaction);
                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                        $don->setType($obj_type);
                        $db->persist($don);
                        $db->flush($don);

                        $instruction = $don->getTransaction()->getPaymentInstruction();
                        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
                            $payment = $this->ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
                        } else {
                            $payment = $pendingTransaction->getPayment();
                        }

                        $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
                        if (Result::STATUS_PENDING === $result->getStatus()) {
                            $ex = $result->getPluginException();

                            if ($ex instanceof ActionRequiredException) {
                                $action = $ex->getAction();

                                if ($action instanceof VisitUrl) {
                                    return new RedirectResponse($action->getUrl());
                                }

                                throw $ex;
                            }
                        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                            throw new \RuntimeException('Transaction was not successful: ' . $result->getReasonCode());
                        }

                    } elseif ($this->isset_notempty($cheque_payment_method)) {
                        // redirect
                        $d2 = $this->get('fulldon.donateur.barecodes');
                        $transaction = $don->getTransaction();
                        $d2 = $this->get('fulldon.donateur.barecodes');
                        $string = $don->getId() . "|edon";
                        $d2->setStrToEncode($string);
                        //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                        $approx_size = 60;
                        // Et on trace le code barre avec comme argument la taille en pixels voulue
                        $filename = 'datamatrix' . '_' . $don->getId();
                        $d2->stroke($approx_size, $filename);
                        $assoc_name = $this->container->getParameter('assoc_name');

                        return $this->render('FulldonDonateurBundle:Donateur:Payment/cheque.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'datamatrix' => $filename, 'assoc_name' => $assoc_name));
                    }

                }


            } else {
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add('error', $error);
                    $display_error = true;
                }
            }
        }
        //check form values
        //redirect to the appropriate page
        return $this->render('FulldonDonateurBundle:OnePage:auth.html.twig', array('init' => $this->init, 'display_error' => $display_error, 'data' => $data, 'countries' => $countries, 'sub_cb' => $sub_cb, 'is_company' => $isCompany));
    }

    function authAdhAction()
    {
        //Extract all countries
        $countries = Intl::getRegionBundle()->getCountryNames();
        $request = $this->getRequest();
        $errors = array();
        $display_error = false;
        $subscription = false;
        $data = array();
        $db = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $donateurRep->findOneBy(array('user' => $user->getId()));

        $sub_cb = $this->container->getParameter('subscription_cb');
        $isCompany = false;
        $min_don_r = 5;
        $companyName = $donateur->getNomEntreprise();
        if (!empty($companyName)) {
            $isCompany = true;
        }
        //test if it's a post method
        if ($request->getMethod() == 'POST') {


            $full_montant_r = $request->get('full_montant_r');
            $montant_reguilier = $request->get('montant_reguilier');
            $iban = $request->get('iban');
            $bic = $request->get('bic');
            $telephone = $request->get('telephone');
            $indicatif = $request->get('indicatif');
            $data['full_montant_r'] = $full_montant_r;
            $data['montant_reguilier'] = $montant_reguilier;


            if ( /*$this->isset_notempty($montant_ponctuel) && !is_numeric($montant_ponctuel)) ||*/
                ($this->isset_notempty($montant_reguilier) && !is_numeric($montant_reguilier)) ||
                ($this->isset_notempty($montant_reguilier) && $montant_reguilier < $min_don_r)
            ) {
                $errors['mp_v'] = 'Veuillez spécifier un montant valide';
            }

            if ( /*!$this->isset_notempty($full_montant_p) && !$this->isset_notempty($montant_ponctuel) &&*/
                !$this->isset_notempty($full_montant_r) && !$this->isset_notempty($montant_reguilier)
            ) {
                // error il faut définir un montant
                $errors['mp_1'] = 'Veuillez SVP spécifier le montant du don';
            }

            // payement information

            $cb_payment_method = $request->get('payment_method');
            $cheque_payment_method = $request->get('cheque');
            $pa_payment_method = $request->get('pa');
            $data['cb_payment_method'] = $cb_payment_method;
            $data['cheque_payment_method'] = $cheque_payment_method;
            $data['pa_payment_method'] = $pa_payment_method;
            // Handle the payment method

            if ($this->isset_notempty($cb_payment_method) && $this->isset_notempty($cheque_payment_method) && $this->isset_notempty($pa_payment_method)) {
                // erreur use only one payment method
                $errors['mp_1'] = 'Veuillez uniquement une seule méthode de paiement';
            } elseif (!$this->isset_notempty($cb_payment_method) && !$this->isset_notempty($cheque_payment_method) && !$this->isset_notempty($pa_payment_method)) {
                // at least one payment method
                $errors['mp_2'] = 'Veuillez sélectionner au moins une méthode de paiement';
            } elseif ($this->isset_notempty($cheque_payment_method) && $this->isset_notempty($pa_payment_method)) {
                // error only one other payment
                $errors['mp_3'] = 'Il faut choisir soit prélevement automatique ou chèque ';
            }
            if ($this->isset_notempty($pa_payment_method)) {
                if ($this->container->getParameter('subscription_online')) {
                    if (!$this->isset_notempty($telephone) || !$this->isset_notempty($bic) || !$this->isset_notempty($iban) || !$this->isset_notempty($indicatif)) {
                        $errors['prelev_'] = 'Veuillez SVP revérifier les informations bancaires du prélèvement.';
                    }
                }
            }

            if (count($errors) == 0) {

                $user = $this->get('security.context')->getToken()->getUser();
                $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
                $donateur = $donateurRep->findOneBy(array('user' => $user->getId()));
                $donateur->addCategory($db->getRepository('FulldonDonateurBundle:CategoryDonateur')->findOneBy(array('code' => 'ADHERENTS')));
                $db->flush();
                //creation of donation
                $don = new Don;

                if (!empty($full_montant_r) && isset($full_montant_r) && is_numeric($full_montant_r)) {
                    $montant = $full_montant_r;
                    $subscription = true;
                } elseif (!empty($montant_reguilier) && isset($montant_reguilier)) {
                    $montant = $montant_reguilier;
                    $subscription = true;
                }

                // subscription ?
                if ($subscription) {

                    $periodicite = 1;
                    $date_debut = $this->get('fulldon.intersa.rf_service')->getStartDate();
                    $abo = new Abonnement();
                    $abo->setActif(true);
                    $abo->setRumIndice(1);
                    $don->setAbonnement($abo);
                    $don->setIspa(true);
                    $don->setType($db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET')));
                    $don->getAbonnement()->setPeriodicite($db->getRepository('FulldonDonateurBundle:Periodicite')->find($periodicite));
                    $don->setIspa(true);
                    $don->setMontant($montant);
                    $don->setUser($donateur->getUser());
                    $don->setCause($db->getRepository('FulldonDonateurBundle:Cause')->findOneBy(array('code' => 'don_en_ligne')));
                    $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'PA'));
                    $don->setType($obj_type);
                    $transaction = new Transaction();
                    $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                    $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                    $don->getAbonnement()->setDateFirstPa($date_debut);
                    $don->getAbonnement()->setDateNextPa($date_debut);
                    $transaction->setStatut($statut);
                    $don->setTransaction($transaction);
                    if ($this->isset_notempty($cb_payment_method)) {
                        $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CB_MODE_)));
                    } elseif ($this->isset_notempty($pa_payment_method)) {
                        $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_PA_MODE_)));
                    }
                    $db->persist($don);
                    $db->flush();
                    $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                    $abo->setRum($rum);
                    $db->persist($abo);
                    $db->flush();
                    $html = $this->renderView('FulldonDonateurBundle:Emails:administrator.html.twig', array(
                        'donateur' => $donateur,
                        'don' => $don,
                    ));
                    // Send an information email to the administrator.
                    $admins = explode(',', $this->container->getParameter('administrator_email'));
                    $this->get('fulldon.intersa.email_servies')->sendInfosDonation($admins, $html);

                    if ($this->isset_notempty($cb_payment_method)) {
                        //redirect
                        $accepturl = 'http://' . $request->getHttpHost() . $this->generateUrl('payment_callback_anonymous');
                        $brand = 'CreditCard';
                        if ($request->getLocale() == 'fr') {
                            $lang = 'fr_FR';
                        } else {
                            $lang = 'en_GB';
                        }
                        if ($cb_payment_method == 'PAYPAL')
                            $brand = 'PAYPAL';
                        $date_debut = $this->get('fulldon.intersa.rf_service')->getStartDate();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $period_obj = $don->getAbonnement()->getPeriodicite();
                        $don->getTransaction()->setStatut($statut);
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        if (is_object($donateur->getPays())) {
                            $pays = $donateur->getPays()->getName();

                        }
                        if (is_object($donateur->getVille())) {
                            $ville = $donateur->getVille()->getName();
                        }
                        $options = array(
                            'amount' => $don->getMontant(),
                            'currency' => 'EUR',
                            'default_method' => 'ogone_gateway', // Optional
                            'predefined_data' => array(
                                'ogone_gateway' => array(
                                    'SUB_AM' => $don->getMontant(),
                                    'SUB_AMOUNT' => $don->getMontant() * 100,
                                    'SUB_CUR' => 'EUR',
                                    'SUB_ENDDATE' => '',
                                    'SUB_PERIOD_MOMENT' => $recommanded_day,
                                    'SUB_PERIOD_NUMBER' => $periodicite,
                                    'SUB_PERIOD_UNIT' => 'm',
                                    'SUB_STATUS' => 1,
                                    'SUB_STARTDATE' => $date_debut->format('d/m/Y'),
                                    'SUB_ORDERID' => $don->getId(), // Optional
                                    'PM' => $brand, // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                                    'BRAND' => $cb_payment_method, // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                                    'CN' => $donateur->getNom(), // Optional
                                    'EMAIL' => $donateur->getEmail(), // Optional
                                    'OWNERZIP' => $donateur->getZipcode(), // Optional
                                    'OWNERADDRESS' => $donateur->getAdresse3(), // Optional
                                    'OWNERCTY' => $pays, // Optional
                                    'OWNERTOWN' => $ville, // Optional
                                    'OWNERTELNO' => '',
                                    'lang' => $lang, // 5 characters maximum, for e.g: fr_FR
                                    'SUBSCRIPTION_ID' => $don->getId(), // Optional, 30 characters maximum
                                    'ORDERID' => $don->getId(),
                                    'acceptUrl' => $accepturl,
                                ),
                            ),
                        );

                        $this->ppc->addPlugin($this->pogone);
                        $this->ppc->createPaymentInstruction($instruction = $this->reverseTransform(null, $options));
                        $transaction = new Transaction();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $transaction->setStatut($statut);
                        $transaction->setPaymentInstruction($instruction);
                        $don->setTransaction($transaction);
                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                        $don->setType($obj_type);
                        $db->persist($don);
                        $db->flush($don);
                        $instruction = $don->getTransaction()->getPaymentInstruction();
                        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
                            $payment = $this->ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
                        } else {
                            $payment = $pendingTransaction->getPayment();
                        }

                        $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
                        if (Result::STATUS_PENDING === $result->getStatus()) {
                            $ex = $result->getPluginException();

                            if ($ex instanceof ActionRequiredException) {
                                $action = $ex->getAction();

                                if ($action instanceof VisitUrl) {
                                    return new RedirectResponse($action->getUrl());
                                }

                                throw $ex;
                            }
                        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                            throw new \RuntimeException('Transaction was not successful: ' . $result->getReasonCode());
                        }

                    } elseif ($this->isset_notempty($pa_payment_method)) {

                        // if we have the online subscription
                        if ($this->container->getParameter('subscription_online')) {
                            // Enregistrement de l'iban et bic
                            $iban = strtoupper(trim($iban));
                            $bic = strtoupper(trim($bic));
                            $don->getAbonnement()->setIban($iban);
                            $don->getAbonnement()->setBic($bic);
                            $db->flush();
                            $app_code = $this->container->getParameter('certeurope_certisms_app_code');
                            $identifiant = '0' . $telephone;
                            $url = $this->container->getParameter('certeurope_certisms_url');
                            $session = new Session();

                            // add access to sertisms
                            try {
                                $client = new \SoapClient($this->container->getParameter('certeurope_certisms_wsdl'), array('trace' => 1));
                                $a = array('codeApplication' => $app_code, 'indicaddtifRegional' => $indicatif, 'identifiant' => $identifiant, 'url' => $url, 'paramaters' => null);
                                $response = $client->__soapCall('AddAcces', $a);
                                $filename = uniqid(mt_rand()) . 'mandat_' . $don->getId() . '.pdf';
                                if ($response->error == 0) {
                                    //Afficher la page de confirmation
                                    //Génération du pdf
                                    $this->get('knp_snappy.pdf')->generateFromHtml(
                                        $this->renderView(
                                            'FulldonDonateurBundle:OnePage:mandat_numerique.html.twig',
                                            array(
                                                'don' => $don,
                                                'donateur' => $donateur,
                                                'telephone' => $telephone,
                                                'indicatif' => $indicatif
                                            )
                                        ),
                                        '/' . $this->container->getParameter('folder_app') . '/users/' . $filename,
                                        array(
                                            'page-height' => 100,
                                            'page-width' => 200,
                                            'margin-top' => 0,
                                            'margin-bottom' => 0,
                                            'margin-left' => 0,
                                            'margin-right' => 0,
                                            'no-background' => false
                                        )
                                    );
                                    // Générer un PDF
                                    // Display confirmation page
                                    $session->set('don_id', $don->getId());
                                    return $this->render('FulldonDonateurBundle:OnePage:mandat_confirmation.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'mandat_filename' => $filename, 'mandat_telephone' => $telephone, 'indicatif' => $indicatif));
                                } else {
                                    //Rediriger l'utilisateur vers la page courante avec un message d'erreur.
                                }
                            } catch (\SoapFault $e) {
                                var_dump($e->getMessage(), $client->__getLastResponse());
                                throw new \Exception('Un problème technique est survenu, veuillez contacter l\'administrateur!');
                            }
                            //return array('response' => $response);

                        } else {
                            $d2 = $this->get('fulldon.donateur.barecodes');
                            $string = $don->getId() . "|edon";
                            $d2->setStrToEncode($string);
                            //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                            $approx_size = 60;
                            // Et on trace le code barre avec comme argument la taille en pixels voulue
                            $filename = 'datamatrix' . '_' . $don->getId();
                            $d2->stroke($approx_size, $filename);
                            $assoc_name = $this->container->getParameter('assoc_name');
                            $this->get('session')->getFlashBag()->add('info', 'Votre prélèvement automatique est désormais en cours de traitement,merci pour votre générosité.');
                            return $this->render('CustomFulldonExtDonateurBundle:print:virement_adhesion.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'datamatrix' => $filename, 'assoc_name' => $assoc_name));
                        }

                    }

                }


            } else {
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add('error', $error);
                    $display_error = true;
                }
            }
        }


        //check form values
        //redirect to the appropriate page
        return $this->render('FulldonDonateurBundle:OnePage:adh_auth.html.twig', array('init' => $this->init, 'display_error' => $display_error, 'data' => $data, 'countries' => $countries, 'sub_cb' => $sub_cb, 'is_company' => $isCompany));
    }
    private function isset_notempty($val)
    {
        return isset($val) && !empty($val);
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

    public function reverseTransform($data, array $options)
    {
        $method = isset($data['method']) ? $data['method'] : null;
        $data = isset($data['data_' . $method]) ? $data['data_' . $method] : array();
        //Fake
        $method = 'ogone_gateway';
        $extendedData = new ExtendedData();
        foreach ($data as $k => $v) {
            $extendedData->set($k, $v);
        }

        if (isset($options['predefined_data'][$method])) {
            if (!is_array($options['predefined_data'][$method])) {
                throw new \RuntimeException(sprintf('"predefined_data" is expected to be an array for each method, but got "%s" for method "%s".', json_encode($options['extra_data'][$method]), $method));
            }

            foreach ($options['predefined_data'][$method] as $k => $v) {
                $extendedData->set($k, $v);
            }
        }

        $amount = $this->computeAmount($options['amount'], $options['currency'], $method, $extendedData);

        return new PaymentInstruction($amount, $options['currency'], $method, $extendedData);
    }

    private function computeAmount($amount, $currency, $method, ExtendedData $extendedData)
    {
        if ($amount instanceof \Closure) {
            return $amount($currency, $method, $extendedData);
        }

        return $amount;
    }

    function validateDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public function unsubscribeAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repEmailingInterface = $db->getRepository('FulldonDonateurBundle:EmailingInterface');
        $eI = $repEmailingInterface->findOneBy(array('uniqueId' => $id));
        $donateur = null;
        if (is_object($eI)) {
            $donateur = $eI->getDonateur();
            $receptionModeRep = $db->getRepository('FulldonDonateurBundle:ReceptionMode');
            $rm = $receptionModeRep->findOneBy(array('code' => Vars\DonVars::_COM_EMAIL_));
            $donateur->removeReceptionMode($rm);
            $db->flush();
            $this->get('session')->getFlashBag()->add('success', 'Votre désabonnement a été pris en compte.');
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Désabonnement impossible : donateur introuvable');
        }
        return $this->render('FulldonDonateurBundle:OnePage:unsubscribe.html.twig', array('init' => $this->init, 'donateur' => $donateur));
    }

    public function viewHtmlAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repEmailingInterface = $db->getRepository('FulldonDonateurBundle:EmailingInterface');
        $eI = $repEmailingInterface->findOneBy(array('uniqueId' => $id));

        return $this->render('FulldonDonateurBundle:OnePage:viewhtml.html.twig', array('init' => $this->init, 'content' => $eI->getContent()));
    }

    public function signeDocumentAction()
    {
        $db = $this->getDoctrine()->getManager();
        $request = Request::createFromGlobals();
        $app_code = $this->container->getParameter('certeurope_oneclick_app_code');
        $app_code_certi = $this->container->getParameter('certeurope_certisms_app_code');
        $indicatif = '33';
        $url = $this->container->getParameter('certeurope_oneclick_url');
        $session = new Session();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $donRep = $db->getRepository('FulldonDonateurBundle:Don');
        $don = $donRep->find($session->get('don_id'));
        $donateur = $donateurRep->findOneBy(array('user' => $don->getUser()));
        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
        if ($request->getMethod() == 'POST') {
            // vérification des informations .
            $codeSMS = $request->get('sms_code');
            $mandat_check = $request->get('mandat_check');
            $mandat_telephone = $request->get('mandat_telephone');
            $mandat_filename = $request->get('mandat_filename');
            $indicatif = $request->get('indicatif');
            $identifiant = '0' . $mandat_telephone;
            try {
                $client = new \SoapClient($this->container->getParameter('certeurope_certisms_wsdl'), array('trace' => 1));
                $a = array('codeApplication' => $app_code_certi, 'indicaddtifRegional' => $indicatif, 'identifiant' => $identifiant, 'code' => $codeSMS);
                $response = $client->__soapCall('CheckAcces', $a);
                $filename = '/' . $this->container->getParameter('folder_app') . '/users/' . $mandat_filename;
                $file_contents = file_get_contents($filename);
                $file_sha1 = sha1_file($filename);
                $signedPdf = $mandat_filename;

                if ($response->error == 0) {
                    $xml = '<?xml version="1.0" encoding="UTF-8" ?>';
                    $xml .= "<REQUEST>";
                    $xml .= "<TYPE>1</TYPE>";
                    $xml .= "<APPLICATION_CODE>$app_code</APPLICATION_CODE>";
                    $xml .= "<REQUESTER_IDENTIFICATION>";
                    $xml .= "<NAME>{$donateur->getNom()}</NAME>";
                    $xml .= "<FIRSTNAME>{$donateur->getPrenom()}</FIRSTNAME>";
                    $xml .= "<EMAIL>{$donateur->getEmail()}</EMAIL>";
                    $xml .= "<ORGANISATION>  </ORGANISATION>";
                    $xml .= "</REQUESTER_IDENTIFICATION>";
                    $xml .= "<DOCUMENTS>";
                    $xml .= "<DOCUMENT>";
                    $xml .= "<TYPE>1</TYPE>";
                    $xml .= "<PARAM_SIGNATURE_PDF>";
                    $xml .= "<SIGNATURE_PDF_PAGE>1</SIGNATURE_PDF_PAGE>";
                    $xml .= "				<SIGNATURE_PDF_IMAGE>0</SIGNATURE_PDF_IMAGE>
				<SIGNATURE_PDF_LOGO_LLX>10</SIGNATURE_PDF_LOGO_LLX>
				<SIGNATURE_PDF_LOGO_LLY>10</SIGNATURE_PDF_LOGO_LLY>
				<SIGNATURE_PDF_LOGO_URX>240</SIGNATURE_PDF_LOGO_URX>
				<SIGNATURE_PDF_LOGO_URY>50</SIGNATURE_PDF_LOGO_URY>";
                    $xml .= "</PARAM_SIGNATURE_PDF>";
                    $xml .= "<SIGNATURE>0</SIGNATURE>";
                    $xml .= "<NAME>$mandat_filename</NAME>";
                    $xml .= "<HASH>$file_sha1</HASH>";
                    $xml .= "<ALGO>SHA1</ALGO>";
                    $xml .= "<WITHDRAWAL></WITHDRAWAL>
                            <DEPOSIT></DEPOSIT>";
                    $xml .= "</DOCUMENT>";
                    $xml .= "</DOCUMENTS>";
                    $xml .= "</REQUEST>";

                    define('MULTIPART_BOUNDARY', '--------------------------' . microtime(true));
                    $header = 'Content-Type: multipart/form-data; boundary=' . MULTIPART_BOUNDARY;
                    $content = "--" . MULTIPART_BOUNDARY . "\r\n" .
                        "Content-Disposition: form-data; name=\"file1\"; filename=\"" . $mandat_filename . "\"\r\n" .
                        "Content-Type: application/pdf\r\n\r\n" .
                        $file_contents . "\r\n";

                    // add some POST fields to the request too: $_POST['foo'] = 'bar'
                    $content .= "--" . MULTIPART_BOUNDARY . "\r\n" .
                        "Content-Disposition: form-data; name=\"xmlReq\"\r\n\r\n" .
                        $xml . "\r\n";

                    // signal end of request (note the trailing "--")
                    $content .= "--" . MULTIPART_BOUNDARY . "--\r\n";


                    //Récupération et téléchargement du fichier
                    //Redirection vers la page resultat
                    $params = array('http' => array(
                        'method' => 'POST',
                        'header' => $header,
                        'content' => $content
                    ));

                    $ctx = stream_context_create($params);
                    $fp = @fopen($url, 'rb', false, $ctx);

                    if (!$fp) {
                        throw new Exception("Problem with $url, $php_errormsg");
                    }
                    $response = @stream_get_contents($fp);

                    if ($response === false) {
                        throw new Exception("Problem reading data from $url, $php_errormsg");
                        $this->get('session')->getFlashBag()->add('error', 'Veuillez SVP vérifier votre code SMS !');
                    } else {
                        $myfile = fopen('/' . $this->container->getParameter('folder_app') . '/signed_pdf/' . $signedPdf, "w");
                        fwrite($myfile, $response);
                        fclose($myfile);
                        // view Pdf
                        $don->getAbonnement()->setSignedFile($signedPdf);
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_DON_VALIDE_);
                        $transaction = new Transaction();
                        $transaction->setStatut($statut);
                        $don->setTransaction($transaction);
                        $db->flush();

                    }
                } else {
                    $this->get('session')->getFlashBag()->add('error', 'Veuillez SVP vérifier votre code SMS !');
                    try {
                        $client = new \SoapClient($this->container->getParameter('certeurope_certisms_wsdl'), array('trace' => 1));
                        $a = array('codeApplication' => $app_code_certi, 'indicaddtifRegional' => '33', 'identifiant' => $identifiant, 'url' => $url, 'paramaters' => null);
                        $response = $client->__soapCall('AddAcces', $a);
                    } catch (\SoapFault $e) {

                        var_dump($e->getMessage(), $client->__getLastResponse());
                        throw new \Exception('Un problème technique est survenu, veuillez contacter l\'administrateur!');
                    }

                    //Rediriger l'utilisateur vers la page courante avec un message d'erreur.
                    return $this->render('FulldonDonateurBundle:OnePage:mandat_confirmation.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'mandat_filename' => $mandat_filename, 'mandat_telephone' => $mandat_telephone, 'indicatif' => $indicatif));

                }
            } catch (\SoapFault $e) {

                var_dump($e->getMessage(), $client->__getLastResponse());
                throw new \Exception('Un problème technique est survenu, veuillez contacter l\'administrateur!');
            }

            //Click one sign.

        }
        return $this->render('FulldonDonateurBundle:OnePage:signed_mandat.html.twig', array('init' => $this->init, 'file' => $mandat_filename));
    }

    public function eventAction()
    {
        //Extract all countries
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $eventRep = $db->getRepository('FulldonDonateurBundle:Event');
        $events = $eventRep->findBy(array('actif' => true));
        $countries = Intl::getRegionBundle()->getCountryNames();
        $request = $this->getRequest();
        $errors = array();
        $display_error = false;
        $subscription = false;
        $data = array();
        $db = $this->getDoctrine()->getManager();
        $session = new Session();
        //test if it's a post method
        if ($request->getMethod() == 'POST') {
            //donor informations
            $email = $request->get('email');
            $civilite = $request->get('civilite');
            $prenom = $request->get('prenom');
            $nom = $request->get('nom');
            $adresse1 = $request->get('adresse1');
            $adresse2 = $request->get('adresse2');
            $zipcode = $request->get('zipcode');
            $ville = $request->get('ville');
            $pays = $request->get('pays');
            $iban = $request->get('iban');
            $bic = $request->get('bic');
            $telephone = $request->get('telephone');
            $indicatif = $request->get('indicatif');
            $raisonSociale = $request->get('raison_sociale');

            $form_token = $request->get('token');
            $min_don_r = 5;
            $gmontant = 0;
            $event_options =  array();
            if ($form_token == $session->get('token')) {
                $data['email'] = $email;
                if (!isset($email) || empty($email)) {
                    $errors['email'] = 'Veuillez spécifier un email valide';
                }
                if(!$this->container->get('fulldon.intersa.global')->isNewEmail($email)) {
                    $errors['email'] = 'Cet email est déjà utilisé';
                }
                $data['civilite'] = $civilite;
                if (!isset($civilite) || empty($civilite)) {
                    $errors['civilite'] = 'La Civilité n\'est un fournie valide';
                }
                $data['prenom'] = $prenom;
                if (!isset($prenom) || empty($prenom)) {
                    $errors['prenom'] = 'Veuillez spécifier un prénom valide';
                }
                $data['nom'] = $nom;
                if(!isset($raisonSociale) || empty($raisonSociale)) {
                    if (!isset($nom) || empty($nom)) {
                        $errors['nom'] = 'Veuillez spécifier un nom valide';
                    }
                }

                $data['adresse1'] = $adresse1;
                $data['adresse2'] = $adresse2;
                if (!isset($adresse1) || empty($adresse1)) {
                    $errors['adresse1'] = 'Veuillez spécifier un adresse valide';
                }
                $data['zipcode'] = $zipcode;
                if (!isset($zipcode) || empty($zipcode) || !is_numeric($zipcode)) {
                    $errors['zipcode'] = 'Veuillez spécifier un code postal valide';
                }
                $data['ville'] = $ville;
                if (!isset($ville) || empty($ville)) {
                    $errors['ville'] = 'Veuillez spécifier un ville valide';
                }
                $data['pays'] = $pays;
                if (!isset($pays) || empty($pays)) {
                    $errors['pays'] = 'Veuillez spécifier un pays valide';
                }

                foreach ($events as $event) {
                    $val = $request->request->get('event_' . $event->getId());
                    if (isset($val) && !empty($val)) {
                        $arr_val = explode('_', $val);
                        $amount = 0;
                        if ($arr_val[0] == 'nadh') {
                            $amount = $event->getPrixNonAdh();
                            $event_options[] = $event->getId().':'.$event->getPrixNonAdh().':nadh';
                        } elseif ($arr_val[0] == 'adh') {
                            $amount = $event->getPrixAdh();
                            $event_options[] = $event->getId().':'.$event->getPrixAdh().':adh';
                        }
                        $gmontant += $amount;

                    }
                }
                if ($gmontant == 0) {
                    $errors['event'] = 'Veuillez séléctionner au moins un événement !';
                }




                // payement information

                $cb_payment_method = $request->get('payment_method');
                $cheque_payment_method = $request->get('cheque');
                $pa_payment_method = $request->get('pa');
                $data['cb_payment_method'] = $cb_payment_method;
                $data['raison_sociale'] = $raisonSociale;
                // Handle the payment method

                if (!$this->isset_notempty($cb_payment_method)) {
                    // at least one payment method
                    $errors['mp_2'] = 'Veuillez sélectionner au moins une méthode de paiement';
                }
                if (count($errors) == 0) {

                    $donateur = new Donateur;
                    // Création du donateur
                    $duser = new User();
                    $i = 0;

                    do {
                        $i++;
                        $login = 'donateur' . $i;
                        $plain_password = base64_encode('p@s' . $i);
                    } while (!$this->isNewUser($login));

                    $duser->setUsername($login);
                    $duser->setPassword($plain_password);
                    $donateur->setUser($duser);
                    // On enregistre notre objet $article dans la base de données
                    $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                    $donateur->getUser()->setIsActive(true);
                    $repRole = $db->getRepository('FulldonSecurityBundle:Role');
                    $role_donateur = $repRole->findOneBy(array('role' => 'ROLE_DONATEUR'));
                    $donateur->getUser()->addRole($role_donateur);
                    // Set encrypted password
                    $encoder = $this->container->get('security.encoder_factory')
                        ->getEncoder($donateur->getUser());
                    $password = $encoder->encodePassword($donateur->getUser()->getPassword(), $donateur->getUser()->getSalt());
                    $donateur->getUser()->setPassword($password);

                    //Pays & ville
                    $donateur->setIsopays($pays);
                    $donateur->setIsoville($ville);

                    $rm1 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'courrier'));
                    $rm2 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'email'));
                    $rm3 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'sms'));

                    $donateur->addReceptionMode($rm1);
                    $donateur->addReceptionMode($rm2);
                    $donateur->addReceptionMode($rm3);
                    $donateur->setEmail($email);
                    $donateur->setCivilite($civilite);
                    $donateur->setNom($nom);
                    if (isset($raisonSociale) && !empty($raisonSociale)) {
                        $donateur->setNomEntreprise($raisonSociale);
                    }
                    $donateur->setPrenom($prenom);
                    $donateur->setAdresse3($adresse1);
                    $donateur->setAdresse4($adresse2);
                    $donateur->setZipcode($zipcode);

                    $donateur->setAllowRf(true);
                    $donateur->setRemoved(false);
                    $refId = $this->get('fulldon.intersa.global')->getUniqueRefDonateur();
                    $donateur->setRefDonateur($refId);
                    //$donateur->setPays();
                    //$donateur->setVille();
                    //Gestion des doublons
                    $percent = 0;
                    $pid = 0;
                    $pdonateur = $donateurRep->findOneBy(array('nom' => $donateur->getNom(), 'prenom' => $donateur->getPrenom(), 'removed' => false));
                    if ($pdonateur) {
                        $percent += 60;
                        $pid = $pdonateur->getId();
                        $pdonateur2 = $donateurRep->findOneBy(array('adresse3' => $donateur->getAdresse3(), 'id' => $pid));
                        if ($pdonateur2) {
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
                        $current_user = $donateur->getUser();
                        $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_DOUBLON_CREATED_);
                        $dispatcher = $this->get('event_dispatcher');
                        $dispatcher->dispatch(StatVar::CREATE, $event);

                    } else {
                        $db->persist($donateur);
                        $db->flush();
                    }

                    //Génération du pdf
                    $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->renderView(
                            'FulldonIntersaBundle:Donateurs:pdf/identifiants.html.twig',
                            array(
                                'login' => $donateur->getUser()->getUsername(),
                                'password' => $plain_password,
                                'donateur' => $donateur,

                            )
                        ),
                        '/' . $this->container->getParameter('folder_app') . '/users/donateur_fiche_' . $donateur->getId() . '.pdf'
                    );


                    //creation of donation
                    $don = new Don;
                    $don->setType($db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET')));
                    $don->setIspa(false);
                    $don->setMontant($gmontant);
                    $don->setUser($donateur->getUser());
                    $don->setCause($db->getRepository('FulldonDonateurBundle:Cause')->findOneBy(array('code' => 'don_en_ligne')));
                    $transaction = new Transaction();
                    $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                    $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                    $transaction->setStatut($statut);
                    $don->setTransaction($transaction);
                    if ($this->isset_notempty($cb_payment_method)) {
                        $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CB_MODE_)));

                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                        $don->setType($obj_type);
                    }
                    $db->persist($don);
                    $db->flush();

                    // Send an information email to the administrator.
                    $html = $this->renderView('FulldonDonateurBundle:Emails:administrator.html.twig', array(
                        'donateur' => $donateur,
                        'don' => $don,
                    ));
                    $admins = explode(',', $this->container->getParameter('administrator_email'));
                    $this->get('fulldon.intersa.email_servies')->sendInfosDonation($admins, $html);


                    if ($this->isset_notempty($cb_payment_method)) {

                        //redirect
                        $accepturl = 'http://' . $request->getHttpHost() . $this->generateUrl('payment_callback_anonymous');
                        $brand = 'CreditCard';
                        if ($request->getLocale() == 'fr') {
                            $lang = 'fr_FR';
                        } else {
                            $lang = 'en_GB';
                        }
                        if ($cb_payment_method == 'PAYPAL')
                            $brand = 'PAYPAL';

                        if (is_object($donateur->getPays())) {
                            $pays = $donateur->getPays()->getName();
                        }

                        if (is_object($donateur->getVille())) {
                            $ville = $donateur->getVille()->getName();
                        }
                        $options = array(
                            'amount' => $don->getMontant(),
                            'currency' => 'EUR',
                            'default_method' => 'ogone_gateway', // Optional
                            'predefined_data' => array(
                                'ogone_gateway' => array(
                                    'tp' => '', // Optional
                                    'PM' => $brand, // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                                    'BRAND' => $cb_payment_method, // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                                    'CN' => $donateur->getNom(), // Optional
                                    'EMAIL' => $donateur->getEmail(), // Optional
                                    'OWNERZIP' => $donateur->getZipcode(), // Optional
                                    'OWNERADDRESS' => $donateur->getAdresse3(), // Optional
                                    'OWNERCTY' => $ville, // Optional
                                    'OWNERTOWN' => $pays, // Optional
                                    'OWNERTELNO' => '',
                                    'lang' => $lang, // 5 characters maximum, for e.g: fr_FR
                                    'ORDERID' => $don->getId(), // Optional, 30 characters maximum
                                    'acceptUrl' => $accepturl,
                                ),
                            ),
                        );


                        $this->ppc->addPlugin($this->pogone);
                        $this->ppc->createPaymentInstruction($instruction = $this->reverseTransform(null, $options));
                        $transaction = new Transaction();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $transaction->setStatut($statut);
                        $transaction->setPaymentInstruction($instruction);
                        $don->setTransaction($transaction);
                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                        $don->setType($obj_type);

                        foreach ($events as $event) {
                            $val = $request->request->get('event_' . $event->getId());
                            if (isset($val) && !empty($val)) {
                                $don->addEvent($event);
                            }
                        }
                        $don->setEventOption(implode('|', $event_options));
                        $db->persist($don);
                        $db->flush($don);
                        //Envoi Email
                        $email = $donateur->getEmail();
                        if (isset($email) && !empty($email)) {
                            $html = $this->renderView('FulldonIntersaBundle:Email:send_new_event.html.twig', array(
                                'don' => $don,
                                'donateur' => $donateur,
                                'init' => $this->init,
                            ));
                            $tag = $this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_notification');
                            $objet = Email::_OBJET_CONFIRM_EVENT_ ;
                            $this->get('fulldon.intersa.email_servies')->sendEmail(array($email), $html,$objet,$tag, null, null);
                        }
                        // Get montant
                        $instruction = $don->getTransaction()->getPaymentInstruction();
                        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
                            $payment = $this->ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
                        } else {
                            $payment = $pendingTransaction->getPayment();
                        }

                        $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
                        if (Result::STATUS_PENDING === $result->getStatus()) {
                            $ex = $result->getPluginException();

                            if ($ex instanceof ActionRequiredException) {
                                $action = $ex->getAction();

                                if ($action instanceof VisitUrl) {
                                    return new RedirectResponse($action->getUrl());
                                }
                                throw $ex;
                            }
                        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                            throw new \RuntimeException('Transaction was not successful: ' . $result->getReasonCode());
                        }
                    }

                } else {
                    foreach ($errors as $key => $error) {
                        $this->get('session')->getFlashBag()->add('error', $error);
                        $display_error = true;
                    }
                }
            } else {
                // Alert security
                $this->get('session')->getFlashBag()->add('error', 'Session expirée');
                return $this->redirect($this->generateUrl('fulldon_donateur_onepage'));
            }
        }
        //Security form
        $token = sha1(uniqid(rand(), TRUE));
        $session->set('token', $token);

        //check form values
        //redirect to the appropriate page
        return $this->render('FulldonDonateurBundle:OnePage:event.html.twig', array('init' => $this->init, 'display_error' => $display_error, 'data' => $data, 'countries' => $countries, 'token' => $token, 'events' => $events));
    }

    function adherentAction()
    {

        //Extract all countries
        $db = $this->getDoctrine()->getManager();
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
        $countries = Intl::getRegionBundle()->getCountryNames();
        $request = $this->getRequest();
        $errors = array();
        $display_error = false;
        $subscription = false;
        $data = array();
        $db = $this->getDoctrine()->getManager();
        $session = new Session();
        //test if it's a post method
        if ($request->getMethod() == 'POST') {
            //donor informations
            $email = $request->get('email');
            $civilite = $request->get('civilite');
            $prenom = $request->get('prenom');
            $nom = $request->get('nom');
            $adresse1 = $request->get('adresse1');
            $adresse2 = $request->get('adresse2');
            $zipcode = $request->get('zipcode');
            $ville = $request->get('ville');
            $pays = $request->get('pays');
            $iban = $request->get('iban');
            $bic = $request->get('bic');
            $telephone = $request->get('telephone');
            $indicatif = $request->get('indicatif');
            $raisonSociale = $request->get('raison_sociale');
            $form_token = $request->get('token');
            $min_don_r = 5;
            $company_check = $request->get('company_check');


            if ($form_token == $session->get('token')) {
                $data['email'] = $email;
                if (!isset($email) || empty($email)) {
                    $errors['email'] = 'Veuillez spécifier un email valide';
                }
                if(!$this->container->get('fulldon.intersa.global')->isNewEmail($email)) {
                    $errors['email'] = 'Cet email est déjà utilisé';
                }
                $data['civilite'] = $civilite;
                if (!isset($civilite) || empty($civilite)) {
                    $errors['civilite'] = 'La Civilité n\'est un fournie valide';
                }
                $data['prenom'] = $prenom;
                if (!isset($prenom) || empty($prenom)) {
                    $errors['prenom'] = 'Veuillez spécifier un prénom valide';
                }

                if(!isset($company_check) || empty($company_check)) {
                    $data['civilite'] = $civilite;
                    if (!isset($civilite) || empty($civilite)) {
                        $errors['civilite'] = 'La Civilité n\'est un fournie valide';
                    }
                    $data['prenom'] = $prenom;
                    if (!isset($prenom) || empty($prenom)) {
                        $errors['prenom'] = 'Veuillez spécifier un prénom valide';
                    }
                    $data['nom'] = $nom;
                    if (!isset($nom) || empty($nom)) {
                        $errors['nom'] = 'Veuillez spécifier un nom valide';
                    }
                } else {
                    if (!isset($raisonSociale) || empty($raisonSociale)) {
                        $errors['raison_sociale'] = 'Veuillez spécifier une raison sociale';
                    }
                }
                $data['adresse1'] = $adresse1;
                $data['adresse2'] = $adresse2;
                if (!isset($adresse1) || empty($adresse1)) {
                    $errors['adresse1'] = 'Veuillez spécifier un adresse valide';
                }
                $data['zipcode'] = $zipcode;
                if (!isset($zipcode) || empty($zipcode) || !is_numeric($zipcode)) {
                    $errors['zipcode'] = 'Veuillez spécifier un code postal valide';
                }
                $data['ville'] = $ville;
                if (!isset($ville) || empty($ville)) {
                    $errors['ville'] = 'Veuillez spécifier un ville valide';
                }
                $data['pays'] = $pays;
                if (!isset($pays) || empty($pays)) {
                    $errors['pays'] = 'Veuillez spécifier un pays valide';
                }

                // amount information
                //$full_montant_p = $request->get('full_montant_p');
                $full_montant_r = $request->get('full_montant_r');
                //$montant_ponctuel = $request->get('montant_ponctuel');
                $montant_reguilier = $request->get('montant_reguilier');
                // $data['full_montant_p'] = $full_montant_p;
                $data['full_montant_r'] = $full_montant_r;
                //$data['montant_ponctuel'] = $montant_ponctuel;
                $data['montant_reguilier'] = $montant_reguilier;
                // Handle the amount

                if ( /*$this->isset_notempty($montant_ponctuel) && !is_numeric($montant_ponctuel)) ||*/
                    ($this->isset_notempty($montant_reguilier) && !is_numeric($montant_reguilier)) ||
                    ($this->isset_notempty($montant_reguilier) && $montant_reguilier < $min_don_r)
                ) {
                    $errors['mp_v'] = 'Veuillez spécifier un montant valide';
                }

                if ( /*!$this->isset_notempty($full_montant_p) && !$this->isset_notempty($montant_ponctuel) &&*/
                    !$this->isset_notempty($full_montant_r) && !$this->isset_notempty($montant_reguilier)
                ) {
                    // error il faut définir un montant
                    $errors['mp_1'] = 'Veuillez SVP spécifier le montant du don';
                }

                // payement information

                $cb_payment_method = $request->get('payment_method');
                $cheque_payment_method = $request->get('cheque');
                $pa_payment_method = $request->get('pa');
                $data['cb_payment_method'] = $cb_payment_method;
                $data['cheque_payment_method'] = $cheque_payment_method;
                $data['pa_payment_method'] = $pa_payment_method;
                $data['raison_sociale'] = $raisonSociale;
                // Handle the payment method

                if ($this->isset_notempty($cb_payment_method) && $this->isset_notempty($cheque_payment_method) && $this->isset_notempty($pa_payment_method)) {
                    // erreur use only one payment method
                    $errors['mp_1'] = 'Veuillez sélectionner uniquement une seule méthode de paiement';
                } elseif (!$this->isset_notempty($cb_payment_method) && !$this->isset_notempty($cheque_payment_method) && !$this->isset_notempty($pa_payment_method)) {
                    // at least one payment method
                    $errors['mp_2'] = 'Veuillez sélectionner au moins une méthode de paiement';
                } elseif ($this->isset_notempty($cheque_payment_method) && $this->isset_notempty($pa_payment_method)) {
                    // error only one other payment
                    $errors['mp_3'] = 'Il faut choisir soit prélevement automatique soit chèque ';
                }
                if ($this->isset_notempty($pa_payment_method)) {
                    if ($this->container->getParameter('subscription_online')) {
                        if (!$this->isset_notempty($telephone) || !$this->isset_notempty($bic) || !$this->isset_notempty($iban) || !$this->isset_notempty($indicatif)) {
                            $errors['prelev_'] = 'Veuillez SVP revérifier les informations bancaires du prélèvement.';
                        }
                    }
                }
                if (count($errors) == 0) {

                    $donateur = new Donateur;
                    // Création du donateur
                    $duser = new User();
                    $i = 0;

                    do {
                        $i++;
                        $login = 'donateur' . $i;
                        $plain_password = base64_encode('p@s' . $i);
                    } while (!$this->isNewUser($login));

                    $duser->setUsername($login);
                    $duser->setPassword($plain_password);
                    $donateur->setUser($duser);
                    // On enregistre notre objet $article dans la base de données
                    $donateur->getUser()->setSalt(uniqid(mt_rand())); // Unique salt for user
                    $donateur->getUser()->setIsActive(true);
                    $repRole = $db->getRepository('FulldonSecurityBundle:Role');
                    $role_donateur = $repRole->findOneBy(array('role' => 'ROLE_DONATEUR'));
                    $donateur->getUser()->addRole($role_donateur);
                    // Set encrypted password
                    $encoder = $this->container->get('security.encoder_factory')
                        ->getEncoder($donateur->getUser());
                    $password = $encoder->encodePassword($donateur->getUser()->getPassword(), $donateur->getUser()->getSalt());
                    $donateur->getUser()->setPassword($password);

                    //Pays & ville
                    $donateur->setIsopays($pays);
                    $donateur->setIsoville($ville);

                    $rm1 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'courrier'));
                    $rm2 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'email'));
                    $rm3 = $db->getRepository('FulldonDonateurBundle:ReceptionMode')->findOneBy(array('code' => 'sms'));

                    $donateur->addReceptionMode($rm1);
                    $donateur->addReceptionMode($rm2);
                    $donateur->addReceptionMode($rm3);
                    $donateur->setEmail($email);
                    $donateur->setCivilite($civilite);
                    $donateur->setNom($nom);
                    if (isset($raisonSociale) && !empty($raisonSociale)) {
                        $donateur->setNomEntreprise($raisonSociale);
                    }
                    $donateur->setPrenom($prenom);
                    $donateur->setAdresse3($adresse1);
                    $donateur->setAdresse4($adresse2);
                    $donateur->setZipcode($zipcode);
                    $donateur->addCategory($db->getRepository('FulldonDonateurBundle:CategoryDonateur')->findOneBy(array('code' => 'ADHERENTS')));
                    $donateur->setAllowRf(true);
                    $donateur->setRemoved(false);
                    $refId = $this->get('fulldon.intersa.global')->getUniqueRefDonateur();
                    $donateur->setRefDonateur($refId);
                    //$donateur->setPays();
                    //$donateur->setVille();
                    //Gestion des doublons
                    $percent = 0;
                    $pid = 0;
                    $pdonateur = $donateurRep->findOneBy(array('nom' => $donateur->getNom(), 'prenom' => $donateur->getPrenom(), 'removed' => false));
                    if ($pdonateur) {
                        $percent += 60;
                        $pid = $pdonateur->getId();
                        $pdonateur2 = $donateurRep->findOneBy(array('adresse3' => $donateur->getAdresse3(), 'id' => $pid));
                        if ($pdonateur2) {
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
                        $current_user = $donateur->getUser();
                        $event = HistoryStatEvent::constr1($current_user, StatVar::_STAT_TYPE_DOUBLON_CREATED_);
                        $dispatcher = $this->get('event_dispatcher');
                        $dispatcher->dispatch(StatVar::CREATE, $event);

                    } else {
                        $db->persist($donateur);
                        $db->flush();
                    }

                    //Génération du pdf
                    $this->get('knp_snappy.pdf')->generateFromHtml(
                        $this->renderView(
                            'FulldonIntersaBundle:Donateurs:pdf/identifiants.html.twig',
                            array(
                                'login' => $donateur->getUser()->getUsername(),
                                'password' => $plain_password,
                                'donateur' => $donateur,

                            )
                        ),
                        '/' . $this->container->getParameter('folder_app') . '/users/donateur_fiche_' . $donateur->getId() . '.pdf'
                    );
                    //Envoi Email
                    $email = $donateur->getEmail();
                    if (isset($email) && !empty($email)) {
                        $html = $this->renderView('FulldonIntersaBundle:Email:send_new_adhesion.html.twig', array(
                            'login' => $donateur->getUser()->getUsername(),
                            'password' => $plain_password,
                            'donateur' => $donateur,
                            'init' => $this->init,
                        ));
                        $objet = Email::_OBJET_CONFIRM_ADHESION_.' '.$this->container->getParameter('assoc_name');
                        $tag = $this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_notification');
                        $this->get('fulldon.intersa.email_servies')->sendEmail(array($email), $html,$objet,$tag,null,null);
                    }

                    //creation of donation
                    $don = new Don;

                    if (!empty($full_montant_r) && isset($full_montant_r) && is_numeric($full_montant_r)) {
                        $montant = $full_montant_r;
                        $subscription = true;
                    } elseif (!empty($montant_reguilier) && isset($montant_reguilier)) {
                        $montant = $montant_reguilier;
                        $subscription = true;
                    }

                    // subscription ?
                    if ($subscription) {
                        $periodicite = 1;
                        $date_debut = $this->get('fulldon.intersa.rf_service')->getStartDate();
                        $abo = new Abonnement();
                        $abo->setActif(true);
                        $abo->setRumIndice(1);
                        $don->setAbonnement($abo);
                        $don->setIspa(true);
                        $don->setType($db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET')));
                        $don->getAbonnement()->setPeriodicite($db->getRepository('FulldonDonateurBundle:Periodicite')->find($periodicite));
                        $don->setIspa(true);
                        $don->setMontant($montant);
                        $don->setUser($donateur->getUser());
                        $don->setCause($db->getRepository('FulldonDonateurBundle:Cause')->findOneBy(array('code' => 'don_en_ligne')));
                        $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'PA'));
                        $don->setType($obj_type);
                        $transaction = new Transaction();
                        $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                        $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                        $don->getAbonnement()->setDateFirstPa($date_debut);
                        $don->getAbonnement()->setDateNextPa($date_debut);
                        $transaction->setStatut($statut);
                        $don->setTransaction($transaction);
                        if ($this->isset_notempty($cb_payment_method)) {
                            $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_CB_MODE_)));
                        } elseif ($this->isset_notempty($pa_payment_method)) {
                            $don->setModePaiement($db->getRepository('FulldonDonateurBundle:ModePaiement')->findOneBy(array('codeSolution' => Vars\DonVars::_PA_MODE_)));
                        }
                        $db->persist($don);
                        $db->flush();
                        $rum = '01-' . str_pad($donateur->getId(), 10, 0, STR_PAD_LEFT) . '-R' . str_pad($don->getId(), 10, 0, STR_PAD_LEFT) . str_pad(1, 6, 0, STR_PAD_LEFT);
                        $abo->setRum($rum);
                        $db->persist($abo);
                        $db->flush();
                        $html = $this->renderView('FulldonDonateurBundle:Emails:administrator.html.twig', array(
                            'donateur' => $donateur,
                            'don' => $don,
                        ));
                        // Send an information email to the administrator.
                        $admins = explode(',', $this->container->getParameter('administrator_email'));
                        $this->get('fulldon.intersa.email_servies')->sendInfosDonation($admins, $html);

                        if ($this->isset_notempty($cb_payment_method)) {
                            //redirect
                            $accepturl = 'http://' . $request->getHttpHost() . $this->generateUrl('payment_callback_anonymous');
                            $brand = 'CreditCard';
                            if ($request->getLocale() == 'fr') {
                                $lang = 'fr_FR';
                            } else {
                                $lang = 'en_GB';
                            }
                            if ($cb_payment_method == 'PAYPAL')
                                $brand = 'PAYPAL';


                            $date_debut = $this->get('fulldon.intersa.rf_service')->getStartDate();

                            $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                            $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                            $period_obj = $don->getAbonnement()->getPeriodicite();
                            $don->getTransaction()->setStatut($statut);
                            $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                            if (is_object($donateur->getPays())) {
                                $pays = $donateur->getPays()->getName();

                            }
                            if (is_object($donateur->getVille())) {
                                $ville = $donateur->getVille()->getName();
                            }
                            $options = array(
                                'amount' => $don->getMontant(),
                                'currency' => 'EUR',
                                'default_method' => 'ogone_gateway', // Optional
                                'predefined_data' => array(
                                    'ogone_gateway' => array(
                                        'SUB_AM' => $don->getMontant(),
                                        'SUB_AMOUNT' => $don->getMontant() * 100,
                                        'SUB_CUR' => 'EUR',
                                        'SUB_ENDDATE' => '',
                                        'SUB_PERIOD_MOMENT' => $recommanded_day,
                                        'SUB_PERIOD_NUMBER' => $periodicite,
                                        'SUB_PERIOD_UNIT' => 'm',
                                        'SUB_STATUS' => 1,
                                        'SUB_STARTDATE' => $date_debut->format('d/m/Y'),
                                        'SUB_ORDERID' => $don->getId(), // Optional
                                        'PM' => $brand, // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                                        'BRAND' => $cb_payment_method, // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                                        'CN' => $donateur->getNom(), // Optional
                                        'EMAIL' => $donateur->getEmail(), // Optional
                                        'OWNERZIP' => $donateur->getZipcode(), // Optional
                                        'OWNERADDRESS' => $donateur->getAdresse3(), // Optional
                                        'OWNERCTY' => $pays, // Optional
                                        'OWNERTOWN' => $ville, // Optional
                                        'OWNERTELNO' => '',
                                        'lang' => $lang, // 5 characters maximum, for e.g: fr_FR
                                        'SUBSCRIPTION_ID' => $don->getId(), // Optional, 30 characters maximum
                                        'ORDERID' => $don->getId(),
                                        'acceptUrl' => $accepturl,
                                    ),
                                ),
                            );

                            $this->ppc->addPlugin($this->pogone);
                            $this->ppc->createPaymentInstruction($instruction = $this->reverseTransform(null, $options));
                            $transaction = new Transaction();
                            $repStatut = $db->getRepository('FulldonDonateurBundle:StatutPaiement');
                            $statut = $repStatut->find(Vars\DonVars::_STATUT_ATTENTE_);
                            $transaction->setStatut($statut);
                            $transaction->setPaymentInstruction($instruction);
                            $don->setTransaction($transaction);
                            $obj_type = $db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code' => 'INTERNET'));
                            $don->setType($obj_type);
                            $db->persist($don);
                            $db->flush($don);
                            $instruction = $don->getTransaction()->getPaymentInstruction();
                            if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
                                $payment = $this->ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
                            } else {
                                $payment = $pendingTransaction->getPayment();
                            }

                            $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
                            if (Result::STATUS_PENDING === $result->getStatus()) {
                                $ex = $result->getPluginException();

                                if ($ex instanceof ActionRequiredException) {
                                    $action = $ex->getAction();

                                    if ($action instanceof VisitUrl) {
                                        return new RedirectResponse($action->getUrl());
                                    }

                                    throw $ex;
                                }
                            } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                                throw new \RuntimeException('Transaction was not successful: ' . $result->getReasonCode());
                            }

                        } elseif ($this->isset_notempty($pa_payment_method)) {

                            // if we have the online subscription
                            if ($this->container->getParameter('subscription_online')) {
                                // Enregistrement de l'iban et bic
                                $iban = strtoupper(trim($iban));
                                $bic = strtoupper(trim($bic));
                                $don->getAbonnement()->setIban($iban);
                                $don->getAbonnement()->setBic($bic);
                                $db->flush();
                                $app_code = $this->container->getParameter('certeurope_certisms_app_code');
                                $identifiant = '0' . $telephone;
                                $url = $this->container->getParameter('certeurope_certisms_url');
                                $session = new Session();

                                // add access to sertisms
                                try {
                                    $client = new \SoapClient($this->container->getParameter('certeurope_certisms_wsdl'), array('trace' => 1));
                                    $a = array('codeApplication' => $app_code, 'indicaddtifRegional' => $indicatif, 'identifiant' => $identifiant, 'url' => $url, 'paramaters' => null);
                                    $response = $client->__soapCall('AddAcces', $a);
                                    $filename = uniqid(mt_rand()) . 'mandat_' . $don->getId() . '.pdf';
                                    if ($response->error == 0) {
                                        //Afficher la page de confirmation
                                        //Génération du pdf
                                        $this->get('knp_snappy.pdf')->generateFromHtml(
                                            $this->renderView(
                                                'FulldonDonateurBundle:OnePage:mandat_numerique.html.twig',
                                                array(
                                                    'don' => $don,
                                                    'donateur' => $donateur,
                                                    'telephone' => $telephone,
                                                    'indicatif' => $indicatif
                                                )
                                            ),
                                            '/' . $this->container->getParameter('folder_app') . '/users/' . $filename,
                                            array(
                                                'page-height' => 100,
                                                'page-width' => 200,
                                                'margin-top' => 0,
                                                'margin-bottom' => 0,
                                                'margin-left' => 0,
                                                'margin-right' => 0,
                                                'no-background' => false
                                            )
                                        );
                                        // Générer un PDF
                                        // Display confirmation page
                                        $session->set('don_id', $don->getId());
                                        return $this->render('FulldonDonateurBundle:OnePage:mandat_confirmation.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'mandat_filename' => $filename, 'mandat_telephone' => $telephone, 'indicatif' => $indicatif));
                                    } else {
                                        //Rediriger l'utilisateur vers la page courante avec un message d'erreur.
                                    }
                                } catch (\SoapFault $e) {
                                    var_dump($e->getMessage(), $client->__getLastResponse());
                                    throw new \Exception('Un problème technique est survenu, veuillez contacter l\'administrateur!');
                                }
                                //return array('response' => $response);

                            } else {
                                $d2 = $this->get('fulldon.donateur.barecodes');
                                $string = $don->getId() . "|edon";
                                $d2->setStrToEncode($string);
                                //echo $d2->getBarcodePNGPath("dinesh", 'datamatrix',2, 2, 'black');
                                $approx_size = 60;
                                // Et on trace le code barre avec comme argument la taille en pixels voulue
                                $filename = 'datamatrix' . '_' . $don->getId();
                                $d2->stroke($approx_size, $filename);
                                $assoc_name = $this->container->getParameter('assoc_name');
                                $this->get('session')->getFlashBag()->add('info', 'Votre prélèvement automatique est désormais en cours de traitement,merci pour votre générosité.');
                                return $this->render('CustomFulldonExtDonateurBundle:print:virement_adhesion.html.twig', array('don' => $don, 'donateur' => $donateur, 'init' => $this->init, 'datamatrix' => $filename, 'assoc_name' => $assoc_name));
                            }

                        }

                    }


                } else {
                    foreach ($errors as $key => $error) {
                        $this->get('session')->getFlashBag()->add('error', $error);
                        $display_error = true;
                    }
                }
            } else {
                // Alert security
                $this->get('session')->getFlashBag()->add('error', 'Session expirée');
                return $this->redirect($this->generateUrl('fulldon_donateur_onepage'));
            }
        }
        //Security form
        $token = sha1(uniqid(rand(), TRUE));
        $session->set('token', $token);

        //check form values
        //redirect to the appropriate page
        return $this->render('FulldonDonateurBundle:OnePage:adherent.html.twig', array('init' => $this->init, 'display_error' => $display_error, 'data' => $data, 'countries' => $countries, 'token' => $token));
    }

    /**
     * @param $email
     * @return bool
     * the function return true if the email is a new email and false in the other case
     */

    function checkEmailAction($email) {

        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if($this->container->get('fulldon.intersa.global')->isNewEmail($email)) {
                return new  JsonResponse(array('result'=> true));
            }
        }
        return new JsonResponse(array('result'=> false));
    }

    function getpwdAction($email) {
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $db = $this->getDoctrine()->getManager();
            $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur');
            $donateur = $donateurRep->findOneBy(array('email'=>$email));
            if(is_object($donateur)) {

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
                return new JsonResponse(array('result'=> true));
            }
        }
        return new JsonResponse(array('result'=> false));
    }

}
