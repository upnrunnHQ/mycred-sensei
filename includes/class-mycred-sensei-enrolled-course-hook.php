<?php
/**
 * Enrolled course
 */
if ( ! class_exists( 'myCRED_Sensei_Enrolled_Course_Hook' ) ) :
class myCRED_Sensei_Enrolled_Course_Hook extends myCRED_Hook {

	/**
	 * Construct
	 */
	function __construct( $hook_prefs, $type ) {

		wp_enqueue_script( 'mycred-sensei-admin' );

		$defaults = array(
			array(
				'course_id'   => 'any',
				'creds'   => 1,
				'log'     => __('%plural% for enrolled course', MYCRED_SENSEI ),
				'limit'  => '0/x'
			)
		);

		if ( isset( $hook_prefs['enrolled_course'] ) )
			$defaults = $hook_prefs['enrolled_course'];

		parent::__construct( array(
			'id'       => 'enrolled_course',
			'defaults' => $defaults
		), $hook_prefs, $type );
	}

	/**
	 * Hook into WordPress
	 */
	public function run() {
		add_action( 'sensei_user_course_start', array($this, 'do_log'), 10, 2 );
	}


	public function do_log($user_id, $course_id){

		// Check for exclusions
		if ( $this->core->exclude_user( $user_id ) === true ) return;

		// check if match selected course
		$course_ids = wp_list_pluck( $this->prefs, 'course_id' );
		if(!in_array('any', $course_ids) && !in_array($course_id, $course_ids) ) return;

		$creds = 0;
		// process any
		if(in_array('any', $course_ids)){
			$need_process = true;

			$used_any = filter_by_value($this->prefs, 'course_id', 'any');
			$instance_any = key($used_any);

			// Make sure we award points other then zero
			if ( ! isset( $this->prefs[ $instance_any ]['creds'] ) ) $need_process = false;
			if ( empty( $this->prefs[ $instance_any ]['creds'] ) || $this->prefs[ $instance_any ]['creds'] == 0 ) $need_process = false;	

			// check limit
			if ($this->over_hook_limit( $instance_any, 'enrolled_course', $user_id )) $need_process = false;

			if($need_process){
				$entry = $this->get_replaced_log($instance_any, $course_id);
				$creds  = $creds + $this->core->number($this->prefs[ $instance_any ]['creds']);
			} 
			
		}

		// need to process others?
		if(in_array($course_id, $course_ids)){
			$need_process = true;

			$used = filter_by_value($this->prefs, 'course_id', $course_id);
			$instance = key($used);

			// Make sure we award points other then zero
			if ( ! isset( $this->prefs[ $instance ]['creds'] ) ) $need_process = false;
			if ( empty( $this->prefs[ $instance ]['creds'] ) || $this->prefs[ $instance ]['creds'] == 0 ) $need_process = false;

			if ($this->over_hook_limit( $instance, 'enrolled_course', $user_id )) $need_process = false;

			if ($need_process){ 
				$entry = $this->get_replaced_log($instance, $course_id);
				$creds  = $creds + $this->core->number($this->prefs[ $instance ]['creds']);
			}
		}

		// Prep
		$data = array( 'ref_type' => 'course' );

		// Make sure this is unique
		if ( $this->core->has_entry( 'enrolled_course', $course_id, $user_id, $data, $this->mycred_type ) ) return;
		
		// Check creds 
		if ( $creds > 0 ){
			// echo $course_id . " processed" . $entry;
			$this->core->add_creds(
				'enrolled_course',
				$user_id,
				$this->core->format_number($creds),
				$entry,
				$course_id,
				$data,
				$this->mycred_type
			);
		}
	}

	public function get_replaced_log($instance, $course_id){
		$replace = get_the_title( $course_id );
		return str_replace('%course_title%', $replace, $this->prefs[ $instance ]['log']);
	}
	

	/**
	 * Add Settings
	 */
	 public function preferences() {
		// Our settings are available under $this->prefs
		$prefs = $this->prefs; 

		if(!class_exists('Sensei_Course')){
			echo '<h2>'.__('Please activate WooThemes Sensei plugin.', MYCRED_SENSEI ).'</h2>';
			return;
		}
		
		// use sensei builtin class to get course so if sensei get course filter exists can be applied
		$courses = Sensei_Course::get_all_courses();

		// echo "<pre>";
		// print_r($prefs);
		// echo "</pre>";
		?>
		<div class="container-setting lalal" style="padding: 10px 0;">
			<?php
			echo $this->get_repeatable_template($courses);
			echo $this->display_saved_data($prefs, $courses); 
			?>

			<div class="repeatable-container" data-template="#template_enrolled_course"></div>
		    <input type="button" value="<?php _e('Add New', MYCRED_SENSEI); ?>" class="mycred_sensei_add_new button button-primary" />
		</div>
		<?php
	}

