<?php

declare(strict_types=1);

namespace DrupalCodeGenerator\Tests\Unit\Validator;

use DrupalCodeGenerator\Validator\ServiceName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests ServiceName validator.
 */
final class ServiceNameTest extends TestCase {

  /**
   * Test callback.
   */
  #[DataProvider('dataProvider')]
  public function test(mixed $machine_name, ?\UnexpectedValueException $exception): void {
    if ($exception) {
      $this->expectExceptionObject($exception);
    }
    self::assertSame($machine_name, (new ServiceName())($machine_name));
  }

  public static function dataProvider(): array {
    $exception = new \UnexpectedValueException('The value is not correct service name.');
    return [
      ['snake_case_here', NULL],
      ['dot.inside', NULL],
      ['CamelCaseHere', $exception],
      [' not_trimmed ', $exception],
      ['.leading.dot', $exception],
      ['ending.dot.', $exception],
      ['special&character', $exception],
      [TRUE, $exception],
      [NULL, $exception],
      [[], $exception],
      [new \stdClass(), $exception],
    ];
  }

}
