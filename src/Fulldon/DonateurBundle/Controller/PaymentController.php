<?php
namespace Fulldon\DonateurBundle\Controller;
use Fulldon\DonateurBundle\Entity\MotifAbo;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Event\LogVar;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fulldon\DonateurBundle\Entity\Transaction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Hip\MandrillBundle\Message;
use Hip\MandrillBundle\Dispatcher;

class PaymentController extends Controller
{
    const _PAYPAL_ = 'paypal';
    const _CHEQUE_= 'cheque';
    const _VIREMENT_ = 'virement';
    const _CB_ = 'cb';
    const _STATUT_ATTENTE_ = 1;
    const _STATUT_TRAITEMENT_PAIEMENT_ = 2;
    const _STATUT_DON_VALIDE_ = 3;
    const _STATUT_DON_ANNULE_ = 4;

    /** @DI\Inject */
    private $request;

    /** @DI\Inject */
    private $router;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $db;

    /** @DI\Inject("payment.plugin_controller") */
    private $ppc;

    /** @DI\Inject("payment.ogone") */
    private $ogone;

    /** @DI\Inject("payment.plugin.ogone_gateway") */
    private $pogone;

    /** @DI\Inject("logger") */
    private $logger;

    /** @DI\Inject("security.context") */
    private $security;



    private $init = array();

