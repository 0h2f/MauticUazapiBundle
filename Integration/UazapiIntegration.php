<?php

namespace MauticPlugin\MauticUazapiBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;

class UazapiIntegration extends AbstractIntegration implements ConfigFormInterface
{
    use ConfigurationTrait;
    use DefaultConfigFormTrait;

    public function getName(): string
    {
        return 'Uazapi';
    }
    
    public function getDisplayName(): string
    {
	return 'Uazapi API';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticUazapiBundle/Assets/img/Uazapi.png';
    }

    public function getSecretKeys(): array
    {
        return ['api_key'];
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'api_url' => 'mautic.plugin.uazapi.api_url'
        ];
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }

   public function getSupportedFeatures(): array
   {
       return ['sms'];
   }
    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
    }
}

