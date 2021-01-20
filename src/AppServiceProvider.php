<?php
declare(strict_types=1);

namespace App;

use App\Controller\NullController;
use App\Serialization\BlockWalkerSubscriber;
use App\Serialization\NodesSourcesUriSubscriber;
use App\Serialization\WalkerApiSubscriber;
use App\TreeWalker\BlockNodeSourceWalker;
use App\TreeWalker\NodeSourceWalkerContext;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\FirewallMap;

class AppServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     * @return void
     */
    public function register(Container $container)
    {
        /*
         * Prevent accessing JSON resources from their Node path.
         */
        $container['nodeDefaultControllerClass'] = NullController::class;

        $container[NodeSourceWalkerContext::class] = function ($c) {
            return new NodeSourceWalkerContext(
                $c['stopwatch'],
                $c['nodeTypesBag'],
                $c['nodeSourceApi'],
                $c['requestStack'],
                $c['em']
            );
        };

        $container['api.base_request_matcher'] = function (Container $c) {
            return new RequestMatcher(
                '^'.preg_quote($c['api.prefix']).'/'.preg_quote($c['api.version'])
            );
        };

        $container->extend('serializer.subscribers', function (array $subscribers, Container $c) {
            $subscribers[] = new NodesSourcesUriSubscriber($c['router']);
            $subscribers[] = new WalkerApiSubscriber();
            $subscribers[] = new BlockWalkerSubscriber(
                BlockNodeSourceWalker::class,
                $c[NodeSourceWalkerContext::class],
                $c['nodesSourcesUrlCacheProvider'],
                4
            );
            return $subscribers;
        });

        $container->extend('accessMap', function (AccessMap $accessMap, Container $c) {
            $accessMap->add(
                $c['api.base_request_matcher'],
                [$c['api.base_role']]
            );
            return $accessMap;
        });

        $container->extend('firewallMap', function (FirewallMap $firewallMap, Container $c) {
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

        $container['app.file_locator'] = function (Container $c) {
            $resourcesFolder = dirname(__FILE__) . '/Resources';
            return new FileLocator([
                $resourcesFolder,
                $resourcesFolder . '/routing',
                $resourcesFolder . '/config',
            ]);
        };

        $container->extend('routeCollection', function (RouteCollection $routeCollection, Container $c) {
            $loader = new YamlFileLoader($c['app.file_locator']);
            $routeCollection->addCollection($loader->load('routes.yml'));
            return $routeCollection;
        });
    }
}
