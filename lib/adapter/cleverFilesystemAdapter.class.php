<?php

abstract class cleverFilesystemAdapter
{
  public function __construct($options = array())
  {
    $default = array('root' => '');
    $options = array_merge($default, $options);
    $this->initialize($options);
  }

  protected function checkIsDir($path)
  {
    if (!$this->isDir($path))
    {
      throw new sfException(sprintf('The item "%s" is not a directory.', $path));
    }
  }

  protected function checkIsFile($path)
  {
    if (!$this->isFile($path))
    {
      throw new sfException(sprintf('The item "%s" is not a regular file.', $path));
    }
  }

  protected function checkExists($path)
  {
    if (!$this->exists($path))
    {
      throw new sfException(sprintf('The item "%s" does not exist.', $path));
    }
  }

  public function chmod($path, $permission)
  {
    return true;
  }

  abstract function copy($from, $to);
  abstract function exists($path);

  public function fileperms($path)
  {
    return false;
  }

  abstract function getSize($filepath);
  abstract function isDir($path);
  abstract function isFile($path);
  abstract function listdir($path);
  abstract function mkdir($path);
  abstract function read($filepath);

  protected function rename($from, $to)
  {
    $this->copy($from, $to);
    $this->unlink($from);
  }

  protected function initialize($options)
  {
    $this->root = $options['root'];
  }

  abstract function unlink($path);
  abstract function write($filepath, $data, $overwrite = true);
}