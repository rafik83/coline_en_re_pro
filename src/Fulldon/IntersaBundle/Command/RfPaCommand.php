<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\IntersaBundle\Command;

use Fulldon\DonateurBundle\Entity\Prelevement;
use Fulldon\DonateurBundle\Entity\Rf;
use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Abonnement;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Fulldon\IntersaBundle\Vars;
use Fulldon\IntersaBundle\Service\PDFMerger;

class RfPaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:rf:pa')
            ->setDescription('Generation des reçus fiscaux pour les prélèvements automatiques')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Garbage collector
        gc_collect_cycles();
        $db = $this->getContainer()->get('doctrine')->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $prelevementRep = $db->getRepository('FulldonDonateurBundle:Prelevement');
        $curdate = new \DateTime();
        $curdate->sub(new \DateInterval('P1Y'));
        $pdf = new PDFMerger;
        $files = array();
        $app = new Application();
        $init = $app->getHelperSet()->get('progress');


        $prelevements = $prelevementRep->createQueryBuilder('p')
            ->select(' SUM(p.montant) as somme, a.id as abo_id ')
            ->join('p.abo','a')
            ->where('p.rejet = 0')
            ->andwhere('p.datePrelevement LIKE :date')
            ->setParameter('date', '%'.$curdate->format('Y').'%')
            ->groupBy('p.abo')
            ->getQuery()
            ->getResult();
        $output->writeln("Début de génération des reçus fiscaux : \n");
        $realRf = 0;
        echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL;
        $s = microtime(true);
        $batchSize = 20;
        $init->start($output, count($prelevements));
        $okPrelevements = array();
        $output->writeln("\nInitialisation ... ");
        foreach($prelevements as $key=>$pr) {
            $repAbo = $db->getRepository('FulldonDonateurBundle:Abonnement') ;
            $abo = $repAbo->find($pr['abo_id']);
            $don = $repDon->findOneBy(array('abonnement'=>$abo));
            $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur') ;
            $donateur = $donateurRep->findOneBy(array('user'=>$don->getUser()->getId()));

                if(is_object($donateur) && $this->canIgenerateRf($don, $donateur, 3)) {
                    $okPrelevements[] = $pr;
                }

            if (($key % $batchSize) == 0) {
                $db->flush();
                $db->clear();
            }
            $init->advance();

        }
        $init->finish();
        $output->writeln("\nTraitement ... ");
        $progress = $app->getHelperSet()->get('progress');
        $progress->start($output, count($okPrelevements));
        //$progress->start($output, count($prelevements));
        foreach($okPrelevements as $pr) {
            $repAbo = $db->getRepository('FulldonDonateurBundle:Abonnement') ;
            $abo = $repAbo->find($pr['abo_id']);
            $don = $repDon->findOneBy(array('abonnement'=>$abo));
            $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur') ;
            $donateur = $donateurRep->findOneBy(array('user'=>$don->getUser()->getId()));
            $prelevementRep = $db->getRepository('FulldonDonateurBundle:Prelevement');

            $curdate = new \DateTime();
            $curdate->sub(new \DateInterval('P1Y'));
            $prelevements = $prelevementRep->createQueryBuilder('p')
                ->join('p.abo','a')
                ->where('p.datePrelevement LIKE :date')
                ->andwhere('a.id = :id')
                ->setParameter('id', $pr['abo_id'])
                ->andwhere('p.rejet = 0')
                ->setParameter('date', '%'.$curdate->format('Y').'%')
                ->getQuery()
                ->getResult();




                $rf = new Rf();
                $name = uniqid(mt_rand());
                $is_courrier = false;
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

                    $fileName = $name.'-'.date('Y').'.pdf';

                    $file_origin = '/'.$this->getContainer()->getParameter('folder_app').'/RF_PA/ORIGIN/'.$fileName;
                    $file_duplicata = '/'.$this->getContainer()->getParameter('folder_app').'/RF_PA/DUPLICATA/'.$fileName;


                $files[] = $file_origin;

                    $rf->setNom($fileName);
                    $rf->setNomDuplicata($fileName);
                    $rf->setDon($don);
                    $db->persist($rf);
                    $db->persist($don);
                    $db->flush();
                    //Log
                    $msg =$this->getContainer()->get('fulldon.intersa.global')->getAddMsgLog($rf,'RF');
                    $typeLog = $db->getRepository('FulldonIntersaBundle:TypeLog')->find(LogVar::_LOG_TYPE_INFO_RF_);
                    $role = "AUTOMATIQUE";
                    // Log the user creation
                    $event = HistoryLogEvent::mainConstr(null, $db->getRepository('FulldonDonateurBundle:Donateur')->findOneBy(array('user'=>$don->getUser())), $typeLog, $msg, $role, $don);
                    $dispatcher =$this->getContainer()->get('event_dispatcher');
                    $dispatcher->dispatch( LogVar::CREATE  , $event);


//                //Génération du duplicata
//                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
//                    $this->getContainer()->get('templating')->render(
//                        'FulldonIntersaBundle:Rf:rf_duplicata_pa.html.twig',
//                        array(
//                            'don'  => $don,
//                            'donateur' => $donateur,
//                            'prelevements' => $prelevements,
//                            'somme' => $pr['somme'],
//                            'rf' => $rf
//                        )
//                    ),
//                    $file_duplicata,
//                    array(),
//                    true
//                );
                //Génération du RF pa
                $html = $this->getContainer()->get('fulldon.intersa.rf_service')->getHtmlArray( $don, $donateur,$rf,  'rf_pa', $prelevements, $pr['somme']);
                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                    $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Rf:rf.html.twig',
                        array(
                            'html'  => $html
                        )
                    ),
                    $file_origin,
                    array(),
                    true

                );

                $email = $donateur->getEmail();
                if($is_email && $email and !empty($email)) {
                    $html = $this->getContainer()->renderView( 'FulldonIntersaBundle:Email:rf.html.twig', array('don' => $don,'rf'=>$rf));
                    $this->$this->getContainer()->get('fulldon.intersa.email_servies')->sendRf($email,$html,$file_origin);
                }
                $progress->advance();
            unset($pr);


        }
       foreach ($files as $f) {
            $pdf->addPDF($f, 'all');
        }

        @$pdf->merge('file', '/'.$this->getContainer()->getParameter('folder_app').'/RF_PA/TOSEND/'.date('Y').'TEST2.pdf');
        $progress->finish();
        $output->writeln("\nGeneration des recus fiscaux avec succes!");
        //gc_collect_cycles(); // explained later!
        echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL;

        $e = microtime(true);
        echo ' Inserted 10000 objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
}
    public function canIgenerateRf(Don $don, Donateur $donateur,$type=1) {
        $seuilRf = $this->getContainer()->get('fulldon.custom_params')->getParam('seuil_rf');
        $montant = 0;
        $curdate = new \DateTime();
        $curdate->sub(new \DateInterval('P1Y'));
//        if($don->getIspa()) {
//            $prelevementRep = $this->em->getRepository('FulldonDonateurBundle:Prelevement');
//            $prelevements = $prelevementRep->createQueryBuilder('p')
//                ->select(' SUM(p.montant) as somme, a.id as abo_id ')
//                ->join('p.abo','a')
//                ->where('p.datePrelevement LIKE :date')
//                ->setParameter('date', '%'.$curdate->format('Y').'%')
//                ->andwhere('p.rejet = 0')
//                ->andwhere('p.abo = :abo')
//                ->setParameter('abo', $don->getAbonnement())
//                ->groupBy('p.abo')
//                ->getQuery()
//                ->getSingleResult();
//            $montant = $prelevements['somme'];
//        } else {
        $montant = $don->getMontant();
//        }

        if($type == 1) {
            return $don->getCause()->getRf() && $donateur->getAllowRf() /*&& !$donateur->getAllowAnnualRf()*/ && $montant >= $seuilRf && count($don->getRfs()) == 0;
        } elseif($type == 2 ) {
            /*$cummule = $repDon->createQueryBuilder('d')
                ->select(' SUM(d.montant) as somme, d.id as don_id ')
                ->join('d.cause','c')
                ->where('d.date_fiscale LIKE :date')
                ->andwhere('d.removed = 0 and d.user = :user')
                ->setParameter('date', '%'.$curdate->format('Y').'%')
                ->setParameter('user', $donateur->getUser())
                ->andWhere('c.rf = true')
                ->groupBy('d.user')
                ->getQuery()
                ->getSingleResult();
            $montant =$cummule['somme'];
            return $donateur->getAllowRf() && $donateur->getAllowAnnualRf() && $montant >= $this->seuilRf ;*/
        } else {
            $rfofyear = true;
            foreach($don->getRfs() as $rf) {
                if($rf->getCreatedAt()->format('Y') == date('Y')) {
                    $rfofyear = false;
                }

            }
            return $don->getCause()->getRf() && $donateur->getAllowRf() && $montant >= $seuilRf && $rfofyear;
        }

    }
}