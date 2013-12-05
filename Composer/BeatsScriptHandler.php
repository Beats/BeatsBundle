<?php
namespace BeatsBundle\Composer;

use Composer\Script\CommandEvent;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class BeatsScriptHandler {

  protected static function _getPhp() {
    $phpFinder = new PhpExecutableFinder;
    if (!$phpPath = $phpFinder->find()) {
      throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
    }
    return $phpPath;
  }

  protected static function _mergeOptions(array $options) {
    foreach ($options as $name => &$value) {
      $env = getenv(strtoupper(str_replace('-', '_', $name)));
      if ($env !== false) {
        $value = $env;
      }
    }
    if (is_readable('composer.yml')) {
      return array_merge($options, Yaml::parse('composer.yml'));
    }
    return $options;
  }

  protected static function _getOptions(CommandEvent $event) {
    $options = self::_mergeOptions(array_merge(array(
      'symfony-env'            => 'dev',
      'symfony-app-dir'        => 'app',
      'symfony-web-dir'        => 'web',
      'symfony-cache-clear'    => '--no-warmup',
      'symfony-cache-warmup'   => '',
      'symfony-assets-install' => 'hard',
      'symfony-beats-fe-build' => '-M',
      'symfony-assetic-dump'   => '',
    ), $event->getComposer()->getPackage()->getExtra()));

    if (!is_dir($options['symfony-app-dir'])) {
      echo 'The symfony-app-dir (' . $options['symfony-app-dir'] . ') specified in composer.json was not found in ' . getcwd() . '.' . PHP_EOL;
      return false;
    }
    if (!is_dir($options['symfony-web-dir'])) {
      echo 'The symfony-web-dir (' . $options['symfony-web-dir'] . ') specified in composer.json was not found in ' . getcwd() . '.' . PHP_EOL;
      return false;
    }
    // Workaround for https://github.com/schmittjoh/JMSDiExtraBundle/issues/96
    if (!preg_match('#--no-warmup#', $options['symfony-cache-clear'])) {
      $options['symfony-cache-clear'] .= ' --no-warmup';
    }

    $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');
    return $options;
  }

  protected static function _executeCommand(CommandEvent $event, $appDir, $cmd, $timeout = 300) {
    $php     = escapeshellarg(self::_getPhp());
    $console = escapeshellarg($appDir . '/console');
    if ($event->getIO()->isDecorated()) {
      $console .= ' --ansi';
    }

    $process = new Process($php . ' ' . $console . ' ' . $cmd, null, null, null, $timeout);
    $process->run(function ($type, $buffer) {
      echo $buffer;
    });
    if (!$process->isSuccessful()) {
      throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', escapeshellarg($cmd)));
    }
  }

  public static function cacheClear(CommandEvent $event) {
    $options = self::_getOptions($event);
    if (empty($options)) {
      return;
    }

    $cmd = sprintf('%s %s --env=%s', 'cache:clear', $options['symfony-cache-clear'], $options['symfony-env']);
    static::_executeCommand($event, $options['symfony-app-dir'], $cmd, $options['process-timeout']);
  }

  public static function cacheWarmup(CommandEvent $event) {
    $options = self::_getOptions($event);
    if (empty($options)) {
      return;
    }
    $cmd = sprintf('%s %s --env=%s', 'cache:warmup', $options['symfony-cache-warmup'], $options['symfony-env']);
    static::_executeCommand($event, $options['symfony-app-dir'], $cmd, $options['process-timeout']);
  }

  public static function installAssets(CommandEvent $event) {
    $options = self::_getOptions($event);
    if (empty($options)) {
      return;
    }
    $arguments = '';
    if ($options['symfony-assets-install'] == 'symlink') {
      $arguments = '--symlink ';
    } elseif ($options['symfony-assets-install'] == 'relative') {
      $arguments = '--symlink --relative ';
    }
    $arguments .= escapeshellarg($options['symfony-web-dir']);
    $cmd = sprintf('%s %s --env=%s', 'assets:install', $arguments, $options['symfony-env']);
    static::_executeCommand($event, $options['symfony-app-dir'], $cmd, $options['process-timeout']);
  }

  public static function beatsFEBuild(CommandEvent $event) {
    $options = self::_getOptions($event);
    if (empty($options)) {
      return;
    }
    $cmd = sprintf('%s %s --env=%s', 'beats:fe:build', $options['symfony-beats-fe-build'], $options['symfony-env']);
    static::_executeCommand($event, $options['symfony-app-dir'], $cmd, $options['process-timeout']);
  }

  public static function asseticDump(CommandEvent $event) {
    $options = self::_getOptions($event);
    if (empty($options)) {
      return;
    }
    $cmd = sprintf('%s %s --env=%s', 'assetic:dump', $options['symfony-assetic-dump'], $options['symfony-env']);
    static::_executeCommand($event, $options['symfony-app-dir'], $cmd, $options['process-timeout']);
  }

}
