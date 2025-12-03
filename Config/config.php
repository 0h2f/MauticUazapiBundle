<?php

return [
    'name'        => 'Uazapi',
    'description' => 'Enables integrations with Uazapi for whatsapp messages sending',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'services'    => [
	    'other' => [
            'mautic.sms.uazapi.configuration' => [
                'class'        => MauticPlugin\MauticUazapiBundle\Config\UazapiConfiguration::class,
                'arguments'    => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.sms.uazapi.transport' => [
                'class'        => MauticPlugin\MauticUazapiBundle\Transport\UazapiTransport::class,
                'arguments'    => [
                    'mautic.sms.uazapi.configuration',
		            'monolog.logger.mautic',
                ],
                'tag'          => 'mautic.sms_transport',
                'tagArguments' => [
			'integrationAlias' => 'Uazapi',
                ],
                'serviceAliases' => [
		    'sms_api',
                    'mautic.sms.api',
		],
            ],
	],
        'integrations' => [
            'mautic.integration.uazapi' => [
                'class'     => MauticPlugin\MauticUazapiBundle\Integration\UazapiIntegration::class,
		'tags' => [
			'mautic.integration',
			'mautic.config_integration'
		],
		'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
	    ],
        
    ],
    'menu' => [
        'main' => [
            'items' => [
		    'mautic.sms.smses' => [
			    'route' => 'mautic_sms_index',
			    'access' => ['sms:smses:viewown', 'sms:smses:viewother'],
			    'parent' => 'mautic.core.channels',
			    'checks' => [
				    'integration' => [
					    'Uazapi' => [
						    'enabled' => true,
					    ],
                    ],
                ],
                'priority' => 70,
            ],
        ],
    ],
    ],
    'parameters' => [
        'sms_enabled'                                                      => false,
        'api_key'       	                                               => null,
        'api_url' 	                                                       => null,
        'sms_transport'                                                    => 'mautic.sms.uazapi.transport',
        Mautic\SmsBundle\Form\Type\ConfigType::SMS_DISABLE_TRACKABLE_URLS  => false,
    ],
 ];
