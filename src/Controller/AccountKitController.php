<?php

namespace Drupal\accountkit\Controller;

use Drupal\accountkit\AccountKitManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AccountKitController.
 *
 * @package Drupal\accountkit\Controller
 */
class AccountKitController extends ControllerBase {

  /**
   * The Account Kit manager service.
   *
   * @var \Drupal\accountkit\AccountKitManager;
   */
  protected $accountKitManager;


  /**
   * {@inheritdoc}
   */
  public function __construct(AccountKitManager $accountkit_manager) {
    $this->accountKitManager = $accountkit_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('accountkit.accountkit_manager')
    );
  }

  /**
   * Login.
   *
   * @return string
   *   Return Hello string.
   */
  public function login() {
    $markup = [
      '#theme' => 'accountkit_login_form',
      '#attached' => [
        'library' => [
          'accountkit/sdk',
          'accountkit/client',
        ],
        'drupalSettings' => [
          'accountkit' => [
            'client' => [
              'app_id' => $this->accountKitManager->getAppId(),
              'api_version' => $this->accountKitManager->getApiVersion(),
            ],
          ],
        ],
      ],
      '#redirect_url' => $this->accountKitManager->getRedirectUrl(),
    ];

    return $markup;
  }

}
