<?php
namespace Fulldon\SecurityBundle\Service;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class Sha256Salted implements PasswordEncoderInterface
{

    public function encodePassword($raw, $salt)
    {
        $raw = trim($raw);
        return hash('sha256', $salt . $raw); // Custom function for encrypt
    }

    public function isPasswordValid($encoded, $raw, $salt)
    {
        $raw = trim($raw);
        return $encoded === $this->encodePassword($raw, $salt);
    }

}