<?php

namespace MauticPlugin\MauticUazapiBundle\Callback;

use Mautic\SmsBundle\Callback\CallbackInterface;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Mautic\SmsBundle\Helper\ContactHelper;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use MauticPlugin\MauticUazapiBundle\Config\UazapiConfiguration;

class UazapiCallback implements CallbackInterface
{
    public function __construct(
        private ContactHelper $contactHelper,
        private UazapiConfiguration $configuration,
    ) {
    }

    public function getTransportName(): string
    {
        return 'Uazapi';
    }

    /**
     * @throws NumberNotFoundException
     */
    public function getContacts(Request $request): \Doctrine\Common\Collections\ArrayCollection
    {
        $this->validateRequest($request->request);

        $number = $request->get('From');

        return $this->contactHelper->findContactsByNumber($number);
    }

    public function getMessage(Request $request): string
    {
        $this->validateRequest($request->request);

        return trim($request->get('Body'));
    }

    /**
     * @param InputBag<bool|float|int|string> $request
     */
    private function validateRequest(InputBag $request): void
    {
        try {
            $accountSid = $this->configuration->getAccountSid();
        } catch (Exception $e) {
            // Not published or not configured
            throw new Exception('Account SID not configured.');
        }

        // Validate this is a request from Twilio
        if ($accountSid !== $request->get('AccountSid')) {
            throw new Exception('Invalid Account SID.');
        }

        // Who is the message from?
        $number = $request->get('From');
        if (empty($number)) {
            throw new Exception('From number not found.');
        }

        // What did they say?
        $message = trim($request->get('Body'));
        if (empty($message)) {
            throw new Exception('Message body not found.');
        }
    }
}
