<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Deepest generation allowed in a clan tree. */
$config['max_generation'] = 5;

/** Optional friendly labels shown next to the generation number. */
$config['generation_labels'] = [
    1 => 'Sibling generation',
    2 => "Grandfather's generation",
    3 => "Mother's generation",
    4 => 'My generation',
    5 => "My children's generation",
];

/** Failed-login throttle (per browser session). */
$config['login_max_attempts'] = 5;
$config['login_lock_seconds'] = 300;
