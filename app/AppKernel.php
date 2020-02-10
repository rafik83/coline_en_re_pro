<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel {

    public function registerBundles() {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Fulldon\DonateurBundle\FulldonDonateurBundle(),
            new Fulldon\IntersaBundle\FulldonIntersaBundle(),
            new Fulldon\AdminBundle\FulldonAdminBundle(),
            new Fulldon\SecurityBundle\FulldonSecurityBundle(),


            new Knp\Bundle\SnappyBundle\KnpSnappyBundle(),
            new Liuggio\ExcelBundle\LiuggioExcelBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\I18nRoutingBundle\JMSI18nRoutingBundle(),
            new JMS\TranslationBundle\JMSTranslationBundle(),
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
            new JMS\Payment\CoreBundle\JMSPaymentCoreBundle(),
            new ETS\Payment\OgoneBundle\ETSPaymentOgoneBundle(),
            new CustomFulldon\ExtDonateurBundle\CustomFulldonExtDonateurBundle(),
            new CustomFulldon\ExtIntersaBundle\CustomFulldonExtIntersaBundle(),
//            new Hip\MandrillBundle\HipMandrillBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\ElasticaBundle\FOSElasticaBundle(),
//            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new Slot\MandrillBundle\SlotMandrillBundle(),
            
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Webfactory\Bundle\ExceptionsBundle\WebfactoryExceptionsBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader) {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
    }

}
