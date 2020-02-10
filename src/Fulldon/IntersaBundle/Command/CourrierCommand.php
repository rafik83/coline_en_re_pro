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
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\CodeOccasion;
use Fulldon\DonateurBundle\Entity\Cause;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Fulldon\IntersaBundle\Vars;
use Fulldon\IntersaBundle\Entity\CourrierAttente;
use Hip\MandrillBundle\Message;
use Hip\MandrillBundle\Dispatcher;

class CourrierCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
                ->setName('generate:courrier')
                ->setDescription('Génération des courriers')
                ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $db = $this->getContainer()->get('doctrine')->getManager();
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $repOccasion = $db->getRepository('FulldonDonateurBundle:CodeOccasion');
        $repCause = $db->getRepository('FulldonDonateurBundle:Cause');
        $repUPros = $db->getRepository('FulldonIntersaBundle:UploadProspection');
        $repRec = $db->getRepository('FulldonDonateurBundle:ReceptionMode');
        $repCourrier = $db->getRepository('FulldonIntersaBundle:CourrierAttente');
        $allCourrier = $repCourrier->findBy(array('done' => false));
        $app = new Application();
        $progress = $app->getHelperSet()->get('progress');
        $progress->start($output, count($allCourrier));
        $file = null;
        $is_courrier = false;
        $is_email = false;
        $is_sms = false;
        if (count($allCourrier) > 0) {


            foreach ($allCourrier as $courrier) {
                //Génération du courrier pour chaque type
                $donateur = $courrier->getDonateur();
                $don = $courrier->getDon();
                $fichier = null;
                switch ($courrier->getTypeTraitements()->getCode()) {
                    case 'arret_pa' :
                        $fichier = 'courrier_arret_pa.html.twig';
                        break;
                    case 'maj_adresse' :
                        $fichier = 'courrier_change_adresse.html.twig';
                        break;
                    case 'maj_mo_pa' :
                        $fichier = 'courrier_change_amount_pa.html.twig';
                        break;
                    case 'maj_pe_pa' :
                        $fichier = 'courrier_change_period_pa.html.twig';
                        break;
                    case 'maj_cob' :
                        $fichier = 'courrier_maj_cb.html.twig';
                        break;
                    case 'create_pa' :
                        $fichier = 'courrier_create_pa.html.twig';
                        break;
                }
                //Traitement des modes de récéption
                $modes = $donateur->getReceptionMode();
                foreach ($modes as $mode) {
                    switch ($mode->getCode()) {
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
                if ($is_courrier) {
                    $subfile = 'send';
                } else {
                    $subfile = 'notsend';
                }


                $file = '/courriers/' . date('Y-m-d') . '/' . $subfile . '/' . $donateur->getId() . '-' . $courrier->getTypeTraitements()->getId() . '-' . date('H-i-s') . '.pdf';
                var_dump('$file');
                var_dump($file);
                $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
                        $this->getContainer()->get('templating')->render(
                                'FulldonIntersaBundle:Saisie:type_courrier/' . $fichier, array(
                            'donateur' => $donateur,
                            'don' => $don
                                )
                        ), $file, array(), true
                );
                $email = $donateur->getEmail();
                if ($is_email and $email and ! empty($email)) {

                    $dispatcher = $this->get('hip_mandrill.dispatcher');

                    $message = new Message();

                    $message
                            ->setFromEmail($this->getContainer()->getParameter('mailer_sender'))
                            ->setFromName($this->getContainer()->getParameter('assoc_name'))
                            ->addTo($email)
                            ->setSubject('Modification de vos coordonées : ')
                            ->setHtml(
                                    $this->getContainer()->get('templating')->render('FulldonIntersaBundle:Saisie:type_courrier/' . $fichier, array('donateur' => $donateur, 'don' => $don))
                            )
                            ->addTag($this->getContainer()->getParameter('prefix_tag') . '-' . $this->getContainer()->getParameter('tag_courrier'));

                    $dispatcher->send($message);
                }
                $progress->advance();
                //MAJ the stat
                $courrier->setDone(true);
                $courrier->setDoneAt(new \Datetime());
                $db->persist($courrier);
                $db->flush();
            }
            $progress->finish();
            $output->writeln('Génération des courriers est terminée avec succes !');
        } else {
            $output->writeln('Pas de courriers à générer !');
        }
    }

    function dqlDate($date, $format = 'd/m/Y') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d->format('Y-m-d');
    }

}
