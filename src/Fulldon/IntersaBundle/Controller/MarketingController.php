<?php

namespace Fulldon\IntersaBundle\Controller;

use Fulldon\IntersaBundle\Entity\BiblioImage;
use Fulldon\IntersaBundle\Form\newPicType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query\ResultSetMapping;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Fulldon\IntersaBundle\Entity\MarketingCronTask;
use Fulldon\IntersaBundle\Form\MarketingCronTaskType;
use Symfony\Component\HttpFoundation\Request;

class MarketingController extends Controller {

    public function indexAction($page) {
        $db = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $tableRep = $db->getRepository('FulldonIntersaBundle:MarketingCronTask');
        $elements = $tableRep->findAll();


        $total_elements = count($elements);
        $elements_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_elements / $elements_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        /* résultat  à afficher */
        $elements = $this->getDoctrine()
                ->getRepository('FulldonIntersaBundle:MarketingCronTask')
                ->createQueryBuilder('t');

        $elements = $elements
                        ->orderBy('t.id', 'DESC')
                        ->setFirstResult(($page * $elements_per_page) - $elements_per_page)
                        ->setMaxResults($this->get('fulldon.custom_params')->getParam('max_articles_on_listepage'))->getQuery()->getResult();

        return $this->render('FulldonIntersaBundle:Marketing:index.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_elements' => $total_elements,
                    'elements' => $elements,
        ));
    }

    public function newCampagneAction($copiedCampagne) {

        $biblioimg = new BiblioImage();
        $class = new MarketingCronTask;
        $db = $this->getDoctrine()->getManager();
        $repFavoris = $db->getRepository('FulldonIntersaBundle:RechercheFavoris');
        $repBiblioImg = $db->getRepository('FulldonIntersaBundle:BiblioImage');
        $repDonateur = $db->getRepository('FulldonDonateurBundle:Donateur');
        $favorisDons = $repFavoris->findBy(array('section' => 'dons'));
        $favorisDonateurs = $repFavoris->findBy(array('section' => 'donateurs'));
        $images = $repBiblioImg->findBy(array(), array('id' => 'desc'));
        if ($copiedCampagne != null) {
            $campagneRep = $db->getRepository('FulldonIntersaBundle:MarketingCronTask');
            $campagne = $campagneRep->find($copiedCampagne);
            $class->setEmailContent($campagne->getEmailContent());
            $class->setSmsContent($campagne->getSmsContent());
            $class->setObjet($campagne->getObjet());
            $class->setIsEmail($campagne->getIsEmail());
            $class->setIsSms($campagne->getIsSms());
        }
        $formBiblio = $this->createForm(new newPicType(), $biblioimg);
        $form = $this->createForm(new MarketingCronTaskType(), $class);
        $errors = array();
        $display_error = false;
        $categories_doanteurs = $db->getRepository('FulldonDonateurBundle:CategoryDonateur')->findAll();

        // On récupère la requête
        $request = $this->getRequest();
        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            $import = $request->get('extraction');
            $periode = $request->get('reportrange');
            $amount_start = $request->get('amount_start');
            $memo_search['amount_start'] = $amount_start;
            $amount_end = $request->get('amount_end');
            $memo_search['amount_end'] = $amount_end;
            $type_don = $request->get('type_don');
            $memo_search['type_don'] = $type_don;
            $code_activite = $request->get('cause');
            $memo_search['cause'] = $code_activite;
            $type_donateur = $request->get('type_donateur');
            $memo_search['type_donateur'] = $type_donateur;
            $columns = $request->get('columns');

            // Email testing.
            $testMode = $request->get('test_mode');
            $testMode = isset($testMode) && $testMode ? "1" : "0";
            $memo_search['test_mode'] = $testMode;
            $idDonateurTest = $request->get('id_donateur_test');
            $memo_search['id_donateur_test'] = $idDonateurTest;

            $civiliteTest = $request->get('civilite_test');
            $memo_search['civilite_test'] = $civiliteTest;

            $codeActiviteTest = $request->get('code_activite_test');
            $memo_search['code_activite_test'] = $codeActiviteTest;

            $nomTest = $request->get('nom_test');
            $memo_search['nom_donateur'] = $nomTest;

            $prenomTest = $request->get('prenom_test');
            $memo_search['prenom_test'] = $prenomTest;

            $telephoneTest = $request->get('telephone_test');
            $memo_search['telephone_test'] = $telephoneTest;

            $emailTest = $request->get('email_test');
            $memo_search['email_test'] = $emailTest;

            $sommePaTest = $request->get('somme_pa_test');
            $memo_search['somme_pa_test'] = $sommePaTest;

            $periodiciteTest = $request->get('periodicite_test');
            $memo_search['periodicite_test'] = $periodiciteTest;

            $idDonTest = $request->get('id_don_test');
            $memo_search['id_don_test'] = $idDonTest;

            $montantDonTest = $request->get('montant_don_test');
            $memo_search['montant_don_test'] = $montantDonTest;

            $rumPaTest = $request->get('rum_pa_test');
            $memo_search['rum_pa_test'] = $rumPaTest;

            $datePaTest = $request->get('date_pa_test');
            $memo_search['date_pa_test'] = $datePaTest;

            $adresseTest = $request->get('adresse_test');
            $memo_search['adresse_test'] = $adresseTest;

            $okSmsTest = $request->get('ok_sms_test');
            $memo_search['ok_sms_test'] = $okSmsTest;
            $okSmsTest = isset($okSmsTest) && $okSmsTest ? "1" : "0";
            $okEmailTest = $request->get('ok_email_test');
            $memo_search['ok_email_test'] = $okEmailTest;
            $okEmailTest = isset($okEmailTest) && $okEmailTest ? "1" : "0";

            $fRechercheDons = $request->get('search_fav_dons');
            $memo_search['search_fav_dons'] = $fRechercheDons;
            $fRechercheDonateurs = $request->get('search_fav_donateurs');
            $memo_search['search_fav_donateurs'] = $fRechercheDonateurs;
            //Fin de traitement du test.

            $brutDateDebut = null;
            $brutDateFin = null;
            $fileNumLines = null;
            $requiredColumns = array(
                "iddonateur",
                "civilitedonateur",
                "nomdonateur",
                "prenomdonateur",
                "telephonedonateur",
                "emaildonateur",
                "issms",
                "isemail"
            );
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
            $columns = str_replace('list[]=', '', $columns);
            $arrColumn = explode('&', $columns);
            $nbColumns = count($arrColumn);
            foreach ($colCorresp as $key => $col) {
                // si la column existe dans le fichier et n'existe pas dans le texte de l'email  et du SMS si c'est coché on registre une erreur
                if ($class->getIsEmail() && strpos($class->getEmailContent(), $col)) {
                    if (!in_array($key, $arrColumn)) {
                        // Erreur
                        $errors['coerance_' . $key] = 'La variable ' . $col . ' est utilisée dans le contenu Email mais elle n\'est pas fournie dans le fichier CSV';
                    }
                }
                if ($class->getIsSms() && strpos($class->getSmsContent(), $col)) {
                    if (!in_array($key, $arrColumn)) {
                        // Erreur
                        $errors['coerance_' . $key] = 'La variable ' . $col . ' est utilisée dans le contenu SMS mais elle n\'est pas fournie dans le fichier CSV';
                    }
                }
            }

            if ($this->isset_notempty($import)) {
                if ($import == 'by_search_fav' && $testMode != 1) {
                    if (!$this->isset_notempty($fRechercheDonateurs) && !$this->isset_notempty($fRechercheDons)) {
                        $errors['by_search_fav'] = 'Veuillez sélectionner une ou plusieurs recherches pour votre campagne';
                    }
                }
            }
            if ($this->isset_notempty($import)) {
                if ($import == 'by_file' && $testMode != 1) {

                    foreach ($requiredColumns as $key => $col) {
                        if (!in_array($col, $arrColumn)) {
                            $errors['colrequired_' . $key] = 'Le champs ' . $col . ' est obligatoire dans la structure du fichier';
                        }
                    }

                    // We check if we have the file
                    if (!$this->isset_notempty($class->getFile())) {
                        $errors['files'] = 'Veuillez spécifier un fichier d\'import';
                    } else {
                        // Vérify the uploaded file
                        $class->setAppFolder($this->container->getParameter('folder_app'));
                        $isValidFile = true;
                        $row = 1;

                        if (($handle = fopen($class->getFile(), "r")) !== FALSE) {
                            while (($data = fgetcsv($handle)) !== FALSE) {
                                $num = count($data);
                                if ($num != $nbColumns) {
                                    $isValidFile = false;
                                }
                                $row++;
                            }
                            fclose($handle);
                        }
                        if (!$isValidFile) {
                            $errors['file_valide'] = 'La structure du fichier uploadé n\'est pas compatible ';
                        } else {
                            $fileNumLines = $row - 1;
                        }
                    }
                } else {
                    if (empty($import) && empty($amount_start) && empty($amount_end) && empty($type_don) && empty($code_activite)) {
                        $errors['criteria'] = 'Il faut au moins un critère pour pouvoir créer le fichier client';
                    }
                }
            }
            if ($this->isset_notempty($periode)) {
                $arrPeriode = explode('à', $periode);
                $brutDateDebut = explode('/', trim($arrPeriode[0]));
                $brutDateFin = explode('/', trim($arrPeriode[1]));
                if (!checkdate($brutDateDebut[1], $brutDateDebut[0], $brutDateDebut[2]) || !checkdate($brutDateFin[1], $brutDateFin[0], $brutDateFin[2])) {
                    $errors['date'] = 'Veuillez fournir un interval de date valide';
                }
            }
            if ($this->isset_notempty($testMode)) {

                // control variables
                if (!$this->isset_notempty($emailTest)) {
                    $errors['email_test'] = 'L\'email est obligatoire pour le mode test';
                }
            }
            // test if form section is ok
            if ($class->getIsEmail()) {
                if (!$this->isset_notempty($class->getObjet())) {
                    $errors['objet'] = 'l\'objet de l\'email est obligatoire';
                }
                if (!$this->isset_notempty($class->getEmailContent())) {
                    $errors['emailContent'] = 'Le contenu de l\'email est obligatoire';
                }
            }
            if ($class->getIsSms()) {
                if (!$this->isset_notempty($class->getSmsContent())) {
                    $errors['smsContent'] = 'Le contenu du SMS est obligatoire';
                }
            }
            if (!$this->isset_notempty($class->getIsEmail()) && !$this->isset_notempty($class->getIsSms())) {
                $errors['send'] = 'Vous devez choisir au moins une méthode d\'envoie';
            }

            $class->setAppFolder($this->container->getParameter('folder_app'));


            if ($form->isValid() && count($errors) == 0) {

                $rsm = new ResultSetMapping();
                $name = uniqid(mt_rand());
                $fileName = $name . '-' . date('Y') . '.csv';
                $fileLocation = '/' . $this->container->getParameter('folder_app') . '/MARKETING/' . $fileName;

                $class->setColumns($columns);
                //Test mode
                if ($testMode == 1) {
                    $lignes[] = array(-1, $civiliteTest, $nomTest, $prenomTest, $telephoneTest, $emailTest, $codeActiviteTest, $okSmsTest, $okEmailTest);
                    $delimiteur = ','; // Pour une tabulation, utiliser $delimiteur = "t";

                    $fichier_csv = fopen($fileLocation, 'wb');
                    //utf8encoding

                    fprintf($fichier_csv, chr(0xEF) . chr(0xBB) . chr(0xBF));

                    foreach ($lignes as $ligne) {
                        // chaque ligne en cours de lecture est insérée dans le fichier
                        // les valeurs présentes dans chaque ligne seront séparées par $delimiteur
                        fputcsv($fichier_csv, $ligne, $delimiteur);
                    }

                    fclose($fichier_csv);
                    $class->setTestMode(true);
                    $class->setFile($fileName);
                    $class->setTotalDonateur(1);
                }
                if ($import == 'by_search_fav' && $testMode != 1) {
                    // remove dupplication
                    $ids = array();
                    $fields = array();
                    $s = microtime(true);
                    $batchSize = 20;
                    $nbdo1 = 0;
                    $nbdo2 = 0;
                    if (count($fRechercheDonateurs) > 0) {
                        foreach ($fRechercheDonateurs as $r) {
                            $favObj = $repFavoris->find($r);
                            if (is_object($favObj)) {
                                $request = Request::create($this->generateUrl('elastic_donateur') . '?' . $favObj->getUrl());
                                $i = 1;
                                do {
                                    $res = $this->container->get('fulldon.intersa.global')->getDonateurResult($request, $i, 1000);
                                    $data[] = $res['result'];
                                    $i++;
                                } while ($i <= $res['nboffset']);
                                $result = array();
                                foreach ($data as $dons) {
                                    foreach ($dons as $don) {
                                        $result[] = $don;
                                    }
                                }
                                $nbdo1 = count($result);
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
                                    if (!in_array($res->getId(), $ids)) {
                                        $fields[] = array($res->getId(), $res->getCivilite(), $res->getNom(), $res->getPrenom(), $res->getTelephoneMobile(), $res->getEmail(), '', $istel, $isemail);
                                    }
                                    $ids[] = $res->getId();
                                    if (($key % $batchSize) == 0) {
                                        $db->flush();
                                        $db->clear();
                                    }
                                }
                            }
                        }
                    }
                    if (count($fRechercheDons) > 0) {
                        foreach ($fRechercheDons as $r) {
                            $favObj = $repFavoris->find($r);
                            if (is_object($favObj)) {
                                $request = Request::create($this->generateUrl('elastic_don') . '?' . $favObj->getUrl());
                                $i = 1;
                                do {
                                    $res = $this->container->get('fulldon.intersa.global')->getDonsResult($request, $i, 1000);
                                    $data[] = $res['result'];
                                    $i++;
                                } while ($i <= $res['nboffset']);
                                $result = array();
                                foreach ($data as $dons) {
                                    foreach ($dons as $don) {
                                        $result[] = $don;
                                    }
                                }
                                $nbdo2 = count($result);
                                foreach ($result as $key => $res) {
                                    $istel = 0;
                                    $isemail = 0;
                                    $donateur = $repDonateur->findOneBy(array('user' => $res->getUser()));
                                    foreach ($donateur->getReceptionMode() as $rm) {
                                        if ($rm->getCode() == 'sms') {
                                            $istel = 1;
                                        }
                                        if ($rm->getCode() == 'email') {
                                            $isemail = 1;
                                        }
                                    }
                                    if (!in_array($donateur->getId(), $ids)) {
                                        $fields[] = array($donateur->getId(), $donateur->getCivilite(), $donateur->getNom(), $donateur->getPrenom(), $donateur->getTelephoneMobile(), $donateur->getEmail(), '', $istel, $isemail);
                                    }
                                    $ids[] = $res->getId();
                                    if (($key % $batchSize) == 0) {
                                        $db->flush();
                                        $db->clear();
                                    }
                                }
                            }
                        }
                    }
                    $fp = fopen($fileLocation, 'wb');
                    //utf8encoding
                    fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    $class->setTotalDonateur($nbdo1 + $nbdo2);
                    foreach ($fields as $field) {
                        fputcsv($fp, $field);
                    }
                    fclose($fp);
                    $class->setTotalDonateur(count($fields));
                    $class->setFile($fileName);
                }
                if ($import == 'by_form' && $testMode != 1) {
                    $ids = array();
                    //Create file
                    $rsm->addScalarResult('id', 'id');
                    $rsm->addScalarResult('civilite', 'civilite');
                    $rsm->addScalarResult('nom', 'nom');
                    $rsm->addScalarResult('prenom', 'prenom');
                    $rsm->addScalarResult('telephone', 'telephone');
                    $rsm->addScalarResult('email', 'email');
                    $rsm->addScalarResult('code', 'code');
                    $rsm->addScalarResult('istel', 'istel');
                    $rsm->addScalarResult('isemail', 'isemail');


                    $ext_sql = array();

                    $sql = "SELECT d.id, d.civilite, d.nom, d.prenom , d.telephone_mobile as telephone, d.email, c.code,
                    CASE WHEN 'sms' IN
                    (Select srm.code from reception_mode as srm INNER JOIN donateur_receptionmode as sdrm ON sdrm.receptionmode_id = srm.id WHERE sdrm.donateur_id = d.id) THEN 1
                    ELSE 0 END as istel,
                    CASE WHEN 'email' IN
                    (Select srm.code from reception_mode as srm INNER JOIN donateur_receptionmode as sdrm ON sdrm.receptionmode_id = srm.id WHERE sdrm.donateur_id = d.id) THEN 1
                    ELSE 0 END AS isemail
                    FROM donateur as d
                    INNER JOIN User as u ON d.user_id = u.id
                    INNER JOIN don as don ON don.user_id = u.id
                    LEFT JOIN cause as c ON c.id = don.cause_id
                    ";

                    if ($this->isset_notempty($periode)) {
                        $arrPeriode = explode('à', $periode);
                        $dateDebut = implode('-', array_reverse($brutDateDebut));
                        $dateFin = implode('-', array_reverse($brutDateFin));
                        $ext_sql[] = " don.created_at >= '" . $dateDebut . " 00:00:00' and  don.created_at <= '$dateFin 23:59:59' ";
                    }
                    if ($this->isset_notempty($type_donateur) && is_numeric($type_donateur)) {
                        $ext_sql[] = " d.categorie_id = " . $type_donateur . " ";
                    }
                    if ($this->isset_notempty($amount_start) || is_numeric($amount_start)) {

                        $ext_sql[] = " don.montant >= " . $amount_start . " ";
                    }
                    if ($this->isset_notempty($amount_end) || is_numeric($amount_end)) {

                        $ext_sql[] = " don.montant <= " . $amount_end . " ";
                    }
                    if ($this->isset_notempty($type_don)) {
                        if ($type_don == 'regulier') {
                            $ext_sql[] = " don.ispa = 1 ";
                        } else {
                            $ext_sql[] = " don.ispa = 0 ";
                        }
                    }
                    if ($this->isset_notempty($code_activite)) {
                        $ext_sql[] = " c.code = '" . $code_activite . "' ";
                    }

                    $sql = $sql . ' WHERE ' . implode('AND', $ext_sql) . ' GROUP By d.id';

                    $query = $db->createNativeQuery($sql, $rsm);
                    $result = $query->getResult();

                    $fp = fopen($fileLocation, 'wb');
                    //utf8encoding
                    fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    $class->setTotalDonateur(count($result));
                    foreach ($result as $fields) {
                        if (!in_array($fields['id'], $ids)) {
                            fputcsv($fp, $fields);
                        }
                        $ids[] = $fields['id'];
                    }

                    fclose($fp);
                    $class->setFile($fileName);
                } elseif ($testMode != 1 && $import == 'by_file') {
                    $class->setTotalDonateur($fileNumLines);
                }

                // We should use the helper instead of persist method
                $this->get('fulldon.intersa.global')->updateCronTask($class);
                $db->flush();
                $this->get('session')->getFlashBag()->add('info', 'La campagne marketing a été enregistrée, elle sera traitée très bientôt');
                return $this->redirect($this->generateUrl('intersa_marketing', array('page' => 1)));
            } elseif (count($errors) > 0) {
                foreach ($errors as $key => $error) {
                    $this->get('session')->getFlashBag()->add('error', $error);
                    $display_error = true;
                }
            }
        }
        return $this->render('FulldonIntersaBundle:Marketing:new_campagne.html.twig', array(
                    'form' => $form->createView(),
                    'display_error' => $display_error,
                    'cats' => $categories_doanteurs,
                    'class' => $class, 'favoris_dons' => $favorisDons,
                    'favoris_donateurs' => $favorisDonateurs,
                    'formBiblio' => $formBiblio->createView(),
                    'images' => $images
        ));
    }

    private function isset_notempty($val) {
        return isset($val) && !empty($val);
    }

    public function getStatsAction($tag) {
//        var_dump($tag);
//        die('getStatsAction');
        $result = null;

        $mandrill = new \Mandrill($this->container->getParameter('slot_mandrill.api_key'));
        $tag2 = 'coline-tag-emarketing-41';
//            $result2 = $mandrill->tags->timeSeries($tag2);
        $result = $mandrill->tags->allTimeSeries();
//            $result = $mandrill->tags->getList();
//        var_dump($result);
//        die('$result2');


        try {

            
//        $tag='coline-tag-emarketing-5';
//        var_dump($mandrill->tags->info($tag));
//            $result = $mandrill->tags->info($tag);
//        var_dump($result);
//        die('$result');
            if ($tag == 'coline-tag-emarketing-5') {
                $result = $result['stats']['last_7_days'];
            }

//            $mandrill = new \Mandrill($this->container->getParameter('hip_mandrill.api_key'));
        } catch (\Mandrill_Error $e) {
            // Mandrill errors are thrown as exceptions
            //echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            $this->get('session')->getFlashBag()->add('warn', 'La mise à jour des statistiques de la campagne ne sont pas disponible actuellement, veuillez réssayer ultérieurement.');
        }

        return $this->render('FulldonIntersaBundle:Marketing:stats.html.twig', array(
                    'result' => $result,
        ));
    }

    public function ajaxMajContactsAction() {
        $result = null;
        $count = 0;
        try {

//            $mandrill = new \Mandrill($this->container->getParameter('hip_mandrill.api_key'));
            $mandrill = new \Mandrill($this->container->getParameter('slot_mandrill.api_key'));
//            $result = $mandrill->rejects->getList(null, true, $this->container->getParameter('hip_mandrill.default.subaccount'));
            $result = $mandrill->rejects->getList(null, true, $this->container->getParameter('slot_mandrill.default.subaccount'));
            $count = count($result);
        } catch (\Mandrill_Error $e) {
            // Mandrill errors are thrown as exceptions
            //echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            $this->get('session')->getFlashBag()->add('warn', 'La mise à jour des statistiques de la campagne ne sont pas disponible actuellement, veuillez réssayer ultérieurement.');
        }
        return $this->render('FulldonIntersaBundle:Marketing/ajax:sync_rejects.html.twig', array(
                    'count' => $count,
        ));
    }

    public function majContactsAction() {
        $result = null;
        $count = 0;
        $em = $this->getDoctrine()->getManager();
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');
        $repRm = $em->getRepository('FulldonDonateurBundle:ReceptionMode');
        $emailMode = $repRm->findOneBy(array('code' => 'email'));
        $subaccount = $this->container->getParameter('hip_mandrill.default.subaccount');
        try {

            $mandrill = new \Mandrill($this->container->getParameter('hip_mandrill.api_key'));
            $result = $mandrill->rejects->getList(null, true, $subaccount);
            foreach ($result as $rs) {
                $donateur = $repDonateur->findOneBy(array('email' => $rs['email']));
                if (is_object($donateur)) {
                    $donateur->removeReceptionMode($emailMode);
                    $em->flush();
                    // supprimer l'email de la blacklist.
                    $mandrill->rejects->delete($rs['email'], $subaccount);
                }
            }
        } catch (\Mandrill_Error $e) {
            // Mandrill errors are thrown as exceptions
            //echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            $this->get('session')->getFlashBag()->add('warning', 'Une erreur est survenue lors de la mise à jour');
            return $this->redirect($this->generateUrl('intersa_marketing', array('page' => 1)));
        }
        $this->get('session')->getFlashBag()->add('info', 'Mise à jour effectuée avec succès.');
        return $this->redirect($this->generateUrl('intersa_marketing', array('page' => 1)));
    }

    public function disableComAction($id) {
        $em = $this->getDoctrine()->getManager();
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $repDonateur->find($id);
        $repRm = $em->getRepository('FulldonDonateurBundle:ReceptionMode');
        $emailMode = $repRm->findOneBy(array('code' => 'email'));
        $subaccount = $this->container->getParameter('hip_mandrill.default.subaccount');
        try {

            $mandrill = new \Mandrill($this->container->getParameter('hip_mandrill.api_key'));
            if (is_object($donateur)) {
                $modes = array();

                $donateur->removeReceptionMode($emailMode);
                $em->flush();
                // supprimer l'email de la blacklist.
                $mandrill->rejects->delete($donateur->getEmail(), $subaccount);
            }
        } catch (\Mandrill_Error $e) {
            $this->get('session')->getFlashBag()->add('warning', 'Une erreur est survenue lors de la mise à jour du donateur');
            return $this->redirect($this->generateUrl('intersa_marketing_rejects_emailing', array('page' => 1)));
        }

        $this->get('session')->getFlashBag()->add('info', 'La mise à jour du donateur est effectuée avec succès.');
        return $this->redirect($this->generateUrl('intersa_marketing_rejects_emailing', array('page' => 1)));
    }

    public function ajaxDisableComAction($id) {
        $em = $this->getDoctrine()->getManager();
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');
        $donateur = $repDonateur->find($id);
        return $this->render('FulldonIntersaBundle:Marketing/ajax:disable_com.html.twig', array(
                    'donateur' => $donateur,
        ));
    }

    public function listRejectsAction($page) {
        $result = null;
        $count = 0;
        $em = $this->getDoctrine()->getManager();
        $repDonateur = $em->getRepository('FulldonDonateurBundle:Donateur');
        $repRm = $em->getRepository('FulldonDonateurBundle:ReceptionMode');
        $emailMode = $repRm->findOneBy(array('code' => 'email'));
        $subaccount = $this->container->getParameter('hip_mandrill.default.subaccount');
        $donateurs = array();
        try {

            $mandrill = new \Mandrill($this->container->getParameter('hip_mandrill.api_key'));
            $result = $mandrill->rejects->getList(null, true, $subaccount);
            foreach ($result as $rs) {
                $donateur = $repDonateur->findOneBy(array('email' => $rs['email']));
                if (is_object($donateur)) {
                    foreach ($donateur->getReceptionMode() as $mode) {
                        $modes[] = $mode->getCode();
                    }
                    if (in_array('email', $modes)) {
                        $donateurs[] = $donateur;
                    }
                }
            }
        } catch (\Mandrill_Error $e) {
            // Mandrill errors are thrown as exceptions
            //echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            $this->get('session')->getFlashBag()->add('warning', 'Une erreur est survenue lors de la mise à jour');
            return $this->redirect($this->generateUrl('intersa_marketing', array('page' => 1)));
        }
        $total_elements = count($donateurs);
        $elements_per_page = $this->get('fulldon.custom_params')->getParam('max_articles_on_listepage');
        $last_page = ceil($total_elements / $elements_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
        $start = ($page * $elements_per_page) - $elements_per_page;
        $elements = array_slice($donateurs, $start, $elements_per_page);

        $cumul = 0.00;
        $date = date("Y-m-d H:i:s");

        return $this->render('FulldonIntersaBundle:Marketing:list_rejects.html.twig', array(
                    'last_page' => $last_page,
                    'previous_page' => $previous_page,
                    'current_page' => $page,
                    'next_page' => $next_page,
                    'total_elements' => $total_elements,
                    'elements' => $elements,
                    'cumul' => $cumul,
                    'date' => $date
        ));
    }

    public function newImageBiblioAction() {
        $request = Request::createFromGlobals();
        $em = $this->getDoctrine()->getManager();


        $biblioimg = new BiblioImage();
        $formBiblio = $this->createForm(new newPicType(), $biblioimg);
        $response = new Response('start');
        if ($request->getMethod() == 'POST') {
            $formBiblio->bind($request);
            if ($formBiblio->isValid()) {
                $em->persist($biblioimg);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', 'Upload effectué avec succès ');
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'Une erreur est survenue : veuillez SVP uploader un fichier valide');
            }
        }
        $repBiblioImg = $em->getRepository('FulldonIntersaBundle:BiblioImage');

        $images = $repBiblioImg->findBy(array(), array('id' => 'desc'));
        return $this->render('FulldonIntersaBundle:Marketing/ajax:biblio_images.html.twig', array(
                    'images' => $images,
        ));
    }

    public function removeImageBiblioAction($id) {
        $db = $this->getDoctrine()->getManager();
        $repBiblio = $db->getRepository('FulldonIntersaBundle:BiblioImage');
        $img = $repBiblio->find($id);

        if ($id > 0) {
            if (!is_null($img)) {
                try {
                    $db->remove($img);
                    $db->flush();

                    // On définit un message flash
                    $this->get('session')->getFlashBag()->add('success', 'L\'image a été supprimée avec succès !');
                } catch (\Doctrine\DBAL\DBALException $e) {
                    $this->get('session')->getFlashBag()->add('erreur', 'La suppression de l\'image  est intérdite !');
                }
            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'L\'image n\'existe pas');
            }
        }
        $images = $repBiblio->findBy(array(), array('id' => 'desc'));
        return $this->render('FulldonIntersaBundle:Marketing/ajax:biblio_images.html.twig', array(
                    'images' => $images,
        ));
    }

}
