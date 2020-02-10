<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\IntersaBundle\Entity\CustomCronTask;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Vars;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fulldon\IntersaBundle\Entity\BillReport;
use Fulldon\IntersaBundle\Entity\MarketingCronTask;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\DonateurBundle\Entity\MotifAbo;
use Fulldon\DonateurBundle\Entity\Donateur;
use Fulldon\DonateurBundle\Entity\Prelevement;
use Fulldon\DonateurBundle\Entity\Don;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\IntersaBundle\Entity\RechercheFavoris;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Vars\DonVars;
use Fulldon\IntersaBundle\Vars\Email;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class ExportController extends Controller {

    /**
     * @return Response
     * @deprecated
     */
    public function donateurEmarketingAction() {
        $request = Request::createFromGlobals();
        $result = $this->get('fulldon.intersa.global')->getDonateurResult($request);
        $fields = array();
        $s = microtime(true);
        $batchSize = 20;
        $db = $this->get('doctrine')->getManager();
        foreach ($result as $key => $res) {
            $istel = 0;
            $isemail = 0;
            foreach ($res->getReceptionMode() as $rm) {
                if ($rm->getCode() == 'sms') {
                    $istel = 1;
                }
                if ($rm->getCode() == 'email') {
                    $isemail = 1;
                }
            }

            $fields[] = array($res->getId(), $res->getCivilite(), $res->getNom(), $res->getPrenom(), $res->getTelephoneMobile(), $res->getEmail(), '', $istel, $isemail);

            if (($key % $batchSize) == 0) {
                $db->flush();
                $db->clear();
            }
        }

        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->container->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
//utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
        $response = new Response(file_get_contents($fileLocation), 200);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        return $response;
    }

    /**
     * @return Response
     * @deprecated
     */
    public function donateurExcelAction() {
        $request = Request::createFromGlobals();
//          die('here donateur excel');
        $result = $this->get('fulldon.intersa.global')->getDonateurResult($request);


        $fields = array();

        foreach ($result as $res) {
            $istel = 0;
            $isemail = 0;
            foreach ($res->getReceptionMode() as $rm) {
                if ($rm->getCode() == 'sms') {
                    $istel = 1;
                }
                if ($rm->getCode() == 'email') {
                    $isemail = 1;
                }
            }
            $fields[] = array($res->getId(), $res->getCivilite(), $res->getNom(), $res->getPrenom(), $res->getNomEntreprise()
                , trim($res->getAdresse1() . ' ' . $res->getAdresse2() . ' ' . $res->getAdresse3() . ' ' . $res->getAdresse4()),
                $res->getIsoPays(), $res->getIsoVille(), $res->getZipcode(), $res->getTelephoneMobile(), $res->getEmail());
        }

        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
                ->setLastModifiedBy("FulldonV2.0")
                ->setTitle("Export donateur")
                ->setSubject("Edition du " . date('d/m/Y'));
        $champs = array(
            'Identifiant', 'civilité', 'Prénom', 'Nom', 'Nom de l\'entrprise', 'Adresse', 'Pays', 'Ville', 'Code postal', 'Téléphone mobile', 'Email'
        );
        $col_array = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');


        $phpExcelObject->setActiveSheetIndex(0);
        foreach ($col_array as $key => $col) {
            $phpExcelObject->getActiveSheet()->setCellValue($col . '1', $champs[$key]);
            $phpExcelObject->getActiveSheet()->getStyle($col . '1')->getFill()->applyFromArray(array('type' => 'solid',
                'startcolor' => array('rgb' => '5bc0de')
            ));
        };

        $i = 2;
        foreach ($fields as $key => $field) {
            $phpExcelObject->getActiveSheet()->setCellValue('A' . $i + $key, $field[0]);
            $phpExcelObject->getActiveSheet()->setCellValue('B' . $i + $key, $field[1]);
            $phpExcelObject->getActiveSheet()->setCellValue('B' . $i + $key, $field[2]);
        }

        $phpExcelObject->getActiveSheet()->setCellValue('A' . $i, 'Statistiques des dons');
        $phpExcelObject->getActiveSheet()->getStyle('A' . $i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));
        $phpExcelObject->getActiveSheet()->getStyle('A' . $i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle('A' . $i)->getFont()->setSize(20);
        $phpExcelObject->getActiveSheet()->mergeCells('A' . $i . ':B' . $i);
        $i++;
        foreach ($keyValuesB as $key => $value) {
            $phpExcelObject->getActiveSheet()->setCellValue('A' . $i, $key);
            $phpExcelObject->getActiveSheet()->getStyle('A' . $i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle('A' . $i)->getFont()->setSize(12);
            $phpExcelObject->getActiveSheet()->setCellValue('B' . $i, $value);
            $i++;
        }
        $total = 0;
        $isnull = 1;
        $array_month = array(
            'Janvier' => '01',
            'Février' => '02',
            'Mars' => '03',
            'Avril' => '04',
            'Mai' => '05',
            'Juin' => '06',
            'Juillet' => '07',
            'Aout' => '08',
            'Septembre' => '09',
            'Octobre' => '10',
            'Novembre' => '11',
            'Décembre' => '12',
        );
// tableau
// Thead
// Nouveaux donateurs de l'année précédente.
        $start_col = 3;
        $i = 1;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, 'Nouveaux donateurs de l\'année précédente');
        $phpExcelObject->getActiveSheet()->mergeCells($col_array[$start_col] . $i . ':' . $col_array[$start_col + count($data['entities']) + 1] . $i);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));
        $i++;
        $start_col = 3;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, 'Mois');
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
        $start_col++;
        foreach ($data['entities'] as $ent) {

            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, $ent->getName());
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
            $start_col++;
        }
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, 'Total');
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
        $start_col++;
        $i++;
// Tbody
        $start_col = 3;
        $i = 3;
        foreach ($array_month as $key => $value) {
            $start_col = 3;
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, $key);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
            $start_col++;
            foreach ($data['entities'] as $ent) {
                $isnull = 1;
                foreach ($data['tab_nouveau_donateur_preyear'] as $tnd) {
                    if ($tnd['nom_entity'] == $ent->getName() && $tnd['mois'] == $value) {
                        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, $tnd['cpt']);
                        $start_col++;
                        $total = $total + $tnd['cpt'];
                        $isnull = 0;
                    }
                }
                if ($isnull == 1) {
                    $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, 0);
                    $start_col++;
                }
            }
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, $total);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
            $start_col++;
            $total = 0;
            $i++;
        }
// Donateurs

        $start_col = 3;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, 'Nouveaux donateurs de l\'année courante');
        $phpExcelObject->getActiveSheet()->mergeCells($col_array[$start_col] . $i . ':' . $col_array[$start_col + count($data['entities']) + 1] . $i);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '5bc0de')
        ));

        $i++;
        $start_col = 3;
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, 'Mois');
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
        $start_col++;
        foreach ($data['entities'] as $ent) {

            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, $ent->getName());
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
            $start_col++;
        }
        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, 'Total');
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
        $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
        $start_col++;
        $i++;
// Tbody
        $start_col = 3;
        foreach ($array_month as $key => $value) {
            $start_col = 3;
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, $key);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
            $start_col++;
            foreach ($data['entities'] as $ent) {
                $isnull = 1;
                foreach ($data['tab_nouveau_donateur_curyear'] as $tnd) {
                    if ($tnd['nom_entity'] == $ent->getName() && $tnd['mois'] == $value) {
                        $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, $tnd['cpt']);
                        $start_col++;
                        $total = $total + $tnd['cpt'];
                        $isnull = 0;
                    }
                }
                if ($isnull == 1) {
                    $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, 0);
                    $start_col++;
                }
            }
            $phpExcelObject->getActiveSheet()->setCellValue($col_array[$start_col] . $i, $total);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setBold(true);
            $phpExcelObject->getActiveSheet()->getStyle($col_array[$start_col] . $i)->getFont()->setSize(14);
            $start_col++;
            $total = 0;
            $i++;
        }

        $phpExcelObject->getActiveSheet()->setTitle('Simple');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

