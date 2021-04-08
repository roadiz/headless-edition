<?php
declare(strict_types=1);

namespace App\Controller;

use RZ\Roadiz\Utils\ContactFormManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class ContactFormController
{
    private ContactFormManager $contactFormManager;
    private RateLimiterFactory $rateLimiterFactory;

    /**
     * @param ContactFormManager $contactFormManager
     * @param RateLimiterFactory $rateLimiterFactory
     */
    public function __construct(
        ContactFormManager $contactFormManager,
        RateLimiterFactory $rateLimiterFactory
    ) {
        $this->contactFormManager = $contactFormManager;
        $this->rateLimiterFactory = $rateLimiterFactory;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function formAction(Request $request): Response
    {
        // create a limiter based on a unique identifier of the client
        // (e.g. the client's IP address, a username/email, an API key, etc.)
        $limiter = $this->rateLimiterFactory->create($request->getClientIp());
        // only claims 1 token if it's free at this moment (useful if you plan to skip this process)
        $limit = $limiter->consume();
        $headers = [
            'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
            'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp(),
            'X-RateLimit-Limit' => $limit->getLimit(),
        ];
        // the argument of consume() is the number of tokens to consume
        // and returns an object of type Limit
        if (false === $limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp());
        }

        // Do not forget to disable CSRF and form-name
        $this->contactFormManager
            ->setUseRealResponseCode(true)
            ->setFormName('')
            ->disableCsrfProtection();
        /*
         * Do not call form builder methods BEFORE defining options.
         */
        $this->contactFormManager
            ->withDefaultFields()
            ->withUserConsent()
            ->withGoogleRecaptcha()
        ;

        if (null !== $response = $this->contactFormManager->handle()) {
            $response->headers->add($headers);
            return $response;
        }
        throw new BadRequestHttpException('Form has not been submitted.');
    }
}
