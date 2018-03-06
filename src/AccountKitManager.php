<?php

namespace Drupal\accountkit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\externalauth\ExternalAuthInterface;

/**
 * Contains all Account Kit related logic.
 */
class AccountKitManager {

  /**
   * @var ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  private $externalAuth;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * AccountKitManager constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   *   The config factory to get the account kit config from.
   * @param ExternalAuthInterface $externalAuth
   *   The authentication service for external authentication methods.
   * @param LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger factory to get the logger for our module.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ExternalAuthInterface $externalAuth, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->configFactory = $configFactory;
    $this->externalAuth = $externalAuth;
    $this->logger = $loggerChannelFactory->get('accountkit');
  }

  /**
   * Log a user in based on the account kit code, create it if necessary.
   *
   * @param string $code
   *   The account kit code.
   *
   * @return bool
   *   Indicates success.
   */
  public function userLoginFromCode($code) {

    $data = $this->getUserInfo($code);

    if (!empty($data['id'])) {
      // The account kit id will be the username.
      $user_name = $data['id'];
      $account_data = [];
      if (!empty($data['email']['address'])) {
        $account_data['mail'] = $data['email']['address'];
      }

      try {
        $this->externalAuth->loginRegister($user_name, 'accountkit', $account_data);
      }
      catch (ExternalAuthRegisterException $exception) {
        $this->logger->error($exception->getMessage());

        return FALSE;
      }

      return TRUE;
    }
    elseif (!empty($data['error'])) {
      $this->logger->error($data['error']['type'] . ': ' . $data['error']['message']);
    }
    return FALSE;
  }

  /**
   * Get user information like email or phone.
   *
   * This code is copied from the developer documentation of account kit.
   *
   * @param string $code
   *   The account kit code.
   *
   * @return array
   *   Array containing user info.
   */
  protected function getUserInfo($code) {
    // This code is copied from the developer documentation of account kit.
    $data = NULL;
    $access_token = $this->getAccessToken($code);
    if (!empty($access_token)) {
      // Get Account Kit information
      $me_endpoint_url = 'https://graph.accountkit.com/' . $this->getConfig('api_version') . '/me?' .
        'access_token=' . $access_token;
      $data = $this->curlit($me_endpoint_url);
    }
    else {
      $this->logger->error('The access token was empty.');
    }

    return $data;
  }

  /**
   * Get the Access token for a given code.
   *
   * This code is copied from the developer documentation of account kit.
   *
   * @param string $code
   *   The account kit code.
   *
   * @return string|null
   *   The access token.
   */
  protected function getAccessToken($code) {
    $app_id = $this->getConfig('app_id');
    $secret = $this->getConfig('app_secret');
    $version = $this->getConfig('api_version');

    // Exchange authorization code for access token
    $token_exchange_url = 'https://graph.accountkit.com/' . $version . '/access_token?' .
      'grant_type=authorization_code' .
      '&code=' . $code .
      "&access_token=AA|$app_id|$secret";

    $data = $this->curlit($token_exchange_url);

    if(!empty($data['error'])) {
      $error = $data['error']['message']
        . " type: ". $data['error']['type']
        . " code: " . $data['error']['code']
        . " fbtrace_id:" . $data['error']['fbtrace_id'];
      $this->logger->error($error);
    }

    return $data['access_token'];
  }

  /**
   * Get the account kit config.
   *
   * @param $key
   *   The config key.
   *
   * @return mixed
   *   the config.
   */
  protected function getConfig($key) {
    return $this->configFactory->get('accountkit.settings')->get($key);
  }

  /**
   * Make a curl request.
   *
   * This code is copied from the developer documentation of account kit.
   *
   * @param string $url
   *   The url to curl
   *
   * @return mixed
   *   The result
   */
  private function curlit($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $data = json_decode(curl_exec($ch), TRUE);
    curl_close($ch);
    return $data;
  }



}
