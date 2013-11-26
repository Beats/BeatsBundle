<?php
namespace BeatsBundle\Command\FE;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\BaseAsset;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\AssetManager;
use Assetic\Filter\CallablesFilter;
use Assetic\Filter\FilterInterface;
use BeatsBundle\Command\ServiceCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Yaml\Yaml;

class  BuildCommand extends ServiceCommand {

  const FILE_BUILD = 'build.yml';

  /*********************************************************************************************************************/

  protected function configure() {
    $name = 'beats:fe:build';
    $this
      ->setName($name)
      ->setDescription('Builds the front-end engine')
      ->addOption('no-minify', 'M', InputOption::VALUE_NONE, 'Whether to minify the build')
      ->addOption('no-embedded', 'E', InputOption::VALUE_NONE, 'Whether to embed the templates')
      ->addOption('no-exported', 'X', InputOption::VALUE_NONE, 'Whether to export the templates')
      ->addOption('no-dependencies', 'D', InputOption::VALUE_NONE, 'Whether to download dependencies')
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

  /*********************************************************************************************************************/

  /**
   * @return FilterInterface
   */
  protected function _minFilter() {
    static $filter = null;
    if (empty($filter)) {
      $filter = $this->getContainer()->get('assetic.filter_manager')->get('yui_js');
    }
    return $filter;
  }

  /**
   * @param string $prefix
   * @return FilterInterface
   */
  protected function _tplFilter($prefix) {
//    static $filter = null;
//    if (empty($filter)) {
    $filter = new CallablesFilter(null, function (BaseAsset $asset) use ($prefix) {
      $id     = $this->_scriptID($asset->getSourcePath(), $prefix);
      $result = sprintf(
        "{%% raw %%}\n<script type=\"text/ejs\"  id=\"%s\">\n%s</script>\n{%% endraw %%}\n", $id, $asset->getContent()
      );
      $asset->setContent($result);
    });
//    }
    return $filter;
  }

  private function _scriptID($path, $prefix = 'beats') {
    return strtolower(sprintf('%s.%s.ejs', $prefix, str_replace('/', '.', $path)));
  }

  /********************************************************************************************************************/

  protected function _prepare($name, array &$config, $srcHome, $libHome, Bundle $bundle,
                              &$dependencies = array(), &$javascripts = array(), &$stylesheets = array(), &$templates = array(),
                              OutputInterface $output
  ) {
    $output->writeln("Loading components: $name");

    $jsFilter  = new CallablesFilter(function (BaseAsset $asset) {
      $asset->setContent($result = rtrim(trim($asset->getContent()), ';') . ";\n");
    });
    $ejsFilter = new CallablesFilter(function (BaseAsset $asset) {
      $asset->setContent($result = trim($asset->getContent()) . "\n");
    });
    /** @noinspection PhpUnusedLocalVariableInspection */
    $cssFilter = new CallablesFilter(function (BaseAsset $asset) {
      $asset->setContent($result = trim($asset->getContent()) . "\n");
    });

    $config['name']      = $name;
    $config['bundle']    = $bundle->getName();
    $config['namespace'] = $bundle->getNamespace();

    $dependencies = array();
    $output->writeln("  Dependencies");
    if (!empty($config['dependencies'])) {
      foreach ($config['dependencies'] as $href) {
        if (preg_match('#^@(?<bundle>\w+)(?<path>.*)\.(?<fext>\w+)$#', $href, $matches)) {
          $home  = $this->_kernel()->getBundle($matches['bundle'])->getPath();
          $asset = $this->_load($bundle, $matches['path'], $home, $matches['fext'], $jsFilter, $output);
        } else {
          $asset = $this->_load($bundle, $href, null, null, $jsFilter, $output);
          try {
            $output->write(sprintf("    %s <info>%s</info>", 'Exporting', $asset->getSourcePath()));
            $dstPath = $libHome . DIRECTORY_SEPARATOR . $asset->getSourcePath();
            $this->_write($dstPath, $asset->dump($jsFilter));
            $output->writeln(" <info>Done</info>");
          } catch (\Exception $ex) {
            $output->writeln(" <error>Fail</error>");
          }
          $file  = $libHome . DIRECTORY_SEPARATOR . $asset->getSourcePath();
          $asset = new FileAsset($file, $jsFilter, $libHome, $asset->getSourcePath());
        }
        $dependencies[] = $asset;
      }
    }

    $javascripts = array();
    $output->writeln("  Javascripts");
    if (!empty($config['javascripts'])) {
      foreach ($config['javascripts'] as $path) {
        $asset = $this->_load($bundle, $path, $srcHome, 'js', $jsFilter, $output);
        if ($this->_isReformat) {
          $this->_export($asset, $srcHome, null, 'js', $output);
        }
        $javascripts[] = $asset;
      }
    }

    $stylesheets = array();
//    $output->writeln("  Stylesheets");
//    if (!empty($config['stylesheets'])) {
//      foreach ($config['stylesheets'] as $path) {
//        $asset = $this->_load($bundle, $path, $srcHome, 'css', $cssFilter, $output);
//        if ($this->_isReformat) {
//          $this->_export($asset, $srcHome, null, 'css', $output);
//        }
//        $stylesheets[] = $asset;
//      }
//    }

    $templates = array();
    $output->writeln("  Templates");
    if (!empty($config['templates'])) {

      foreach ($config['templates'] as $path) {
        $asset = $this->_load($bundle, $path, $srcHome, 'ejs', $ejsFilter, $output);
        if ($this->_isReformat) {
          $this->_export($asset, $srcHome, null, 'ejs', $output);
        }
        $templates[] = $asset;
      }

    }

    $output->writeln("Components loaded.\n");
  }

  /********************************************************************************************************************/

  protected function _minifyComponents($name, array $config, $dstHome,
                                       array $dependencies, array $javascripts, array $stylesheets, array $templates,
                                       OutputInterface $output
  ) {
    $output->writeln("Minifying components: $name");

    foreach ($dependencies as $asset) {
      $this->_minify($asset, $dstHome, $this->_minFilter(), $output);
    }

    foreach ($javascripts as $asset) {
      $this->_minify($asset, $dstHome, $this->_minFilter(), $output);
    }
//    foreach ($stylesheets as $asset) {
//      $this->_minify($asset, $dstHome, $this->_minFilter(), $output);
//    }
    foreach ($templates as $asset) {
      $this->_export($asset, $dstHome, null, 'ejs', $output);
    }

    $output->writeln("Components minified.\n");
  }

  protected function _bundleComponents($name, array $config, $outHomeJS, $outHomeSS, $outHomeTF,
                                       array $dependencies, array $javascripts, array $stylesheets, array $templates,
                                       OutputInterface $output
  ) {
    $output->writeln("Packaging components: $name");

    $output->writeln("  DEV");
    $dstDev = implode(DIRECTORY_SEPARATOR, array($outHomeJS, sprintf(
      '%s-%s.js', $config['name'], $config['version']
    )));
    $this->_write($dstDev);
    foreach ($dependencies as $asset) {
      $this->_bundle($asset, $dstDev, null, $output);
    }
    foreach ($javascripts as $asset) {
      $this->_bundle($asset, $dstDev, null, $output);
    }

    if ($this->_isMinify) {
      $output->writeln("  MIN");
      $dstMin = implode(DIRECTORY_SEPARATOR, array($outHomeJS, sprintf(
        '%s-%s.min.js', $config['name'], $config['version']
      )));
      $this->_write($dstMin);
      foreach ($dependencies as $asset) {
        $this->_bundle($asset, $dstMin, $this->_minFilter(), $output);
      }
      foreach ($javascripts as $asset) {
        $this->_bundle($asset, $dstMin, $this->_minFilter(), $output);
      }
    }

    if ($this->_isTplEmbed) {
      $dstTpl = implode(DIRECTORY_SEPARATOR, array($outHomeTF, sprintf(
        '%s-%s.html.twig', $config['name'], $config['version']
      )));

      if (preg_match('#^(?<prefix>\w+)\\\\(\w+\\\\)*\w+Bundle$#', $config['namespace'], $matches)) {
        $prefix = $matches['prefix'];
      } else {
        $prefix = 'beats';
      }
      $this->_write($dstTpl);
      foreach ($templates as $asset) {
        $this->_bundle($asset, $dstTpl, $this->_tplFilter($prefix), $output);
      }
    }
    if ($this->_isTplExport) {
      foreach ($templates as $asset) {
        $this->_export($asset, $outHomeJS, null, 'ejs', $output);
      }
    }

    $output->writeln("Components bundled.\n");
  }

  /*********************************************************************************************************************/

  protected function _readConfig(Bundle $bundle, $home) {
    $file  = $home . DIRECTORY_SEPARATOR . self::FILE_BUILD;
    $asset = new FileAsset($file, array(), $home, self::FILE_BUILD);
    $this->_observe($bundle, $file, $asset);
    return Yaml::parse($file);
  }

  protected function _buildBundle(Bundle $bundle, InputInterface $input, OutputInterface $output) {
    $root = realpath($bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources');
    if (empty($root)) {
      return;
    }
    $home = realpath($root . DIRECTORY_SEPARATOR . 'engine');
    if (empty($home)) {
      return;
    }
    $output->writeln(sprintf("Building Beats MX: <info>%s</info>\n", $bundle->getName()));

    $configs = $this->_readConfig($bundle, $home);

    $srcHome   = implode(DIRECTORY_SEPARATOR, array($home, 'src'));
    $binHome   = implode(DIRECTORY_SEPARATOR, array($home, 'bin'));
    $libHome   = implode(DIRECTORY_SEPARATOR, array($home, 'lib'));
    $outHomeJS = implode(DIRECTORY_SEPARATOR, array($root, 'public', 'js', 'beatsmx'));
    $outHomeSS = implode(DIRECTORY_SEPARATOR, array($root, 'public', 'css', 'beatsmx'));
    $outHomeTS = implode(DIRECTORY_SEPARATOR, array($root, 'views', 'beatsmx'));

    $fs = $this->_fsal()->fs();
    $fs->mkdir(array(
      $binHome, $libHome, $outHomeJS, $outHomeSS, $outHomeTS
    ));
    $fs->remove(array(
      $binHome, $outHomeJS, $outHomeSS, $outHomeTS
    ));

    foreach ($configs as $name => $config) {
      $this->_prepare($name, $config, $srcHome, $libHome, $bundle,
        $dependencies, $javascripts, $stylesheets, $templates,
        $output
      );

      if ($this->_isMinify) {
        $this->_minifyComponents($name, $config, $binHome,
          $dependencies, $javascripts, $stylesheets, $templates,
          $output
        );
      }
      $this->_bundleComponents($name, $config, $outHomeJS, $outHomeSS, $outHomeTS,
        $dependencies, $javascripts, $stylesheets, $templates,
        $output
      );
    }

  }

  /********************************************************************************************************************/

  private function _minify(BaseAsset $asset, $dstHome, $filter = null, OutputInterface $output) {
    if (empty($filter)) {
      $filter = $this->_minFilter();
    }
    $output->writeln(sprintf("    %s <info>%s</info>", 'Minifying', $asset->getSourcePath()));
    $dstPath = $dstHome . DIRECTORY_SEPARATOR . $asset->getSourcePath() . '.min.js';
    $this->_write($dstPath, $asset->dump($filter));
  }

  private function _export(BaseAsset $asset, $dstHome, $filter = null, $fext = 'ejs', OutputInterface $output) {
    $output->writeln(sprintf("    %s <info>%s</info>", 'Exporting', $asset->getSourcePath()));
    $dstPath = $dstHome . DIRECTORY_SEPARATOR . $asset->getSourcePath() . '.' . $fext;
    $this->_write($dstPath, $asset->dump($filter));
  }

  private function _import(BaseAsset $asset, $srcHome, $filters = null, OutputInterface $output) {
    $file  = $srcHome . DIRECTORY_SEPARATOR . $asset->getSourcePath();
    $asset = new FileAsset($file, $filters, $srcHome, $asset->getSourcePath());
    return $asset;
  }

  private function _bundle(AssetInterface $asset, $dstPath, $filter = null, OutputInterface $output) {
    $output->writeln(sprintf("    %s <info>%s</info>", 'Packaging', $asset->getSourcePath()));
    $this->_write($dstPath, $asset->dump($filter), true);
  }

  /*********************************************************************************************************************/

  private function _write($path, $content = '', $append = false) {
    $this->_fsal()->fs()->mkdir(dirname($path));
    file_put_contents($path, $content, $append ? FILE_APPEND : null);
  }

  private function _load(Bundle $bundle, $path, $srcHome, $fext, $filter = null, OutputInterface $output) {
    $output->writeln(sprintf("    %s <info>%s</info>", 'Loading', $path));
    $filters = empty($filter) ? array() : array($filter);

    if (empty($srcHome)) {
      $file  = $path;
      $asset = new HttpAsset($file, $filters);
    } else {
      $file  = $srcHome . DIRECTORY_SEPARATOR . $path . '.' . $fext;
      $asset = new FileAsset($file, $filters, $srcHome, $path);

      $this->_observe($bundle, $file, $asset);
    }

    return $asset;
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
  private $_isTplEmbed = true;
  private $_isTplExport = true;
  private $_isDependencies = false;
  private $_isReformat = false;

  protected $_bundles;

  protected $_watcher = array();

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->_isMinify       = !$input->getOption('no-minify');
    $this->_isTplEmbed     = !$input->getOption('no-embedded');
    $this->_isTplExport    = !$input->getOption('no-exported');
    $this->_isDependencies = !$input->getOption('no-dependencies');
    $this->_isReformat     = $input->getOption('reformat');

    if ($name = $input->getArgument('bundle')) {
      $this->_bundles = array($name => $this->_kernel()->getBundle($name));
    } else {
      $this->_bundles = $this->_kernel()->getBundles();
    }

    if ($input->getOption('watch')) {
      $this->_isMinify       = false;
      $this->_isReformat     = false;
      $this->_isDependencies = true;

      $this->_watch($input, $output);
    } else {
      $this->_build($input, $output);
    }

  }

  /*********************************************************************************************************************/

  protected function _build(InputInterface $input, OutputInterface $output) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    foreach ($this->_bundles as $name => $bundle) {
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
