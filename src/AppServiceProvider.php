<?php
declare(strict_types=1);

namespace App;

use App\Controller\CommonContentController;
use App\Controller\ContactFormController;
use App\Controller\NullController;
use App\Model\NodesSourcesHeadFactory;
use App\Serialization\BlockWalkerSubscriber;
use App\Serialization\NodesSourcesHeadSubscriber;
use App\Serialization\WalkerApiSubscriber;
use App\TreeWalker\AutoChildrenNodeSourceWalker;
use App\TreeWalker\NodeSourceWalkerContext;
use JMS\Serializer\Serializer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\FirewallMap;
use Themes\AbstractApiTheme\Breadcrumbs\BreadcrumbsFactoryInterface;
use Themes\AbstractApiTheme\Breadcrumbs\NaiveBreadcrumbsFactory;
use Themes\AbstractApiTheme\Serialization\SerializationContextFactoryInterface;

class AppServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     * @return void
     */
    public function register(Container $pimple)
    {
        /**
         * @return int in minutes
         */
        $pimple['api.cache.ttl'] = !is_array(getenv('APP_API_CACHE_TTL')) ?
            ((int) getenv('APP_API_CACHE_TTL') ?: 0) :
            0;

        /**
         * @return bool Displays cache tags in response headers.
         */
        $pimple['api.use_cache_tags'] = true;

        /**
         * @return array
         */
        $pimple['api.cors_options'] = [
            'allow_credentials' => true,
            'allow_origin' => ['*'],
            'allow_headers' => true,
            'origin_regex' => false,
            'allow_methods' => ['GET', 'POST'], // Allow POST for contact-forms
            'expose_headers' => ['Link', 'Etag'],
            'max_age' => 60*60*24
        ];
        /*
         * Prevent accessing JSON resources from their Node path.
         */
        $pimple['nodeDefaultControllerClass'] = NullController::class;

        $pimple[NodeSourceWalkerContext::class] = function ($c) {
            return new NodeSourceWalkerContext(
                $c['stopwatch'],
                $c['nodeTypesBag'],
                $c['nodeSourceApi'],
                $c['requestStack'],
                $c['em']
            );
        };

        $pimple['api.base_request_matcher'] = function (Container $c) {
            return new RequestMatcher(
                '^'.preg_quote($c['api.prefix']).'/'.preg_quote($c['api.version'])
            );
        };

        $pimple->extend('serializer.subscribers', function (array $subscribers, Container $c) {
            $subscribers[] = new WalkerApiSubscriber();
            $subscribers[] = new NodesSourcesHeadSubscriber($c[NodesSourcesHeadFactory::class]);
            $subscribers[] = new BlockWalkerSubscriber(
                AutoChildrenNodeSourceWalker::class,
                $c[NodeSourceWalkerContext::class],
                $c['nodesSourcesUrlCacheProvider'],
                4
            );
            return $subscribers;
        });

        $pimple->extend('accessMap', function (AccessMap $accessMap, Container $c) {
            $accessMap->add(
                $c['api.base_request_matcher'],
                [$c['api.base_role']]
            );
            return $accessMap;
        });

        $pimple->extend('firewallMap', function (FirewallMap $firewallMap, Container $c) {
            /*
            * Add default API firewall entry.
            */
            $firewallMap->add(
                $c['api.base_request_matcher'], // launch firewall rules for any request within /api/1.0 path
                [
                    $c['api.firewall_listener'],
                    $c['securityAccessListener']
                ],
                $c['api.exception_listener']
            );
            /*
             * OR add OAuth2 API firewall entry.
             */
//            $firewallMap->add(
//                $c['api.base_request_matcher'], // launch firewall rules for any request within /api/1.0 path
//                [
//                    $c['api.oauth2_firewall_listener'],
//                    $c['securityAccessListener']
//                ],
//                $c['api.exception_listener']
//            );
            return $firewallMap;
        });

        $pimple['app.file_locator'] = function (Container $c) {
            $resourcesFolder = dirname(__FILE__) . '/Resources';
            return new FileLocator([
                $resourcesFolder,
                $resourcesFolder . '/routing',
                $resourcesFolder . '/config',
            ]);
        };

        $pimple->extend('routeCollection', function (RouteCollection $routeCollection, Container $c) {
            $loader = new YamlFileLoader($c['app.file_locator']);
            $routeCollection->addCollection($loader->load('routes.yml'));
            return $routeCollection;
        });

        /*
         * RateLimiterFactory for POST contact forms
         */
        $pimple['limiter.contact_form'] = function (Container $c) {
            return new RateLimiterFactory([
                'id' => 'contact-form',
                'policy' => 'token_bucket',
                'limit' => 5,
                'rate' => ['interval' => '1 minute'],
            ], new CacheStorage($c[CacheItemPoolInterface::class]));
        };

        /*
         * Configure custom controllers
         */
        $pimple[ContactFormController::class] = function (Container $c) {
            return new ContactFormController(
                $c['contactFormManager'],
                $c['limiter.contact_form']
            );
        };
        $pimple[NodesSourcesHeadFactory::class] = function (Container $c) {
            return new NodesSourcesHeadFactory($c['settingsBag'], $c['router'], $c['nodeSourceApi']);
        };
        $pimple[BreadcrumbsFactoryInterface::class] = function (Container $c) {
            return new NaiveBreadcrumbsFactory();
        };
        $pimple[CommonContentController::class] = function (Container $c) {
            return new CommonContentController(
                $c[Serializer::class],
                $c['em'],
                $c[SerializationContextFactoryInterface::class],
                $c['nodeSourceApi'],
                $c[NodeSourceWalkerContext::class],
                $c['nodesSourcesUrlCacheProvider'],
                $c['urlGenerator'],
                $c[NodesSourcesHeadFactory::class]
            );
        };
    }
}
