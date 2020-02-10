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
use Fulldon\IntersaBundle\Entity\StatCom;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Entity\Log;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Fulldon\IntersaBundle\Event\StatVar;
use Fulldon\IntersaBundle\Entity\UploadProspection;
use Fulldon\DonateurBundle\Entity\Prospection;
use Fulldon\DonateurBundle\Entity\EmailingInterface;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\CodeOccasion;
use Fulldon\DonateurBundle\Entity\Cause;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Fulldon\IntersaBundle\Vars;
use Fulldon\IntersaBundle\Entity\CourrierAttente;
use Hip\MandrillBundle\Message;
use Hip\MandrillBundle\Dispatcher;
use Symfony\Component\HttpFoundation\Request;

class DailyCronCommand extends ContainerAwareCommand
{
    private $output;

    protected function configure()
    {
        $this
            ->setName('daily:cron:run')
            ->setDescription('Cron quotidien')
            ->addArgument('date', InputArgument::OPTIONAL, 'date optionnelle ');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Running daily cron...</comment>');
        $this->getContainer()->enterScope('request');
        $this->getContainer()->set('request', new Request(), 'request');
        $this->output = $output;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $customtasks = $em->getRepository('FulldonIntersaBundle:CustomCronTask')->findAll();
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');
        $repDons = $em->getRepository('FulldonDonateurBundle:Don');
        $repConfAvance = $em->getRepository('FulldonIntersaBundle:ConfAvance');
        $repEntity = $em->getRepository('FulldonIntersaBundle:Entity');
        $entities = $repEntity->findAll();
        $conf = $repConfAvance->find(1);
        $generateRf = false;
        $sendStats = false;
        $date = $input->getArgument('date');

        if(isset($date) && !empty($date)) {
            $curdate =  $date;
        } else {
            $curdate = date('d/m/Y');
        }
        // MAJ du bill report
        $this->getContainer()->get('fulldon.intersa.global')->getDayBillStats($curdate);

        if (is_object($conf)) {
            if ($conf->getGenerateRf()) {
                $generateRf = true;
            }
            if ($conf->getSendStats()) {
                $sendStats = true;
            }
        }
        if ($generateRf) {
            $dons = $repDons->createQueryBuilder('d')
                ->join('d.transaction', 't')
                ->where('t.statut = 3')
                ->orderBy('d.id ', 'DESC')
                ->getQuery()->getResult();

            $this->getContainer()->get('fulldon.intersa.rf_service')->buildRfs($dons, 'rf');
        }
        if ($sendStats) {

            $date = $input->getArgument('date');
            if(isset($date) && !empty($date)) {
                $curdate =  $date;
            } else {
                $curdate = date('d/m/Y');
            }


            $date_debut = \DateTime::createFromFormat('d/m/Y H:i:s', $curdate . ' 00:00:00')->format('Y-m-d H:i:s');
            $date_fin = \DateTime::createFromFormat('d/m/Y H:i:s', $curdate . ' 23:59:59')->format('Y-m-d H:i:s');


            foreach ($entities as $entity) {

                $entity = $entity->getCode();
                $object = 'Statistiques journalières de l\'entité : ' . $entity;
                $filename_global = 'cumulatif-journalier' . '-' . $entity . '-' . date('d-m-Y-H-m-s') . md5(time());
                $filename_par_mode = 'cumulatif-par-mode' . '-' . $entity . '-' . date('d-m-Y-H-m-s') . md5(time());
                //Création des fichier pdf.
                $data = $this->getContainer()->get('fulldon.intersa.global')->getCumulatifStats($date_debut, $date_fin, $entity);
                $global_filename = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/daystat/' . $filename_global . '.pdf';
                //Définition du date
                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                    $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/cumulatif.html.twig',
                        array(
                            'data' => $data,
                            'entity' => $entity,
                            'date_debut' => $date_debut,
                            'date_fin' => $date_fin
                        )
                    ),
                    $global_filename,
                    array(),
                    true
                );

                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:day_stats_global.html.twig',array('entity'=>$entity));
                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $global_filename, $object);
                // Traitement du deuxième fichier.
                //Création des fichier pdf.
                $data = $this->getContainer()->get('fulldon.intersa.global')->getdaystat($date_debut, $date_fin, $entity);
                $modefilename = '/' . $this->getContainer()->getParameter('folder_app') . '/global_stats/daystat/' . $filename_par_mode . '.pdf';
                //Définition du date
                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                    $this->getContainer()->get('templating')->render(
                        'FulldonIntersaBundle:Stats:pdf/par_type.html.twig',
                        array(
                            'data' => $data['par_type'],
                            'entity' => $entity,
                            'date_debut' => $date_debut,
                            'date_fin' => $date_fin
                        )
                    ),
                    $modefilename,
                    array(),
                    true
                );

                $html = $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Email:day_stats_mode.html.twig',array('entity'=>$entity));
                $admins = explode(',', $this->getContainer()->getParameter('administrator_email'));
                $this->getContainer()->get('fulldon.intersa.email_servies')->sendAutomatique($admins, $html, $modefilename, $object);
            }

        }
        $output->writeln('<comment>Done!</comment>');
    }
}