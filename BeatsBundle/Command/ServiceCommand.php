<?php
namespace BeatsBundle\Command;

use BeatsBundle\DBAL\AbstractDBAL;
use BeatsBundle\FSAL\AbstractFSAL;
use BeatsBundle\FSAL\Imager;
use BeatsBundle\Model\AbstractModel;
use BeatsBundle\Service\Mailer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface as Templater;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\SecurityContextInterface;

abstract class ServiceCommand extends ContainerAwareCommand {

  /*********************************************************************************************************************/

  /**
   * @return Kernel
   */
  final protected function _kernel() {
    return $this->getContainer()->get('kernel');
  }

  /**
   * @return SecurityContextInterface
   */
  final protected function _security() {
    return $this->getContainer()->get('security.context');
  }

  /**
   * @return LoggerInterface
   */
  final protected function _logger() {
    return $this->getContainer()->get('logger');
  }

  /********************************************************************************************************************/

  /**
   * @return Templater
   */
  final protected function _templater() {
    return $this->getContainer()->get('templating');
  }

  /**
   * @return Mailer
   */
  final protected function _mailer() {
    return $this->getContainer()->get('beats.mailer');
  }

  /*********************************************************************************************************************/

//  /**
//   * @return Validator
//   */
//  final protected function _validator() {
//    return $this->getContainer()->get('beats.validation.validator');
//  }

  /*********************************************************************************************************************/

  /**
   * @return AbstractFSAL
   */
  final protected function _fsal() {
    return $this->getContainer()->get('beats.fsal.domfs');
  }

  /**
   * @return Imager
   */
  final protected function _imager() {
    return $this->getContainer()->get('beats.fsal.imager');
  }

  /**
   * @param $name
   * @throws \Exception
   * @return AbstractDBAL
   */
  final protected function _dbal($name) {
    return $this->getContainer()->get('beats.dbal.' . $name);
  }

  /**
   * Returns a business logic model
   * @param null|string $bundle
   * @param string $name
   * @return AbstractModel
   */
  final protected function _model($name, $bundle = null) {
    return $this->getContainer()->get(empty($bundle) ? "beats.model.$name" : "beats.model.$bundle.$name");
  }

  /********************************************************************************************************************/

  /**
   * @return DialogHelper
   */
  final protected function _dialog() {
    return $this->getHelperSet()->get('dialog');
  }

  /**
   * @return FormatterHelper
   */
  final protected function _formatter() {
    return $this->getHelperSet()->get('formatter');
  }

  protected function _command($name, array $arguments = array(), InputInterface $input, OutputInterface $output) {
    $command = $this->getApplication()->find($name);
    $input   = new ArrayInput(array_merge($arguments, array(
      'command'             => $name,

      '--quiet'             => $input->getOption('quiet'),
      '--ansi'              => $input->getOption('ansi'),
      '--no-ansi'           => $input->getOption('no-ansi'),
      '--no-interaction'    => $input->getOption('no-interaction'),

      '--process-isolation' => $input->getOption('process-isolation'),
      '--no-debug'          => $input->getOption('no-debug'),
      '--env'               => $input->getOption('env'),
    )));

    return $command->run($input, $output);
  }

  /********************************************************************************************************************/

}