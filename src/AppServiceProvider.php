<?php
declare(strict_types=1);

namespace App;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
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
        $container['api.base_request_matcher'] = function (Container $c) {
            return new RequestMatcher(
                '^'.preg_quote($c['api.prefix']).'/'.preg_quote($c['api.version'])
            );
        };

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
    }
}
