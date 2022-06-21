<?php declare(strict_types=1);

namespace DrupalCodeGenerator;

use Symfony\Component\String\ByteString;
use Symfony\Component\String\Inflector\EnglishInflector;

/**
 * Helper methods for code generators.
 */
class Utils {

  /**
   * Transforms a machine name to human name.
   */
  public static function machine2human(string $machine_name, bool $title_case = FALSE): string {
    $output = \trim(\str_replace('_', ' ', $machine_name));
    return $title_case ? \ucwords($output) : \ucfirst($output);
  }

  /**
   * Transforms a human name to machine name.
   */
  public static function human2machine(string $human_name): string {
    return \trim(\preg_replace(
      ['/^[0-9]+/', '/[^a-z0-9_]+/'],
      '_',
      \strtolower($human_name),
    ), '_');
  }

  /**
   * Transforms a camelized sting to machine name.
   */
  public static function camel2machine(string $input): string {
    return self::human2machine(\preg_replace('/[A-Z]/', ' \0', $input));
  }

  /**
   * Camelize a string.
   */
  public static function camelize(string $input, bool $upper_camel = TRUE): string {
    $output = \preg_replace('/[^a-z0-9]/i', ' ', $input);
    $output = (string) (new ByteString($output))->camel();
    return $upper_camel ? \ucfirst($output) : $output;
  }

  /**
   * Returns extension root.
   */
  public static function getExtensionRoot(string $directory): ?string {
    $extension_root = NULL;
    for ($i = 1; $i <= 5; $i++) {
      $info_file = $directory . '/' . \basename($directory) . '.info';
      if ((\file_exists($info_file) && \basename($directory) !== 'drush') || \file_exists($info_file . '.yml')) {
        $extension_root = $directory;
        break;
      }
      $directory = \dirname($directory);
    }
    return $extension_root;
  }

  /**
   * Replaces all tokens in a given string with appropriate values.
   *
   * @param string $text
   *   A string potentially containing replaceable tokens.
   * @param array $data
   *   An array where keys are token names and values are replacements.
   *
   * @return string
   *   Text with tokens replaced.
   */
  public static function replaceTokens(string $text, array $data): string {

    if (\count($data) === 0) {
      return $text;
    }

    $process_token = static function (array $matches) use ($data): string {
      [$name, $filter] = \array_pad(\explode('|', $matches[1], 2), 2, NULL);
      if (!\array_key_exists($name, $data)) {
        throw new \UnexpectedValueException(\sprintf('Variable "%s" is not defined', $name));
      }
      $result = $data[$name];
      return match ($filter) {
        'u2h' => \str_replace('_', '-', $result),
        'h2u' => \str_replace('-', '_', $result),
        'h2m' => self::human2machine($result),
        'm2h' => self::machine2human($result),
        'camelize' => self::camelize($result),
        'pluralize' => self::pluralize($result),
        'c2m' => self::camel2machine($result),
        NULL => $result,
        default => throw new \UnexpectedValueException(\sprintf('Filter "%s" is not defined', $filter))
      };
    };

    $escaped_brackets = ['\\{', '\\}'];
    $tmp_replacement = ['DCG-open-bracket', 'DCG-close-bracket'];
    $text = \str_replace($escaped_brackets, $tmp_replacement, $text);
    $text = \preg_replace_callback('/{(.+?)}/', $process_token, $text);
    return \str_replace($tmp_replacement, $escaped_brackets, $text);
  }

  /**
   * Quote curly brackets with slashes.
   */
  public static function addSlashes(string $input): string {
    return \addcslashes($input, '{}');
  }

  /**
   * Un-quotes a quoted string.
   */
  public static function stripSlashes(string $input): string {
    return \str_replace(['\{', '\}'], ['{', '}'], $input);
  }

  /**
   * Pluralizes a noun.
   */
  public static function pluralize(string $input): string {
    return (new EnglishInflector())->pluralize($input)[0];
  }

  /**
   * Remove a string from the beginning of a string.
   */
  public static function removePrefix(string $input, string $prefix): string {
    return \str_starts_with($input, $prefix)
      ? \substr_replace($input, '', 0, \strlen($prefix)) : $input;
  }

}
