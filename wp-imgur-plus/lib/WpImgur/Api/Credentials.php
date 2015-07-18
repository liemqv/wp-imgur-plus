<?php

namespace WpImgur\Api;

class Credentials extends \Imgur\Credentials {

  public $optionsStore;

  protected $clientId     = '0d290e8b1610d9a';
  protected $clientSecret = '8b2e1d6e8001b11c177fce34a35832b951475ece';

  protected $didLoad      = false;

  function needs() {
    return array('optionsStore');
  }

  function loaded() {
    return $this->didLoad;
  }

  function load() {
    if ($this->loaded()) {
      return;
    }

    $this->optionsStore->load();
    $this->didLoad = true;
  }

  function save() {
    $this->optionsStore->save();
  }

  /* overridden to use credentials stored in options */
  function getAccessToken() {
    return $this->getOption('accessToken');
  }

  function setAccessToken($accessToken) {
    $this->setOption('accessToken', $accessToken);
  }

  function getAccessTokenExpiry() {
    return $this->getOption('accessTokenExpiry');
  }

  function setAccessTokenExpiry($expireIn) {
    $expiry   = strtotime("+{$expireIn} seconds");
    $this->setOption('accessTokenExpiry', $expiry);
  }

  function getRefreshToken() {
    return $this->getOption('refreshToken');
  }

  function setRefreshToken($refreshToken) {
    $this->setOption('refreshToken', $refreshToken);
  }

  /* helpers */
  function getOption($name) {
    return $this->optionsStore->getOption($name);
  }

  function setOption($name, $value) {
    $this->optionsStore->setOption($name, $value);
  }

}
