<?php
namespace BeatsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends ServiceCommand {

  /*********************************************************************************************************************/
  protected function configure() {
    $name = 'beats:clean';
    $this
      ->setName($name)
      ->addOption('no-cache', 'C', InputOption::VALUE_NONE, 'Skip cache:clear')
      ->addOption('no-build', 'B', InputOption::VALUE_NONE, 'Skip beats:fe:build')
      ->addOption('no-minify', 'M', InputOption::VALUE_NONE, 'Whether to minify the build')
      ->setDescription('Performs all Symfony2 and Beats cleanup and build processes ')
      ->setHelp(<<<EOT
EOT
      );
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!$input->getOption('no-cache')) {
      $this->_command('cache:clear', array(), $input, $output);
    }
    if (!$input->getOption('no-build')) {
      $this->_command('beats:fe:build', array(
        '--no-minify' => $input->getOption('no-minify'),
      ), $input, $output);
    }
    $this->_command('assets:install', array(
      'target'    => dirname($this->_kernel()->getRootDir()) . DIRECTORY_SEPARATOR . 'web',
      '--symlink' => true
    ), $input, $output);
    $this->_command('assetic:dump', array(), $input, $output);
  }

}
