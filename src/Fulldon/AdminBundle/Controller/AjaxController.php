<?php

namespace Fulldon\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AjaxController extends Controller
{
    public function deleteUserAction($id)
    {
        $db = $this->getDoctrine()->getManager();
        $repUsers = $db->getRepository('FulldonSecurityBundle:User') ;
        $user = $repUsers->find($id);

        return $this->render('FulldonAdminBundle:Admin/Ajax:deleteuser.html.twig', array('user' => $user));
    }

}
