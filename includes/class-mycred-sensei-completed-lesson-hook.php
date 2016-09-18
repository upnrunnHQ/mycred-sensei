<?php
/**
 * Completed Lesson
 */
if ( ! class_exists( 'myCRED_Sensei_Completed_Lesson_Hook' ) ) :
class myCRED_Sensei_Completed_Lesson_Hook extends myCRED_Hook {

	/**
	 * Construct
	 */
	function __construct( $hook_prefs, $type ) {

		wp_enqueue_script( 'mycred-sensei-admin' );

		$defaults = array(
			array(
				'lesson_id'   => 'any',
				'creds'   => 1,
				'log'     => __('%plural% for completed lesson', MYCRED_SENSEI ),
				'limit'  => '0/x'
			)
		);

		if ( isset( $hook_prefs['completed_lesson'] ) )
			$defaults = $hook_prefs['completed_lesson'];

		parent::__construct( array(
			'id'       => 'completed_lesson',
			'defaults' => $defaults
		), $hook_prefs, $type );
	}

	/**
	 * Hook into WordPress
	 */
	public function run() {
		add_action( 'sensei_user_lesson_end', array($this, 'do_log'), 10, 2 );
	}


	public function do_log($user_id, $lesson_id){

		// Check for exclusions
		if ( $this->core->exclude_user( $user_id ) === true ) return;

		// check if match selected lesson
		$lesson_ids = wp_list_pluck( $this->prefs, 'lesson_id' );
		if(!in_array('any', $lesson_ids) && !in_array($lesson_id, $lesson_ids) ) return;

		$creds = 0;
		// process any
		if(in_array('any', $lesson_ids)){
			$need_process = true;

			$used_any = filter_by_value($this->prefs, 'lesson_id', 'any');
			$instance_any = key($used_any);

			// Make sure we award points other then zero
			if ( ! isset( $this->prefs[ $instance_any ]['creds'] ) ) $need_process = false;
			if ( empty( $this->prefs[ $instance_any ]['creds'] ) || $this->prefs[ $instance_any ]['creds'] == 0 ) $need_process = false;	

			// check limit
			if ($this->over_hook_limit( $instance_any, 'completed_lesson', $user_id )) $need_process = false;

			if($need_process){
				$entry = $this->get_replaced_log($instance_any, $lesson_id);
				$creds  = $creds + $this->core->number($this->prefs[ $instance_any ]['creds']);
			} 
		}

		// need to process others?
		if(in_array($lesson_id, $lesson_ids)){
			$need_process = true;

			$used = filter_by_value($this->prefs, 'lesson_id', $lesson_id);
			$instance = key($used);

			// Make sure we award points other then zero
			if ( ! isset( $this->prefs[ $instance ]['creds'] ) ) $need_process = false;
			if ( empty( $this->prefs[ $instance ]['creds'] ) || $this->prefs[ $instance ]['creds'] == 0 ) $need_process = false;

			if ($this->over_hook_limit( $instance, 'completed_lesson', $user_id )) $need_process = false;

			if ($need_process){ 
				$entry = $this->get_replaced_log($instance, $lesson_id);
				$creds  = $creds + $this->core->number($this->prefs[ $instance ]['creds']);
			}
		}

		// Prep 
		$data = array( 'ref_type' => 'lesson' );
		
		// Make sure this is unique
		if ( $this->core->has_entry( 'completed_lesson', $lesson_id, $user_id, $data, $this->mycred_type ) ) return;
		
		// Check creds
		if ( $creds > 0 ){

			// echo $this->core->format_number($creds);

			$this->core->add_creds(
				'completed_lesson',
				$user_id,
				$this->core->format_number($creds),
				$entry,
				$lesson_id,
				$data,
				$this->mycred_type
			);
		}
	}

	public function get_replaced_log($instance, $lesson_id){
		$replace = get_the_title( $lesson_id );
		return str_replace('%lesson_title%', $replace, $this->prefs[ $instance ]['log']);
	}
	

	/**
	 * Add Settings
	 */
	 public function preferences() {
		// Our settings are available under $this->prefs
		$prefs = $this->prefs; 

		if(!class_exists('Sensei_Lesson')){
			echo '<h2>'.__('Please activate WooThemes Sensei plugin.', MYCRED_SENSEI ).'</h2>';
			return;
		}
		
		$args = array(
            'post_type'=>'lesson',
            'post_status' => 'publish',
            'suppress_filters' 	=> false,
            'numberposts' => -1, // legacy support
            'post_per_page' => -1
        );

		$lessons = get_posts( $args );

		// echo "<pre>";
		// print_r($lessons);
		// echo "</pre>";
		?>
		<div class="container-setting" style="padding: 10px 0;">
			<?php
			echo $this->get_repeatable_template($lessons);
			echo $this->display_saved_data($prefs, $lessons); 
			?>

			<div class="repeatable-container" data-template="#template_completed_lesson"></div>
		    <input type="button" value="<?php _e('Add New', MYCRED_SENSEI); ?>" class="mycred_sensei_add_new button button-primary" />
		</div>
		<?php
	}

	public function display_saved_data($prefs, $lessons){

		$html = '';
		if(!empty($prefs)){
			foreach ($prefs as $key => $pref) {
			
			ob_start();
			?>
			<div class="field-group" style="position: relative; padding-left: 50px; ">
				<!-- select lesson -->
				<label class="subheader"><?php _e('Lesson', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<select name="<?php echo $this->field_name( array('exist'.$key, 'lesson_id') ); ?>" id="<?php echo $this->field_name( array('exist'.$key, 'lesson_id') ); ?>">
							<option value="any"><?php _e('Any Lessons', MYCRED_SENSEI); ?></option>
							<?php 
							if(!empty($lessons)):
							foreach ($lessons as $lesson) {
							echo '<option value="'.$lesson->ID.'" '.selected( $pref['lesson_id'], $lesson->ID, false ).'>'.$lesson->post_title.'</option>';
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

	public function get_repeatable_template($lessons){
		ob_start();
		?>

		<!-- template -->
		<script type="text/template" id="template_completed_lesson">
			<div class="field-group" style="position: relative; padding-left: 50px; ">
			    <!-- select lesson -->
				<label class="subheader"><?php _e('Lesson', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<select name="<?php echo $this->field_name( array('{?}','lesson_id') ); ?>" id="<?php echo $this->field_name( array('{?}','lesson_id') ); ?>">
							<option value="any"><?php _e('Any Lessons', MYCRED_SENSEI); ?></option>
							<?php 
							if(!empty($lessons)):
							foreach ($lessons as $lesson) {
							echo '<option value="'.$lesson->ID.'">'.$lesson->post_title.'</option>';
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