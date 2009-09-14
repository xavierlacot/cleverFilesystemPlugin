<?php
class cleverFilesystemCheckTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
      new sfCommandArgument('filesystem', sfCommandArgument::REQUIRED, 'The filesystem configuration name, see app.yml'),
    ));
    
    $this->aliases = array('filesystem-check');
    $this->namespace = 'filesystem';
    $this->name = 'check';
    $this->briefDescription = 'Checks a filesystem configuration';

    $this->detailedDescription = <<<EOF
The [filesystem:check|INFO] task checks for the validity of a filesystem configuration:

  [./symfony filesystem:check|INFO]

EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance($this->configuration);
    
    // load the configuration
    $configuration = cleverFilesystem::getConfiguration($arguments['filesystem']);
    
    if (is_null($configuration))
    {
      throw new sfException(sprintf('The filesystem configuration "%s" does not exist. Check the file app.yml!', $arguments['filesystem']));
    }

    // create the filesystem
    $filesystem = cleverFilesystem::getInstance($configuration);

    if (is_null($filesystem))
    {
      throw new sfException(sprintf('Could not create the filesystem "%s"!', $arguments['filesystem']));
    }

    if (!$filesystem->exists(''))
    {
      throw new sfException(sprintf('The filesystem "%s" does not seem to be reachable.', $arguments['filesystem']));
    }

    $this->log('The filesystem seems to be valid.');
  }
}