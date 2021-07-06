<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\CommonContentResponse;
use App\Model\NodesSourcesHeadFactory;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\TreeWalker\WalkerContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;
use Themes\AbstractApiTheme\Controllers\LocalizedController;
use Themes\AbstractApiTheme\Serialization\Exclusion\PropertiesExclusionStrategy;
use Themes\AbstractApiTheme\Serialization\SerializationContextFactory;
use Themes\AbstractApiTheme\Subscriber\CachableApiResponseSubscriber;

final class CommonContentController
{
    use LocalizedController;

    private Serializer $serializer;
    private EntityManagerInterface $entityManager;
    private SerializationContextFactory $serializationContextFactory;
    private WalkerContextInterface $walkerContext;
    private CacheProvider $cacheProvider;
    private NodeSourceApi $nodeSourceApi;
    private UrlGeneratorInterface $urlGenerator;
    private NodesSourcesHeadFactory $headFactory;

    /**
     * @param Serializer $serializer
     * @param EntityManagerInterface $entityManager
     * @param SerializationContextFactory $serializationContextFactory
     * @param NodeSourceApi $nodeSourceApi
     * @param WalkerContextInterface $walkerContext
     * @param CacheProvider $cacheProvider
     * @param UrlGeneratorInterface $urlGenerator
     * @param NodesSourcesHeadFactory $headFactory
     */
    public function __construct(
        Serializer $serializer,
        EntityManagerInterface $entityManager,
        SerializationContextFactory $serializationContextFactory,
        NodeSourceApi $nodeSourceApi,
        WalkerContextInterface $walkerContext,
        CacheProvider $cacheProvider,
        UrlGeneratorInterface $urlGenerator,
        NodesSourcesHeadFactory $headFactory
    ) {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->serializationContextFactory = $serializationContextFactory;
        $this->walkerContext = $walkerContext;
        $this->cacheProvider = $cacheProvider;
        $this->nodeSourceApi = $nodeSourceApi;
        $this->urlGenerator = $urlGenerator;
        $this->headFactory = $headFactory;
    }

    /**
     * @param Translation $translation
     * @param array $properties
     * @return SerializationContext
     */
    protected function getSerializationContext(Translation $translation, array $properties = []): SerializationContext
    {
        $context = $this->serializationContextFactory->create()
            ->enableMaxDepthChecks()
            ->setGroups([
                'nodes_sources_base',
                'document_display',
                'nodes_sources_default',
                'nodes_sources_lien',
                'urls',
                'meta',
                'walker',
                'children',
            ])
            ->setAttribute('translation', $translation);

        if (count($properties) > 0) {
            $context->addExclusionStrategy(new PropertiesExclusionStrategy(
                $properties,
                []
            ));
        }

        return $context;
    }

    protected function getTranslationRepository(): TranslationRepository
    {
        /** @var TranslationRepository */
        return $this->entityManager->getRepository(Translation::class);
    }

    protected function getDefaultLocale(): string
    {
        return 'fr';
    }

    /**
     * @return UrlGeneratorInterface
     */
    public function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urlGenerator;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function defaultAction(Request $request): JsonResponse
    {
        $translation = $this->getTranslationFromLocaleOrRequest($request, $request->query->get('_locale'));

        $menuResponse = new CommonContentResponse(
            $this->nodeSourceApi,
            $translation,
            $this->walkerContext,
            $this->cacheProvider,
            $this->headFactory->createForTranslation($translation)
        );
        $context = $this->getSerializationContext($translation, $request->query->get('properties', []));

        $response = new JsonResponse(
            $this->serializer->serialize(
                $menuResponse,
                'json',
                $context
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );

        $response->setEtag(md5($response->getContent() ?: ''));
        /*
         * Returns a 304 if request Etag matches response's
         */
        if ($response->isNotModified($request)) {
            return $response;
        }

        if ($context->hasAttribute('cache-tags') &&
            $context->getAttribute('cache-tags') instanceof CacheTagsCollection) {
            /** @var CacheTagsCollection $cacheTags */
            $cacheTags = $context->getAttribute('cache-tags');
            if ($cacheTags->count() > 0) {
                $response->headers->add([
                    'X-Cache-Tags' => implode(', ', $cacheTags->toArray())
                ]);
            }
        }
        $this->injectAlternateHrefLangLinks($request);
        $varyingHeaders = [
            'Accept-Encoding',
            'Origin', // Need if request comes from Nuxt backend
            'Accept',
            'Authorization',
            'x-api-key'
        ];
        if ($request->attributes->getBoolean(CachableApiResponseSubscriber::VARY_ON_ACCEPT_LANGUAGE_ATTRIBUTE)) {
            $varyingHeaders[] = 'Accept-Language';
        }
        if ($request->attributes->has(CachableApiResponseSubscriber::CONTENT_LANGUAGE_ATTRIBUTE)) {
            $response->headers->set(
                'content-language',
                $request->attributes->getAlpha(CachableApiResponseSubscriber::CONTENT_LANGUAGE_ATTRIBUTE)
            );
        }
        $response->setVary(implode(', ', $varyingHeaders));
        if ($request->isMethodCacheable()) {
            $response->setTtl($menuResponse->getTtl());
        }

        return $response;
    }
}
