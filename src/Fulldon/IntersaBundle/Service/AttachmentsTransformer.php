<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\IntersaBundle\Service;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\DataTransformerInterface;

class AttachmentsTransformer extends ContainerAware implements DataTransformerInterface
{
    public function transform($value)
    {
        // TODO: Implement transform() method.
    }

    public function reverseTransform($files)
    {
        $attachments = null;
        global $kernel;
        foreach($files as $file){
            if(!empty($file)) {
                $attachments .= file_get_contents($file, FILE_USE_INCLUDE_PATH);
            }
        }
        if(!empty($attachments)) {
            $returnedFile = $files[0];
            $filename = sha1(uniqid(mt_rand(), true));
            $filename = $filename.'.csv';
            $returnedFile = $returnedFile->move('/'.$kernel->getContainer()->getParameter('folder_app').'/MARKETING/', $filename);
            //utf8encoding
            file_put_contents('/'.$kernel->getContainer()->getParameter('folder_app').'/MARKETING/'.$filename, $attachments);
            return $returnedFile;
        } else {
            return null;
        }



    }
}