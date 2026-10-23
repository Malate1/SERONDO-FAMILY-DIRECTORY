<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Deepest generation allowed in a clan tree (Gen 1 = clan ancestor). */
$config['max_generation'] = 5;

/** Optional friendly labels shown next to the generation number. */
$config['generation_labels'] = [
    1 => 'Clan Ancestor',
    2 => '',
    3 => 'Grandfather generation',
    4 => 'Reunion generation',
    5 => 'Children',
];

/** Failed-login throttle (per browser session). */
$config['login_max_attempts'] = 5;
$config['login_lock_seconds'] = 300;
