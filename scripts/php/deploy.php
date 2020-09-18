<?php

require_once 'vendor/autoload.php';

/**
 *
 * Running commands on the local host
 *
 */

use Parallel\Exceptions\InvalidBinaryException;
use Parallel\Wrapper;

// You can initialize the Wrapper with or without parameters
$parallel = new Wrapper();
$sites = explode("\n", getenv('SITES'));
$note = getenv('NOTE');
global $note;

function terminus_deploy($site_name, $env = 'test')
{
  $dev_site = "${site_name}.dev";
  $sequence_list = [
    "echo -e 'Starting ${site_name}'",
    "terminus site:upstream:clear-cache ${site_name}",
    "terminus upstream:update:status ${dev_site}",
    "terminus upstream:updates:apply ${dev_site}",
    "terminus drush ${dev_site} -- updb -y",
    "terminus env:clear-cache ${dev_site}"
  ];

  if ($env != 'dev') {
    $site_env = "${site_name}.${env}";
    $sequence_list[] = "terminus env:deploy ${site_env} --cc --updatedb --note '${note}'";
  }

  // Join all commands into single sequence
  return implode(";", $sequence_list);
}

die(terminus_deploy('purina-demo-1'));

try {
  // Set path to binary
  $parallel->initBinary(exec('which parallel'));

  // Add the commands you want to run in parallel
  foreach ($sites as $site) {
    $parallel->addCommand(terminus_deploy($site));
  }

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
  print $exception;
}
