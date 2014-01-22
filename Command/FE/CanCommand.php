<?php
namespace BeatsBundle\Command\FE;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\BaseAsset;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\AssetManager;
use Assetic\Filter\CallablesFilter;
use Assetic\Filter\FilterCollection;
use Assetic\Filter\FilterInterface;
use BeatsBundle\Command\ServiceCommand;
use Symfony\Bundle\AsseticBundle\FilterManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Yaml\Yaml;

class  CanCommand extends ServiceCommand {

  const DIR_NAME   = 'beats-can';
  const FILE_BUILD = 'build.yml';


  /**
   * @param $path
   * @return string
   */
  static private function _fext($path) {
    return pathinfo($path, PATHINFO_EXTENSION);
  }

  /*********************************************************************************************************************/

  protected function configure() {
    $name = 'beats:fe:can';
    $this
      ->setName($name)
      ->setDescription('Builds the front-end engine')
      ->addOption('no-minify', 'M', InputOption::VALUE_NONE, 'Do not minify the build')
      ->addOption('no-remote', 'R', InputOption::VALUE_NONE, 'Do not fetch remote dependencies')
      ->addOption('no-embed', 'E', InputOption::VALUE_NONE, 'Whether to embed the templates')
      ->addOption('no-dependencies', 'D', InputOption::VALUE_NONE, 'Whether to download dependencies')
      ->addOption('exported', 'X', InputOption::VALUE_NONE, 'Whether to export the templates')
      ->addOption('reformat', 'r', InputOption::VALUE_NONE, 'Whether to reformat the source code')
      ->addOption('watch', null, InputOption::VALUE_NONE, 'Check for changes every second, debug mode only')
      ->addOption('period', null, InputOption::VALUE_REQUIRED, 'Set the polling period in seconds (used with --watch)', 1)
      ->addArgument('bundle', InputArgument::OPTIONAL, 'Bundle on which to build')
      ->setHelp(<<<EOT
The <info>$name</info> command minifies and packages the front-end engine source code
EOT
      );
  }

  /**
   * @return AssetManager
   */
  private function _am() {
    return $this->getContainer()->get('assetic.asset_manager');
  }

  /**
   * @return FilterManager
   */
  private function _afm() {
    return $this->getContainer()->get('assetic.filter_manager');
  }


  /*********************************************************************************************************************/

  /**
   * @param string $fext
   * @return FilterInterface
   */
  protected function _minFilter($fext) {
    switch ($fext) {
      case 'js':
        return $this->_afm()->get('yui_js');
      case 'css':
        return $this->_afm()->get('yui_css');
    }
    return null;
  }

  protected function _srcFilter($fext) {
    switch ($fext) {
      case 'js':
        return new CallablesFilter(function (BaseAsset $asset) {
          $asset->setContent($result = rtrim(trim($asset->getContent()), ';') . ";\n");
        });
      default:
        return new CallablesFilter(function (BaseAsset $asset) {
          $asset->setContent($result = trim($asset->getContent()) . "\n");
        });
    }
  }

  /**
   * @param string $prefix
   * @return FilterInterface
   */
  protected function _tplFilter($id, $fext) {
    $filter = new CallablesFilter(null, function (BaseAsset $asset) use ($id, $fext) {
      $result = sprintf(
        "{%% raw %%}\n<script type=\"text/%s\"  id=\"%s\">\n%s</script>\n{%% endraw %%}\n", $fext, $id, $asset->getContent()
      );
      $asset->setContent($result);
    });
    return $filter;
  }

  private function _scriptID(AssetInterface $asset, $fext, $prefix = 'beats') {
    return strtolower(sprintf('%s.%s.%s', $prefix, str_replace('/', '.', $asset->getSourcePath()), $fext));
  }

  /*********************************************************************************************************************/

