<?php

namespace CustomFulldon\ExtIntersaBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CustomFulldonExtIntersaBundle extends Bundle
{
    public function getParent()
    {
        return 'FulldonIntersaBundle';
    }
}
