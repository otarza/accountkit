<?php

namespace Drupal\accountkit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\accountkit\AccountKitManager;

/**
 * Class EmailLoginForm.
 */
class AccountKitLoginForm extends FormBase {

  /**
   * Drupal\accountkit\AccountKitManager definition.
   *
   * @var \Drupal\accountkit\AccountKitManager
   */
  protected $accountkitAccountkitManager;
  /**
   * Constructs a new EmailLoginForm object.
   */
  public function __construct(
    AccountKitManager $accountkit_accountkit_manager
  ) {
    $this->accountkitAccountkitManager = $accountkit_accountkit_manager;
  }

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
    $config = $this->config('accountkit.settings');

    $enable_email = $config->get('enable_email_auth');
    $enable_phone = $config->get('enable_phone_auth');
    if (!$enable_email && ! $enable_phone) {
      // Both options are disabled, the form needs to be empty.
      return $form;
    }


    if ($enable_email) {
      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#description' => $this->t('Please input your email address.'),
      ];
      $form['email_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Email Login'),
        '#attributes' => ['id' => 'email-login-submit'],
      ];
    }

    $csrf = $form_state->get('csrf');
    if (!$csrf) {
      $csrf = rand(0, 10);
      $form_state->set('csrf', $csrf);
    }


    if ($enable_phone) {
      $form['country_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Country code'),
        '#description' => $this->t('Please input your country code.'),
        '#maxlength' => 64,
        '#size' => 5,
      ];
      $form['phone_number'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Phone number'),
        '#description' => $this->t('Please input your phone number.'),
        '#maxlength' => 64,
        '#size' => 64,
      ];
      $form['sms_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('SMS Login'),
        '#attributes' => ['id' => 'sms-login-submit'],
      ];
    }

    $form['code'] = [
      '#type' => 'hidden',
      '#title' => t('Code'),
      '#description' => t('Hidden code field.'),
      '#attributes' => ['id' => 'code'],
    ];

    $form['#attached'] = [
      'library' => [
        'accountkit/sdk',
        'accountkit/client',
      ],
      'drupalSettings' => [
        'accountkit' => [
          'client' => [
            'app_id' => $config->get('app_id'),
            'api_version' => $config->get('api_version'),
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($this->accountkitAccountkitManager->userLoginFromCode($form_state->getValue('code'))){
      $uri = $this->config('accountkit.settings')->get('redirect_url');
      $form_state->setRedirectUrl(Url::fromUri($uri));
    }
  }

}
