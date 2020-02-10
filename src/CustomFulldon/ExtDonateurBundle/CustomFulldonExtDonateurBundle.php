<?php

namespace CustomFulldon\ExtDonateurBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CustomFulldonExtDonateurBundle extends Bundle
{
    public function getParent()
    {
        return 'FulldonDonateurBundle';
    }

}
