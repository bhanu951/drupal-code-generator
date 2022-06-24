<?php declare(strict_types=1);

namespace DrupalCodeGenerator\Helper\Dumper;

use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Helper\DumperOptions;
use DrupalCodeGenerator\IOAwareInterface;
use DrupalCodeGenerator\IOAwareTrait;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Asset dumper form generators.
 */
class Dumper extends Helper implements IOAwareInterface {

  use IOAwareTrait;

  public function __construct(private Filesystem $filesystem) {}

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'dumper';
  }

  /**
   * Dumps the generated code to file system.
   */
  public function dump(AssetCollection $assets, string $destination, DumperOptions $options): AssetCollection {

    $dumped_assets = new AssetCollection();
    $default_resolver = new Resolver($options, $this->io);

    // -- Directories.
    /** @var \DrupalCodeGenerator\Asset\Directory $asset */
    foreach ($assets->getDirectories() as $directory) {

      $directory_path = $destination . '/' . $directory->getPath();

      // Recreating directories makes no sense.
      if ($this->filesystem->exists($directory_path)) {
        $directory = $default_resolver($directory, $directory_path);
        if ($directory === NULL) {
          continue;
        }
      }

      if ($options->dryRun) {
        $this->io->title(($options->fullPath ? $directory_path : $directory->getPath()) . ' (empty directory)');
      }
      else {
        $this->filesystem->mkdir($directory_path, $directory->getMode());
        $dumped_assets[] = $directory;
      }

    }

    // -- Files.
    /** @var \DrupalCodeGenerator\Asset\File $file */
    foreach ($assets->getFiles() as $file) {

      $file_path = $destination . '/' . $file->getPath();

      if ($this->filesystem->exists($file_path)) {
        $resolver = $file->getResolver() ?? $default_resolver;
        $file = $resolver($file, $file_path);
        if ($file === NULL) {
          continue;
        }
      }

      // Nothing to dump.
      if ($file->getContent() === NULL) {
        continue;
      }

      if ($options->dryRun) {
        $this->io->title($options->fullPath ? $file_path : $file->getPath());
        $this->io->writeln($file->getContent(), OutputInterface::OUTPUT_RAW);
      }
      else {
        $this->filesystem->dumpFile($file_path, $file->getContent());
        $this->filesystem->chmod($file_path, $file->getMode());
        $dumped_assets[] = $file;
      }

    }

    // -- Symlinks.
    /** @var \DrupalCodeGenerator\Asset\Symlink $asset */
    foreach ($assets->getSymlinks() as $symlink) {

      $link_path = $destination . '/' . $symlink->getPath();

      if ($file_exists = $this->filesystem->exists($link_path)) {
        $symlink = $default_resolver($symlink, $link_path);
        if ($symlink === NULL) {
          continue;
        }
      }

      $target = $symlink->getTarget();

      if ($options->dryRun) {
        $this->io->title($options->fullPath ? $link_path : $symlink->getPath());
        $this->io->writeln('Symlink to ' . $target, OutputInterface::OUTPUT_RAW);
      }
      else {
        if ($file_exists) {
          $this->filesystem->remove($link_path);
        }
        if (!@\symlink($target, $link_path)) {
          throw new \RuntimeException('Could not create a symlink to ' . $target);
        }
        $this->filesystem->chmod($link_path, $symlink->getMode());
        $dumped_assets[] = $symlink;
      }

    }

    return $dumped_assets;
  }

}
