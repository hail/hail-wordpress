<?php

namespace League\OAuth2\Client\Provider;

class HailUser implements ResourceOwnerInterface {

  protected $data;

  public function __construct(array $response) {
    $this->data = $response;
  }

  public function getId() {
    return $this->getField('id');
  }

  public function toArray() {
    return $this->data;
  }

  private function getField($key) {
    return isset($this->data[$key]) ? $this->data[$key] : null;
  }

}
