<?php
/**
 * Plugin Name: myCRED Sensei Add-On
 * Plugin URI: https://shop.opentuteplus.com/product/mycred-sensei/
 * Description: This myCRED add-on integrates myCRED features with Sensei
 * Version: 1.0.1
 * Author: OpenTute+
 * Author URI: http://opentuteplus.com
 * Requires at least: WP 4, Sensei 1.9.3, myCRED 1.7.2
 * Text Domain: mycred-sensei-addon
 * Domain Path: /languages/
 * Copyright: 2016 OpenTute+
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define("MYCRED_SENSEI", 'mycred-sensei-addon');
define("MYCRED_SENSEI_DIR", plugin_dir_path( __FILE__ ));
define("MYCRED_SENSEI_URL", plugin_dir_url( __FILE__ ));

require_once(MYCRED_SENSEI_DIR . 'includes/functions-mycred-sensei.php');

Class myCRED_Sensei {

	function __construct() {
		// hooks
		add_action('admin_notices', array($this, 'admin_notices'));
		add_action('plugins_loaded', array($this, 'plugins_loaded'));	
		add_action('mycred_load_hooks', array($this, 'load_hook_files') );
		add_filter('mycred_setup_hooks', array($this, 'register_mycred_sensei_hooks') );
		add_filter('mycred_all_references', array($this, 'register_references') );

		add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts') );

		/**
		 * TODO | Add custom badge
		 */
		// add_action( 'mycred_ready', array($this, 'load_badge') );
	}

	public function load_admin_scripts(){
		wp_register_script( 'mycred-sensei-admin', MYCRED_SENSEI_URL . 'assets/js/admin.js', array('jquery'), '', true );
	}

	public function load_hook_files(){
		require_once( MYCRED_SENSEI_DIR . 'includes/class-mycred-sensei-enrolled-course-hook.php');
		require_once( MYCRED_SENSEI_DIR . 'includes/class-mycred-sensei-enrolled-course-category-hook.php');
		require_once( MYCRED_SENSEI_DIR . 'includes/class-mycred-sensei-passed-quiz-hook.php');
		require_once( MYCRED_SENSEI_DIR . 'includes/class-mycred-sensei-quiz-grade-hook.php');
		require_once( MYCRED_SENSEI_DIR . 'includes/class-mycred-sensei-completed-lesson-hook.php');
		require_once( MYCRED_SENSEI_DIR . 'includes/class-mycred-sensei-completed-course-hook.php');
		require_once( MYCRED_SENSEI_DIR . 'includes/class-mycred-sensei-completed-course-category-hook.php');
	}

	public function register_mycred_sensei_hooks($installed){

		$hooks = $this->mycred_sensei_hooks();
		if(!empty($hooks) && is_array($hooks)){
		foreach ($hooks as $key => $hook) {
			if(!$hook['references'])
				continue;

			if(isset($hook['references']))
				unset($hook['references']);

			if(isset($hook['reference_title']))
				unset($hook['reference_title']);

			$installed[$key] = $hook;
		}
		}

		return $installed;
	}

	public function register_references($references){

		$hooks = $this->mycred_sensei_hooks();
		if(!empty($hooks) && is_array($hooks)){
		$addons = array();
		foreach ($hooks as $key => $hook) {
			if(!$hook['references'] || !$hook['reference_title'])
				continue;

			$addons[$key] = $hook['reference_title'];
		}
		$references = array_merge( $addons, $references );
		}

		return $references;
	}

	public function mycred_sensei_hooks(){
		$hooks = array(
			'enrolled_course' => array(
				'title'       => __( '%plural% for enrolled course', MYCRED_SENSEI ),
				'description' => __( 'Award %_plural% to users when enrolled course.', MYCRED_SENSEI ),
				'callback'    => array( 'myCRED_Sensei_Enrolled_Course_Hook' ),
				'references'  => true,
				'reference_title' => __('Enrolled Course', MYCRED_SENSEI)
			),
			'enrolled_course_category' => array(
				'title'       => __( '%plural% for enrolled course category', MYCRED_SENSEI ),
				'description' => __( 'Award %_plural% to users when enrolled course on category.', MYCRED_SENSEI ),
				'callback'    => array( 'myCRED_Sensei_Enrolled_Course_Category_Hook' ),
				'references'  => true,
				'reference_title' => __('Enrolled Course in Category', MYCRED_SENSEI)
			),
			'passed_quiz' => array(
				'title'       => __( '%plural% for passed quiz', MYCRED_SENSEI ),
				'description' => __( 'Award %_plural% to users when passed quiz.', MYCRED_SENSEI ),
				'callback'    => array( 'myCRED_Sensei_Passed_Quiz_Hook' ),
				'references'  => true,
				'reference_title' => __('Passed Quiz', MYCRED_SENSEI)
			),
			'quiz_grade' => array(
				'title'       => __( '%plural% for grade of quiz', MYCRED_SENSEI ),
				'description' => __( 'Award %_plural% to users when reach grade on quiz.', MYCRED_SENSEI ),
				'callback'    => array( 'myCRED_Sensei_Quiz_Grade_Hook' ),
				'references'  => true,
				'reference_title' => __('Quiz Grade', MYCRED_SENSEI)
			),
			'completed_lesson' => array(
				'title'       => __( '%plural% for completed lesson', MYCRED_SENSEI ),
				'description' => __( 'Award %_plural% to users when completed lesson.', MYCRED_SENSEI ),
				'callback'    => array( 'myCRED_Sensei_Completed_Lesson_Hook' ),
				'references'  => true,
				'reference_title' => __('Completed Lesson', MYCRED_SENSEI)
			),
			'completed_course' => array(
				'title'       => __( '%plural% for completed course', MYCRED_SENSEI ),
				'description' => __( 'Award %_plural% to users when completed course.', MYCRED_SENSEI ),
				'callback'    => array( 'myCRED_Sensei_Completed_Course_Hook' ),
				'references'  => true,
				'reference_title' => __('Completed Course', MYCRED_SENSEI)
			),
			'completed_course_category' => array(
				'title'       => __( '%plural% for completed course category', MYCRED_SENSEI ),
				'description' => __( 'Award %_plural% to users when completed course on category.', MYCRED_SENSEI ),
				'callback'    => array( 'myCRED_Sensei_Completed_Course_Category_Hook' ),
				'references'  => true,
				'reference_title' => __('Completed Course in Category', MYCRED_SENSEI)
			)
		);

		return apply_filters( 'mycred_sensei_hooks', $hooks );
	}

	/**
	 * The requirements to run this plugin
	 */
	function meets_requirements() {
		// check the both of sensei and mycred plugin is activated
		if(! class_exists( 'WooThemes_Sensei' ) || ! class_exists( 'myCRED_Core' )) {
			return false;
		}
		
		return true;
	}

	/**
	 * Disable the plugin if it's dont meet the requirements
	 */
	function admin_notices() {
		// notice and then deactivate if dont meets the requirements
		if(! $this->meets_requirements()) {
			echo '<div id="message" class="error">';
			printf('<p>%1$s</p>', __('Plugin myCRED Sensei Add-On need myCRED and Sensei to run!', MYCRED_SENSEI));
			echo '</div>';

			deactivate_plugins( __FILE__ );
		}
	}

	/**
	 * This will run on the plugins_loaded hook, before after_setup_theme
	 */
	function plugins_loaded() {
		// Localization
    	load_plugin_textdomain(MYCRED_SENSEI, false, dirname( __FILE__ ) . '/languages');
	}

	function load_badge(){
		// if(function_exists('mycred_assign_badge_to_user')) {
			require_once(MYCRED_SENSEI_DIR . 'includes/class-mycred-sensei-badges.php');
		// }
	}
}

$GLOBALS[ 'mycred_sensei' ] = new myCRED_Sensei();