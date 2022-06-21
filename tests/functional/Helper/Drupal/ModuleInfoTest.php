<?php declare(strict_types=1);

namespace DrupalCodeGenerator\Tests\Functional;

use DrupalCodeGenerator\Helper\Drupal\ModuleInfo;
use DrupalCodeGenerator\Test\Functional\FunctionalTestBase;
use DrupalCodeGenerator\Utils;

/**
 * Tests 'module info' helper.
 */
final class ModuleInfoTest extends FunctionalTestBase {

  public function testModuleInfo(): void {
    $container = $this->application->getContainer();
    $module_info = new ModuleInfo($container->get('module_handler'));
    self::assertModules($module_info->getModules());

    self::assertDestination('modules', $module_info->getDestination(TRUE, 'node'));
    self::assertDestination('core/modules/node', $module_info->getDestination(FALSE, 'node'));
    self::assertDestination('modules', $module_info->getDestination(TRUE, 'foo'));
    self::assertDestination('modules/foo', $module_info->getDestination(FALSE, 'foo'));

    self::assertSame('Database Logging', $module_info->getModuleName('dblog'));
    self::assertSame('Unknown module', $module_info->getModuleName('unknown_module'));
  }

  private static function assertModules(array $modules): void {
    // Full list of modules is rather long and may vary depending on
    // environment.
    self::assertSame('Custom Block', $modules['block_content']);
    self::assertSame('Database Logging', $modules['dblog']);
    self::assertSame('Options', $modules['options']);
    self::assertSame('Views', $modules['views']);
  }

  private static function assertDestination(string $expected, string $actual): void {
    self::assertSame($expected, Utils::removePrefix($actual, \DRUPAL_ROOT . '/'));
  }

}