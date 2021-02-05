<?php
$data = array();

$data[] = array(
  'option_name'=>'order_unattended_minutes',
  'option_value'=>5,
);
$data[] = array(
  'option_name'=>'ready_outgoing_minutes',
  'option_value'=>5,
);
$data[] = array(
  'option_name'=>'ready_unattended_minutes',
  'option_value'=>30,
);

$data[] = array(
  'option_name'=>'refresh_order',
  'option_value'=>1,
);
$data[] = array(
  'option_name'=>'refresh_cancel_order',
  'option_value'=>2,
);

$data[] = array(
  'option_name'=>'interval_ready_order',
  'option_value'=>1,
);

$data[] = array(
  'option_name'=>'booking_incoming_unattended_minutes',
  'option_value'=>5,
);
$data[] = array(
  'option_name'=>'booking_cancel_unattended_minutes',
  'option_value'=>5,
);

$data[] = array(
  'option_name'=>'refresh_booking',
  'option_value'=>1,
);
$data[] = array(
  'option_name'=>'refresh_cancel_booking',
  'option_value'=>2,
);
$data[] = array(
  'option_name'=>'merchantapp_keep_awake',
  'option_value'=>1,
);

$data[] = array(
  'option_name'=>'order_estimated_time',
  'option_value'=>'[{"value":"5"},{"value":"10"},{"value":"15"},{"value":"20"},{"value":"25"},{"value":"30"},{"value":"35"}]',
);
$data[] = array(
  'option_name'=>'decline_reason_list',
  'option_value'=>'[{"value":"Closing early"},{"value":"Problem with merchant"},{"value":"Out of stock"},{"value":"Merchant is too busy"}]',
);

/*ORDER SETTINGS*/
$data[] = array(
  'option_name'=>'order_incoming_status',
  'option_value'=>'["paid","pending"]',
);
$data[] = array(
  'option_name'=>'order_outgoing_status',
  'option_value'=>'["accepted","delayed","delivered","acknowledged"]',
);
$data[] = array(
  'option_name'=>'order_ready_status',
  'option_value'=>'["food is ready"]',
);

$data[] = array(
  'option_name'=>'order_failed_status',
  'option_value'=>'["cancelled","decline","failed"]',
);
$data[] = array(
  'option_name'=>'order_successful_status',
  'option_value'=>'["delivered","successful"]',
);

$data[] = array(
  'option_name'=>'order_action_accepted_status',
  'option_value'=>'accepted',
);

$data[] = array(
  'option_name'=>'order_action_decline_status',
  'option_value'=>'decline',
);

$data[] = array(
  'option_name'=>'order_action_cancel_status',
  'option_value'=>'cancelled',
);

$data[] = array(
  'option_name'=>'order_action_food_done_status',
  'option_value'=>'food is ready',
);

$data[] = array(
  'option_name'=>'order_action_delayed_status',
  'option_value'=>'delayed',
);

$data[] = array(
  'option_name'=>'order_action_completed_status',
  'option_value'=>'successful',
);

$data[] = array(
  'option_name'=>'order_action_approved_cancel_order',
  'option_value'=>'cancelled',
);

$data[] = array(
  'option_name'=>'order_action_decline_cancel_order',
  'option_value'=>'decline',
);

$data[] = array(
  'option_name'=>'accepted_based_time',
  'option_value'=>1,
);

$data[] = array(
  'option_name'=>'merchantapp_enabled_booking',
  'option_value'=>1,
);

$data[] = array(
  'option_name'=>'booking_incoming_status',
  'option_value'=>'["pending"]',
);

$data[] = array(
  'option_name'=>'booking_cancel_status',
  'option_value'=>'["request_cancel_booking"]',
);

$data[] = array(
  'option_name'=>'booking_done_status',
  'option_value'=>'["approved","denied","cancel_booking_approved"]',
);

$data[] = array(
  'option_name'=>'set_language',
  'option_value'=>'en',
);

