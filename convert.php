<?php
/**
 * Whether to ignore the given line based on the key
 *
 * @param $key
 *
 * @return bool
 */
function should_ignore($key) {
  $ignore = [
    'Creation_date',
    'Modified_date',
    'Remark',
    'Fpc_remark',
  ];
  return in_array(trim($key), $ignore);
}

/**
 * Add a line.
 *
 * @param $data array of info per field
 */
function put_cmap_line($data) {
  $mapped = [
    'map_acc' => '',
    'map_name' => 'BAC',
    'map_start' => 0,
    'map_stop' => 0,
    'feature_acc' => '',
    'feature_name' => '',
    'feature_aliases' => '',
    'feature_start' => 0,
    'feature_stop' => 0,
    'feature_type_acc' => 'BAC',
    'is_landmark' => '',
  ];

  if (isset($data['Map'])) {
    foreach ($data['Map'] as $map) {
      $mapped['map_acc'] = str_replace('"', '', $map['name']);
      if ($map['dir'] === 'Left') {
        $mapped['feature_start'] = $map['value'];
      }
      else {
        $mapped['feature_stop'] = $map['value'];
      }
    }
  } else {
    return;
  }

  if (isset($data['BAC'])) {
    $mapped['feature_name'] = str_replace('"', '', $data['BAC']);
  }

  if (isset($data['Approximate_match_to_cosmid'])) {
    $mapped['feature_aliases'] = str_replace('"', '', $data['Approximate_match_to_cosmid']);
  }

  if (isset($data['Exact_match_to_cosmid'])) {
    $mapped['feature_aliases'] = str_replace('"', '', $data['Exact_match_to_cosmid']);
  }

  if (!empty($mapped['map_acc'])) {
    $mapped['feature_acc'] = $mapped['map_acc'];
  }

  if (!empty($mapped['feature_name'])) {
    if (!empty($mapped['feature_acc'])) {
      $mapped['feature_acc'] .= ':';
    }
    $mapped['feature_acc'] .= $mapped['feature_name'];
  }

  echo implode("\t", $mapped) . "\n";
}

/**
 * Print the header line.
 */
function put_header_line() {
  $header = [
    'map_acc',
    'map_name',
    'map_start',
    'map_stop',
    'feature_acc',
    'feature_name',
    'feature_aliases',
    'feature_start',
    'feature_stop',
    'feature_type_acc',
    'is_landmark',
  ];

  echo implode("\t", $header) . "\n";
}

function shift(&$array) {
  $item = NULL;
  while (count($array) > 0 && empty(($item = trim(array_shift($array))))) {
    // Intentionally empty
  }

  return $item;
}

/**
 * Parse and add line to a block.
 *
 * @param $line
 *
 * @return void
 */
function append_to_block($line, &$block) {
  // If `:` exists, split by `:`
  if (strstr($line, ':') !== FALSE) {
    $parts = array_map('trim', explode(':', $line));
    if (should_ignore($parts[0])) {
      return;
    }

    $block[$parts[0]] = trim($parts[1]);
    return;
  }

  // Data is split by first word
  $parts = explode(' ', $line);
  $key = shift($parts);

  if (should_ignore($key)) {
    return;
  }

  if ($key === 'Map') {
    $name = shift($parts);
    // We intentionally ignore the word `Ends`
    $ends = shift($parts);
    $direction = shift($parts);
    $value = shift($parts);

    $value = [
      [
        'name' => trim($name),
        'dir' => trim($direction),
        'value' => trim($value),
      ],
    ];

    if (isset($block['Map'])) {
      $value += $block['Map'];
    }
  }
  elseif ($key === 'Bands') {
    $start = shift($parts);
    $end = shift($parts);
    $value = [
      'start' => $start,
      'end' => $end,
    ];
  }
  else {
    $value = trim(implode(' ', $parts));
  }

  $block[$key] = $value;
}

if (count($argv) > 2) {
  echo "ERROR: Only 1 argument is allowed. The argument accepts the path to the FPC file.\n";
}

// Ge the file name from the user or from the cli arguments if available.
$fpc = '';
if (!isset($argv[1])) {
  do {
    echo "Enter FPC file path: ";
    $fpc = trim(trim(readline(), "\n"));
  } while (empty($fpc));
}
else {
  $fpc = trim($argv[1]);
}

// Open the file
$file = fopen($fpc, 'r');
if (!$file) {
  echo "ERROR: Could not open file\n";
  exit(1);
}

// Read the lines
$data = [];
$block = [];
while (!feof($file)) {
  // Get the line
  $line = fgets($file);
  $line = trim($line, "\n");

  // Clear white spaces
  $line = trim($line);

  // Ignore empty lines
  if (empty($line)) {
    if (!empty($block)) {
      $data[] = $block;
      $block = [];
    }
    continue;
  }

  // Ignore comment lines
  if (substr($line, 0, 2) === "//") {
    continue;
  }

  // Once we see these, we are done
  if (strstr($line, 'Contigdata') !== FALSE || strstr($line, 'Markerdata') !== FALSE) {
    break;
  }


  // Parse the line and add it to the array
  append_to_block($line, $block);
}

// Close the file
fclose($file);

// Convert data matrix to cmap
put_header_line();
foreach ($data as $block) {
  put_cmap_line($block);
}
