<?php

namespace Drupal\authenticated_frontpage\EventSubscriber;

use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\State;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;

/**
 * Custom Frontpage for Authenticated users event subscriber.
 */
class AuthenticatedFrontpageSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The path matcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\State\State $state
   *   The state service.
   */
  public function __construct(MessengerInterface $messenger,
                              AccountProxyInterface $currentUser,
                              PathMatcherInterface $pathMatcher,
                              ConfigFactoryInterface $configFactory,
                              State $state) {
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->pathMatcher = $pathMatcher;
    $this->configFactory = $configFactory;
    $this->state = $state;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    // Get the config value.
    $loggedin_frontpage = $this->configFactory
      ->get('authenticated_frontpage.settings')
      ->get('authenticated_frontpage.field_loggedin_frontpage');

    // Return if no value is set.
    if (!isset($loggedin_frontpage)) {
      return;
    }

    // Make sure front page module is not run when using cli (drush).
    // Make sure front page module does not run when installing Drupal either.
    if (PHP_SAPI === 'cli' || InstallerKernel::installationAttempted()) {
      return;
    }

    // Don't run when site is in maintenance mode.
    if ($this->state->get('system.maintenance_mode')) {
      return;
    }

    // Ignore non index.php requests (like cron).
    if (!empty($_SERVER['SCRIPT_FILENAME'])
      && realpath(DRUPAL_ROOT . '/index.php') != realpath($_SERVER['SCRIPT_FILENAME'])) {
      return;
    }

    // Ignore anonymous users.
    if ($this->currentUser->isAnonymous()) {
      return;
    }

    if ($this->pathMatcher->isFrontPage()) {
      $url_object = new Url('entity.node.canonical', [
        'node' => $loggedin_frontpage,
      ]);

      $event->setResponse(new RedirectResponse($url_object->toString()));
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