// create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
// create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
// adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=tableau-bord.xlsx');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }

    public function donateursAction() {

        $class = new CustomCronTask();
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $favorisDonateurs = $repFavoris->findBy(array('section' => 'donateurs'));
        $errors = array();
        $display_error = false;
// On récupère la requête
        $request = $this->getRequest();
// On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {

            $action = $request->get('action');
            $fRechercheDons = $request->get('search_fav_dons');
            $memo_search['search_fav_dons'] = $fRechercheDons;
            $fRechercheDonateurs = $request->get('search_fav_donateurs');
            $memo_search['search_fav_donateurs'] = $fRechercheDonateurs;
//Fin de traitement du test.

            if (count($errors) == 0) {
                foreach ($fRechercheDonateurs as $r) {
                    $favObj = $repFavoris->find($r);
                    if (is_object($favObj)) {
                        $class->addRecherch($favObj);
                    }
                }

                $class->setAction($action);
                $class->setProgress(false);
                $db->persist($class);
//Test mode
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'La tâche a été palnifiée avec succès');
                return $this->redirect($this->generateUrl('fulldon_bo_custom_tasks', array('page' => 1)));
            } elseif (count($errors) > 0) {
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add('error', $error);
                    $display_error = true;
                }
            }
        }
        return $this->render('FulldonIntersaBundle:Export:donateurs.html.twig', array(
                    'display_error' => $display_error,
                    'class' => $class, 'favoris_donateurs' => $favorisDonateurs
        ));
    }

    /**
     * Generate the pdf file from the dons list
     */
    public function generationDonsPdfAction(Request $request) {
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
//        var_dump($request);
//        die('$request');
        $data = array();
        $data = $request->query->all();
//$typedon = $data["type_don"];
//        var_dump($typedon);
//        die('$typedon');
//        var_dump($typedon);
//        die('$typedon');
//$iddons = $data["id_don"];
// $cause = $data["cause"];
//        $typedon = $data["type_don"];
// $datestopfin = $data["date_stop_fin"];
//        var_dump($cause);
//        die('$cause');
//$numcheque = $data["num_cheque"];
//        var_dump($numcheque);
////        die('$numcheque');
//$codeocasion = $data["code_occasion"]; // cause code_campagne  num_rf  lot_don  type_don  date_debut
//
        //date_fin date_stop_debut 
//        var_dump($codeocasion);
//        die('$codeocasion');
//        var_dump($typedon);
//        die('$typedon');

        $request2 = Request::createFromGlobals();
//        var_dump($request2);
//        die('request2');
        $QUERY_String = $request2->server->get('QUERY_STRING');
        $url = $QUERY_String;
        $existe_favoris_recherche_dons = $db->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-pdf-gestion-don'));
        if (!$existe_favoris_recherche_dons) {
            $recherche_favoris_news = new RechercheFavoris();
            $recherche_favoris_news->setUrl($url);
            $recherche_favoris_news->setTitle('dons-pdf-gestion-don');
            $recherche_favoris_news->setDescription('dons-pdf-gestion-don');
            $recherche_favoris_news->setSection('dons');
            $db->persist($recherche_favoris_news);
            $db->flush();
            $class = new CustomCronTask;
            $fRechercheDons = $db->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-pdf-gestion-don'));
//$action = 'dons-pdf-home';
            if ($fRechercheDons) {
                $class->addRecherch($fRechercheDons);
                $class->setAction('dons-pdf-gestion-don');
                $class->setProgress(false);
                $db->persist($class);
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'Création de l\'extraction avec succès vous allez bientot recevoir un e-mail');
                return new Response('success');
            } else {
                $this->get('session')->getFlashBag()->add('warning', 'vous avez une erreur de saisie, Attention !!');
                return new Response('warning');
            }
        } else {
            $this->get('session')->getFlashBag()->add('info', ' Veuillez patienter l\'ancienne extraction est en cours vous allez bientot recevoir un e-mail !!');
            return new Response('encours');
        }
    }

    /**
     * Search the Existe cause
     */
    public function existeCauseAction(Request $request) {

        if ($request->isXmlHttpRequest()) {
//            die('ici');
            $em = $this->getDoctrine()->getManager();
            $data = array();
            $data = $request->query->all();
            $cause = $data["cause"];
//            var_dump($cause);
//            die('cause');
            $serializer = $this->container->get('serializer');
//            var_dump($serializer);
//            die('$serializer');
            $entity = $em->getRepository('FulldonDonateurBundle:Cause')->byCause($cause);
//            var_dump($entity);
//            die('$entity');
            if ($entity) {
//ar_dump($entity);
//                $causeSerialized = $serializer->serialize($entity, 'json');
                return new Response('success');
//            var_dump($causeSerialized);
//            die('$causeSerialized');
//                $response = new Response($causeSerialized);
//                $response->headers->set('Content-Type', 'application/json');
//                return $response;
            } else {

                return new Response('warning');
            }
        } else {
            $this->createNotFoundException("n'est pas une requette ajax");
        }
    }

    /**
     * Generate the Exel file from the dons list
     */
    public function generationDonsExelAction(Request $request) {


// ici c est obligatoire de saisir la cause pour préciser le donateur du dons
        $db = $this->getDoctrine()->getManager();
        $data = array();
        $data = $request->query->all();
        $request2 = Request::createFromGlobals();
        $existe_favoris_recherche_dons = $db->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-xsl-gestion-don'));
        if (!$existe_favoris_recherche_dons) {
            $QUERY_String = $request2->server->get('QUERY_STRING');
            $url = $QUERY_String;
            $recherche_favoris_news = new RechercheFavoris();
            $recherche_favoris_news->setUrl($url);
            $recherche_favoris_news->setTitle('dons-xsl-gestion-don');
            $recherche_favoris_news->setDescription('dons-xsl-gestion-don');
            $recherche_favoris_news->setSection('dons');
            $db->persist($recherche_favoris_news);
            $db->flush();
            $class = new CustomCronTask;
            $fRechercheDons = $db->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-xsl-gestion-don'));
//$action = 'dons-pdf-home';
            if ($fRechercheDons) {
                $class->addRecherch($fRechercheDons);
                $class->setAction('dons-xsl-gestion-don');
                $class->setProgress(false);
                $db->persist($class);
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'Création de l\'extraction avec succès vous allez bientot recevoir un e-mail');
//                return new Response('success');
            } else {
                $this->get('session')->getFlashBag()->add('warning', 'vous avez une erreur de saisie, Attention !!');
//                return new Response('erreur');
            }
        } else {
            $this->get('session')->getFlashBag()->add('info', ' Veuillez patienter l\'ancienne extraction est en cours vous allez bientot recevoir un e-mail !!');
//            return new Response('encours');
        }
    }

    public function generationDonateurExelAction(Request $request) {
        $db = $this->getDoctrine()->getManager();
//        $data = array();
        //$data = $request->query->all();
        //$request2 = Request::createFromGlobals();
        $headers = apache_request_headers();
        $Referer = "";
        $existe_favoris_recherche_donateur = $db->getRepository('FulldonIntersaBundle:RechercheFavoris')->findBy(array('title' => 'donateur-xsl-gestion-donateur'));
        if (!$existe_favoris_recherche_donateur) {
            foreach ($headers as $key => $value) {

                if ($key == "Referer") {
                    $Referer = $value;
                }
            }

            $explode = explode('?', $Referer);
            $QUERY_String = $explode[1]; //$request2->server->get('QUERY_STRING');
            $url = $QUERY_String;



            $recherche_favoris_news = new RechercheFavoris();
            $recherche_favoris_news->setUrl($url);
            $recherche_favoris_news->setTitle('donateur-xsl-gestion-donateur');
            $recherche_favoris_news->setDescription('donateur-xsl-gestion-donateur');
            $recherche_favoris_news->setSection('donateurs');
            $db->persist($recherche_favoris_news);
            $db->flush();
            $class = new CustomCronTask;
            $fRechercheDonateur = $db->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'donateur-xsl-gestion-donateur'));
            //$action = 'dons-pdf-home';
            if ($fRechercheDonateur) {
                $class->addRecherch($fRechercheDonateur);
                $class->setAction('donateur-xsl-gestion-donateur');
                $class->setProgress(false);
                $db->persist($class);
                $db->flush();
//                $this->get('session')->getFlashBag()->add('info', 'Création de l\'extraction avec succès vous allez bientot recevoir un e-mail');
                return new Response('success');
            } else {
//                $this->get('session')->getFlashBag()->add('warning', 'vous avez une erreur de saisie, Attention !!');
                return new Response('erreur');
            }
        } else {
//            $this->get('session')->getFlashBag()->add('info', ' Veuillez patienter l\'ancienne extraction est en cours vous allez bientot recevoir un e-mail !!');
            return new Response('encours');
        }
    }

    public function generationDonateurExel2(Request $request) {
        $db = $this->getDoctrine()->getManager();
//        $data = array();
        //$data = $request->query->all();
        //$request2 = Request::createFromGlobals();

        $Referer = "fulldon_donateurbundle_donateursearchtype%5BrefDonateur%5D=&fulldon_donateurbundle_donateursearchtype%5Bcivilite%5D=&fulldon_donateurbundle_donateursearchtype%5Bnom%5D=&fulldon_donateurbundle_donateursearchtype%5Bprenom%5D=&fulldon_donateurbundle_donateursearchtype%5BdateNaissance%5D=&fulldon_donateurbundle_donateursearchtype%5BnomEntreprise%5D=&fulldon_donateurbundle_donateursearchtype%5Bemail%5D=&pnd=&fulldon_donateurbundle_donateursearchtype%5Bisopays%5D=&fulldon_donateurbundle_donateursearchtype%5Bzipcode%5D=&fulldon_donateurbundle_donateursearchtype%5Bisoville%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse3%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse1%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse2%5D=&fulldon_donateurbundle_donateursearchtype%5Badresse4%5D=&columns=list%5B%5D%3Dnumdonateur%26list%5B%5D%3Dnom%26list%5B%5D%3Dprenom%26list%5B%5D%3Dnomentreprise%26list%5B%5D%3Dstatut&fulldon_donateurbundle_donateursearchtype%5B_token%5D=aGOIznfVst_rSXu9u88WwTR1qh26GqNy2ltXjtgFSgI";

//        $headers = apache_request_headers();
//        foreach ($headers as $key => $value) {
//
//            if ($key == "Referer") {
//                $Referer = $value;
//            }
//        }
//        $explode = explode('?', $Referer);
//        $QUERY_String = $explode[1]; //$request2->server->get('QUERY_STRING');
//        $url = $QUERY_String;
        $data = array();
        $colDispaly = null;
        $fileLocations = array();
        $url = $Referer;
//        $tt = $this->getDynamiqueColumnsDonateur();
//        $JsonResponse = new JsonResponse(array('tab' => $tt));
//        return $JsonResponse;

        if ($url != '') {
            $request = Request::create($this->container->get('router')->generate('elastic_donateur') . '?' . $url);
            $data = $this->getDynamiqueColumnsDonateur();
            $i = 1;
            do {
                $res = $this->container->get('fulldon.intersa.global')->getDonateurResult($request, $i, 1000);
                $data[] = $res['result']; //$this->getDynamiqueColumnsDonateur(); //$res['result'];
//                $JsonResponse = new JsonResponse(array('tab' => $data));
//                return $JsonResponse;
                $colDispaly = $res['coldisplay'];
                $i++;
            } while ($i <= $res['nboffset']);
        }


        // Création du fichier CSV
        $fields = array();
        $cumulDon = 0;
        $head = array();
        $cols = array('numdonateur' => '#REF', 'nom' => 'Nom', 'prenom' => 'Prénom', 'statut' => 'Statut',
            'nomentreprise' => 'Nom d\'entreprise', 'email' => 'Email', 'birthday' => 'Date de naissance',
            'telmobile' => 'Téléphone mobile', 'telfixe' => 'Téléphone fixe', 'cat' => 'Catégories',
            'adresse' => 'Adresse', 'ville' => 'Ville', 'pays' => 'Pays', 'zipcode' => 'Code postal', 'createdat' => 'Date de création', 'cumuldon' => 'Cumul des dons');
        foreach ($colDispaly as $key => $cd) {
            if ($cd == "cumuldon") {
                //array_splice($colDispaly, 0, 0);array_splice ==> numeric keys
                unset($colDispaly[0]);
                array_values($colDispaly);
            }
        }

        foreach ($colDispaly as $cd) {
            if (array_key_exists($cd, $cols)) {
                $head[] = $cols[$cd];
            }
        }
        $fields[] = $head;

//        $JsonResponse = new JsonResponse(array('tab' => $head));
//        return $JsonResponse;

        foreach ($data as $donateurs) {
            foreach ($donateurs as $donateur) {

                if ($donateur) {

                    if ($donateur->getId()) {
                        $dataxx = $this->DonateurCumulDon($donateur->getId());

                        $cumulDon = $dataxx[0]['cumul'];
                    }
                }
                $body = array();
                foreach ($colDispaly as $col) {
                    if ($col == 'numdonateur') {
                        if ($donateur->getRefDonateur()) {
                            $body[] = $donateur->getRefDonateur();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'nom') {
                        if ($donateur->getNom()) {
                            $body[] = $donateur->getNom();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'prenom') {
                        if ($donateur->getPrenom()) {
                            $body[] = $donateur->getPrenom();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'statut') {
                        if (!$donateur->getRemoved()) {
                            $body[] = 'Activé';
                        } else {
                            $body[] = 'Désactivé';
                        }
                    } elseif ($col == 'nomentreprise') {
                        if ($donateur->getNomEntreprise() == NULL) {
                            $body[] = 'N/A';
                        } else {
                            $body[] = $donateur->getNomEntreprise();
                        }
                    } elseif ($col == 'birthday') {
                        if ($donateur->getDateNaissance()) {
                            $body[] = $donateur->getDateNaissance()->format('d/m/Y');
                        } else {
                            $today = new \DateTime('now');
                            $body[] = $today->format('d/m/Y'); //"";
                        }
                    } elseif ($col == 'email') {
                        if ($donateur->getEmail()) {
                            $body[] = $donateur->getEmail();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'telmobile') {
                        if ($donateur->getTelephoneMobile()) {
                            $body[] = $donateur->getTelephoneMobile();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'telfixe') {
                        if ($donateur->getTelephoneFixe()) {
                            $body[] = $donateur->getTelephoneFixe();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'cat') {
                        if ($donateur->getCategories()) {
                            $cats_ids = array();
                            foreach ($donateur->getCategories() as $cat) {
                                $cats_ids[] = $cat->getName();
                            }
                            $body[] = implode('|', $cats_ids);
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'adresse') {
                        if ($donateur->getAdresse3()) {
                            $body[] = $donateur->getAdresse3() . ' ' . $donateur->getAdresse4();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'ville') {
                        if ($donateur->getIsoville()) {
                            $body[] = $donateur->getIsoville();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'pays') {
                        if ($donateur->getIsopays()) {
                            $body[] = $donateur->getIsopays();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'zipcode') {
                        if ($donateur->getZipcode()) {
                            $body[] = $donateur->getZipcode();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'createdat') {
                        if ($donateur->getCreatedAt()) {
                            $body[] = $donateur->getCreatedAt()->format('d/m/Y');
                        } else {
                            $today = new \DateTime('now');
                            $body[] = $today->format('d/m/Y'); //"";
                        }
                    } elseif ($col == 'cumuldon') {

                        if ($donateur) {
                            if ($donateur->getId()) {
                                $body[] = $cumulDon;
                            } else {
                                $body[] = "";
                            }
                        }
                    }
                }
                $fields[] = $body;
            }
        }

        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->container->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['titre'] = '/' . $this->container->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {

            fputcsv($fp, $field);
        }
        fclose($fp);


        $JsonResponse = new JsonResponse(array('tab' => $fileLocation)); //return vide
        return $JsonResponse;


        return $fileLocations; //return vide









        $fileLocations = $this->getDonateurCsv($url);
        $JsonResponse = new JsonResponse(array('tab' => $fileLocations));
        return $JsonResponse;
//        foreach ($fileLocations as $key => $fileLocation) {
//            $objReader = \PHPExcel_IOFactory::createReader('CSV');
//
//            // If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
//            //$objReader->setDelimiter("\t");
//            // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
//            $objReader->setInputEncoding('UTF-8');
//
//            $objPHPExcel = $objReader->load($fileLocation);
//            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//            // create the writer
////            $writer = $this->get('phpexcel')->createWriter($objPHPExcel, 'Excel2007');
////            $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
//            // create the response
//            $response = $this->get('phpexcel')->createStreamedResponse($writer);
////            $response = $this->get('phpexcel')->createStreamedResponse($writer);
//            // adding headers
////            $response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
//            $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
//            $response->headers->set('Content-Disposition', 'attachment;filename=elasticsearch.xlsx');
//            $response->headers->set('Pragma', 'public');
//            $response->headers->set('Cache-Control', 'maxage=1');
//
//            return $response;
//        }
    }

    public function getDonateurCsv($url) {
        $data = array();
        $colDispaly = null;
        $fileLocations = array();
//        $em = $this->container->get('doctrine.orm.entity_manager');
//        $favObj = $em->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'donateur-xsl-gestion-donateur'));
        if ($url != '') {
            $request = Request::create($this->container->get('router')->generate('elastic_donateur') . '?' . $url);
            $data = $this->getDynamiqueColumnsDonateur();
            $i = 1;
            do {
                $res = $this->container->get('fulldon.intersa.global')->getDonateurResult($request, $i, 1000);
//                $data = $this->getDynamiqueColumnsDonateur(); //$res['result'];
//                $JsonResponse = new JsonResponse(array('tab' => $data));
//                return $JsonResponse;
                $colDispaly = $res['coldisplay'];
                $i++;
            } while ($i <= $res['nboffset']);
        }



        // Création du fichier CSV
        $fields = array();
        $cumulDon = 0;
        $head = array();
        $cols = array('numdonateur' => '#REF', 'nom' => 'Nom', 'prenom' => 'Prénom', 'statut' => 'Statut',
            'nomentreprise' => 'Nom d\'entreprise', 'email' => 'Email', 'birthday' => 'Date de naissance',
            'telmobile' => 'Téléphone mobile', 'telfixe' => 'Téléphone fixe', 'cat' => 'Catégories',
            'adresse' => 'Adresse', 'ville' => 'Ville', 'pays' => 'Pays', 'zipcode' => 'Code postal', 'createdat' => 'Date de création', 'cumuldon' => 'Cumul des dons');
        foreach ($colDispaly as $key => $cd) {
            if ($cd == "cumuldon") {
                //array_splice($colDispaly, 0, 0);array_splice ==> numeric keys
                unset($colDispaly[0]);
                array_values($colDispaly);
            }
        }

        foreach ($colDispaly as $cd) {
            if (array_key_exists($cd, $cols)) {
                $head[] = $cols[$cd];
            }
        }
//        var_dump($head);
//        die('head');
        $fields[] = $head;
        foreach ($data as $donateurs) {
            foreach ($donateurs as $donateur) {

                if ($donateur) {

                    if ($donateur->getId()) {
                        $dataxx = $this->DonateurCumulDon($donateur->getId());

                        $cumulDon = $dataxx[0]['cumul'];
                    }
                }
                $body = array();
                foreach ($colDispaly as $col) {
                    if ($col == 'numdonateur') {
                        if ($donateur->getRefDonateur()) {
                            $body[] = $donateur->getRefDonateur();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'nom') {
                        if ($donateur->getNom()) {
                            $body[] = $donateur->getNom();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'prenom') {
                        if ($donateur->getPrenom()) {
                            $body[] = $donateur->getPrenom();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'statut') {
                        if (!$donateur->getRemoved()) {
                            $body[] = 'Activé';
                        } else {
                            $body[] = 'Désactivé';
                        }
                    } elseif ($col == 'nomentreprise') {
                        if ($donateur->getNomEntreprise() == NULL) {
                            $body[] = 'N/A';
                        } else {
                            $body[] = $donateur->getNomEntreprise();
                        }
                    } elseif ($col == 'birthday') {
                        if ($donateur->getDateNaissance()) {
                            $body[] = $donateur->getDateNaissance()->format('d/m/Y');
                        } else {
                            $today = new \DateTime('now');
                            $body[] = $today->format('d/m/Y'); //"";
                        }
                    } elseif ($col == 'email') {
                        if ($donateur->getEmail()) {
                            $body[] = $donateur->getEmail();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'telmobile') {
                        if ($donateur->getTelephoneMobile()) {
                            $body[] = $donateur->getTelephoneMobile();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'telfixe') {
                        if ($donateur->getTelephoneFixe()) {
                            $body[] = $donateur->getTelephoneFixe();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'cat') {
                        if ($donateur->getCategories()) {
                            $cats_ids = array();
                            foreach ($donateur->getCategories() as $cat) {
                                $cats_ids[] = $cat->getName();
                            }
                            $body[] = implode('|', $cats_ids);
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'adresse') {
                        if ($donateur->getAdresse3()) {
                            $body[] = $donateur->getAdresse3() . ' ' . $donateur->getAdresse4();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'ville') {
                        if ($donateur->getIsoville()) {
                            $body[] = $donateur->getIsoville();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'pays') {
                        if ($donateur->getIsopays()) {
                            $body[] = $donateur->getIsopays();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'zipcode') {
                        if ($donateur->getZipcode()) {
                            $body[] = $donateur->getZipcode();
                        } else {
                            $body[] = "";
                        }
                    } elseif ($col == 'createdat') {
                        if ($donateur->getCreatedAt()) {
                            $body[] = $donateur->getCreatedAt()->format('d/m/Y');
                        } else {
                            $today = new \DateTime('now');
                            $body[] = $today->format('d/m/Y'); //"";
                        }
                    } elseif ($col == 'cumuldon') {

                        if ($donateur) {
                            if ($donateur->getId()) {
                                $body[] = $cumulDon;
                            } else {
                                $body[] = "";
                            }
                        }
                    }
                }
                $fields[] = $body;
            }
        }


        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv';
        $fileLocation = '/' . $this->container->getParameter('folder_app') . '/Export/' . $fileName;
        $fileLocations['titre'] = '/' . $this->container->getParameter('folder_app') . '/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {

            fputcsv($fp, $field);
        }
        fclose($fp);

        //}end foreach
//        $em->remove($favObj);
//        $em->flush();
        return $fileLocations;
    }

    public function downloadDonateurExelAction(Request $request) {
        $results = $this->getActifDonateur();
        $excel = $this->get('phpexcel')->createPHPExcelObject();
        $excel->getProperties()->setCreator('iStyle')
                ->setTitle('');

        $i = 2;
        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->setCellValue('A1', '');
//        $excel->getActiveSheet()->getColumnDimension('A1')->setWidth(20);
//        $excel->getActiveSheet()->getColumnDimension('A1')->setHeight(20);
//        $excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
//        $excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18);
        $excel->getActiveSheet()->getStyle('A1')->getFill()->applyFromArray(array('type' => 'solid',
            'startcolor' => array('rgb' => '0F87CD')
        ));


        $columns_excel = array('A' => 'N°Donateur', 'B' => 'Nom',
            'C' => 'Prénom', 'D' => 'Email', 'E' => 'Pays',
            'F' => 'Ville', 'G' => 'Adresse', 'H' => 'Code Postal');

        $excel->getActiveSheet()->setTitle('Donateur. ')
                ->mergeCells('A1:G1')
                ->setCellValue('A' . $i, 'N°Donateur')
                ->setCellValue('B' . $i, 'Nom')
                ->setCellValue('C' . $i, 'Prénom')
                ->setCellValue('D' . $i, 'Email')
                ->setCellValue('E' . $i, 'Pays')
                ->setCellValue('F' . $i, 'Ville')
                ->setCellValue('G' . $i, 'Adresse')
                ->setCellValue('H' . $i, 'Code Postal');

        foreach ($columns_excel as $key => $value) {

            $excel->getActiveSheet()->getColumnDimension($key)->setWidth(30);
            $excel->getActiveSheet()->getStyle($key . $i)->getFont()->setBold(true);
            $excel->getActiveSheet()->getStyle($key . $i)->getFont()->setSize(12);
            $excel->getActiveSheet()->getStyle($key . $i)->getFill()->applyFromArray(array('type' => 'solid',
                'startcolor' => array('rgb' => '0F87CD')
            ));
        }


        $i = 3;
        for ($d = 0; $d < count($results); $d++) {
            $excel->getActiveSheet()
                    ->setCellValue('A' . $i, $results[$d]['id'])
                    ->setCellValue('B' . $i, $results[$d]['nom'])
                    ->setCellValue('C' . $i, $results[$d]['prenom'])
                    ->setCellValue('D' . $i, $results[$d]['email'])
                    ->setCellValue('E' . $i, $results[$d]['iso_pays'])
                    ->setCellValue('F' . $i, $results[$d]['iso_ville'])
                    ->setCellValue('G' . $i, $results[$d]['adresse3'])
                    ->setCellValue('H' . $i, $results[$d]['zipcode']);
            $i++;
        }


        $no = 'download-donateur-excel';
        $writer = $this->get('phpexcel')->createWriter($excel, 'Excel2007');
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=donateur-' . $no . '.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }

    public function getActifDonateur() {

        $query = "SELECT id,nom,prenom,email,iso_pays,iso_ville,adresse3,zipcode FROM coline_en_re_full_db.donateur

where removed = 0";

        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function DonateurCumulDon($donateur_id) {

        $query = "SELECT d.id as don_id,d.user_id,dt.id as donateur_id, SUM(d.montant) AS cumul,MAX(d.created_at) AS dernier_don,dt.removed as statut_donateur    FROM coline_en_re_full_db.don d

LEFT JOIN coline_en_re_full_db.donateur dt ON  d.user_id=dt.user_id

WHERE dt.removed=0 AND d.removed=0 

and dt.id = '" . $donateur_id . "' ";

        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDynamiqueColumnsDonateur() {

        $query = "SELECT ref_donateur,nom,prenom,nom_entreprise,removed FROM coline_en_re_full_db.donateur

        where removed = 0";

        $em = $this->container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function generationDonsPdfAction2(Request $request) {
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
//        var_dump($request);
//        die('$request');
        $data = array();
        $data = $request->query->all();
        $typedon = $data["type_don"];
//        var_dump($typedon);
//        die('$typedon');
//        var_dump($typedon);
//        die('$typedon');
        $iddons = $data["id_don"];
        $cause = $data["cause"];
//        $typedon = $data["type_don"];
        $datestopfin = $data["date_stop_fin"];
//        var_dump($cause);
//        die('$cause');
        $numcheque = $data["num_cheque"];
//        var_dump($numcheque);
////        die('$numcheque');
        $codeocasion = $data["code_occasion"]; // cause code_campagne  num_rf  lot_don  type_don  date_debut
        //
        //date_fin date_stop_debut 
//        var_dump($codeocasion);
//        die('$codeocasion');
//        var_dump($typedon);
//        die('$typedon');

        $request2 = Request::createFromGlobals();
//        var_dump($request2);
//        die('request2');
        $QUERY_String = $request2->server->get('QUERY_STRING');
        $url = $QUERY_String;
        $recherche_favoris_news = new RechercheFavoris();
        $recherche_favoris_news->setUrl($url);
        $recherche_favoris_news->setTitle('dons-pdf-home');
        $recherche_favoris_news->setDescription('');
        $recherche_favoris_news->setSection('dons');
        $db->persist($recherche_favoris_news);
        $db->flush();
        $class = new CustomCronTask;
        $fRechercheDons = $db->getRepository('FulldonIntersaBundle:RechercheFavoris')->findOneBy(array('title' => 'dons-pdf-home'));
        //$action = 'dons-pdf-home';
        if ($fRechercheDons) {
            $class->addRecherch($fRechercheDons);
            $class->setAction('dons-pdf-home');
            $class->setProgress(false);
            $db->persist($class);
            $db->flush();
            $this->get('session')->getFlashBag()->add('info', 'Création de l\'extraction avec succès vous allez bientot recevoir un e-mail');
            return new Response('success');
        } else {
            $this->get('session')->getFlashBag()->add('warning', 'vous avez une erreur de saisie, Attention !!');
            return new Response('warning');
        }


        //si on veut faire un ajout favoris  appartir d'une popup on le fait ici et non dont le controleur de favoris
        //
        // avec la section du favoris  = dons
        // 
        //et si utilisateur fait ajout fravoris avant recherche  je doit faire un update sur place sur url
        //
//        var_dump($typedon);
//        die('$typedon');
        // il faut tester si la bonne url existe
        if ((strpos($url, 'type_don') !== false) && (strpos($url, 'date_stop_fin') !== false)) {
            if ($cause != '') { // ici il faut tester sur les autres champs
                // load recherche favoris by code_occasion ET 
                // ET tester si code_ocassion existe dont recherche favoris sinon redirection avac obligation d'ajout donnt favoris
//                var_dump($cause);
//                die('enter cause');
                $existefavoris = $repFavoris->findOneBy(array('title' => $cause));
                if ($existefavoris) {
                    $existefavoris->setUrl($url);
                    $db->persist($existefavoris);
                    $db->flush();
                    $class = new CustomCronTask;
                    $favorisDons = $repFavoris->findBy(array('section' => 'dons'));
                    // On récupère la requête
                    $action = 'dons-pdf-home'; //$request->get('action'); //ici je trouve dons-pdf que j'ai selectionner, action (column dont table custom_cron_task : dons-xsl, dons-csv,dons-pdf,etc...
                    //var_dump($action);
                    //die('$action');
                    $fRechercheDons = $repFavoris->findOneBy(array('title' => $cause)); //$request->get('search_fav_dons'); // ici je trouve   17 quiest id de la table recherche_favoris avec title concerts-pianistes
                    //Fin de traitement du test.
                    if ($fRechercheDons) {
                        $class->addRecherch($fRechercheDons);
                        $class->setAction($action);
                        $class->setProgress(false);
                        $db->persist($class);
//                        //Test mode
                        $db->flush();
                        $this->get('session')->getFlashBag()->add('info', 'Création de l\'extraction avec succès vous allez bientot recevoir un e-mail');
                        return new Response('success');
//                        return $this->redirect($this->generateUrl('elastic_don'));
                    }
                } else {
//                    var_dump('code occasion n existe pas dont recherche favoris');
                    $this->get('session')->getFlashBag()->add('warning', 'vous devez ajouter le Code activité aux Gestion des favoris, Attention !!');
//                    return $this->redirect($this->generateUrl('elastic_don'));
                    return new Response('warning');
                }
            } else {

                // il ya un problemme si je saisie un code activité n existe pas et je fait chercher( a voir)
                return new Response('recherche sur tout les champs');
                die('enter else cause');
                var_dump('recherche sur tout les champs');
                $favorismatch_dons = $repFavoris->findBy(array('section' => 'dons'));
                $array = array();
                $array = $favorismatch_dons;
                if ($favorismatch_dons) {
                    $action = 'dons-pdf-home';
                    if (count($array) === 1) {
                        if ($favorisDons) {
                            var_dump('one favorismatch dons trouve');
                            $favorisDons->setUrl($url);
                            $db->persist($favorismatch_dons);
                            $db->flush();

                            $class = new CustomCronTask;
                            $class->addRecherch($favorismatch_dons);
                            $class->setAction($action);
                            $class->setProgress(false);
                            $db->persist($class);
                            //Test mode
                            $db->flush();
                            var_dump('extraction one pdf for all champ');
                            $this->get('session')->getFlashBag()->add('info', 'Création de l\'extraction avec succès vous allez bientot recevoir un e-mail');
                            return $this->redirect($this->generateUrl('elastic_don'));
                        }
                    }
                    if (count($array) > 1) {
                        foreach ($favorismatch_dons as $key => $value) {
                            if ($value) {
                                var_dump('many favor ismatch dons trouve');
                                $value->setUrl($url);
                                $db->persist($value);
                                $db->flush();
                                $class = new CustomCronTask;
                                $class->addRecherch($value);
                                $class->setAction($action);
                                $class->setProgress(false);
                                $db->persist($class);
                                //Test mode
                                $db->flush();
                                var_dump('extraction many pdf for all champ');
                                $this->get('session')->getFlashBag()->add('info', 'Création de l\'extraction avec succès vous allez bientot recevoir un e-mail');
                                return $this->redirect($this->generateUrl('elastic_don'));
                            }
                        }
                    }
                }
            }
        }
    }

    public function donsAction() {


//        var_dump($request = $this->getRequest());
//        die('dons action');
//         if ($this->getRequest()->getMethod() == 'POST'){
//              die('request POST ');
//         }
//         else{
//            die('request GET '); 
//         }
        // 1 entrer la requette est get
        // 2enter requette post
        $class = new CustomCronTask;
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $favorisDons = $repFavoris->findBy(array('section' => 'dons'));
//        var_dump($favorisDons);
//        die('$favorisDons');
        $errors = array();
        $display_error = false;
        // On récupère la requête
        $request = $this->getRequest(); // dont request je trouve dons-pdf
//        var_dump($request);
//        die('request');
//        var_dump($request->getMethod());
//        die('get or post');
//        var_dump($request);
//        die('request');
        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {


            $action = $request->get('action'); //ici je trouve dons-pdf que j'ai selectionner, action (column dont table custom_cron_task : dons-xsl, dons-csv,dons-pdf,etc...
//            var_dump($action);
//            die('$action');
            $fRechercheDons = $request->get('search_fav_dons'); // ici je trouve   17 quiest id de la table recherche_favoris avec title concerts-pianistes
//            var_dump($fRechercheDons);
//            die('$fRechercheDons');
            $memo_search['search_fav_dons'] = $fRechercheDons; // $memo_search['search_fav_dons'], ici je trouve   17 quiest id de la table recherche_favoris avec title concerts-pianistes cad concerts-pianistes,Theatre,etc(colum title dont table recherche favoris)....
//            var_dump($memo_search);
//            die('$memo_search');
            // dont table recherche_favoris il faut stocker la requeste dont columns url
            //Fin de traitement du test.

            if (count($errors) == 0) {
//               die('enter if');
                foreach ($fRechercheDons as $r) { //ici $r = 17 ==> id de recherche_favoris
//                    die('enter foreach');
//                    var_dump($r);
//                    die('r');
                    $favObj = $repFavoris->find($r); // $favobj est entity RechercheFavoris
//                     var_dump($favObj);
//                     var_dump($action); // 'dons-xsl'
//                      die('$action');
//                    die('$favObj');
                    if (is_object($favObj)) {
                        $class->addRecherch($favObj);
                    }
                }

                $class->setAction($action);
                $class->setProgress(false);
                $db->persist($class);
                //Test mode
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'La tâche a été palnifiée avec succès');
                return $this->redirect($this->generateUrl('fulldon_bo_custom_tasks', array('page' => 1)));
            } elseif (count($errors) > 0) {
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add('error', $error);
                    $display_error = true;
                }
            }
        }
        return $this->render('FulldonIntersaBundle:Export:dons.html.twig', array(
                    'display_error' => $display_error,
                    'class' => $class, 'favoris_dons' => $favorisDons
        ));
    }

    public function getResultatDons(Request $request) {

        $memo_search = array();
        $params = null;
        $donateur = new Donateur();
        $dem = $this->getDoctrine()->getManager();
        $repCat = $dem->getRepository('FulldonDonateurBundle:CategoryDonateur');
        $coldisplay = array();
        $withcat = $request->get('withcat');
        $withemail = $request->get('withemail');
        $withtel = $request->get('withtel');
        $statutDonateur = $request->get('statut_donateur');
        $sortelement = $request->get('sortelement');
        $sortdirection = $request->get('sortdirection');
        $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
        $pre_params = $resultform;
        $resultform = $request->get('fulldon_donateurbundle_donateursearchtype');
        $columns = $request->get('columns', 'list[]=numdonateur&list[]=nom&list[]=prenom&list[]=nomentreprise&list[]=statut');
        $restCols = array(
            'refDonateur' => 'numdonateur',
            'nom' => 'nom',
            'prenom' => 'prenom',
            'nomEntreprise' => 'nomentreprise',
            'isoville' => 'ville',
            'isopays' => 'pays',
            'zipcode' => 'zipcode',
            'removed' => 'statut',
            'dateNaissance' => 'birthday',
            'email' => 'email',
            'telephoneMobile' => 'telmobile',
            'telephoneFixe' => 'telfixe',
            'createdAt' => 'createdat',
            'cat',
            'adresse');


        if ($request->getMethod() == 'GET') {

            $data = $request->query->all();
//            var_dump($data);
//            die('$data');
            $montant_don = $data["montant_don"];
            $id_don = $data["id_don"];
            $montant_choice = $data["montant_choice"];
//            $cause = $data["cause"];
            $num_cheque = $data["num_cheque"];
            $date_debut = $data["date_debut"];
            $date_fin = $data["date_fin"];
            $type_don = $data["type_don"];
            $iban = $data["iban"];
            $date_annule_fin = $data["date_annule_fin"];
            $date_annule_debut = $data["date_annule_debut"];
            $mode_paiement = $data["mode_paiement"];
            $cause = $data["cause"];
            $code_occasion = $data["code_occasion"];
            $code_campagne = $data["code_campagne"];
            $num_rf = $data["num_rf"];
            $lot = $data["lot_don"];
            $date_stop_fin = $data["date_stop_fin"];
            $date_stop_debut = $data["date_stop_debut"];
            $d_actif = $data["d_actif"];
            $d_inactif = $data["d_inactif"];
            $is_rf = $data["is_rf"];
            $baseQuery = false;


            $app = $this->container->getParameter('elastic_db_name');
            $dons = $this->container->get('fos_elastica.finder.' . $app . '.don');
//            var_dump($dons);
//            die('fos elastica');
            $elasticaQuery = new \Elastica\Query();

            $query_part = new \Elastica\Query\Bool();
            $filters = new \Elastica\Filter\Bool();




            switch ($montant_choice) {
                case 'inf':
                    $operator = 'lte';
                    break;
                case 'sup':
                    $operator = 'gte';
                    break;
                case 'eq':
                    $operator = '=';
                    break;
                default :
                    break;
            }
            if (isset($montant_don) && !is_null($montant_don) && $montant_don != "") {
                if ($operator == '=') {
                    $baseQuery = true;
                    $query_part->addMust(
                            new \Elastica\Query\Match('montant', $montant_don)
                    );
                } else {
                    $filters->addMust(
                            new \Elastica\Filter\Range('montant', array(
                        $operator => $montant_don
                            ))
                    );
                }
            }
            if (isset($id_don) && !empty($id_don)) {

                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('id', $id_don)
                );
            }
            if (isset($type_don) && !empty($type_don)) {
                $baseQuery = true;
                $bol = 'false';
                if ($type_don == 'regulier') {
                    $bol = 'true';
                }


                $query_part->addMust(
                        new \Elastica\Query\Term(array('ispa' => $bol))
                );
            }
            if (isset($d_actif) && !empty($d_actif)) {
                $baseQuery = true;
                if ($d_actif == "on")
                    $var = 'false';
                else
                    $var = 'true';
                $query_part->addMust(
                        new \Elastica\Query\Term(array('removed' => $var))
                );
            }

            if (isset($d_inactif) && !empty($d_inactif)) {
                $baseQuery = true;
                if ($d_inactif == "on")
                    $var = 'true';
                else
                    $var = 'false';
                $query_part->addMust(
                        new \Elastica\Query\Term(array('removed' => $var))
                );
            }


            if (isset($cause) && !empty($cause)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('cause.code', $cause)
                );
            }

            if (isset($code_occasion) && !empty($code_occasion)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('cause.codeOccasion.code', $code_occasion)
                );
            }

            if (isset($code_campagne) && !empty($code_campagne)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('cause.codeOccasion.codeCompagne.code', $code_campagne)
                );
            }

            if (isset($lot) && !empty($lot)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('lot', $lot)
                );
            }
            if (isset($date_debut) && !empty($date_debut)) {
                $date_debut = $date_debut . ' 00:00:00';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('dateFiscale', array(
                    'gte' => $mydate
                        ))
                );
            }

            if (isset($date_fin) && !empty($date_fin)) {

                $date_fin = $date_fin . ' 23:59:59';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('dateFiscale', array(
                    'lte' => $mydate
                        ))
                );
            }
            if (isset($date_annule_debut) && !empty($date_annule_debut)) {
                $date_annule_debut = $date_annule_debut . ' 00:00:00';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_annule_debut);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('removedAt', array(
                    'gte' => $mydate
                        ))
                );
            }
            if (isset($date_annule_fin) && !empty($date_annule_fin)) {
                $date_annule_fin = $date_annule_fin . ' 23:59:59';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_annule_fin);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('removedAt', array(
                    'lte' => $mydate
                        ))
                );
            }

            if (isset($date_stop_debut) && !empty($date_stop_debut)) {
                $date_stop_debut = $date_stop_debut . ' 00:00:00';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_stop_debut);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('abonnement.disabledAt', array(
                    'gte' => $mydate
                        ))
                );
            }
            if (isset($date_stop_fin) && !empty($date_stop_fin)) {
                $date_stop_fin = $date_stop_fin . ' 23:59:59';
                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_stop_fin);
                $mydate = $d->format('c');
                $filters->addMust(
                        new \Elastica\Filter\Range('abonnement.disabledAt', array(
                    'lte' => $mydate
                        ))
                );
            }

            if (isset($iban) && !empty($iban)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\MatchPhrasePrefix('abonnement.iban', $iban)
                );
            }
            if (isset($num_rf) && !empty($num_rf)) {
                $baseQuery = true;
                $filtersQuery = new \Elastica\Query\Bool();
                $rfQuery = new \Elastica\Query\Term();
                $rfQuery->setTerm('rfs.id', $num_rf);
                $filtersQuery->addMust($rfQuery);
                $nestedRfQuery = new \Elastica\Query\Nested();
                $nestedRfQuery->setPath('rfs');
                $nestedRfQuery->setQuery($filtersQuery);

                //adding the parameters to the main query
                $query_part->addMust($nestedRfQuery);
            }

            if (isset($is_rf) && !empty($is_rf)) {
                $baseQuery = true;
                $filtersQuery = new \Elastica\Query\Bool();
                $rfQuery = new \Elastica\Query\Term();
                $rfQuery->setTerm('rfs.id', '00000000');
                $filtersQuery->addMustNot($rfQuery);
                $nestedRfQuery = new \Elastica\Query\Nested();
                $nestedRfQuery->setPath('rfs');
                $nestedRfQuery->setQuery($filtersQuery);

//adding the parameters to the main query
                $query_part->addMust($nestedRfQuery);
            }

            if (isset($num_cheque) && !empty($num_cheque)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\MatchPhrasePrefix('transaction.cheque.numeroCheque', $num_cheque)
                );
            }

            if (isset($mode_paiement) && !empty($mode_paiement)) {
                $baseQuery = true;
                $query_part->addMust(
                        new \Elastica\Query\Match('modePaiement.codeSolution', $mode_paiement)
                );
            }
            if (!$baseQuery) {
                $query_part = new \Elastica\Query\MatchAll();
            }

            $query = new \Elastica\Query\Filtered($query_part, $filters);
            $finalQuery = new \Elastica\Query($query);
//            var_dump($finalQuery);
//            die('final query');
            if (!empty($sortelement) && !empty($sortdirection)) {
                $index = array_search($sortelement, $restCols);
                if (!is_string($index)) {
                    $index = 'id';
                }
                $finalQuery->setSort(array($index => array('order' => $sortdirection)));
            } else {
                $finalQuery->setSort(array('id' => array('order' => 'desc')));
            }

            $mycol = explode('&', $columns);
            foreach ($mycol as $col) {
                $coldisplay[] = substr($col, 7, strlen($col));
            }


//            die('avant result');
//            echo ' result';
//            $adapter = new ArrayAdapter(NULL);
//            $pagerfanta = new Pagerfanta($adapter);
//            var_dump($pagerfanta);
//            die('pager fanta');
//            $result = array();
            $result = $dons->findPaginated($finalQuery);
//            $adapter = new ArrayAdapter($result);
//            $pagerfanta = new Pagerfanta($adapter);
//             var_dump($pagerfanta);
//             die('pager fanta');
//            var_dump($result);
            // le problemme appartir d ici
//            echo '0';
//            die('result');



            $total_dons = $result->getNbResults();


            $batchSize = $result->getMaxPerPage();


            $result->setMaxPerPage($batchSize);



            $last_page = ceil($total_dons / $batchSize);


            $offset = $result->getCurrentPage();

            $result->setCurrentPage($offset);



//            var_dump($result->getCurrentPageResults());
//            die('$result->getCurrentPageResults()');
            $currentPage = $result->getCurrentPageResults();

            if (count($currentPage) == 0) {
                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
            }
        }



        return array('result' => $result, 'nboffset' => $last_page, 'coldisplay' => $coldisplay);
    }

    public function donsCsvAction(Request $request) {
        
    }

    public function donsCsvAction2(Request $request) {


//        $data = $request->query->all();
//        var_dump($data);
//        die('$data');
        //return $this->redirect($this->generateUrl('elastic_don'));
        $db = $this->getDoctrine()->getManager();
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
//       
//        $request = Request::createFromGlobals();
//        $montant_don = $request->get('montant_don');    
//       
//        $result = $this->get('fulldon.intersa.global')->getDonsResult($request);
//        
        $result = $this->getResultatDons($request);
//        var_dump($result);
//        die('result');
        $fields = array();
        $fields[] = array("Identifiant", "Civilité", "Nom", "Prenom", "Telephone Mobile", "Email", "Montant", "Code d'activité", "Code d'occasion", "Mode de paiement", "Com tel", "Com email");
        $occasionRep = $db->getRepository('FulldonDonateurBundle:CodeOccasion');
//        die('$occasionRep');
        $campagneRep = $db->getRepository('FulldonDonateurBundle:CodeCompagne');
//        die('$campagneRep');
        $ids = array();
        $batchSize = 20;
//           die('$batchSize');
//        var_dump(count($result));

        if (count($result) > 0) {

            foreach ($result as $key => $res) {
                var_dump($res);
//                die('res'); 
                $istel = 0;
                $isemail = 0;
//                $res = $db->getRepository('FulldonDonateurBundle:Don')->find(10243);
                // 10243
                $id_dons = 'id';
                $exp = explode('id', $res);
//                var_dump($exp);
//                die('expolde');
//                if (strpos($res, $id_dons) !== false) {
//                    die('true'); 
//                }
//                $d2 = explode('currentPageResults', $res);
//                var_dump($d2);
//                die('$d2');
//                $user = $db->getRepository('FulldonDonateurBundle:CodeCompagne');
//                var_dump($res->getUser()->getUsername());
//                var_dump($res->getUser());
//                die('get user');
                if (is_object($res->getUser())) {
//                    die('enter if 1');
                    $donateur = $repDonateur->findOneBy(array('user' => $res->getUser()));
//                    die('$donateur');

                    if (is_object($donateur)) {
//                        die('enter if 2');
                        foreach ($donateur->getReceptionMode() as $rm) {
//                            die('enter foreach 2');
                            if ($rm->getCode() == 'sms') {
                                $istel = 1;
                            }
                            if ($rm->getCode() == 'email') {
                                $isemail = 1;
                            }
                        }
//                         die('end foreach 2');

                        $fields[] = array($donateur->getId(), $donateur->getCivilite(), $donateur->getNom(), $donateur->getPrenom(), $donateur->getTelephoneMobile(), $donateur->getEmail(), $res->getMontant() . '€', $res->getCause()->getCode(), $res->getCause()->getCodeOccasion()->getCode(), $res->getModePaiement()->getCodeSolution(), $res->getTransaction()->getStatut()->getLibelle(), $istel, $isemail);

                        if (($key % $batchSize) == 0) {
                            $db->flush();
                            $db->clear();
                        }
//                         die('end if 3');
                    }
//                    // fin test donateur
                }
            }
        }

        $name = uniqid(mt_rand());
        $fileName = $name . '-' . date('Y') . '.csv'; // 152235236358a19f5169881-2017.csv
//        var_dump($fileName); /// 152235236358a19f5169881-2017.csv
//        die('filename');
//        $fileLocation = '/' . $this->container->getParameter('folder_app') . '/Export/' . $fileName; // /coline_en_re/Export/133446867158a19f9c90892-2017.csv

        $fileLocation = '/var/www/csv_coline/Export/' . $fileName;
        $fp = fopen($fileLocation, 'wb');
//        var_dump($fp);
//        die('$fp');
        //utf8encoding
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        fclose($fp);
//        die('$close fp');
        $response = new Response(file_get_contents($fileLocation), 200);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
//          die('$ fin');
        //return $this->redirect($this->generateUrl('elastic_don'));
        return $response;
    }

    public function donsCsvAction3(Request $request) {
//        $obj = $this->getRequest()->get('mydata');
        $obj2 = htmlspecialchars($_GET["mydata"]);
        $serializer = $this->container->get('jms_serializer');
        $response = $serializer->serialize($obj2, 'json');
//         $response = str_replace(array("[{",""),"",$response);
        $response = str_replace(array("&quot", ""), "", $response);
        $response = str_replace(array(";
ligne_id", ""), "{ligne_id", $response);
        $response = str_replace(array("{{ligne_id", ""), "{ligne_id", $response);
        $response = str_replace(array(";
:;
", ""), ":", $response);
        $response = str_replace(array(";
, ", ""), ", ", $response);
        $response = str_replace(array(",;
", ""), ", ", $response);
        $response = str_replace(array(";
}", ""), "}", $response);
        $response = str_replace(array("[{", ""), "{", $response);
        $response = str_replace(array("}]", ""), "}", $response);
        $response = str_replace(array("{", ""), "", $response);
        $explode = explode("}, ", $response);
        //$data = json_decode($response);


        foreach ($explode as $key => $value) {
            var_dump($value);
        }
//        die("explode");
    }

//    public function donsCsvAction(Request $request) {
//        $last_page = 0;
//        $next_page = 0;
//        $previous_page = 0;
//        $memo_search = array();
//        $params = null;
//        $db = $this->getDoctrine()->getManager();
//       $array_donateur_don = array();
//       $index_donateur_don = 0 ;
//        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
//        $currentPage = 0;
//        $total_dons = 0;
//        $pre_params = $request->query->all();
//        $modeRep = $db->getRepository('FulldonDonateurBundle:ModePaiement');
//        $favoris = $repFavoris->findBy(array('section' => 'dons'));
//        $tellmeall = true;
//        $sortelement = $request->get('sortelement');
//        $sortdirection = $request->get('sortdirection');
//        $coldisplay = array();
//        $modes = $modeRep->findAll();
//        $page = $request->get('page', 1);
//        $columns = $request->get('columns', 'list[]=numdon&list[]=amount&list[]=nom&list[]=prenom&list[]=codeactivite&list[]=statut&list[]=modepay&list[]=createdat&list[]=typedon');
//        $restCols = array(
//            'id' => 'numdon',
//            'dateFiscale' => 'datefiscale',
//            'montant' => 'amount',
//            'nom',
//            'nomentreprise',
//            'prenom',
//            'cause.code' => 'codeactivite',
//            'createdAt' => 'createdat',
//            'transaction.statut.id' => 'statut',
//            'modePaiement.codeSolution' => 'modepay',
//            'ispa' => 'typedon',
//            'rfs.id' => 'rfs',
//            'lot' => 'lot',
//            'cause.codeOccasion.code' => 'codeoccasion',
//            'cause.codeOccasion.codeCompagne.code' => 'codecampagne'
//        );
//        if ($request->getMethod() == 'GET') {
//
//            $montant_don = $request->get('montant_don');
//            $memo_search['montant_don'] = $montant_don;
//            $id_don = $request->get('id_don');
//            $memo_search['id_don'] = $id_don;
//            $montant_choice = $request->get('montant_choice');
//            $memo_search['montant_choice'] = $montant_choice;
//            $cause = $request->get('cause');
//            $memo_search['montant_choice'] = $montant_choice;
//            $num_cheque = $request->get('num_cheque');
//            $memo_search['num_cheque'] = $num_cheque;
//            $date_debut = $request->get('date_debut');
//            $memo_search['date_debut'] = $date_debut;
//            $date_fin = $request->get('date_fin');
//            $memo_search['date_fin'] = $date_fin;
//            $type_don = $request->get('type_don');
//            $memo_search['type_don'] = $type_don;
//            $iban = $request->get('iban');
//            $memo_search['iban'] = $iban;
//            $date_annule_fin = $request->get('date_annule_fin');
//            $memo_search['date_annule_fin'] = $date_annule_fin;
//            $date_annule_debut = $request->get('date_annule_debut');
//            $memo_search['date_annule_debut'] = $date_annule_debut;
//            $mode_paiement = $request->get('mode_paiement');
//            $memo_search['mode_paiement'] = $mode_paiement;
//            $cause = $request->get('cause');
//            $memo_search['cause'] = $cause;
//            $code_occasion = $request->get('code_occasion');
//            $memo_search['code_occasion'] = $code_occasion;
//            $code_campagne = $request->get('code_campagne');
//            $memo_search['code_campagne'] = $code_campagne;
//            $num_rf = $request->get('num_rf');
//            $memo_search['num_rf'] = $num_rf;
//            $lot = $request->get('lot_don');
//            $memo_search['lot_don'] = $lot;
//            $date_stop_fin = $request->get('date_stop_fin');
//            $memo_search['date_stop_fin'] = $date_stop_fin;
//            $date_stop_debut = $request->get('date_stop_debut');
//            $memo_search['date_stop_debut'] = $date_stop_debut;
//            $d_actif = $request->get('d_actif');
//            $memo_search['d_actif'] = $d_actif;
//            $d_inactif = $request->get('d_inactif');
//            $memo_search['d_inactif'] = $d_inactif;
//            $is_rf = $request->get('is_rf');
//            $memo_search['is_rf'] = $is_rf;
//            $baseQuery = false;
//
//
//            $app = $this->container->getParameter('elastic_db_name');
//            $dons = $this->get('fos_elastica.finder.' . $app . '.don');
//            $elasticaQuery = new \Elastica\Query();
//
//            $query_part = new \Elastica\Query\Bool();
//            $filters = new \Elastica\Filter\Bool();
//
//
//
//            switch ($montant_choice) {
//                case 'inf':
//                    $operator = 'lte';
//                    break;
//                case 'sup':
//                    $operator = 'gte';
//                    break;
//                case 'eq':
//                    $operator = '=';
//                    break;
//                default :
//                    break;
//            }
//            if (isset($montant_don) && !is_null($montant_don) && $montant_don != "") {
//                if ($operator == '=') {
//                    $baseQuery = true;
//                    $query_part->addMust(
//                            new \Elastica\Query\Match('montant', $montant_don)
//                    );
//                } else {
//                    $filters->addMust(
//                            new \Elastica\Filter\Range('montant', array(
//                        $operator => $montant_don
//                            ))
//                    );
//                }
//            }
//            if (isset($id_don) && !empty($id_don)) {
//
//                $baseQuery = true;
//                $query_part->addMust(
//                        new \Elastica\Query\Match('id', $id_don)
//                );
//            }
//            if (isset($type_don) && !empty($type_don)) {
//                $baseQuery = true;
//                $bol = 'false';
//                if ($type_don == 'regulier') {
//                    $bol = 'true';
//                }
//
//
//                $query_part->addMust(
//                        new \Elastica\Query\Term(array('ispa' => $bol))
//                );
//            }
//            if (isset($d_actif) && !empty($d_actif)) {
//                $baseQuery = true;
//                if ($d_actif == "on")
//                    $var = 'false';
//                else
//                    $var = 'true';
//                $query_part->addMust(
//                        new \Elastica\Query\Term(array('removed' => $var))
//                );
//            }
//
//            if (isset($d_inactif) && !empty($d_inactif)) {
//                $baseQuery = true;
//                if ($d_inactif == "on")
//                    $var = 'true';
//                else
//                    $var = 'false';
//                $query_part->addMust(
//                        new \Elastica\Query\Term(array('removed' => $var))
//                );
//            }
//
//
//            if (isset($cause) && !empty($cause)) {
//                $baseQuery = true;
//                $query_part->addMust(
//                        new \Elastica\Query\Match('cause.code', $cause)
//                );
//            }
//
//            if (isset($code_occasion) && !empty($code_occasion)) {
//                $baseQuery = true;
//                $query_part->addMust(
//                        new \Elastica\Query\Match('cause.codeOccasion.code', $code_occasion)
//                );
//            }
//
//            if (isset($code_campagne) && !empty($code_campagne)) {
//                $baseQuery = true;
//                $query_part->addMust(
//                        new \Elastica\Query\Match('cause.codeOccasion.codeCompagne.code', $code_campagne)
//                );
//            }
//
//            if (isset($lot) && !empty($lot)) {
//                $baseQuery = true;
//                $query_part->addMust(
//                        new \Elastica\Query\Match('lot', $lot)
//                );
//            }
//            if (isset($date_debut) && !empty($date_debut)) {
//                $date_debut = $date_debut . ' 00:00:00';
//                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_debut);
//                $mydate = $d->format('c');
//                $filters->addMust(
//                        new \Elastica\Filter\Range('dateFiscale', array(
//                    'gte' => $mydate
//                        ))
//                );
//            }
//
//            if (isset($date_fin) && !empty($date_fin)) {
//
//                $date_fin = $date_fin . ' 23:59:59';
//                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_fin);
//                $mydate = $d->format('c');
//                $filters->addMust(
//                        new \Elastica\Filter\Range('dateFiscale', array(
//                    'lte' => $mydate
//                        ))
//                );
//            }
//            if (isset($date_annule_debut) && !empty($date_annule_debut)) {
//                $date_annule_debut = $date_annule_debut . ' 00:00:00';
//                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_annule_debut);
//                $mydate = $d->format('c');
//                $filters->addMust(
//                        new \Elastica\Filter\Range('removedAt', array(
//                    'gte' => $mydate
//                        ))
//                );
//            }
//            if (isset($date_annule_fin) && !empty($date_annule_fin)) {
//                $date_annule_fin = $date_annule_fin . ' 23:59:59';
//                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_annule_fin);
//                $mydate = $d->format('c');
//                $filters->addMust(
//                        new \Elastica\Filter\Range('removedAt', array(
//                    'lte' => $mydate
//                        ))
//                );
//            }
//
//            if (isset($date_stop_debut) && !empty($date_stop_debut)) {
//                $date_stop_debut = $date_stop_debut . ' 00:00:00';
//                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_stop_debut);
//                $mydate = $d->format('c');
//                $filters->addMust(
//                        new \Elastica\Filter\Range('abonnement.disabledAt', array(
//                    'gte' => $mydate
//                        ))
//                );
//            }
//            if (isset($date_stop_fin) && !empty($date_stop_fin)) {
//                $date_stop_fin = $date_stop_fin . ' 23:59:59';
//                $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date_stop_fin);
//                $mydate = $d->format('c');
//                $filters->addMust(
//                        new \Elastica\Filter\Range('abonnement.disabledAt', array(
//                    'lte' => $mydate
//                        ))
//                );
//            }
//
//            if (isset($iban) && !empty($iban)) {
//                $baseQuery = true;
//                $query_part->addMust(
//                        new \Elastica\Query\MatchPhrasePrefix('abonnement.iban', $iban)
//                );
//            }
//            if (isset($num_rf) && !empty($num_rf)) {
//                $baseQuery = true;
//                $filtersQuery = new \Elastica\Query\Bool();
//                $rfQuery = new \Elastica\Query\Term();
//                $rfQuery->setTerm('rfs.id', $num_rf);
//                $filtersQuery->addMust($rfQuery);
//                $nestedRfQuery = new \Elastica\Query\Nested();
//                $nestedRfQuery->setPath('rfs');
//                $nestedRfQuery->setQuery($filtersQuery);
//
////adding the parameters to the main query
//                $query_part->addMust($nestedRfQuery);
//            }
//
//            if (isset($is_rf) && !empty($is_rf)) {
//                $baseQuery = true;
//                $filtersQuery = new \Elastica\Query\Bool();
//                $rfQuery = new \Elastica\Query\Term();
//                $rfQuery->setTerm('rfs.id', '00000000');
//                $filtersQuery->addMustNot($rfQuery);
//                $nestedRfQuery = new \Elastica\Query\Nested();
//                $nestedRfQuery->setPath('rfs');
//                $nestedRfQuery->setQuery($filtersQuery);
//
////adding the parameters to the main query
//                $query_part->addMust($nestedRfQuery);
//            }
//
//            if (isset($num_cheque) && !empty($num_cheque)) {
//                $baseQuery = true;
//                $query_part->addMust(
//                        new \Elastica\Query\MatchPhrasePrefix('transaction.cheque.numeroCheque', $num_cheque)
//                );
//            }
//
//            if (isset($mode_paiement) && !empty($mode_paiement)) {
//                $baseQuery = true;
//                $query_part->addMust(
//                        new \Elastica\Query\Match('modePaiement.codeSolution', $mode_paiement)
//                );
//            }
//            if (!$baseQuery) {
//                $query_part = new \Elastica\Query\MatchAll();
//            }
//
//            $query = new \Elastica\Query\Filtered($query_part, $filters);
//            $finalQuery = new \Elastica\Query($query);
//            if (!empty($sortelement) && !empty($sortdirection)) {
//                $index = array_search($sortelement, $restCols);
//                if (!is_string($index)) {
//                    $index = 'id';
//                }
//                $finalQuery->setSort(array($index => array('order' => $sortdirection)));
//            } else {
//                $finalQuery->setSort(array('id' => array('order' => 'desc')));
//            }
//            $result = $dons->findPaginated($finalQuery);
//            $total_dons = $result->getNbResults();
//            //$result->setMaxPerPage(20);
//            $donateur_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
//            $result->setMaxPerPage($donateur_per_page);
//            $last_page = ceil($total_dons / $donateur_per_page);
//            $result->setCurrentPage($page);
//            $currentPage = $result->getCurrentPageResults();
//            if (count($currentPage) == 0) {
//                $this->get('session')->getFlashBag()->add('warning', 'Aucun résultat pour votre recherche !');
//            }
//            unset($pre_params['page']);
//            $url = array();
//            foreach ($pre_params as $key => $ele) {
//                $url[] = "$key = " . urlencode($ele);
//            }
//            $params = implode('&', $url);
//            // Adding columns to params
//        }
//        $mycol = explode('&', $columns);
//        foreach ($mycol as $col) {
//            $coldisplay[] = substr($col, 7, strlen($col));
//        }
//
//        foreach ($coldisplay as $tab) {
//            $index = array_search($tab, $restCols);
//            unset($restCols[$index]);
//        }
//
//        foreach ($currentPage as $key => $value) {
//            var_dump($value);
//        die('$value');
//        }
//        
//        
//        
//        
//        return $this->render('FulldonIntersaBundle:Dons:elasticsearch.html.twig', array(
//                    'result' => $currentPage,
//                    'modes' => $modes,
//                    'memo_search' => $memo_search,
//                    'last_page' => $last_page,
//                    'previous_page' => $previous_page,
//                    'current_page' => $page,
//                    'next_page' => $next_page,
//                    'total_dons' => $total_dons,
//                    'params' => $params,
//                    'favoris' => $favoris,
//                    'col_display' => $coldisplay,
//                    'columns' => $columns,
//                    'rest_cols' => $restCols,
//                    'sortelement' => $sortelement,
//                    'sortdirection' => $sortdirection
//        ));
//    }
// end csv
}
