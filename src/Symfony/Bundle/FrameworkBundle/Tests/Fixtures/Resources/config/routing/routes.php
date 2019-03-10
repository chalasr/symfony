<?php

namespace Symfony\Bundle\FrameworkBundle\Routing\Loader\Configurator;

return function (RoutingConfigurator $routes) {
    $routes->template('template_route', '/static', 'static.html.twig')
        ->maxAge(300)
        ->sharedMaxAge(100)
        ->private(true)
        ->methods(['GET'])
        ->options(['utf8' => true])
        ->condition('abc')
    ;
    $routes->redirectTo('redirect_to_route', '/redirect', 'target_route')
        ->permanent(true)
        ->ignoreAttributes(['attr', 'ibutes'])
        ->keepRequestMethod(true)
        ->keepQueryParams(true)
        ->schemes(['http'])
        ->host('legacy')
        ->options(['utf8' => true])
    ;
    $routes->redirectToUrl('redirect_to_url_route', '/redirect-url', '/url-target')
        ->permanent(true)
        ->scheme('http')
        ->httpPort(1)
        ->httpsPort(2)
        ->keepRequestMethod(true)
        ->host('legacy')
        ->options(['utf8' => true])
    ;
};
