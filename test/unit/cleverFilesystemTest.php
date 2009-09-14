<?php
$root_dir = '/tmp/cfs_test';
$filesystems_configs = array(
  'Disk' => array(
    'root'      => $root_dir, 
    'cache_dir' => '/tmp'
  ),
  'Ftp'  => array(
    'root'      => 'michel_ftp_test', 
    'cache_dir' => '/tmp',
    'host'      => '127.0.0.1',
    'password'  => 'test',
    'username'  => 'michel'
  )
);

// initializes testing framework
$sf_root_dir = realpath(dirname(__FILE__).'/../../../../');
require_once($sf_root_dir.'/test/bootstrap/unit.php');

// start tests
$t = new lime_test(30 * count($filesystems_configs), new lime_output_color());

exec('rm -Rf '.$root_dir);

foreach ($filesystems_configs as $type => $filesystem_config)
{
  $t->diag(sprintf('testing "%s" filesystem', $type));

  # filesystem creation
  $fs = new cleverFilesystem($type, $filesystem_config);

  if ($fs->exists(''))
  {
    $fs->unlink('');
  }

  if (!$fs->exists(''))
  {
    $fs->mkdir('');
  }

  $t->ok($fs->exists(''), 'able to access the root directory');

  # dirs and file creation
  $fs->mkdir('subdir');
  $t->ok($fs->exists('subdir'), 'able to create a directory');

  $fs->write('subdir/toto.txt', 'Hello, here is toto');
  $t->ok($fs->exists('subdir/toto.txt'), 'able to create a file in a subdirectory');

  # file existance test
  $t->ok(true === $fs->exists('subdir/toto.txt'), 'exists() returns true if the file exists');
  $t->ok(false === $fs->exists('/non/existant/file/toto.txt'), 'exists() returns false if the file does not exist');
  $t->ok(true === $fs->exists('subdir'), 'exists() returns true if the directory exists');
  $t->ok(false === $fs->exists('/non/existant/dir'), 'exists() returns false if the directory does not exist');

  # file copy
  $fs->mkdir('an_other_dir');
  $fs->copy('subdir/toto.txt', 'an_other_dir/toto.txt');
  $t->ok($fs->exists('an_other_dir/toto.txt'), 'copy() copies a file');
  $t->ok($fs->exists('subdir/toto.txt'), 'copy() does not destruct the initial file');

  $fs->copy('subdir', 'path/to/a/non/existing/new/dir');
  $t->ok($fs->exists('path/to/a/non/existing/new/dir/toto.txt'), 'copy() copies a directory and its content');
  $t->ok($fs->exists('subdir/toto.txt'), 'copy() does not destruct the initial directory');

  # getSize() test
  $t->ok(19 === $fs->getSize('subdir/toto.txt'), 'getSize() returns the size of a file');

  # isDir() test
  $t->ok(false === $fs->isDir('an_other_dir/toto.txt'), 'isDir() returns false when testing a file');
  $t->ok(true === $fs->isDir('an_other_dir'), 'isDir() returns true when testing a directory');

  # isFile() test
  $t->ok(true === $fs->isFile('an_other_dir/toto.txt'), 'isFile() returns true when testing a file');
  $t->ok(false === $fs->isFile('/an_other_dir'), 'isFile() returns false when testing a directory');

  # listdir() test
  $t->ok(array('toto.txt') === $fs->listDir('an_other_dir'), 'listDir() lists the items in a directory');

  $fs->mkdir('new');
  $fs->mkdir('new/dir');
  $t->ok(array() === $fs->listDir('new/dir'), 'listDir() returns an empty array when listing an empty dir');

  # mkdir() test
  $before = (false === $fs->exists('test'));
  $fs->mkdir('test');
  $t->ok($before && (true === $fs->exists('test')), 'mkdir() creates a new directory');

  $before = (false === $fs->exists('inexistant'));
  $fs->mkdir('inexistant/path');
  $t->ok($before && (true === $fs->exists('inexistant/path')), 'mkdir() is able to create a whole path at once');

  # read() test
  $t->ok('Hello, here is toto' === $fs->read('/subdir/toto.txt'), 'read() returns the content of a file');
  $t->ok(null === $fs->read('/non/existant/file/toto.txt'), 'read() returns false when trying to read an inexistant file');
  $t->ok(null === $fs->read('/non/existant/dir'), 'read() returns false when trying to read an inexistant directory');

  # rename() test
  $fs->rename('subdir/toto.txt', 'subdir/tutu.txt');
  $t->ok($fs->exists('subdir/tutu.txt'), 'rename() changes the location of a file');
  $t->ok(!$fs->exists('subdir/toto.txt'), 'after calling rename(), the file isn\'t in its initial location anymore');

  $fs->rename('subdir/tutu.txt', 'real/new/sub/dir/new-file.txt');
  $t->ok($fs->exists('real/new/sub/dir/new-file.txt') && !$fs->exists('subdir/tutu.txt'), 'rename() is able to move a file to a new directory, which gets created dynamically');

  $fs->rename('real/new/sub', 'other/real/new/sub/dir');
  $t->ok(
    $fs->exists('other/real/new/sub/dir') && !$fs->exists('real/new/sub'), 
    'rename() is able to move a directory, even to a new one created dynamically'
  );
  $t->ok(
    $fs->exists('other/real/new/sub/dir/dir/new-file.txt') && !$fs->exists('real/new/sub/dir/new-file.txt'), 
    'rename() also moves the content of a renamed directory'
  );

  # unlink() test
  $fs->unlink('other/real/new/sub/dir/dir/new-file.txt');
  $t->ok(!$fs->exists('other/real/new/sub/dir/dir/new-file.txt'), 'unlink() deletes a file');

  $fs->write('real/new/sub/dir/toto.txt', 'Hello, here is toto');
  $before = (true === $fs->exists('real/new/sub/dir/toto.txt'));
  $fs->unlink('real');
  $t->ok($before
    && (false === $fs->exists('real/new')) 
    && (false === $fs->exists('real/new/sub/dir/toto.txt')), 
    'unlink() remove a directory and its content'
  );
}