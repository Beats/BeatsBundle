<?php

namespace BeatsBundle\DependencyInjection\Security\Factory;


use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class OAuthFactory extends AbstractFactory {

  const SERVICE_USER_CHECKER  = 'security.user_checker';
  const SERVICE_AUTH_PROVIDER = 'beats.security.core.authentication.provider.oauth';
  const SERVICE_ENTRY_POINT   = 'beats.security.http.entry_point.oauth';
  const SERVICE_AUTH_LISTENER = 'beats.security.http.firewall.listener.oauth';
  const SERVICE_PROVIDER_MAP  = 'beats.oauth.resource_provider.map';

  /**
   * Subclasses must return the id of a service which implements the
   * AuthenticationProviderInterface.
   *
   * @param ContainerBuilder $container
   * @param string $id The unique id of the firewall
   * @param array $config The options array for this listener
   * @param string $userProviderId The id of the user provider
   *
   * @return string never null, the id of the authentication provider
   */
  protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId) {
    $providerID = self::SERVICE_AUTH_PROVIDER . '.' . $id;

    $definition = new DefinitionDecorator(self::SERVICE_AUTH_PROVIDER);
    $definition->addArgument(new Reference($userProviderId));
    $definition->addArgument(new Reference(self::SERVICE_USER_CHECKER));


    $container->setDefinition($providerID, $definition);
    return $providerID;
  }

  protected function createEntryPoint($container, $id, $config, $defaultEntryPointId) {
    $entryID = self::SERVICE_ENTRY_POINT . '.' . $id;

    $definition = new DefinitionDecorator(self::SERVICE_ENTRY_POINT);
    $definition->addArgument($config['login_path']);

    $container->setDefinition($entryID, $definition);
    return $entryID;
  }

  protected function createListener($container, $id, $config, $userProvider) {
    $listenerID = parent::createListener($container, $id, $config, $userProvider);

    /** @noinspection PhpUndefinedMethodInspection */
    $definition = $container->getDefinition($listenerID);
    /** @noinspection PhpUndefinedMethodInspection */
    $definition->addMethodCall('setProviders', array(new Reference(self::SERVICE_PROVIDER_MAP)));

    return $listenerID;
  }

  protected function createAuthenticationSuccessHandler($container, $id, $config) {
    $handlerID = $config['success_handler'] . '.' . $id . '.' . str_replace('-', '_', $this->getKey());

    /** @noinspection PhpUndefinedMethodInspection */
    $definition = $container->setDefinition($handlerID, new DefinitionDecorator($config['success_handler']));
    /** @noinspection PhpUndefinedMethodInspection */
    $definition->replaceArgument(1, array_intersect_key($config, $this->defaultSuccessHandlerOptions));
    /** @noinspection PhpUndefinedMethodInspection */
    $definition->addMethodCall('setProviderKey', array($id));

    return $handlerID;
  }

  /**********************************************************************************************************************/

  public function addConfiguration(NodeDefinition $builder) {
    parent::addConfiguration($builder);
  }

  /**********************************************************************************************************************/

  /**
   * Subclasses must return the id of the listener template.
   *
   * Listener definitions should inherit from the AbstractAuthenticationListener
   * like this:
   *
   *    <service id="my.listener.id"
   *             class="My\Concrete\Classname"
   *             parent="security.authentication.listener.abstract"
   *             abstract="true" />
   *
   * In the above case, this method would return "my.listener.id".
   *
   * @return string
   */
  protected function getListenerId() {
    return self::SERVICE_AUTH_LISTENER;
  }

  public function getPosition() {
    return 'http';
  }

  public function getKey() {
    return 'oauth';
  }


}
