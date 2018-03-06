<?php

namespace Drupal\accountkit;

use GuzzleHttp\Exception\ClientException;

/**
 * Class AccountKitConnectionException
 *
 * @package Drupal\accountkit
 */
class AccountKitConnectionException extends ClientException {

  protected $data;

  /**
   * AccountKitConnectionException constructor.
   *
   * @param \GuzzleHttp\Exception\ClientException $exception
   *   The Guzzle client exception.
   * @param array $data
   *   The error data sent by facebook.
   */
  public function __construct(ClientException $exception, $data = []) {
    parent::__construct($exception->getMessage(), $exception->getRequest(), $exception->getResponse(), $exception->getPrevious(), $exception->getHandlerContext());
    $this->data = $data;
  }

  /**
   * Get the error data.
   *
   * @return array
   */
  public function getData() {
    return $this->data;
  }
}
