<?php
namespace Fulldon\DonateurBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Templating\EngineInterface;

class SocialBarHelper extends Helper
{
    protected $templating;

    public function __construct(EngineInterface $templating)
    {
        $this->templating  = $templating;
    }


    public function socialButtons($parameters)
    {
        return $this->templating->render('FulldonDonateurBundle:helper:socialButtons.html.twig', $parameters);
    }

    public function facebookButton($parameters)
    {
        return $this->templating->render('FulldonDonateurBundle:helper:facebookButton.html.twig', $parameters);
    }

    public function twitterButton($parameters)
    {
        return $this->templating->render('FulldonDonateurBundle:helper:twitterButton.html.twig', $parameters);
    }

    public function googlePlusButton($parameters)
    {
        return $this->templating->render('FulldonDonateurBundle:helper:googlePlusButton.html.twig', $parameters);
    }

    public function getName()
    {
        return 'socialButtons';
    }
}