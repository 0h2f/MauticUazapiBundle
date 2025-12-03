<?php

namespace MauticPlugin\MauticUazapiBundle\Config;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class UazapiConfiguration
{
    private ?string $api_url = null;
	
    public function __construct(
        private IntegrationHelper $integrationHelper,
    ) {
    }

    public function getApiUrl(): string
    {
        $this->setConfiguration();

        return $this->api_url;
    }

    private function setConfiguration(): void
    {
        if (isset($this->api_url)) {
            return;
        }

        $integration = $this->integrationHelper->getIntegrationObject('Uazapi');
        if (!$integration || !$integration->getIntegrationSettings()->getIsPublished()) {
            throw new \RuntimeException('Integration not published.');
        }

        $keys = $integration->getDecryptedApiKeys();

        $this->api_url = $keys['api_url'];
        if (empty($this->api_url)) {
            throw new \RuntimeException('API URL not configured.');
        }
    }
}
