<?php

namespace BeatsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {
  /**
   * {@inheritDoc}
   */
  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder();

    $rootNode = $treeBuilder->root('beats');

    $this->_setupDBAL($rootNode);
    $this->_setupOAuth($rootNode);

    $this->_setupMailer($rootNode);
    $this->_setupChronos($rootNode);
    $this->_setupFlasher($rootNode);
    $this->_setupSecurity($rootNode);
    $this->_setupTranslator($rootNode);

    // Here you should define the parameters that are allowed to
    // configure your bundle. See the documentation linked above for
    // more information on that topic.

    return $treeBuilder;
  }

  protected function _setupDBAL(NodeDefinition $node) {
    $dbalNode = $node->children()->arrayNode('dbal')->addDefaultsIfNotSet();
    $this->_setupRDB($dbalNode);
    $this->_setupDOM($dbalNode);
    return $dbalNode->end()->end();
  }

  protected function _setupRDB(NodeDefinition $node) {
    /** @noinspection PhpUndefinedMethodInspection */
    return $node->children()
      ->arrayNode('rdb')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('drvr')->cannotBeEmpty()->isRequired()->defaultValue('pgsql')->end()
      ->scalarNode('host')->defaultValue('localhost')->end()
      ->scalarNode('port')->defaultValue(5432)->end()
      ->scalarNode('name')->end()
      ->scalarNode('user')->end()
      ->scalarNode('pass')->end()
      ->end()
      ->end()
      ->end();
  }

  protected function _setupDOM(NodeDefinition $node) {
    /** @noinspection PhpUndefinedMethodInspection */
    return $node->children()
      ->arrayNode('dom')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('drvr')->cannotBeEmpty()->isRequired()->defaultValue('couchdb')->end()
      ->scalarNode('host')->defaultValue('localhost')->end()
      ->scalarNode('port')->defaultValue(5984)->end()
      ->scalarNode('name')->end()
      ->scalarNode('user')->end()
      ->scalarNode('pass')->end()
      ->end()
      ->end()
      ->end();

  }

  protected function _setupOAuth(NodeDefinition $node) {
    /** @noinspection PhpUndefinedMethodInspection */
    return $node->children()
      ->arrayNode('oauth')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('callback')->defaultValue('beats.oauth.connect')->end()
      ->arrayNode('providers')->isRequired()->useAttributeAsKey('name')->prototype('array')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('class')->end()
      ->scalarNode('client_id')->cannotBeEmpty()->end()
      ->scalarNode('client_secret')->cannotBeEmpty()->end()
      ->scalarNode('authorization_url')->end()
      ->scalarNode('access_token_url')->end()
      ->scalarNode('infos_url')->end()
      ->scalarNode('scope')->end()
      ->scalarNode('callback')->defaultValue('beats.oauth.connect')->end()
      ->end()
      ->end()
      ->end()
      ->end()
      ->end();
  }

  protected function _setupMailer(NodeDefinition $node) {
    /** @noinspection PhpUndefinedMethodInspection */
    $node->children()
      ->arrayNode('mailer')->addDefaultsIfNotSet()
      ->children()
      ->arrayNode('mails')->isRequired()->requiresAtLeastOneElement()->useAttributeAsKey('type')
      ->prototype('array')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('mail')->cannotBeEmpty()->isRequired()->end()
      ->scalarNode('name')->cannotBeEmpty()->isRequired()->end()
      ->end()
      ->end()
      ->end()
      ->end()
      ->end();
  }

  protected function _setupChronos(NodeDefinition $node) {
    /** @noinspection PhpUndefinedMethodInspection */
    $node->children()
      ->arrayNode('chronos')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('timezone')->cannotBeEmpty()->isRequired()->defaultValue('America/New_York')->end()
      ->end()
      ->end()
      ->end();
  }

  protected function _setupFlasher(NodeDefinition $node) {
    /** @noinspection PhpUndefinedMethodInspection */
    $node->children()
      ->arrayNode('flasher')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('template')->isRequired()->defaultValue(null)->end()
      ->end()
      ->end()
      ->end();
  }

  protected function _setupSecurity(NodeDefinition $node) {
    /** @noinspection PhpUndefinedMethodInspection */
    $node->children()
      ->arrayNode('security')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('persister')->isRequired()->defaultValue(null)->end()
      ->scalarNode('default')->isRequired()->defaultValue(null)->end()
      ->end()
      ->end()
      ->end();
  }

  protected function _setupTranslator(NodeDefinition $node) {
    /** @noinspection PhpUndefinedMethodInspection */
    $node->children()
      ->arrayNode('translation')->addDefaultsIfNotSet()
      ->children()
      ->arrayNode('locales')->isRequired()->end()
      ->arrayNode('locales')->isRequired()->requiresAtLeastOneElement()->useAttributeAsKey('code')
      ->prototype('array')->addDefaultsIfNotSet()
      ->children()
      ->scalarNode('name')->cannotBeEmpty()->isRequired()->end()
      ->end()
      ->end()
      ->end();
  }
}
