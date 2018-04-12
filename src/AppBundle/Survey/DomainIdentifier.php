<?php

namespace AppBundle\Survey;

use Symfony\Component\HttpFoundation\RequestStack;

class DomainIdentifier
{
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getIdentifier()
    {

        return sprintf(
            '%s/%s',
            $this->request->getHost(),
            $this->getFolder()
        );
    }

    public function getFolder()
    {
        return  explode(
            '/',
            trim($this->request->getRequestUri(), '/')
        )[0];
    }
}
