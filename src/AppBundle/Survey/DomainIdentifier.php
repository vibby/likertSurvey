<?php

namespace AppBundle\Survey;

use Symfony\Component\HttpFoundation\RequestStack;

class DomainIdentifier
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getIdentifier()
    {
        $request = $this->requestStack->getCurrentRequest();

        return sprintf(
            '%s/%s',
            $request->getHost(),
            explode(
                '/',
                trim($request->getRequestUri(), '/')
            )[0]
        );
    }
}
