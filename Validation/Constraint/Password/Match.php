<?php
namespace BeatsBundle\Validation\Constraint\Password;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Match extends Constraint\Callback {

  public function __construct(ProviderInterface $provider, $identity, $kind = null, $strict = true, $failure = 'Values do not match', $success = 'The value is valid') {
    parent::__construct(
      function ($value, Context $context) use ($provider, $identity, $kind, $strict) {
        if (is_null($identity)) {
          return false;
        }
        $password = $provider->password($identity, $kind);

        return $strict ? $password === $value : $password == $value;
      }, $failure, $success
    );
    $this->_optional = true;
  }


}
