<?php

class cleverFilesystemDiskAdapter extends cleverFilesystemAdapter
{
  public function cache($cache_dir, $filename, $force)
  {
    return $this->root.DIRECTORY_SEPARATOR.$filename;
  }

  public function chmod($path, $permission)
  {
    $this->checkExists($path);
    return chmod($this->root.DIRECTORY_SEPARATOR.$path, $permission);
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
      copy($this->root.DIRECTORY_SEPARATOR.$from, $this->root.DIRECTORY_SEPARATOR.$to);
    }
  }

  public function exists($path)
  {
    return file_exists($this->root.DIRECTORY_SEPARATOR.$path);
  }

  public function fileperms($path)
  {
    clearstatcache(true, $this->root.DIRECTORY_SEPARATOR.$path);
    $perms = fileperms($this->root.DIRECTORY_SEPARATOR.$path);

    if (false !== $perms)
    {
      $perms = substr(sprintf('%o', $perms), -4);
    }

    return $perms;
  }

  public function getSize($filepath)
  {
    $this->checkExists($filepath);
    return filesize($this->root.DIRECTORY_SEPARATOR.$filepath);
  }

  public function isDir($path)
  {
    return is_dir($this->root.DIRECTORY_SEPARATOR.$path);
  }

  public function isFile($path)
  {
    return is_file($this->root.DIRECTORY_SEPARATOR.$path);
  }

  public function listDir($path, $options = array())
  {
    $this->checkExists($path);
    $this->checkIsDir($path);
    $return = scandir($this->root.DIRECTORY_SEPARATOR.$path);
    $return = array_filter($return, array($this, 'removeDirsFromList'));
    sort($return);
    return $return;
  }

  public function mkdir($path)
  {
    if (!$this->exists($path))
    {
      if ('' != dirname($path) && $path != dirname($path))
      {
        $this->mkdir(dirname($path));
      }

      mkdir($this->root.DIRECTORY_SEPARATOR.$path);
    }
  }

  public function read($filepath)
  {
    if ($this->exists($filepath) && $this->isFile($filepath))
    {
      $return = file_get_contents($this->root.DIRECTORY_SEPARATOR.$filepath);
    }
    else
    {
      $return = null;
    }

    return $return;
  }

  protected function removeDirsFromList($item)
  {
    return (('.' !== $item) && ('..' !== $item));
  }

  public function rename($from, $to)
  {
    rename($this->root.DIRECTORY_SEPARATOR.$from, $this->root.DIRECTORY_SEPARATOR.$to);
  }

  public function unlink($path)
  {
    $item = $this->root.DIRECTORY_SEPARATOR.$path;

    if (!file_exists($item))
    {
      return true;
    }

    if (is_dir($item))
    {
      foreach (scandir($item) as $entry)
      {
        if ($entry == '.' || $entry == '..') continue;

        if (!$this->unlink($path.DIRECTORY_SEPARATOR.$entry))
        {
          chmod($item.DIRECTORY_SEPARATOR.$entry, 0777);
          return $this->unlink($path.DIRECTORY_SEPARATOR.$entry);
        }
      }

      return rmdir($item);
    }
    else
    {
      return unlink($item);
    }
  }

  public function write($filepath, $data, $overwrite = true, $append = false)
  {
    if (!$this->exists($filepath) || (true === $overwrite))
    {
      if (true === $append)
      {
        // append data to the existing content
        file_put_contents($this->root.DIRECTORY_SEPARATOR.$filepath, $data, FILE_APPEND);
      }
      else
      {
        // replace the existing content
        file_put_contents($this->root.DIRECTORY_SEPARATOR.$filepath, $data);
      }
    }
    else
    {
     throw new sfException(sprintf('The file "%s" exists, and can not be overwritten.', $filepath));
    }
  }
}