	public function display_saved_data($prefs, $courses){

		$html = '';
		if(!empty($prefs)){
			foreach ($prefs as $key => $pref) {
			
			ob_start();
			?>
			<div class="field-group" style="position: relative; padding-left: 50px; ">
				<!-- select course -->
				<label class="subheader"><?php _e('Course', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<select name="<?php echo $this->field_name( array('exist'.$key, 'course_id') ); ?>" id="<?php echo $this->field_name( array('exist'.$key, 'course_id') ); ?>">
							<option value="any"><?php _e('Any Courses', MYCRED_SENSEI); ?></option>
							<?php 
							if(!empty($courses)):
							foreach ($courses as $course) {
							echo '<option value="'.$course->ID.'" '.selected( $pref['course_id'], $course->ID, false ).'>'.$course->post_title.'</option>';
							}
							endif;
							?>
						</select>
					</li>
				</ol>
				<!-- we set the amount -->
				<label class="subheader"><?php echo $this->core->plural(); ?></label>
				<ol>
					<li>
						<input type="text" name="<?php echo $this->field_name( array('exist'.$key, 'creds') ); ?>" id="<?php echo $this->field_id( array('exist'.$key, 'creds') ); ?>" value="<?php echo $this->core->format_number( $pref['creds'] ); ?>" size="8" />
					</li>
				</ol>

				<label class="subheader"><?php _e('Limit', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<?php echo $this->hook_limit_setting( $this->field_name( array('exist'.$key, 'limit') ), $this->field_id( array('exist'.$key, 'limit') ), $pref['limit'] ); ?>
					</li>

				</ol>

				<!-- Then the log template -->
				<label class="subheader"><?php _e( 'Log template', 'mycred' ); ?></label>
				<ol>
					<li>
						<input type="text" name="<?php echo $this->field_name( array('exist'.$key, 'log') ); ?>" id="<?php echo $this->field_id( array('exist'.$key, 'log') ); ?>" value="<?php echo $pref['log']; ?>" class="long" />
					</li>
				</ol>
				<span class="delete_exist_row" style="position: absolute; left: 0; top: 0; ">
					<a href="javascript:;" class="mycred_sensei_delete button button-primary" title="<?php _e('Delete', MYCRED_SENSEI) ?>">X</a>
				</span>
			</div>
			<?php
				$html .= ob_get_clean();

			} // end foreach
		}

		return $html;
	}

	public function get_repeatable_template($courses){
		ob_start();
		?>

		<!-- template -->
		<script type="text/template" id="template_enrolled_course">
			<div class="field-group" style="position: relative; padding-left: 50px; ">
			    <!-- select course -->
				<label class="subheader"><?php _e('Course', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<select name="<?php echo $this->field_name( array('{?}','course_id') ); ?>" id="<?php echo $this->field_name( array('{?}','course_id') ); ?>">
							<option value="any"><?php _e('Any Courses', MYCRED_SENSEI); ?></option>
							<?php 
							if(!empty($courses)):
							foreach ($courses as $course) {
							echo '<option value="'.$course->ID.'">'.$course->post_title.'</option>';
							}
							endif;
							?>
						</select>
					</li>
				</ol>
				<!-- we set the amount -->
				<label class="subheader"><?php echo $this->core->plural(); ?></label>
				<ol>
					<li>
						<input type="text" name="<?php echo $this->field_name( array('{?}', 'creds') ); ?>" id="<?php echo $this->field_id( array('{?}', 'creds') ); ?>" value="<?php echo $this->prefs[0]['creds']; ?>" size="8" />
					</li>
				</ol>

				<label class="subheader"><?php _e('Limit', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<?php echo $this->hook_limit_setting( $this->field_name( array('{?}', 'limit') ), $this->field_id( array('{?}', 'limit') ), $this->prefs[0]['limit'] ); ?>
					</li>

				</ol>

				<!-- Then the log template -->
				<label class="subheader"><?php _e( 'Log template', 'mycred' ); ?></label>
				<ol>
					<li>
						<input type="text" name="<?php echo $this->field_name( array('{?}', 'log') ); ?>" id="<?php echo $this->field_id( array('{?}', 'log') ); ?>" value="<?php echo $this->prefs[0]['log']; ?>" class="long" />
					</li>
				</ol>
			    
				<span class="delete_row" style="position: absolute; left: 0; top: 0; ">
					<a href="javascript:;" class="mycred_sensei_delete button button-primary" title="<?php _e('Delete', MYCRED_SENSEI) ?>">X</a>
				</span>
			</div>
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Sanitise Preferences
	 * @since 1.6
	 * @version 1.0
	 */
	function sanitise_preferences( $data ) {

		$new_data = array();

		if(!empty($data)):
		$i = 0;
		foreach ($data as $key => $each_data) {

			if ( isset( $each_data['limit'] ) && isset( $each_data['limit_by'] ) ) {
				$limit = sanitize_text_field( $each_data['limit'] );
				if ( $limit == '' ) $limit = 0;
				$each_data['limit'] = $limit . '/' . $each_data['limit_by'];
				unset( $each_data['limit_by'] );
			}
			$new_data[$i] = $each_data;

		$i++;
		}
		endif;

		return $new_data;

	}

	
}
endif;