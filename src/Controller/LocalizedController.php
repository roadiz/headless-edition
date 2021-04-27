<?php
declare(strict_types=1);

namespace App\Controller;

use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractApiTheme\Subscriber\LinkedApiResponseSubscriber;

trait LocalizedController
{
    abstract protected function getUrlGenerator();
    abstract protected function getTranslationRepository();

    protected function getTranslation(string $locale): Translation
    {
        /** @var Translation|null $translation */
        $translation = $this->getTranslationRepository()->findOneBy([
            'locale' => $locale
        ]);
        if (null === $translation) {
            throw new NotFoundHttpException('No translation for locale ' . $locale);
        }
        return $translation;
    }

    /**
     * @param Request $request
     */
    protected function injectAlternateHrefLangLinks(Request $request): void
    {
        if ($request->attributes->has('_route')) {
            $availableLocales = $this->getTranslationRepository()
                ->getAvailableLocales();
            if (count($availableLocales) > 1 && !$request->query->has('path')) {
                $links = [];
                foreach ($availableLocales as $availableLocale) {
                    $linksParams = [
                        sprintf('<%s>', $this->getUrlGenerator()->generate(
                            $request->attributes->get('_route'),
                            array_merge(
                                $request->query->all(),
                                $request->attributes->get('_route_params'),
                                [
                                    '_locale' => $availableLocale
                                ]
                            ),
                            UrlGeneratorInterface::ABSOLUTE_URL
                        )),
                        'rel="alternate"',
                        'hreflang="'.$availableLocale.'"',
                        'type="application/json"'
                    ];
                    $links[] = implode('; ', $linksParams);
                }
                $request->attributes->set(LinkedApiResponseSubscriber::LINKED_RESOURCES_ATTRIBUTE, $links);
            }
        }
    }
}
