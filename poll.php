<?php
/**
 * Plugin Name: Polls
 * Description: Show polls with questions, ACF PRO is required
 * Version: 1.0.0
 * Author: Marko Štimac
 * Author URI: https://marko-stimac.github.io/
 */

namespace ms\Poll;

defined('ABSPATH') || exit;

require_once 'includes/class-frontend.php';
require_once 'includes/class-backend.php';

new Backend();
$poll = new Frontend();
add_shortcode('wppolls', array($poll, 'show_component'));