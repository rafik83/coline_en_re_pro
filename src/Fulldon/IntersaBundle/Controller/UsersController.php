<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulldon\IntersaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fulldon\SecurityBundle\Entity\User;
use Fulldon\SecurityBundle\Form\UserEditType;
use Fulldon\SecurityBundle\Form\UserType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @PreAuthorize(" hasRole('ROLE_INTERSA_N1') or hasRole('ROLE_ASSOC_N1') ")
 */

class UsersController extends Controller
{
    public function indexAction()
    {
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User') ;
        $repRoles = $db->getRepository('FulldonSecurityBundle:Role');
        //Extract all admin users
        $users = $repUsers->createQueryBuilder('u')
            ->join('u.roles','r')
            ->where("r.role not in(:role) ")
            ->setParameter('role','ROLE_DONATEUR')
            ->getQuery()->getResult();
        //Extract all roles
        $roles = $repRoles->findAll();
        return $this->render('FulldonIntersaBundle:Users:accueil.html.twig', array('users' => $users, 'roles' => $roles));
    }

    public function newUserAction()
    {
        $user = new User;
        $db = $this->getDoctrine()->getManager();
        // On crée le formulaire grâce à l'ArticleType
        $form = $this->createForm(new UserType(), $user);

        // On récupère la requête
        $request = $this->getRequest();

        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {
            // On fait le lien Requête <-> Formulaire
            $form->bind($request);

            // On vérifie que les valeurs entrées sont correctes
            if ($form->isValid()) {

                if(!$this->isNewUser($user)) {
                    $this->get('session')->getFlashBag()->add('erreur', ' Le login de l\'utilisateur existe déjà sur notre base ');
                    $this->get('session')->getFlashBag()->add('cookie_user', $user);
                    return $this->redirect($this->generateUrl('intersa_users_new'));
                }
                // On enregistre notre objet $article dans la base de données
                $user->setSalt(uniqid(mt_rand())); // Unique salt for user

                // Set encrypted password
                $encoder = $this->container->get('security.encoder_factory')
                    ->getEncoder($user);
                $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                $user->setPassword($password);
                $db->persist($user);
                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Création de l\'utilisateur avec succès ');

                // On redirige vers la page de visualisation de l'article nouvellement créé
                return $this->redirect($this->generateUrl('intersa_users'));
            }
        }

        return $this->render('FulldonIntersaBundle:Users:new.html.twig', array('form' => $form->createView()));
    }
    public function editUserAction($id)
    {

        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User') ;
        $repRoles = $db->getRepository('FulldonSecurityBundle:Role');
        $roles = $repRoles->findAll();
        //Extract the chosen user
        $user = $repUsers->find($id);

        $form = $this->createForm(new UserEditType(), $user);

        $request = $this->getRequest();
        $oldPassword = $user->getPassword();
        if ($request->getMethod() == 'POST') {
            $form->bind($request);

            if ($form->isValid()) {
                $password = $user->getPassword();
                if( !empty($password)) {

                    $user->setSalt(uniqid(mt_rand())); // Unique salt for user

                    // Set encrypted password
                    // the service name in the get parameter
                    $encoder = $this->container->get('security.encoder_factory')
                        ->getEncoder($user);
                    $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                    $user->setPassword($password);
                } else {
                    $user->setPassword($oldPassword);
                }
                $db->persist($user);
                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'L\'utilisateur '.$user->getUsername().' a été bien modifié');

                return $this->redirect($this->generateUrl('intersa_users'));
            }
        }
        return $this->render('FulldonIntersaBundle:Users:edit.html.twig', array('form' => $form->createView(), 'id'=> $id));
    }

    public function deleteUserAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User') ;
        $user = $repUsers->find($id);

        if ($id > 0) {
            if(!is_null($user)) {

                $db = $this->getDoctrine()->getManager();
                $db->remove($user);
                $db->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'L\'utilisateur '.$user->getUsername().' est supprimé');

            } else {
                $this->get('session')->getFlashBag()->add('erreur', 'L\'utilisateur n\'existe pas');
            }
        }


        // Puis on redirige vers l'accueil
        return $this->redirect($this->generateUrl('intersa_users'));
    }
    public function isNewUser(User $user)
    {
        // Check if it exists first
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User') ;
        $query = $repUsers->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->where("u.username = :username ")
            ->setParameter('username',$user->getUsername())
            ->getQuery();

        if ($query->getSingleScalarResult()>0)
        {
            return false;
        }

        return true;
    }
    public function deleteUserAjaxAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User') ;
        $user = $repUsers->find($id);

        return $this->render('FulldonIntersaBundle:Users/Ajax:deleteuser.html.twig', array('user' => $user));
    }
}
