<?php

require_once 'vendor/autoload.php';

function get_status_emoji($time)
{
  // Under 4 minutes, good.
  $emoji = ':no_entry:';
  if ($time < 5) {
    $emoji = ':white_check_mark:';
  } elseif ($time < 10) {
    $emoji = ':warning:';
  }

  return $emoji;
}

function curl_url($url, $data)
{
  $payload = json_encode($data);
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);


  print("\n==== Posting to Slack ====\n");

  $result = curl_exec($ch);
  print("RESULT: $result");
  // $payload_pretty = json_encode($post,JSON_PRETTY_PRINT); // Uncomment to debug JSON
  // print("JSON: $payload_pretty"); // Uncomment to Debug JSON

  print("\n===== Post Complete! =====\n");
  curl_close($ch);
}

// Process results
try {
  $results = file('/tmp/results.txt');

  if ($results !== FALSE) {
    // Initialize Markdown table
    $note = getenv('NOTE');
    $blocks = [
      [
        "type" => "section",
        "text" => [
          "type" => "mrkdwn",
          'text' => "*Deployment overview*: \n${note}",
        ]
      ],
      ['type' => 'divider']
    ];

    // Add rows to markdown table
    foreach ($results as $result) {
      $site = explode(',', $result);
      // Make some vars
      $site_name = $site[0];
      $site_id = $site[1];
      $dashboard = "<https://dashboard.pantheon.io/sites/${site_id}|${site_name}>";
      $site_link = "https://live-${site_name}.pantheonsite.io";
      $time = trim($site[2]);
      $status = get_status_emoji($time);

      $section = [
        'type' => 'section',
        'text' => [
          'type' => 'mrkdwn',
          'text' => "${status}\n*Site Name*: ${dashboard}\n*Deploy Time*: ${time} min",
        ],
        'accessory' => [
          'type' => "button",
          'text' => [
            'type' => "plain_text",
            'emoji' => true,
            'text' => "View Live Site",
          ],
          'url' => $site_link,
        ]
      ];

      // Add section to block list
      $blocks[] = $section;


      // {
      //   "type": "section",
      //   "text": {
      //     "type": "mrkdwn",
      //     "text": ":sushi: *Ace Wasabi Rock-n-Roll Sushi Bar*\nThe best landlocked sushi restaurant."
      //   },
      //   "accessory": {
      //     "type": "button",
      //     "text": {
      //       "type": "plain_text",
      //       "emoji": true,
      //       "text": "Vote"
      //     },
      //     "value": "click_me_123"
      //   }
      // },

    }

    // Initiate Slack
    $url = getenv('SLACK_WEBHOOK');
    $data = [
      'username' => 'Github Actions',
      'icon_emoji' => ':crystal_ball:',
      'blocks' => $blocks,
    ];

    // Send request
    curl_url($url, $data);
  }
} catch (Exception $e) {
  echo $e->getMessage();
}
