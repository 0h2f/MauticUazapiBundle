<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use MauticPlugin\MauticUazapiBundle\Transport\UazapiTransport;
use MauticPlugin\MauticUazapiBundle\Config\UazapiConfiguration;
use MauticPlugin\MauticUazapiBundle\Integration\UazapiIntegration;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
    ];

    // Manual service definitions for critical components
    $services->set(UazapiTransport::class)
        ->arg('$configuration', service(UazapiConfiguration::class))
        ->arg('$logger', service('monolog.logger.mautic'))
        ->arg('$integration', service(UazapiIntegration::class))
        ->tag('mautic.sms_transport', ['integrationAlias' => 'Uazapi'])
        ->alias('sms_api', UazapiTransport::class)
        ->alias('mautic.sms.api', UazapiTransport::class);

    $services->load('MauticPlugin\\MauticUazapiBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
};
