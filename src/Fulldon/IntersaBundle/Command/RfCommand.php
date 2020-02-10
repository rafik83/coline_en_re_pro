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


class RfCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:rf:all')
            ->setDescription('Generation des reçus fiscaux ponctuels')
            ->addArgument('lot', InputArgument::REQUIRED, 'Un lot est obligatoire.')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Garbage collector
        gc_collect_cycles();
        $lot = $input->getArgument('lot');
        $db = $this->getContainer()->get('doctrine')->getManager();
        $repDon = $db->getRepository('FulldonDonateurBundle:Don') ;
        $donateurRep = $db->getRepository('FulldonDonateurBundle:Donateur') ;
        $pdf = new PDFMerger;
        $files = array();
        $app = new Application();
        $init = $app->getHelperSet()->get('progress');
        $dons = $repDon->findBy(array('lot'=>$lot));


        $output->writeln("Début de génération des reçus fiscaux : \n");
        $realRf = 0;
        echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL;
        $s = microtime(true);
        $batchSize = 20;
        $progress = $app->getHelperSet()->get('progress');
        $progress->start($output, count($dons));
        foreach($dons as $key=>$don) {
            $donateur = $donateurRep->findOneBy(array('user'=>$don->getUser()->getId()));
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
            $file_origin = '/'.$this->getContainer()->getParameter('folder_app').'/RF/ORIGIN/'.$fileName;
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
            $event = HistoryLogEvent::mainConstr(null, $donateur, $typeLog, $msg, $role, $don);
            $dispatcher =$this->getContainer()->get('event_dispatcher');
            $dispatcher->dispatch( LogVar::CREATE  , $event);

            //Génération du RF
            $html = $this->getContainer()->get('fulldon.intersa.rf_service')->getHtmlArray( $don, $donateur,$rf,  'rf', null, null);
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
        }

       foreach ($files as $f) {
            $pdf->addPDF($f, 'all');
        }

        @$pdf->merge('file', '/'.$this->getContainer()->getParameter('folder_app').'/RF_PONC/TOSEND/'.date('Y').'TEST2.pdf');
        $progress->finish();
        $output->writeln("\nGeneration des recus fiscaux avec succes!");
        //gc_collect_cycles(); // explained later!
        echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL;

        $e = microtime(true);
        echo ' Inserted 10000 objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
}

}