    public function preExecute() {
        if($this->container->getParameter('donor_space') == 0) {
//            die('Espace donateur non disponible !');
        }
        $db = $this->getDoctrine()->getManager();
        $securityContext = $this->container->get('security.context');
        $data = null;
        $perso = null;
        if( $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ){

        $user = $this->get('security.context')->getToken()->getUser();
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

    /**
     *
     * @Template
     */
    public function detailsAction($id)
    {
        $accepturl =  'http://'.$this->request->getHttpHost().$this->generateUrl('payment_callback');
        $repDon = $this->db->getRepository('FulldonDonateurBundle:Don') ;
        $don = $repDon->find($id);
        $user = $this->security->getToken()->getUser();
        $donateurRep = $this->db->getRepository('FulldonDonateurBundle:Donateur') ;
        $donateur = $donateurRep->findOneBy(array('user'=>$user->getId()));
        $request = $this->get('request_stack')->getCurrentRequest();
        $period = null;
        $brand = 'CreditCard';
        if($this->request->getLocale() == 'fr') {
            $lang = 'fr_FR';
        } else {
            $lang = 'en_GB';
        }

        if($don->getIspa()) {
            if ($request->getMethod() == 'POST') {
                $repPeriod = $this->db->getRepository('FulldonDonateurBundle:Periodicite') ;
                $date_fin_pa = $this->request->get('date_fin_pa');
                $periodicite = $this->request->get('periodicite');
                $payment_method = $this->request->get('payment_method');
                if($payment_method == 'PAYPAL')
                    $brand = 'PAYPAL';

                $errors = array();
                $date_debut  = new \DateTime();
                $cur_date =  new \DateTime();
                $recommanded_day = $this->container->get('fulldon.custom_params')->getParam('prelevement_jour');

                if($cur_date->format('d') > $recommanded_day)
                {
                    $cur_date->add(new \DateInterval('P1M'));
                }

                if(isset($date_fin_pa) && !empty($date_fin_pa) ) {
                    if(!$this->validateDate($date_fin_pa)) {

                        $errors['error_date_fin_pa'] = ' La date du fin de l\'engagement n\'est pas valide : jj/mm/aaaa' ;
                    }
                }
                if(!isset($payment_method) || empty($payment_method) ) {
                        $errors['error_payment_method'] = 'Veuillez choisir une méthode de paiement' ;
                }
                $date_debut->setDate($cur_date->format('Y'),$cur_date->format('m'),$recommanded_day);
                if(count($errors) == 0) {
                    $period_obj = $repPeriod->find($periodicite);
                    if(is_object($period_obj)) {
                        $period = $period_obj->getCode();
                    }
                    $repStatut = $this->db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
                    $statut = $repStatut->find(self::_STATUT_ATTENTE_);
                    $don->getAbonnement()->setDateFirstPa($date_debut);
                    if(isset($date_fin_pa) && !empty($date_fin_pa) ) {
                    $don->getAbonnement()->setDateFinPa(\DateTime::createFromFormat('d/m/Y', $date_fin_pa));
                    }
                    $don->getAbonnement()->setPeriodicite($repPeriod->find($period_obj));
                    $don->getTransaction()->setStatut($statut);
                    $statut = $repStatut->find(self::_STATUT_ATTENTE_);
                } else {
                    $repPaiement = $this->db->getRepository('FulldonDonateurBundle:ModePaiement') ;
                    $mode = $repPaiement->findOneBy(array('codeSolution'=>self::_CB_))->getId();
                    foreach($errors as $key => $error) {
                        $this->get('session')->getFlashBag()->add($key, $error);
                    }
                    return $this->redirect($this->generateUrl('donateur_don_reglement',  array('mode'=> $mode, 'id' => $don->getId(),'init'=>$this->init )));
                }
            } else {
                $repPaiement = $this->db->getRepository('FulldonDonateurBundle:ModePaiement') ;
                $mode = $repPaiement->findOneBy(array('codeSolution'=>self::_CB_))->getId();
                return $this->redirect($this->generateUrl('donateur_don_reglement',  array('mode'=> $mode, 'id' => $don->getId(),'init'=>$this->init )));
            }

            $options = array(
                'amount'   => $don->getMontant(),
                'currency' => 'EUR',
                'default_method' => 'ogone_gateway', // Optional
                'predefined_data' => array(
                    'ogone_gateway' => array(
                        'SUB_AM'   => $don->getMontant(),
                        'SUB_AMOUNT'   => $don->getMontant() * 100,
                        'SUB_CUR'   => 'EUR',
                        'SUB_ENDDATE'   => $date_fin_pa,
                        'SUB_PERIOD_MOMENT'   => $recommanded_day,
                        'SUB_PERIOD_NUMBER'   => $period,
                        'SUB_PERIOD_UNIT'   => 'm',
                        'SUB_STATUS' => 1,
                        'SUB_STARTDATE'   => $date_debut->format('d/m/Y'),
                        'SUB_ORDERID' => $don->getId(),           // Optional
                        'PM' => $brand,                                     // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                        'BRAND' => $payment_method,                                       // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                        'CN' => $donateur->getNom(),                 // Optional
                        'EMAIL' => $donateur->getEmail(),            // Optional
                        'OWNERZIP' => $donateur->getZipcode(),         // Optional
                        'OWNERADDRESS' => $donateur->getAdresse3(),     // Optional
                        'OWNERCTY' => $donateur->getPays()->getName(), // Optional
                        'OWNERTOWN' => $donateur->getVille()->getName(),              // Optional
                        'OWNERTELNO' => '',
                        'lang'      => $lang,                   // 5 characters maximum, for e.g: fr_FR
                        'SUBSCRIPTION_ID'   => $don->getId(),                                // Optional, 30 characters maximum
                        'ORDERID'   => $don->getId(),
                        'acceptUrl' => $accepturl,
                    ),
                ),
            );
        } else {
            $errors = array();
            if ($request->getMethod() == 'POST') {
                $payment_method = $this->request->get('payment_method');

                if(!isset($payment_method) || empty($payment_method) ) {
                    $errors['error_payment_method'] = 'Veuillez choisir une méthode de paiement' ;
                }
                if(count($errors) != 0) {
                    $repPaiement = $this->db->getRepository('FulldonDonateurBundle:ModePaiement') ;
                    $mode = $repPaiement->findOneBy(array('codeSolution'=>self::_CB_))->getId();
                    foreach($errors as $key => $error) {
                        $this->get('session')->getFlashBag()->add($key, $error);
                    }
                    return $this->redirect($this->generateUrl('donateur_don_reglement',  array('mode'=> $mode, 'id' => $don->getId(),'init'=>$this->init )));
                }
            }

            $options = array(
                'amount'   => $don->getMontant(),
                'currency' => 'EUR',
                'default_method' => 'ogone_gateway', // Optional
                'predefined_data' => array(
                    'ogone_gateway' => array(
                        'tp' => '',           // Optional
                        'PM' => $brand,                                     // Optional - Example value: "CreditCard" - Note: You can consult the list of PM values on Ogone documentation
                        'BRAND' => $payment_method,                                       // Optional - Example value: "VISA" - Note: If you send the BRAND field without sending a value in the PM field (‘CreditCard’ or ‘Purchasing Card’), the BRAND value will not be taken into account.
                        'CN' => $donateur->getNom(),                 // Optional
                        'EMAIL' => $donateur->getEmail(),            // Optional
                        'OWNERZIP' => $donateur->getZipcode(),         // Optional
                        'OWNERADDRESS' => $donateur->getAdresse3(),     // Optional
                        'OWNERCTY' => $donateur->getPays()->getName(), // Optional
                        'OWNERTOWN' => $donateur->getVille()->getName(),              // Optional
                        'OWNERTELNO' => '',
                        'lang'      => $lang,                   // 5 characters maximum, for e.g: fr_FR
                        'ORDERID'   => $don->getId(),                                // Optional, 30 characters maximum
                        'acceptUrl' => $accepturl,
                    ),
                ),
            );
        }


        $this->ppc->addPlugin($this->pogone);
        $this->ppc->createPaymentInstruction($instruction = $this->reverseTransform(null, $options));
                $transaction = new Transaction();
                $repStatut = $this->db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
                $statut = $repStatut->find(self::_STATUT_ATTENTE_);
                $transaction->setStatut($statut);
                $transaction->setPaymentInstruction($instruction);
                $don->setTransaction($transaction);
                $obj_type = $this->db->getRepository('FulldonIntersaBundle:TypeDon')->findOneBy(array('code'=>'INTERNET'));
                $don->setType($obj_type);
                $this->db->persist($don);
                $this->db->flush($don);

                return new RedirectResponse($this->router->generate('payment_complete', array(
                    'id' => $don->getId(),
                )));

    }

    // ...

    /** @DI\LookupMethod("form.factory") */
    protected function getFormFactory() { }

    public function completeAction($id)
    {
        $repDon = $this->db->getRepository('FulldonDonateurBundle:Don') ;
        $don = $repDon->find($id);
        $user = $this->security->getToken()->getUser();
        $donateurRep = $this->db->getRepository('FulldonDonateurBundle:Donateur') ;
        $donateur = $donateurRep->findOneBy(array('user'=>$user->getId()));
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
            throw new \RuntimeException('Transaction was not successful: '.$result->getReasonCode());
        }

        // payment was successful, do something interesting with the order
    }

    public function callbackAction(Request $request)
    {

        $orderId = $request->get('orderID');
        $repDon = $this->db->getRepository('FulldonDonateurBundle:Don') ;

        if (null === $don = $repDon->find($orderId)) {
            throw new NotFoundHttpException(sprintf('unable to find order with id [%s]', $orderId));
        }
        $url_share =  'http://'.$this->request->getHttpHost().$this->generateUrl('payment_share', array('id' => $don->getId()));
        if($don->getTransaction()->getStatut()->getCode() != Vars\DonVars::_CODE_DON_VALIDE_) {
                if (null === $instruction = $don->getTransaction()->getPaymentInstruction()) {
                    $this->logger->info(sprintf('[Ogone - callback] No payment instruction found for OrderId [%s].', $orderId));
                    return new Response('No payment instruction');
                }

                try {
                    $this->ogone->handleTransactionFeedback($instruction);
                } catch (NoPendingTransactionException $e) {
                    $this->logger->info($e->getMessage());
                    return new Response('Nothing pending');
                }
                $repStatut = $this->db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
                $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);
                $don->getTransaction()->setStatut($statut);
                $this->db->persist($don);
                $this->db->flush();
                $this->logger->info(sprintf('[Ogone - callback] Payment instruction %s successfully updated', $instruction->getId()));
                $this->get('session')->getFlashBag()->add('info', 'Paiement effectué avec succès, nous vous remercions pour votre don');

        }
        $socialids = array('facebook'=>$this->container->get('fulldon.custom_params')->getParam('facebook_id'), 'twitter'=>$this->container->get('fulldon.custom_params')->getParam('twitter_id'));
        $assoc_name = $this->container->getParameter('assoc_name');
        return $this->render('FulldonDonateurBundle:Donateur:share.html.twig', array('don' => $don,'init'=>$this->init,'socialids'=>$socialids,'assoc_name'=>$assoc_name));
    }
    public function callbackServerAction(Request $request)
    {
        $logger = $this->get('logger');
        $logger->info('Nous avons récupéré le logger server');

        $orderId = $request->get('orderID');
        $repDon = $this->db->getRepository('FulldonDonateurBundle:Don') ;

        if (null === $don = $repDon->find($orderId)) {
            throw new NotFoundHttpException(sprintf('unable to find order with id [%s]', $orderId));
        }
        if($don->getTransaction()->getStatut()->getCode() != Vars\DonVars::_CODE_DON_VALIDE_) {
        if (null === $instruction = $don->getTransaction()->getPaymentInstruction()) {
            $this->logger->info(sprintf('[Ogone - callback] No payment instruction found for OrderId [%s].', $orderId));

            return new Response('No payment instruction');
        }

        try {
            $this->ogone->handleTransactionFeedback($instruction);
        } catch (NoPendingTransactionException $e) {
            $this->logger->info($e->getMessage());

            return new Response('Nothing pending');
        }
        $repStatut = $this->db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
        $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);
        $don->getTransaction()->setStatut($statut);
        $this->db->persist($don);
        $this->db->flush();

        $this->logger->info(sprintf('[Ogone - callback] Payment instruction %s successfully updated', $instruction->getId()));
        }

