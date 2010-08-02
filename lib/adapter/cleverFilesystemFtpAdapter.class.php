<?php

class cleverFilesystemFtpAdapter extends cleverFilesystemAdapter
{
  protected $cache = array();

  /**
   * Change remote directory,
   * return true if success,
   * throw exception and return false if failed.
   *
   * @param string $directory
   *
   * @return boolean
   */
  protected function cd($directory)
  {
    if (!ftp_chdir($this->connection, $directory))
    {
      throw new sfException('{cleverFilesystemFtpAdapter} changing remote drectory failed');
      return false;
    }

    return true;
  }

  public function chmod($path, $permission)
  {
    $this->checkExists($path);
    return ftp_chmod($this->connection, $permission, $this->root.DIRECTORY_SEPARATOR.$path);
  }

  protected function connect()
  {
    // connect to the ftp
    $this->connection = ftp_connect($this->host, $this->port);

    // login the user
    if ($this->connection)
    {
      $login_result = ftp_login($this->connection, $this->username, $this->password);
    }

    // check connection
    if (!$this->connection || !$login_result)
    {
      throw new sfException(sprintf(
	    '{cleverFilesystemFtpAdapter} FTP connection to host "%s" with username "%s" (password: %s) failed',
        $this->host,
        $this->username,
        ($this->password ? 'yes' : 'no')
      ));
      return false;
    }

    return true;
  }

  public function copy($from, $to)
  {
    if ($this->isDir($from))
    {
      $contents = $this->listDir($from, array('checkExistence' => false, 'force' => true));

      foreach ($contents as $item_from)
      {
        $item_to = $to.'/'.$item_from;

        if ('' != $from)
        {
          $item_from = $from.'/'.$item_from;
        }

        $this->copy($item_from, $item_to);
      }
    }
    else
    {
      $content = $this->read($from);
      $this->write($to, $content);
    }
  }

  /**
   * Close ftp connection,
   * return true if success,
   * throw exception and return false if failed.
   *
   * @return boolean
   */
  public function disconnect()
  {
    if (!ftp_close($this->connection))
    {
      throw new sfException('{cleverFilesystemFtpAdapter} closing ftp connection failed');
      return false;
    }

    return true;
  }

  public function exists($path)
  {
    if (isset($this->existence_cache[$this->root.DIRECTORY_SEPARATOR.$path]))
    {
    //  return $this->existence_cache[$this->root.DIRECTORY_SEPARATOR.$path];
    }

    if ('' == $path)
    {
      $path = $this->root;
      $requested_path = $path;
      $pos = strrpos($this->root, DIRECTORY_SEPARATOR);

      if (false !== $pos)
      {
        $parent_path = substr($this->root, 0, $pos);
      }
      else
      {
        $parent_path = '';
      }

      $parent_content = ftp_nlist($this->connection, $parent_path);
      $this->listDir('', array('checkExistence' => false));
    }
    else
    {
      $requested_path = $path;
      $path = $this->root.DIRECTORY_SEPARATOR.$path;
      $pos = strrpos($path, DIRECTORY_SEPARATOR);

      if ($pos > strlen($this->root) + 1)
      {
        $parent_path = substr($path, strlen($this->root) + 1, $pos - strlen($this->root) - 1);
        $requested_path = substr($path, $pos + 1);
      }
      else
      {
        $parent_path = '';
      }

      $parent_content = $this->listDir($parent_path, array('checkExistence' => false, 'force' => true));
      $parent_content = $parent_content ? $parent_content : array();
    }

    $result = ('' === $path) || in_array($requested_path, $parent_content);

    if ($result)
    {
      $this->existence_cache[$path] = true;
    }

    return $result;
  }

  public function getSize($filepath)
  {
    $this->checkExists($filepath);
    return ftp_size($this->connection, $this->root.'/'.$filepath);
  }

  protected function initialize($options)
  {
    parent::initialize($options);
    $this->host = $options['host'];
    $this->port = isset($options['port']) ? $options['port'] : 21;
    $this->username = isset($options['username']) ? $options['username'] : 'anonymous';
    $this->password = isset($options['password']) ? $options['password'] : '';
    $this->connect();
  }

  public function isDir($path)
  {
    $return = @ftp_chdir($this->connection, '/'.$this->root.'/'.$path);

    if ($return)
    {
      ftp_chdir($this->connection, '/');
    }

    return $return;
  }

  public function isFile($path)
  {
    return $this->exists($path);
  }

  public function listDir($path, $options = array())
  {
    if (!isset($options['checkExistence']) || (false !== $options['checkExistence']))
    {
      $this->checkExists($path);
      $this->checkIsDir($path);
    }

    if (!isset($this->cache[$this->root.DIRECTORY_SEPARATOR.$path])
        || (isset($options['force']) && true === $options['force']))
    {
      $this->cache[$this->root.DIRECTORY_SEPARATOR.$path] = ftp_nlist($this->connection, $this->root.DIRECTORY_SEPARATOR.$path);
    }

    return $this->cache[$this->root.DIRECTORY_SEPARATOR.$path];
  }

  public function mkdir($path)
  {
    if (!$this->exists($path) && $path != '.' && $path != '')
    {
      if ('.' != dirname($path) && '' != dirname($path))
      {
        $this->mkdir(dirname($path));
      }

      if (!ftp_mkdir($this->connection, DIRECTORY_SEPARATOR.$this->root.DIRECTORY_SEPARATOR.$path))
      {
        throw new sfException(sprintf('Could not create directory "%s"', $this->root.DIRECTORY_SEPARATOR.$path));
      }
    }
  }

  public function read($filepath)
  {
    if ($this->exists($filepath))
    {
      $tmpfile = tempnam('/tmp', 'cfs');
      ftp_get($this->connection, $tmpfile, $this->root.DIRECTORY_SEPARATOR.$filepath, FTP_BINARY);
      $return = file_get_contents($tmpfile);
      unlink($tmpfile);
    }
    else
    {
      $return = null;
    }

    return $return;
  }

  public function rename($from, $to)
  {
    return ftp_rename(
      $this->connection,
      $this->root.DIRECTORY_SEPARATOR.$from,
      $this->root.DIRECTORY_SEPARATOR.$to
    );
  }

  public function unlink($path)
  {
    if ($this->isDir($path))
    {
      $contents = $this->listDir($path, array('checkExistence' => false, 'force' => true));

      foreach ($contents as $item)
      {
        if ('' != $path)
        {
          $item = $path.'/'.$item;
        }

        $this->unlink($item);
      }

      return ftp_rmdir($this->connection, '/'.$this->root.'/'.$path);
    }
    else
    {
      return ftp_delete($this->connection, '/'.$this->root.'/'.$path);
    }
  }

  public function write($filepath, $data, $overwrite = true)
  {
    if (!$this->exists($filepath) || (true === $overwrite))
    {
      $path = $this->root.DIRECTORY_SEPARATOR.$filepath;
      $tmpfile = tempnam('/tmp', 'cfs');
      file_put_contents($tmpfile, $data);
      ftp_put($this->connection, $path, $tmpfile, FTP_BINARY);
      unlink($tmpfile);

      $pos = strrpos($path, DIRECTORY_SEPARATOR);

      if ($pos > strlen($this->root) + 1)
      {
        $parent_path = substr($path, strlen($this->root) + 1, $pos - strlen($this->root) - 1);
      }
      else
      {
        $parent_path = '';
      }

      $this->listDir($parent_path, array('force' => true, 'checkExistence' => false));
    }
    else
    {
     throw new sfException(sprintf('The file "%s" exists, and can not be overwritten.', $filepath));
    }
  }
}