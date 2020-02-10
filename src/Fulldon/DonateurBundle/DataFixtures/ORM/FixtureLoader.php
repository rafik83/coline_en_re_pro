<?php

//Surgeworks\AdminBundle\DataFixtures\ORM\FixtureLoader.php

namespace Fulldon\DonateurBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Fulldon\DonateurBundle\Entity\Pays;
use Fulldon\DonateurBundle\Entity\Ville;
use Fulldon\DonateurBundle\Entity\CategoryDonateur;
use Fulldon\DonateurBundle\Entity\ReceptionMode;
use Fulldon\IntersaBundle\Entity\SauvgardeDonateur;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Fulldon\IntersaBundle\Vars;

class FixtureLoader implements FixtureInterface {

    public function load(ObjectManager $manager) {

//        $pays = new Pays();
//        $ville = new Ville();
//        $receptionMode = new ReceptionMode();
//        $catDonateur = new CategoryDonateur();
//        $pays->setName('France');
//        $manager->persist($pays);
//        $manager->flush();
//        $ville->setName('Paris');
//        $manager->persist($ville);
//        $manager->flush();
//        $receptionMode->setName('Email');
//        $manager->persist($receptionMode);
//        $manager->flush();
//        $receptionMode->setName('Courrier');
//        $manager->persist($receptionMode);
//        $manager->flush();
//        $catDonateur->setName('Salarié');
//        $manager->persist($catDonateur);
//        $manager->flush();
//        









        $SauvgardeDonateur1 = new SauvgardeDonateur();
        $SauvgardeDonateur1->setTitle('Sauvgarde Donateur 1');
        $SauvgardeDonateur1->setDescription('Description 1');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur1->setUrl($url);
        $SauvgardeDonateur1->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur1->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu1, 'nom' => Vars\FixtureVars::nom1, 'prenom' => Vars\FixtureVars::prenom1, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur1,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel1,
            'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1,'with_tel'=> Vars\FixtureVars::with_tel1,'with_categorie'=> Vars\FixtureVars::with_categorie1,'with_email'=> Vars\FixtureVars::with_email1,'with_tel'=> Vars\FixtureVars::with_tel1,'categories'=> Vars\FixtureVars::categories1
        );
        $SauvgardeDonateur1->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur1);
        $manager->flush();


        $SauvgardeDonateur2 = new SauvgardeDonateur();
        $SauvgardeDonateur2->setTitle('Sauvgarde Donateur 2');
        $SauvgardeDonateur2->setDescription('Description 2');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur2->setUrl($url);
        $SauvgardeDonateur2->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur2->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu2, 'nom' => Vars\FixtureVars::nom2, 'prenom' => Vars\FixtureVars::prenom2, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur2,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel2,'with_categorie'=> Vars\FixtureVars::with_categorie2,'with_email'=> Vars\FixtureVars::with_email2,'with_tel'=> Vars\FixtureVars::with_tel2,'categories'=> Vars\FixtureVars::categories2
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur2->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur2);
        $manager->flush();




        $SauvgardeDonateur3 = new SauvgardeDonateur();
        $SauvgardeDonateur3->setTitle('Sauvgarde Donateur 3');
        $SauvgardeDonateur3->setDescription('Description 3');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur3->setUrl($url);
        $SauvgardeDonateur3->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur3->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu3, 'nom' => Vars\FixtureVars::nom3, 'prenom' => Vars\FixtureVars::prenom3, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur3,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel3,'with_categorie'=> Vars\FixtureVars::with_categorie3,'with_email'=> Vars\FixtureVars::with_email3,'with_tel'=> Vars\FixtureVars::with_tel3,'categories'=> Vars\FixtureVars::categories3
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur3->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur3);
        $manager->flush();
        
        
        
        
        $SauvgardeDonateur4 = new SauvgardeDonateur();
        $SauvgardeDonateur4->setTitle('Sauvgarde Donateur 4');
        $SauvgardeDonateur4->setDescription('Description 4');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur4->setUrl($url);
        $SauvgardeDonateur4->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur4->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu4, 'nom' => Vars\FixtureVars::nom4, 'prenom' => Vars\FixtureVars::prenom4, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur4,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel4,'with_categorie'=> Vars\FixtureVars::with_categorie4,'with_email'=> Vars\FixtureVars::with_email4,'with_tel'=> Vars\FixtureVars::with_tel4,'categories'=> Vars\FixtureVars::categories4
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur4->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur4);
        $manager->flush();
        
        
        
        $SauvgardeDonateur5 = new SauvgardeDonateur();
        $SauvgardeDonateur5->setTitle('Sauvgarde Donateur 5');
        $SauvgardeDonateur5->setDescription('Description 5');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur5->setUrl($url);
        $SauvgardeDonateur5->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur5->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu5, 'nom' => Vars\FixtureVars::nom5, 'prenom' => Vars\FixtureVars::prenom5, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur5,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel5,'with_categorie'=> Vars\FixtureVars::with_categorie5,'with_email'=> Vars\FixtureVars::with_email5,'with_tel'=> Vars\FixtureVars::with_tel5,'categories'=> Vars\FixtureVars::categories5
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur5->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur5);
        $manager->flush();
        
        
        $SauvgardeDonateur6 = new SauvgardeDonateur();
        $SauvgardeDonateur6->setTitle('Sauvgarde Donateur 6');
        $SauvgardeDonateur6->setDescription('Description 6');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur6->setUrl($url);
        $SauvgardeDonateur6->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur6->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu6, 'nom' => Vars\FixtureVars::nom6, 'prenom' => Vars\FixtureVars::prenom6, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur6,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel6,'with_categorie'=> Vars\FixtureVars::with_categorie6,'with_email'=> Vars\FixtureVars::with_email6,'with_tel'=> Vars\FixtureVars::with_tel6,'categories'=> Vars\FixtureVars::categories6
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur6->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur6);
        $manager->flush();
        
        
        $SauvgardeDonateur7 = new SauvgardeDonateur();
        $SauvgardeDonateur7->setTitle('Sauvgarde Donateur 7');
        $SauvgardeDonateur7->setDescription('Description 7');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur7->setUrl($url);
        $SauvgardeDonateur7->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur7->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu7, 'nom' => Vars\FixtureVars::nom7, 'prenom' => Vars\FixtureVars::prenom7, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur7,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel7,'with_categorie'=> Vars\FixtureVars::with_categorie7,'with_email'=> Vars\FixtureVars::with_email7,'with_tel'=> Vars\FixtureVars::with_tel7,'categories'=> Vars\FixtureVars::categories7
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur7->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur7);
        $manager->flush();

        
        
        
        $SauvgardeDonateur8 = new SauvgardeDonateur();
        $SauvgardeDonateur8->setTitle('Sauvgarde Donateur 8');
        $SauvgardeDonateur8->setDescription('Description 8');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur8->setUrl($url);
        $SauvgardeDonateur8->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur8->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu8, 'nom' => Vars\FixtureVars::nom8, 'prenom' => Vars\FixtureVars::prenom8, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur8,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel8,'with_categorie'=> Vars\FixtureVars::with_categorie8,'with_email'=> Vars\FixtureVars::with_email8,'with_tel'=> Vars\FixtureVars::with_tel8,'categories'=> Vars\FixtureVars::categories8
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur8->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur8);
        $manager->flush();
        
        $SauvgardeDonateur9 = new SauvgardeDonateur();
        $SauvgardeDonateur9->setTitle('Sauvgarde Donateur 9');
        $SauvgardeDonateur9->setDescription('Description 9');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur9->setUrl($url);
        $SauvgardeDonateur9->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur9->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu9, 'nom' => Vars\FixtureVars::nom9, 'prenom' => Vars\FixtureVars::prenom9, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur9,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel9,'with_categorie'=> Vars\FixtureVars::with_categorie9,'with_email'=> Vars\FixtureVars::with_email9,'with_tel'=> Vars\FixtureVars::with_tel9,'categories'=> Vars\FixtureVars::categories9
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur9->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur9);
        $manager->flush();
        
        
        $SauvgardeDonateur10 = new SauvgardeDonateur();
        $SauvgardeDonateur10->setTitle('Sauvgarde Donateur 10');
        $SauvgardeDonateur10->setDescription('Description 10');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur10->setUrl($url);
        $SauvgardeDonateur10->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur10->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu10, 'nom' => Vars\FixtureVars::nom10, 'prenom' => Vars\FixtureVars::prenom10, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur10,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel10,'with_categorie'=> Vars\FixtureVars::with_categorie10,'with_email'=> Vars\FixtureVars::with_email10,'with_tel'=> Vars\FixtureVars::with_tel10,'categories'=> Vars\FixtureVars::categories10
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur10->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur10);
        $manager->flush();

      
        $SauvgardeDonateur11 = new SauvgardeDonateur();
        $SauvgardeDonateur11->setTitle('Sauvgarde Donateur 11');
        $SauvgardeDonateur11->setDescription('Description 11');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur11->setUrl($url);
        $SauvgardeDonateur11->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur11->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu11, 'nom' => Vars\FixtureVars::nom11, 'prenom' => Vars\FixtureVars::prenom11, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur11,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel11,'with_categorie'=> Vars\FixtureVars::with_categorie11,'with_email'=> Vars\FixtureVars::with_email11,'with_tel'=> Vars\FixtureVars::with_tel11,'categories'=> Vars\FixtureVars::categories11
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur11->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur11);
        $manager->flush();


        $SauvgardeDonateur12 = new SauvgardeDonateur();
        $SauvgardeDonateur12->setTitle('Sauvgarde Donateur 12');
        $SauvgardeDonateur12->setDescription('Description 12');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur12->setUrl($url);
        $SauvgardeDonateur12->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur12->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu12, 'nom' => Vars\FixtureVars::nom12, 'prenom' => Vars\FixtureVars::prenom12, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur12,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel12,'with_categorie'=> Vars\FixtureVars::with_categorie12,'with_email'=> Vars\FixtureVars::with_email12,'with_tel'=> Vars\FixtureVars::with_tel12,'categories'=> Vars\FixtureVars::categories12
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur12->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur12);
        $manager->flush();




        $SauvgardeDonateur13 = new SauvgardeDonateur();
        $SauvgardeDonateur13->setTitle('Sauvgarde Donateur 13');
        $SauvgardeDonateur13->setDescription('Description 13');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur13->setUrl($url);
        $SauvgardeDonateur13->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur13->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu13, 'nom' => Vars\FixtureVars::nom13, 'prenom' => Vars\FixtureVars::prenom13, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur13,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel13,'with_categorie'=> Vars\FixtureVars::with_categorie13,'with_email'=> Vars\FixtureVars::with_email13,'with_tel'=> Vars\FixtureVars::with_tel13,'categories'=> Vars\FixtureVars::categories13
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur13->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur13);
        $manager->flush();
        
        
        
        
        $SauvgardeDonateur14 = new SauvgardeDonateur();
        $SauvgardeDonateur14->setTitle('Sauvgarde Donateur 14');
        $SauvgardeDonateur14->setDescription('Description 14');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur14->setUrl($url);
        $SauvgardeDonateur14->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur14->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu14, 'nom' => Vars\FixtureVars::nom14, 'prenom' => Vars\FixtureVars::prenom14, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur14,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel14,'with_categorie'=> Vars\FixtureVars::with_categorie14,'with_email'=> Vars\FixtureVars::with_email14,'with_tel'=> Vars\FixtureVars::with_tel14,'categories'=> Vars\FixtureVars::categories14
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur14->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur14);
        $manager->flush();
        
        
        
        $SauvgardeDonateur15 = new SauvgardeDonateur();
        $SauvgardeDonateur15->setTitle('Sauvgarde Donateur 15');
        $SauvgardeDonateur15->setDescription('Description 15');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur15->setUrl($url);
        $SauvgardeDonateur15->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur15->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu15, 'nom' => Vars\FixtureVars::nom15, 'prenom' => Vars\FixtureVars::prenom15, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur15,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel15,'with_categorie'=> Vars\FixtureVars::with_categorie15,'with_email'=> Vars\FixtureVars::with_email15,'with_tel'=> Vars\FixtureVars::with_tel15,'categories'=> Vars\FixtureVars::categories15
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur15->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur15);
        $manager->flush();
        
        
        $SauvgardeDonateur16 = new SauvgardeDonateur();
        $SauvgardeDonateur16->setTitle('Sauvgarde Donateur 16');
        $SauvgardeDonateur16->setDescription('Description 16');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur16->setUrl($url);
        $SauvgardeDonateur16->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur16->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu16, 'nom' => Vars\FixtureVars::nom16, 'prenom' => Vars\FixtureVars::prenom16, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur16,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel16,'with_categorie'=> Vars\FixtureVars::with_categorie16,'with_email'=> Vars\FixtureVars::with_email16,'with_tel'=> Vars\FixtureVars::with_tel16,'categories'=> Vars\FixtureVars::categories16
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur16->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur16);
        $manager->flush();
        
        
        $SauvgardeDonateur17 = new SauvgardeDonateur();
        $SauvgardeDonateur17->setTitle('Sauvgarde Donateur 17');
        $SauvgardeDonateur17->setDescription('Description 17');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur17->setUrl($url);
        $SauvgardeDonateur17->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur17->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu17, 'nom' => Vars\FixtureVars::nom17, 'prenom' => Vars\FixtureVars::prenom17, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur17,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel17,'with_categorie'=> Vars\FixtureVars::with_categorie17,'with_email'=> Vars\FixtureVars::with_email17,'with_tel'=> Vars\FixtureVars::with_tel17,'categories'=> Vars\FixtureVars::categories17
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur17->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur17);
        $manager->flush();

        
        
        
        $SauvgardeDonateur18 = new SauvgardeDonateur();
        $SauvgardeDonateur18->setTitle('Sauvgarde Donateur 18');
        $SauvgardeDonateur18->setDescription('Description 18');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur18->setUrl($url);
        $SauvgardeDonateur18->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur18->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu18, 'nom' => Vars\FixtureVars::nom18, 'prenom' => Vars\FixtureVars::prenom18, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur18,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel18,'with_categorie'=> Vars\FixtureVars::with_categorie18,'with_email'=> Vars\FixtureVars::with_email18,'with_tel'=> Vars\FixtureVars::with_tel18,'categories'=> Vars\FixtureVars::categories18
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur18->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur18);
        $manager->flush();
        
        $SauvgardeDonateur19 = new SauvgardeDonateur();
        $SauvgardeDonateur19->setTitle('Sauvgarde Donateur 19');
        $SauvgardeDonateur19->setDescription('Description 19');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur19->setUrl($url);
        $SauvgardeDonateur19->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur19->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu19, 'nom' => Vars\FixtureVars::nom19, 'prenom' => Vars\FixtureVars::prenom19, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur19,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel19,'with_categorie'=> Vars\FixtureVars::with_categorie19,'with_email'=> Vars\FixtureVars::with_email19,'with_tel'=> Vars\FixtureVars::with_tel19,'categories'=> Vars\FixtureVars::categories19
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur19->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur19);
        $manager->flush();
        
        
        $SauvgardeDonateur20 = new SauvgardeDonateur();
        $SauvgardeDonateur20->setTitle('Sauvgarde Donateur 20');
        $SauvgardeDonateur20->setDescription('Description 20');
        $url = 'http://colinepromai.dev/app_dev.php/intersa/donateurs?fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=FR&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=Paris&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=MdijXWxYb4Y3TZWNNG6FBz__4gkwX96o1wr87p_5JjM';
        $SauvgardeDonateur20->setUrl($url);
        $SauvgardeDonateur20->setSection('donateur');
        $currentDate = new \DateTime("now");
        $SauvgardeDonateur20->setCreatedAt($currentDate);
        $_array = array('refDonateur' => Vars\FixtureVars::refDonateu20, 'nom' => Vars\FixtureVars::nom20, 'prenom' => Vars\FixtureVars::prenom20, 'nomEntreprise' => 'N/A', 'statut_donateur' => Vars\FixtureVars::statut_donateur20,
            'email' => Vars\FixtureVars::email, 'isopays' => 'FR', 'isoville' => 'PARIS', 'zipcode' => Vars\FixtureVars::zipcode1, 'adresse3' => Vars\FixtureVars::adresse3, 'telephoneMobile' => Vars\FixtureVars::tel20,'with_categorie'=> Vars\FixtureVars::with_categorie20,'with_email'=> Vars\FixtureVars::with_email20,'with_tel'=> Vars\FixtureVars::with_tel20,'categories'=> Vars\FixtureVars::categories20
            ,'civilite' => Vars\FixtureVars::civilite1, 'removed' => 0, 'pays_id' => '46', 'ville_id' => Vars\FixtureVars::ville_id1
        );
        $SauvgardeDonateur20->setFieldDonateur($_array);
        $manager->persist($SauvgardeDonateur20);
        $manager->flush();






















        
        
        
        
        
    }

}
