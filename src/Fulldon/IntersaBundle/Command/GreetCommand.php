<?php

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
use Fulldon\SecurityBundle\Entity\User;
use Hip\MandrillBundle\Message;
use Hip\MandrillBundle\Dispatcher;
use Symfony\Component\HttpFoundation\Request;

class GreetCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
                ->setName('demo:greet')
                ->setDescription('edit object fulldon');
//                ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $db = $this->getContainer()->get('doctrine')->getManager();
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        
        $repUser = $db->getRepository('FulldonSecurityBundle:User');
        $User = $repUser->find(9068);
        $username = 'fulldon';
        $plainPassword = 'intersa115';
        
        $encoder = $this->getContainer()->get('security.encoder_factory')
                ->getEncoder($User);
        $password = $encoder->encodePassword($plainPassword, $User->getSalt());
        $User->setPassword($password);
        $User->setUsername($username);
        $User->setIsActive(true);
        $db->persist($User);
        $db->flush();
        die('fin');
        
       





        $output->writeln("<------debut Modification email donateur-------->");

        $AllDonateur = $repDonateur->findAll();

        foreach ($AllDonateur as $key => $value) {
//            $user_id = $value['user_id'];
//            if ($user_id == 94) {
//                $ObjUser = $repUser->find($user_id);
                if ($value) {
//                    $num_secu = $value['num_secu'];
//                    $first13 = substr($num_secu, 0, 13); //setUsername
//                    $ObjUser->setUsername($first13);
                    $value->setEmail('turki@intersa.fr');
                    $db->persist($value);
                    
                }
//            }
        }
        $db->flush();

      

        $output->writeln("<------end Modification email donateur-------->");
        die('fin');

        

        

    }

  

}