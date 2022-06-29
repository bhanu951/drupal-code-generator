<?php declare(strict_types=1);

namespace DrupalCodeGenerator\InputOutput;

/**
 * Interface for classes that depend on the console input and output.
 */
interface IOAwareInterface {

  /**
   * Sets or gets the console IO.
   */
  public function io(?IO $io = NULL): IO;

}
