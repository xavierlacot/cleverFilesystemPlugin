<?php

class cleverFilesystem
{
  protected $cache_dir;

  public static function getInstance($configuration)
  {
    if (is_string($configuration))
    {
      return self::getInstance(self::getConfiguration($configuration));
    }

    if (isset($configuration['type']))
    {
      return new cleverFilesystem($configuration['type'], $configuration);
    }
    else
    {
      throw new sfException('The configuration is missing a required key "type".');
    }
  }

  public static function getConfiguration($name = null)
  {
    $return = null;
    $configuration = sfConfig::get('app_cleverFilesystemPlugin_filesystems');

    if (is_null($name))
    {
      $return = $configuration;
    }
    else
    {
      if (isset($configuration[$name]))
      {
        $return = $configuration[$name];
      }
    }

    return $return;
  }

  public function __construct($type, $options = array())
  {
    if (is_string($type))
    {
      $filesystem_adapter = sprintf('cleverFilesystem%sAdapter', ucfirst($type));
      $default = array('root' => '', 'cache_dir' => sys_get_temp_dir());
      $this->options = array_merge($default, $options);
      $this->cache_dir = $this->options['cache_dir'];

      if (!class_exists($filesystem_adapter))
      {
        throw new sfException(sprintf('Unknown filesystem type "%s"', $type));
      }

      $this->adapter = new $filesystem_adapter($this->options);
    }
    elseif ($type instanceof cleverFilesystemAdapter)
    {
      $this->adapter = $type;
    }
    else
    {
      throw new sfException('The type of a cleverFileSystem might only be a string or a cleverFileSystem instance.');
    }
  }

  public function __destruct()
  {
    unset($this);
  }

  public function __call($name, $arguments)
  {
    return call_user_func_array(array($this->adapter, $name), $arguments);
  }

  /**
   * Copies a file from the filesystem to the local file cache. Returns the
   * location of the cache file.
   *
   * @param string $filename  absolute virtual path to the file, eg. dir1/subdir2/file.ext
   * @return string
   */
  public function cache($filename, $force = false)
  {
    $cache_filename = $this->cache_dir.DIRECTORY_SEPARATOR.$filename;

    if ($force || !file_exists($cache_filename))
    {
      $file = $this->read($filename);

      if (!is_null($file))
      {
        // create the cache directory, if necessary
        $cache_directory = $this->cache_dir;
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

    return $cache_filename;
  }

  public function chmod($path, $permission)
  {
    return $this->adapter->chmod($path, $permission);
  }

  public function copy($from, $to)
  {
    if ($this->exists($from))
    {
      if ($this->isDir($from))
      {
        $this->mkdir($to);
      }
      else
      {
        $this->mkdir(dirname($to));
      }

      return $this->adapter->copy($from, $to);
    }
    else
    {
      return false;
    }
  }

  public function exists($path)
  {
    return $this->adapter->exists($path);
  }

  public function getSize($filepath)
  {
    return $this->adapter->getSize($filepath);
  }

  public function getRoot()
  {
    return $this->options['root'];
  }

  public function isDir($path)
  {
    return $this->adapter->isDir($path);
  }

  public function isFile($path)
  {
    return $this->adapter->isFile($path);
  }

  public function listdir($path, $options = array())
  {
    return $this->adapter->listDir($path, $options);
  }

  public function mkdir($path)
  {
    if (!$this->exists($path))
    {
      return $this->adapter->mkdir($path);
    }
  }

  public function read($filepath)
  {
    return $this->adapter->read($filepath);
  }

  public function rename($from, $to)
  {
    if ($this->exists($from))
    {
      $this->mkdir(dirname($to));
      return $this->adapter->rename($from, $to);
    }
    else
    {
      return false;
    }
  }

  public function unlink($path)
  {
    return $this->adapter->unlink($path);
  }

  public function write($filepath, $data, $overwrite = true)
  {
    if (!$this->exists($filepath) || (true === $overwrite))
    {
      $this->mkdir(dirname($filepath));
      return $this->adapter->write($filepath, $data, $overwrite);
    }
    else
    {
      throw new sfException(sprintf('The file "%s" exists, and can not be overwritten.', $filepath));
    }
  }
}