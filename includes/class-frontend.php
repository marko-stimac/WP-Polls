<?php

/**
 * Register scripts and styles, retrieve data, show component, register votes
 */

namespace ms\Poll;

defined('ABSPATH') || exit;

class Frontend
{

	public function __construct()
	{
		add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
		add_action('wp_ajax_check_session',  array($this, 'check_session'));
		add_action('wp_ajax_nopriv_check_session',  array($this, 'check_session'));
		add_action('wp_ajax_vote',  array($this, 'vote'));
		add_action('wp_ajax_nopriv_vote',  array($this, 'vote'));
	}

	// Register scripts and styles
	public function register_scripts()
	{
		wp_register_style('poll', plugins_url('assets/poll.css', __DIR__));
		wp_register_script('vue', 'https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.10/vue.min.js', array('jquery'), null, true);
		wp_register_script('poll', plugins_url('assets/poll.js', __DIR__), array('vue'), null, true);
	}

	// Retrieve poll data and pass it to JS
	public function get_poll_data($post_id)
	{

		$this->poll_id = get_the_ID();

		$questions = [];
		if (have_rows('polls_options', $post_id)) :
			while (have_rows('polls_options', $post_id)) : the_row();
				$questions[] = array(
					get_sub_field('poll_option'),
					get_sub_field('poll_option_votes') ? get_sub_field('poll_option_votes') : 0,
				);

			endwhile;
		endif;

		$data = array(
			'main_question' => get_the_title($post_id),
			'choices' => $questions
		);

		return $data;
	}

	// Register a vote for chosen option
	public function vote()
	{

		$poll_id = $_POST['poll_id']; 

		// Increate vote for desired field
		if (have_rows('polls_options', $poll_id)) {
			while (have_rows('polls_options', $poll_id)) {
				the_row();
				if (get_sub_field('poll_option') === $_POST["votes_question"]) {

					// Update vote count into ACF field
					$current_votes_count = get_sub_field('poll_option_votes');
					$vote = update_sub_field('poll_option_votes', $current_votes_count ? ++$current_votes_count : 1, $poll_id);

					// Add poll ID to session 
					session_start();
					if (!isset($_SESSION['wppoll'])) {
						$_SESSION['wppoll'] = array();
					}
					array_push($_SESSION['wppoll'], (int) $poll_id);

				}
			}
		}
		wp_die();

		if (!$vote) {
			$data = array('type' => 'error', 'message' => 'Voting failed');
			header('HTTP/1.1 400 Bad Request');
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($data);
		}
		wp_die();
	}


	// Check if user can vote
	public function check_session()
	{

		session_start();
		
		$poll_id = $_POST['poll_id'];
		header('Content-Type: application/json; charset=UTF-8');

		// User can vote
		if (isset($_SESSION['wppoll']) && !in_array($poll_id, $_SESSION['wppoll'])) {
			echo json_encode(array('can_vote' => true));
		}
		// User has already voted
		else {
			echo json_encode(array('can_vote' => false));
		}
		wp_die();
		
	}


	// Enqueue styles, scripts with data and show poll
	public function show_component($atts)
	{

		$atts = shortcode_atts(array(
			'id' => ''
		), $atts);

		wp_enqueue_style('poll');
		wp_enqueue_script('poll');
		wp_localize_script(
			'poll',
			'wppoll',
			array(
				'url'   => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce("wppoll_nonce"),
				'data' => $this->get_poll_data($atts['id']),
				'poll_id' => $atts['id']
			)
		);

		ob_start();
?>

<div id="wppoll" class="poll" v-cloak>

	<div v-if="items">
		<p>{{ main_question }}</p>
		<ul class="poll__list">
			<li class="poll__item" v-for="(item, index) in items" @click="registerVote({index})" :class="['poll__item--' + item.percentage]">
				<span class="poll__choice-question">{{ item[0] }}</span>
				<span class="poll__choice-votes" v-if="showPollData">{{ item[1] }}</span>
			</li>
		</ul>

		<p class="poll__total" v-if="showPollData"><?php _e('Total votes:', 'wppolls'); ?> {{ totalVotes }}</p>
		<p class="poll__message poll__message--success" v-if="isVoted"><?php _e('Thanks for the feedback.', 'wppolls'); ?></p>
		<p class="poll__message poll__message--fail" v-if="!canVote"><?php _e('Only one vote is possible and you have already voted.', 'wppolls'); ?></p>

	</div>
</div>

<?php
		return ob_get_clean();
	}
}