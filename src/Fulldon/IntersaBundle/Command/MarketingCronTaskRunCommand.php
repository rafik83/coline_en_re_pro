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

class MarketingCronTaskRunCommand extends ContainerAwareCommand
{
    private $output;

    protected function configure()
    {
        $this
            ->setName('marketing:crontask:run')
            ->setDescription('Marketing cron de tache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Running Cron Tasks...</comment>');

        $this->output = $output;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $crontasks = $em->getRepository('FulldonIntersaBundle:MarketingCronTask')->findAll();
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur') ;
        $repOccasion = $em->getRepository('FulldonDonateurBundle:CodeOccasion') ;
        $repCause = $em->getRepository('FulldonDonateurBundle:Cause') ;
        $repRec= $em->getRepository('FulldonDonateurBundle:ReceptionMode');
        $colCorresp = array(
            "iddonateur" => "[[donateur_id]]",
            "civilitedonateur" => "[[civilite_donateur]]",
            "nomdonateur" => "[[nom_donateur]]",
            "prenomdonateur" => "[[prenom_donateur]]",
            "codeactivite" => "[[code_activite]]",
            "sommepa" => "[[pa_somme]]",
            "periodicitepa" => "[[periodicite]]",
            "adressedonateur" => "[[adresse_donateur]]",
            "dateprepa" => "[[date_prelevement]]",
            "montantdon" => "[[montant_don]]",
            "iddon" => "[[ref_don]]",

        );
        foreach ($crontasks as $crontask) {

            $stat = new StatCom();
            $emailCounter = 0;
            $smsCounter = 0;
            $columns = explode('&', $crontask->getColumns());
            $batchSize = 20;
            $cpt= 0;
            if (!$crontask->getDone() && !$crontask->getProgress()) {
                $crontask->setProgress(true);
                $em->flush();

                // Set $lastrun for this crontask
                $crontask->setLastRun(new \DateTime());
                $output->writeln(sprintf('Running Cron Task <info>%s</info>', $crontask->getId()));
                $tag = $this->getContainer()->getParameter('prefix_tag').'-'.$this->getContainer()->getParameter('tag_emarketing').'-'.$crontask->getId();
                $tag_emarketing = $this->getContainer()->getParameter('prefix_tag').'-'.$this->getContainer()->getParameter('tag_emarketing');
                try {

                    // Extract the csv file
                    $row = 1;
                    $filePath = '/' . $this->getContainer()->getParameter('folder_app') . '/MARKETING/' . $crontask->getId() . '/' . $crontask->getFile();
                    if (($handle = fopen($filePath, "r")) !== FALSE) {

                        while (($data = fgetcsv($handle)) !== FALSE) {
                            $cpt++;

                            if (($cpt % $batchSize) == 0) {
                                $em->flush();
                                $em->clear();
                            }

                            $soll = new Prospection();
                            $num = count($data);
                            $interface = new EmailingInterface();
                            $uniqid = uniqid(mt_rand());
                            $interface->setUniqueId($uniqid);
                            $message_sms = $crontask->getSmsContent();
                            $message_email = $crontask->getEmailContent();
                            foreach($columns as $key=>$col) {
                                switch($col)
                                {
                                    case 'iddonateur':
                                        $id = str_replace(chr(0xEF).chr(0xBB).chr(0xBF), '',$data[$key]);
                                        $donateur = $repDonateur->find(intval($id));
                                        if(is_object($donateur)) {
                                            $soll->setDonateur($donateur);
                                            $interface->setDonateur($donateur);
                                        }
                                        break;
                                    case    "telephonedonateur":
                                        $telephone = $data[$key];
                                        break;
                                    case    "emaildonateur":
                                        $email = $data[$key];
                                        break;
                                    case    "isemail":
                                        $isEmail = $data[$key];
                                        break;
                                    case    "issms":
                                        $isTel = $data[$key];
                                        break;
                                    case "codeactivite" :
                                        $cause = $data[$key];
                                        $cause = $repCause->findOneBy(array('code'=>$cause));
                                        if(is_object($cause)) {
                                            $soll->setCause($cause);
                                        }
                                        break;
                                    default:
                                        $message_sms = str_replace($colCorresp[$col],$data[$key], $message_sms);
                                        $message_email = str_replace($colCorresp[$col],$data[$key], $message_email);
                                        break;

                                }

                            }
                            //view html & unsubscribe
                            $viewhtml_link = '<a href="'.$this->getContainer()->getParameter('url_site').'/donateur/onepage/viewhtml/'.$uniqid.'">cliquez sur ce lien</a>';
                            $unsubscribe_link = '<a href="*|UNSUB:'.$this->getContainer()->getParameter('url_site').'/donateur/onepage/unsubscribe/'.$uniqid.'|*"> Cliquez ici </a>';
                            $message_email = str_replace('[[unsub_link]]',$unsubscribe_link, $message_email);
                            $message_email = str_replace('[[viewhtml_link]]',$viewhtml_link, $message_email);

                            $soll->setCreatedAt( new \DateTime());
                            $soll->setRetour(0);


                            $row++;
                            //SMS
                            if ($crontask->getIsSms() && $isTel == 1 && strlen($telephone) == 10) {
                                $url = $this->getContainer()->getParameter('sms_url');

                                //the phone number should have the following structure 0xxxxxxxxx
                                //les paramètres à passer au serveur en POST
                                $myPostParams = "text=" . urlencode($message_sms) . "&recipients=33".substr($telephone, 1)."&sendername=".substr($this->getContainer()->getParameter('assoc_name'), 0,10);


                                //Configuration de la requête
                                $requestConfig = array('http' => array(
                                    'method' => 'POST',
                                    'header' => "Authorization: Basic " . base64_encode($this->getContainer()->getParameter('sms_login') . ":" . $this->getContainer()->getParameter('sms_password')) . "\r\n"
                                        . "Content-type: application/x-www-form-urlencoded\r\n",
                                    'content' => $myPostParams
                                ));

                                //Retour du serveur
                                $response = file_get_contents($url, false, stream_context_create($requestConfig));
                                $smsCounter++;
                                $reception = $repRec->findOneBy(array('code'=>'sms'));
                                $soll->addReception($reception);

                            }
                            //Email
                            if($crontask->getIsEmail() && $isEmail == 1 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                // echo $message_email;
                                $objet = $crontask->getObjet();

                                $this->getContainer()->get('fulldon.intersa.email_servies')->sendEmarketing($email,$message_email,$objet, $tag);



                                $emailCounter++;
                                $reception = $repRec->findOneBy(array('code'=>'email'));
                                $soll->addReception($reception);
                                $interface->setContent($message_email);
                                $em->persist($interface);
                                $em->flush();
                            }

                            $em->persist($soll);
                            $em->flush();
                        }
                        fclose($handle);
                    }
                    //Change status
                    $crontask->setDone(true);
                    $crontask->setProgress(false);
                    $stat->setNbEmail($emailCounter);
                    $stat->setNbSms($smsCounter);
                    $stat->setTag($tag_emarketing);

                    $em->persist($stat);
                    $em->flush();
                    $crontask->setStats($stat);
                    $output->writeln('<info>SUCCESS</info>');
                } catch
                (\Exception $e) {
                    echo $e->getMessage();
                    $output->writeln('<error>ERROR</error>');
                }
                // Persist crontask
                $em->flush($crontask);
            } else {
                $output->writeln(sprintf('Skipping Cron Task <info>%s</info>', $crontask->getId()));
            }

            // Flush database changes

            $em->flush();

        }


        $output->writeln('<comment>Done!</comment>');
    }
}