        return new Response('OK');
    }
    public function callbackChangeStatutAction(Request $request)
    {
        $logger = $this->get('logger');
        $logger->info('Nous avons récupéré le logger server');
        $orderId = $request->get('orderID');
        $repDon = $this->db->getRepository('FulldonDonateurBundle:Don') ;
        $don = $repDon->find($orderId);
        $status = $request->get('STATUS');
        $trxdate = $request->get('TRXDATE');
        $trxdate = urldecode($trxdate);
        $expdate = explode('/', $trxdate);
        if(count($expdate) == 3) {
            $newdate ='20'.$expdate[2].'-'.$expdate[0].'-'.$expdate[1];
        }

        $feedback = $this->ogone->isValidFeedback();
        $this->logger->info(sprintf('[Feedback: [%s]', $feedback));
        $this->logger->info(sprintf('[Request: [%s]', $request));
        $this->logger->info(sprintf('[OGONE statuts : [%s], ID du don : [%s], Order id : [%s]', $status,$don->getId(),$orderId));


        if (null === $don) {
            throw new NotFoundHttpException(sprintf('unable to find order with id [%s]', $orderId));
        } else {
            //est ce que le don est un abonnement
            // Oui  - désactiver l'abonnement.
            if (isset($newdate)) {
                $prelevement = $this->db->getRepository('FulldonDonateurBundle:Prelevement')->findOneBy(array('abo' => $don->getAbonnement(), 'datePrelevement' => $newdate));
            }

            if (null === $instruction = $don->getTransaction()->getPaymentInstruction()) {
                $this->logger->info(sprintf('[Ogone - callback] No payment instruction found for OrderId [%s].', $orderId));
                return new Response('No payment instruction');
            }
            if($don->getIspa()) {
            if ($feedback && $don->getAbonnement()->getActif()) {
                $statusCode = explode('-', $status);
                $statusCode = trim($statusCode[0]);
                $this->logger->info(sprintf('[Status: [%s]', $statusCode));
                $don->getAbonnement()->setActif(false);
                if (in_array($statusCode, array(92, 52, 93, 0, 2))) {
                    $motif = $this->db->getRepository('FulldonDonateurBundle:MotifAbo')->findOneBy(array('code' => 'cb' . $statusCode));
                    $don->getAbonnement()->setMotif($motif);
                    $don->getAbonnement()->setDisabledAt(new \DateTime());
                    // Rejet
                    if (is_object($prelevement)) {
                        $prelevement->setRejet(true);
                        $prelevement->setRejectedAt(new \DateTime());
                        $motif = $this->db->getRepository('FulldonDonateurBundle:MotifAbo')->findOneBy(array('code' => 'rejet-banque'));
                        $prelevement->setMotif($motif);
                    }
                    $donateur = $this->db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user' => $don->getUser()));
                    //Log
                    $msg = $this->get('fulldon.intersa.global')->getModMsgLog($don->getAbonnement(), 'ABONNEMENT');
                    $typeLog = $this->db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_DON_);
                    // Log the user creation
                    $role = "AUTOMATIQUE";
                    // Log the user creation
                    $event = HistoryLogEvent::mainConstr(null, $donateur, $typeLog, $msg, $role, $don);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(LogVar::CREATE, $event);
                    //Fin Log
                    $this->db->persist($don);
                    $this->db->flush();
                }
            }
        }

        }
        return new Response('OK');


    }
    public function callbackAnonymousAction(Request $request)
    {
        $logger = $this->get('logger');
        $logger->info('Nous avons récupéré le logger server');

        $orderId = $request->get('orderID');
        $repDon = $this->db->getRepository('FulldonDonateurBundle:Don') ;

        if (null === $don = $repDon->find($orderId)) {
            throw new NotFoundHttpException(sprintf('unable to find order with id [%s]', $orderId));
        }
        if($don->getTransaction()->getStatut()->getCode() != Vars\DonVars::_CODE_DON_VALIDE_) {
            if (null === $instruction = $don->getTransaction()->getPaymentInstruction()) {
                $this->logger->info(sprintf('[Ogone - callback] No payment instruction found for OrderId [%s].', $orderId));

                return new Response('No payment instruction');
            }

            try {
                $this->ogone->handleTransactionFeedback($instruction);
            } catch (NoPendingTransactionException $e) {
                $this->logger->info($e->getMessage());

                return new Response('Nothing pending');
            }
            $repStatut = $this->db->getRepository('FulldonDonateurBundle:StatutPaiement') ;
            $statut = $repStatut->find(self::_STATUT_DON_VALIDE_);
            $don->getTransaction()->setStatut($statut);
            $this->db->persist($don);
            $this->db->flush();
            $this->logger->info(sprintf('[Ogone - callback] Payment instruction %s successfully updated', $instruction->getId()));
            // Email management
            $donateurRep = $this->db->getRepository('FulldonDonateurBundle:Donateur') ;
            $donateur = $donateurRep->findOneBy(array('user'=>$don->getUser()));
            $email = $donateur->getEmail();
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
            $tr = $this->get('translator');
            $title = $tr->trans('');

            if($is_email && $email and !empty($email)) {
                $html = $this->get('templating')->render( 'FulldonDonateurBundle:Emails:confirmation.html.twig', array('don' => $don,'donateur'=>$donateur));
                $this->get('fulldon.intersa.email_servies')->sendConfirmDonation($email,$html);
                $dispatcher = $this->get('hip_mandrill.dispatcher');

                $message = new Message();

                $message
                    ->setFromEmail($this->container->getParameter('mailer_sender'))
                    ->setFromName($this->container->getParameter('assoc_name'))
                    ->addTo($email)
                    ->setSubject('Confirmation de votre don')
                    ->setHtml(
                        $this->get('templating')->render( 'FulldonDonateurBundle:Emails:confirmation.html.twig', array('don' => $don,'donateur'=>$donateur))
                    )
                    ->addTag($this->container->getParameter('prefix_tag').'-'.$this->container->getParameter('tag_notification'));

                $dispatcher->send($message);
            }

        }
        $socialids = array('facebook'=>$this->container->get('fulldon.custom_params')->getParam('facebook_id'), 'twitter'=>$this->container->get('fulldon.custom_params')->getParam('twitter_id'));
        $assoc_name = $this->container->getParameter('assoc_name');
        return $this->render('FulldonDonateurBundle:Donateur:share.html.twig', array('don' => $don,'init'=>$this->init,'socialids'=>$socialids,'assoc_name'=>$assoc_name));

    }
    public function reverseTransform($data, array $options)
    {
        $method = isset($data['method']) ? $data['method'] : null;
        $data = isset($data['data_'.$method]) ? $data['data_'.$method] : array();
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
}