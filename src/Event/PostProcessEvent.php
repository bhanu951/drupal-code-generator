<?php declare(strict_types = 1);

namespace DrupalCodeGenerator\Event;

use DrupalCodeGenerator\Asset\AssetCollection;

/**
 * Fired when altering dumped assets.
 */
final class PostProcessEvent {

  use StoppableEventTrait;

  /**
   * Constructs the event object.
   */
  public function __construct(
    public AssetCollection $assets,
    // Unlike PreProcessEvent::$destination this one is readonly as there is no
    // point in modifying it after dumping the assets.
    public readonly string $destination,
    public readonly string $commandName,
    public readonly bool $isDry,
  ) {}

}