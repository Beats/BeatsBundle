<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;
use BeatsBundle\Validation\Constraint\Identity\ProviderInterface;

class Identity extends Chain {

  public function __construct(ProviderInterface $provider, $kind, $available, $email = true,
                              $failureRequired = 'The value is required',
                              $failureFormat = 'The value is not in the correct format',
                              $failureProvider = null,
                              $success = 'The value is valid') {

    $constraints = array(
      empty($failureRequired) ? new Required() : new Required($failureRequired),
    );

    if ($email) {
      $constraints[] = empty($failureFormat) ? new Email() : new Email($failureFormat);
    } else {
      $constraints[] = empty($failureFormat) ? new Username() : new Username($failureFormat);
    }
    if (empty($failureProvider)) {
      $failureProvider = $available ? 'The value is not available' : 'The value is not registered';
    }

    $constraints[] = new Callback(function ($value, Context $context) use ($provider, $kind, $available) {
      if (empty($kind)) {
        $kind = null;
      } else {
        $kind = $context->getFields()->get($kind, null, true);
      }
      $exists = (bool)$provider->exists($value, $kind);
      return $available  xor $exists;
    }, $failureProvider);

    parent::__construct($constraints, false, false, false, null, $success);
  }

}
