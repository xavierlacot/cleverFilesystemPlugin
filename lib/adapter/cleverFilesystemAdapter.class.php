<?php

abstract class cleverFilesystemAdapter
{
  public function __construct($options = array())
  {
    $default = array('root' => '');
    $options = array_merge($default, $options);
    $this->initialize($options);
  }

  public function cache($cache_dir, $filename, $force)
  {
    $cache_filename = $cache_dir.DIRECTORY_SEPARATOR.$filename;

    if ($force || !file_exists($cache_filename))
    {
      $file = $this->read($filename);

      if (!is_null($file))
      {
        // create the cache directory, if necessary
        $cache_directory = $cache_dir;
        if (!file_exists($cache_directory))
        {
          mkdir($cache_directory);
        }

        $directory = dirname($cache_filename);
        $directories = explode(DIRECTORY_SEPARATOR, substr($directory, strlen($cache_directory) + 1));

        foreach ($directories as $directory)
        {
          $cache_directory .= DIRECTORY_SEPARATOR.$directory;

          if (!file_exists($cache_directory))
          {
            mkdir($cache_directory);
          }
        }

        // save the file in the cache
        file_put_contents($cache_filename, $file);
      }
    }

    if (!file_exists($cache_filename))
    {
      return false;
    }
    else
    {
      return $cache_filename;
    }
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

  protected function initialize($options)
  {
    $this->root = $options['root'];
  }

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

  abstract function unlink($path);
  abstract function write($filepath, $data, $overwrite = true, $append = false);
}