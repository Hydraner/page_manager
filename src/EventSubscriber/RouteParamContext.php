<?php

/**
 * @file
 * Contains \Drupal\page_manager\EventSubscriber\RouteParamContext.
 */

namespace Drupal\page_manager\EventSubscriber;

use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Routing\RouteProvider;
use Drupal\page_manager\Event\PageManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sets values from the route parameters as a context.
 */
class RouteParamContext implements EventSubscriberInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CurrentUserContext.
   *
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RouteProvider $route_provider, RequestStack $request_stack) {
    $this->routeProvider = $route_provider;
    $this->requestStack = $request_stack;
  }

  /**
   * Adds in the current user as a context.
   *
   * @param \Drupal\page_manager\Event\PageManagerContextEvent $event
   *   The page entity context event.
   */
  public function onPageContext(PageManagerContextEvent $event) {
    $request = $this->requestStack->getCurrentRequest();
    $executable = $event->getPageExecutable();
    $routes = $this->routeProvider->getRoutesByPattern($executable->getPage()->getPath())->all();
    $route = reset($routes);

    if ($route_contexts = $route->getOption('parameters')) {
      foreach ($route_contexts as $route_context_name => $route_context) {
        // Skip this parameter.
        if ($route_context_name == 'page') {
          continue;
        }

        // @todo Remove once https://drupal.org/node/2116341 is in.
        $route_context += \Drupal::typedDataManager()->getDefinition($route_context['type']);

        $context = new Context($route_context);
        if ($request->attributes->has($route_context_name)) {
          $context->setContextValue($request->attributes->get($route_context_name));
        }
        else {
          // @todo Find a way to add in a fake value for configuration.
        }
        $executable->addContext($route_context_name, $context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PageManagerEvents::PAGE_CONTEXT][] = 'onPageContext';
    return $events;
  }

}