  protected function _buildBundle(Bundle $bundle, InputInterface $input, OutputInterface $output) {
    $root = realpath($bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources');
    if (empty($root)) {
      return;
    }
    $home = realpath($root . DIRECTORY_SEPARATOR . 'engine');
    if (empty($home)) {
      return;
    }
    $output->writeln(sprintf("Building frontend: <info>%s</info>", $bundle->getName()));

    $srcHome = implode(DIRECTORY_SEPARATOR, array($home, 'src'));
    $libHome = implode(DIRECTORY_SEPARATOR, array($home, 'lib'));
    $binHome = implode(DIRECTORY_SEPARATOR, array($home, 'bin'));

    $fs = $this->_fsal()->fs();

    $fs->mkdir(array($libHome, $binHome));
    $fs->remove(array($binHome));

    $configs = $this->_readConfig($bundle, $home);
    foreach ($configs as $name => $config) {
      if (!empty($config['ignore'])) {
        $output->writeln("  Ignoring components: <warning>$name</warning>\n");
        continue;
      }
      if ($config['version'][0] != 'v') {
        $config['version'] = 'v' . $config['version'];
      }

      $tplHome = implode(DIRECTORY_SEPARATOR, array($root, 'views', self::DIR_NAME, $config['version']));
      $outHome = array(
        'js'  => implode(DIRECTORY_SEPARATOR, array($root, 'public', 'js', self::DIR_NAME, $config['version'])),
        'css' => implode(DIRECTORY_SEPARATOR, array($root, 'public', 'css', self::DIR_NAME, $config['version'])),
        'ejs' => implode(DIRECTORY_SEPARATOR, array($root, 'public', 'ejs', self::DIR_NAME, $config['version'])),
      );

      $fs->mkdir($outHome);
      $fs->mkdir($tplHome);

      $external = array(
        'js'  => array(),
        'css' => array(),
        'ejs' => array(),
      );
      $internal = array(
        'js'  => array(),
        'css' => array(),
        'ejs' => array(),
      );

      $this->_prepareComponents($bundle, $name, $config, $srcHome, $libHome, $external, $internal, $input, $output);
      if ($this->_isMinify) {
        $this->_minifyComponents($bundle, $name, $config, $binHome, $external, $internal, $input, $output);
      }
      $this->_bundleComponents($bundle, $name, $config, $tplHome, $outHome, $external, $internal, $input, $output);
    }
    $output->writeln("Frontend built.\n");
  }

  /********************************************************************************************************************/

  protected function _readConfig(Bundle $bundle, $home) {
    $file  = $home . DIRECTORY_SEPARATOR . self::FILE_BUILD;
    $asset = new FileAsset($file, array(), $home, self::FILE_BUILD);
    $this->_observe($bundle, $file, $asset);
    return Yaml::parse($file);
  }

  protected function _prepareComponents(Bundle $bundle, $name, array &$config,
                                        $srcHome, $libHome,
                                        &$external, &$internal,
                                        InputInterface $input, OutputInterface $output
  ) {
    $output->writeln("  Loading components: $name");

    $config['name']      = $name;
    $config['bundle']    = $bundle->getName();
    $config['namespace'] = $bundle->getNamespace();

    $output->writeln("    Dependencies");
    if (!empty($config['dependencies'])) {
      foreach ($config['dependencies'] as $href) {
        $fext  = self::_fext($href);
        $asset = $this->_loadReferred($bundle, $href, $libHome, $fext, $output);
        if (!empty($asset)) {
          $external[$fext][] = $asset;
        }
      }
    }

    $output->writeln("    Javascripts");
    if (!empty($config['javascripts'])) {
      foreach ($config['javascripts'] as $path) {
        $asset = $this->_loadInternal($bundle, $path, $srcHome, 'js', $output);
        if (!empty($asset)) {
          $internal['js'][] = $asset;
        }
      }
    }

    $output->writeln("    Stylesheets");
    if (!empty($config['stylesheets'])) {
      foreach ($config['stylesheets'] as $path) {
        $asset = $this->_loadInternal($bundle, $path, $srcHome, 'css', $output);
        if (!empty($asset)) {
          $internal['css'][] = $asset;
        }
      }
    }

    $output->writeln("    Templates");
    if (!empty($config['templates'])) {
      foreach ($config['templates'] as $path) {
        $asset = $this->_loadInternal($bundle, $path, $srcHome, 'ejs', $output);
        if (!empty($asset)) {
          $internal['ejs'][] = $asset;
        }
      }

    }

    $output->writeln("  Components loaded.\n");
  }

  protected function _minifyComponents(Bundle $bundle, $name, array &$config,
                                       $binHome,
                                       $external, $internal,
                                       InputInterface $input, OutputInterface $output
  ) {
    $output->writeln("  Minifying components: $name");

    $output->writeln("    External:");
    foreach ($external as $fext => $assets) {
      foreach ($assets as $asset) {
        $this->_minify($asset, $binHome, $fext, $input, $output);
      }
    }

    $output->writeln("    Internal:");
    foreach ($internal as $fext => $assets) {
      foreach ($assets as $asset) {
        $this->_minify($asset, $binHome, $fext, $input, $output);
      }
    }

    $output->writeln("  Components minified.\n");
  }


  protected function _bundleComponents(Bundle $bundle, $name, array &$config,
                                       $tplHome, $outHome,
                                       $external, $internal,
                                       InputInterface $input, OutputInterface $output
  ) {
    $output->writeln("  Packaging components: $name");

    $output->writeln("    Javascripts:");
    $this->_bundle($config, $outHome, $external, $internal, 'js', $output);
    $output->writeln("    Stylesheets:");
    $this->_bundle($config, $outHome, $external, $internal, 'css', $output);
    $output->writeln("    Templates:");
    $this->_reveal($config, $outHome, $tplHome, $external, $internal, 'ejs', $output);

    $output->writeln("  Components packaged.\n");
  }


  /********************************************************************************************************************/

  private function _loadReferred(Bundle $bundle, $href, $libHome, $fext, OutputInterface $output) {
    if (preg_match('#^@(?<bundle>\w+)(?<path>.*)\.(?<fext>\w+)$#', $href, $matches)) {
      $home = $this->_kernel()->getBundle($matches['bundle'])->getPath();
      return $this->_loadInternal($bundle, $matches['path'], $home, $fext, $output);
    } else {
      return $this->_loadExternal($bundle, $href, $libHome, $fext, $output);
    }
  }

  private function _loadInternal(Bundle $bundle, $path, $srcHome, $fext, OutputInterface $output) {
    $output->write(sprintf("      %s <info>%s</info>", 'Loading', $path));

    $file = $srcHome . DIRECTORY_SEPARATOR . $path . '.' . $fext;

    $filter = $this->_srcFilter($fext);
    $asset  = new FileAsset($file, empty($filter) ? array() : array($filter), $srcHome, $path);

    try {
      $asset->getLastModified();
      if ($this->_isReformat) {
        $output->write(" <info>Reformatting</info>");
        $this->_write($file, $asset->dump());
      }
    } catch (\Exception $ex) {
      $output->writeln(" <info>Fail</info>");
      return null;
    }
    $output->writeln(" <info>Done</info>");
    $this->_observe($bundle, $file, $asset);
    return $asset;
  }

  private function _loadExternal(Bundle $bundle, $href, $libHome, $fext, OutputInterface $output) {
    $output->write(sprintf("      %s <info>%s</info>", 'Loading', $href));

    $filter    = $this->_srcFilter($fext);
    $hrefAsset = new HttpAsset($href, empty($filter) ? array() : array($filter));
    $file      = $libHome . DIRECTORY_SEPARATOR . $hrefAsset->getSourcePath();
    $fileAsset = new FileAsset($file, $filter, $libHome, $hrefAsset->getSourcePath());


    if ($this->_isRemote) {
      try {
        $output->write(sprintf(" <info>Caching</info> %s", $file));
        $this->_write($file, $hrefAsset->dump());
      } catch (\Exception $ex) {
        $output->write(" <error>Fail</error> trying cached");
        try {
          $fileAsset->getLastModified();
        } catch (\Exception $ex) {
          $output->writeln(" <error>Fail</error>");
          return null;
        }
      }
    } else {
      try {
        $output->write(sprintf(" <info>cached</info> %s", $file));
        $fileAsset->getLastModified();
      } catch (\Exception $ex) {
        try {
          $output->write(" <error>Fail</error> trying remote");

          $output->write(sprintf(" <info>Caching</info> %s", $file));
          $this->_write($file, $hrefAsset->dump());
        } catch (\Exception $ex) {
          $output->writeln(" <error>Fail</error>");
          return null;
        }
      }
    }
    $output->writeln(" <info>Done</info>");
    $this->_observe($bundle, $file, $fileAsset);
    return $fileAsset;
  }

  /********************************************************************************************************************/

  private function _minify(BaseAsset $asset, $dstHome, $fext, InputInterface $input, OutputInterface $output) {
    $path = $asset->getSourcePath();
    if (empty($fext)) {
      $fext = self::_fext($path);
    }
    $output->write(sprintf("      %s <info>%s</info>", 'Minifying', $path));
    $dstPath = $dstHome . DIRECTORY_SEPARATOR . $asset->getSourcePath() . '.min.' . $fext;
    $this->_write($dstPath, $asset->dump($this->_minFilter($fext)));
    $output->writeln(" <info>Done</info>");
  }

  private function _bundle($config, $outHome, $external, $internal, $fext, OutputInterface $output) {
    $dstSrc = implode(DIRECTORY_SEPARATOR, array($outHome[$fext], sprintf(
      '%s.%s', $config['name'], $fext
    )));
    $this->_write($dstSrc);
    $output->writeln(sprintf("      %s <info>%s</info>", 'Bundling', $dstSrc));
    foreach ($external[$fext] as $asset) {
      $this->_append($asset, $dstSrc, null, $output);
    }
    foreach ($internal[$fext] as $asset) {
      $this->_append($asset, $dstSrc, null, $output);
    }

    if ($this->_isMinify) {
      $dstMin = implode(DIRECTORY_SEPARATOR, array($outHome[$fext], sprintf(
        '%s.min.%s', $config['name'], $fext
      )));
      $this->_write($dstMin);
      $output->writeln(sprintf("      %s <info>%s</info>", 'Bundling', $dstMin));
      foreach ($external[$fext] as $asset) {
        $this->_append($asset, $dstMin, $this->_minFilter($fext), $output);
      }
      foreach ($internal[$fext] as $asset) {
        $this->_append($asset, $dstMin, $this->_minFilter($fext), $output);
      }
    }
  }

  private function _reveal($config, $outHome, $tplHome, $external, $internal, $fext, OutputInterface $output) {
    if (preg_match('#^(?<prefix>\w+)\\\\(\w+\\\\)*\w+Bundle$#', $config['namespace'], $matches)) {
      $prefix = $matches['prefix'];
    } else {
      $prefix = 'beats';
    }

    $dstSrc = implode(DIRECTORY_SEPARATOR, array($tplHome, sprintf(
      '%s.html.twig', $config['name']
    )));

    if ($this->_isTplEmbed) {
      $this->_write($dstSrc);
      $output->writeln(sprintf("      %s <info>%s</info>", 'Bundling', $dstSrc));
    }
    foreach ($external[$fext] as $asset) {
      $id = $this->_scriptID($asset, $fext, $prefix);
      if ($this->_isTplEmbed) {
        $filters = array($this->_tplFilter($id, $fext));
        $this->_append($asset, $dstSrc, new FilterCollection($filters), $output);
      }
      if ($this->_isTplExport) {
        $filters = array();
        $this->_append($asset, $outHome[$fext] . DIRECTORY_SEPARATOR . $id, new FilterCollection($filters), $output);
      }
    }
    foreach ($internal[$fext] as $asset) {
      $id = $this->_scriptID($asset, $fext, $prefix);
      if ($this->_isTplEmbed) {
        $filters = array($this->_tplFilter($id, $fext));
        $this->_append($asset, $dstSrc, new FilterCollection($filters), $output);
      }
      if ($this->_isTplExport) {
        $filters = array();
        $this->_append($asset, $outHome[$fext] . DIRECTORY_SEPARATOR . $id, new FilterCollection($filters), $output);
      }
    }

//    if ($this->_isMinify) {
//      $dstMin = implode(DIRECTORY_SEPARATOR, array($tplHome, sprintf(
//        '%s-%s.min.html.twig', $config['name'], $config['version']
//      )));
//      if ($this->_isTplEmbed) {
//        $this->_write($dstMin);
//        $output->writeln(sprintf("      %s <info>%s</info>", 'Bundling', $dstMin));
//      }
//
//      $minFilter = $this->_minFilter($fext);
//      foreach ($external[$fext] as $asset) {
//        $id = $this->_scriptID($asset->getSourcePath(), $fext, $prefix);
//        if ($this->_isTplEmbed) {
//          $filters = array($this->_tplFilter($id, $fext));
//          if ($minFilter) {
//            array_unshift($filters, $minFilter);
//          }
//          $this->_append($asset, $dstMin, new FilterCollection($filters), $output);
//        }
//        if ($this->_isTplExport) {
//          $filters = array();
//          if ($minFilter) {
//            array_unshift($filters, $minFilter);
//          }
//          $this->_append($asset, $outHome[$fext] . DIRECTORY_SEPARATOR . $id, new FilterCollection($filters), $output);
//          $this->_write($outHome[$fext] . DIRECTORY_SEPARATOR . $id, $asset->dump($minFilter));
//        }
//      }
//      foreach ($internal[$fext] as $asset) {
//        $id = $this->_scriptID($asset->getSourcePath(), $fext, $prefix);
//        if ($this->_isTplEmbed) {
//          $filters = array($this->_tplFilter($id, $fext));
//          if ($minFilter) {
//            array_unshift($filters, $minFilter);
//          }
//          $this->_append($asset, $dstMin, new FilterCollection($filters), $output);
//        }
//        if ($this->_isTplExport) {
//          $filters = array();
//          if ($minFilter) {
//            array_unshift($filters, $minFilter);
//          }
//          $this->_append($asset, $outHome[$fext] . DIRECTORY_SEPARATOR . $id, new FilterCollection($filters), $output);
//        }
//      }
//    }

  }

  private function _append(AssetInterface $asset, $dstPath, $filter = null, OutputInterface $output) {
    $output->writeln(sprintf("        %s <info>%s</info>", 'Packaging', $asset->getSourcePath()));
    $this->_write($dstPath, $asset->dump($filter), true);
  }

  /*********************************************************************************************************************/

  private function _write($path, $content = '', $append = false) {
    $this->_fsal()->fs()->mkdir(dirname($path));
    file_put_contents($path, $content, $append ? FILE_APPEND : null);
  }

  private function _observe(Bundle $bundle, $file, AssetInterface $asset) {
    $this->_watcher[$bundle->getName()][$file] = array(
      'bundle' => $bundle->getName(),
      'mtime'  => $asset->getLastModified(),
      'crc32'  => $this->_crc32($asset),
      'asset'  => clone $asset,
    );
  }

  private function _crc32(AssetInterface $asset) {
    return crc32($asset->getContent());
  }

  private function _changed($file, $mtime, $crc32, BaseAsset $asset) {
    return $mtime < $asset->getLastModified() || $crc32 != $this->_crc32($asset);
  }

  /*********************************************************************************************************************/

  private $_isMinify = true;
  private $_isRemote = true;
  private $_isTplEmbed = true;
  private $_isTplExport = false;
  private $_isDependencies = false;
  private $_isReformat = false;

  protected $_bundles;

  protected $_watcher = array();

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->_isMinify       = !$input->getOption('no-minify');
    $this->_isRemote       = !$input->getOption('no-remote');
    $this->_isTplEmbed     = !$input->getOption('no-embed');
    $this->_isDependencies = !$input->getOption('no-dependencies');
    $this->_isTplExport    = $input->getOption('exported');
    $this->_isReformat     = $input->getOption('reformat');

    if ($name = $input->getArgument('bundle')) {
      $this->_bundles = array($name => $this->_kernel()->getBundle($name));
    } else {
      $this->_bundles = $this->_kernel()->getBundles();
    }

    if ($input->getOption('watch')) {
      $this->_isMinify       = false;
      $this->_isReformat     = false;
      $this->_isRemote       = false;
      $this->_isDependencies = true;

      $this->_watch($input, $output);
    } else {
      $this->_build($input, $output);
    }

  }

  /*********************************************************************************************************************/

  protected function _build(InputInterface $input, OutputInterface $output) {
//    /** @noinspection PhpUnusedLocalVariableInspection */
    foreach ($this->_bundles as $bundle) {
      $this->_buildBundle($bundle, $input, $output);
    };
  }

  protected function _watch(InputInterface $input, OutputInterface $output) {
    $this->_build($input, $output);
    $period  = $input->getOption('period');
    $changed = true;
    do {
      try {
        foreach ($this->_watcher as $name => $files) {
          foreach ($files as $file => $data) {
            if ($this->_changed($file, $data['mtime'], $data['crc32'], $data['asset'])) {
              $changed = true;
              $output->writeln("\nModification detected: $file\n");
              $this->_buildBundle($this->_bundles[$name], $input, $output);
              break;
            }
          }
        }
        if ($changed) {
          $changed = false;
          $output->writeln(sprintf("**** DONE **** @ <comment>%s</comment>\n", date('H:i:s')));
        }
        sleep($period);
      } catch (\Exception $ex) {
        $output->writeln('<error>[error]</error> ' . $ex->getMessage());
      }
    } while (true);
  }

}
