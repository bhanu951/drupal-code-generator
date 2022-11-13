<?php declare(strict_types = 1);

namespace DrupalCodeGenerator\Tests\Functional;

use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Asset\Directory;
use DrupalCodeGenerator\Asset\File;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Event\PostProcessEvent;
use DrupalCodeGenerator\Event\PreProcessEvent;
use DrupalCodeGenerator\Test\Functional\FunctionalTestBase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Test process events.
 */
final class ProcessEventTest extends FunctionalTestBase {

  /**
   * Working directory.
   */
  private readonly string $directory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->directory = \sys_get_temp_dir() . '/dcg_sandbox';
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    (new Filesystem())->remove($this->directory);
  }

  /**
   * Test callback.
   */
  public function testPreProcessEvent(): void {
    $application = $this->createApplication();
    $application->setAutoExit(FALSE);
    $application->add(self::createTestGenerator());

    $listener = static function (PreProcessEvent $event): void {
      $event->destination .= '/changed';
      /** @var \DrupalCodeGenerator\Asset\File $file */
      $file = $event->assets[0];
      $file->content($file->getContent() . \PHP_EOL . 'New content.');
      $event->assets[] = new File('new-file.txt');
      $event->assets[] = new Directory('extra/directory');
    };
    $application->getContainer()
      ->get('event_dispatcher')
      ->addListener(PreProcessEvent::class, $listener);

    $application->run(
      new StringInput('test -d ' . $this->directory),
      $output = new BufferedOutput(),
    );

    $expected_output = <<< 'TXT'
      Welcome to test generator!
      ––––––––––––––––––––––––––––

       The following directories and files have been created or updated:
      –––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––
       • extra/directory
       • example.txt
       • new-file.txt
      TXT;
    self::assertSame($expected_output, \trim($output->fetch()));

    self::assertDirectoryExists($this->directory . '/changed/extra/directory');
    self::assertSame(
      'Original content.' . \PHP_EOL . 'New content.',
      \file_get_contents($this->directory . '/changed/example.txt'),
    );
    self::assertFileExists($this->directory . '/changed/new-file.txt');
  }

  /**
   * Test callback.
   */
  public function testPostProcessEvent(): void {
    $application = $this->createApplication();
    $application->setAutoExit(FALSE);
    $application->add(self::createTestGenerator());

    $listener = function (PostProcessEvent $event): void {
      /** @var \DrupalCodeGenerator\Asset\File $file */
      $file = $event->assets[0];
      $path = $this->directory . '/' . $file->getPath();
      \file_put_contents($path, $file->getContent() . \PHP_EOL . 'New content.');
      $event->assets[] = new File('new-file.txt');
      $event->assets[] = new Directory('extra/directory');
    };
    $application->getContainer()
      ->get('event_dispatcher')
      ->addListener(PostProcessEvent::class, $listener);

    $application->run(
      new StringInput('test -d ' . $this->directory),
      $output = new BufferedOutput(),
    );

    $expected_output = <<< 'TXT'
      Welcome to test generator!
      ––––––––––––––––––––––––––––

       The following directories and files have been created or updated:
      –––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––
       • extra/directory
       • example.txt
       • new-file.txt
      TXT;
    self::assertSame($expected_output, \trim($output->fetch()));
    self::assertSame(
      'Original content.' . \PHP_EOL . 'New content.',
      \file_get_contents($this->directory . '/example.txt'),
    );
  }

  /**
   * Creates a generator for testing.
   */
  private static function createTestGenerator(): BaseGenerator {
    return new class() extends BaseGenerator {

      protected function generate(array &$vars, AssetCollection $assets): void {
        $assets->addFile('example.txt')->content('Original content.');
      }

      protected function getGeneratorDefinition(): Generator {
        return new Generator('test');
      }

    };
  }

}