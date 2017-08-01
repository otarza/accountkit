<?php

namespace Drupal\accountkit\Form;

use Drupal\accountkit\AccountKitManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures Account Kit settings.
 */
class AccountKitLoginForm extends FormBase {

  protected $requestContext;
  protected $accountKitManager;

  /**
   * Constructor.
   *
   * @param \Drupal\accountkit\AccountKitManager $accountkit_manager
   *
   * @internal param \Drupal\Core\Config\ConfigFactoryInterface $config_factory The factory for configuration objects.*   The factory for configuration objects.
   * @internal param \Drupal\Core\Routing\RequestContext $request_context Holds information about the current request.*   Holds information about the current request.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'accountkit_login_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['country_code'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Country Code'),
    ];

    $form['phone_number'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Phone number'),
      '#placeholder' => $this->t('Phone number'),
    ];

    $form['sms_login'] = [
      '#type' => 'button',
      '#value' => $this->t('Login via sms'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#required' => FALSE,
      '#title' => $this->t('Email'),
      '#placeholder' => $this->t('Email address'),
    ];

    $form['email_login'] = [
      '#type' => 'button',
      '#value' => $this->t('Login via email'),
    ];

    $form['csrf'] = [
      '#type' => 'hidden',
      '#name' => 'csrf',
      '#attributes' => [
        'id' => 'csrf',
      ],
    ];

    $form['code'] = [
      '#type' => 'hidden',
      '#name' => 'code',
      '#attributes' => [
        'id' => 'code',
      ],
    ];

    $form['#attached'] = [
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
    ];


    return $form;
  }


  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }
}
