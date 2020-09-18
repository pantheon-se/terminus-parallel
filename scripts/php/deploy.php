<?php

require_once './vendor/autoload.php';

/**
 *
 * Running commands on the local host
 *
 */

use Parallel\Exceptions\InvalidBinaryException;
use Parallel\Wrapper;

// You can initialize the Wrapper with or without parameters
$parallel = new Wrapper();

try {
  // Set path to binary
  $parallel->initBinary('/path/to/parallel/binary');

  // Add the commands you want to run in parallel
  $parallel->addCommand('/path/to/command/one.sh');
  $parallel->addCommand('/path/to/command/two.sh');
  $parallel->addCommand('/path/to/command/three.sh');

  /**
   * Setting the parallelism to 0 or "auto" will
   * result in a parallelism setting equal to the
   * number of commands you whish to run
   *
   * Use the maxParallelism setting to set a cap
   */
  $parallel->setParallelism('auto');
  $parallel->setMaxParallelism(10);

  // Run the commands and catch the output from the console
  $output = $parallel->run();
} catch (InvalidBinaryException $exception) {
  // The binary file does not exist, or is not executable
}
