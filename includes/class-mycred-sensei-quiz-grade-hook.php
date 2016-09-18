<?php
/**
 * Enrolled reach grade of quiz
 */
if ( ! class_exists( 'myCRED_Sensei_Quiz_Grade_Hook' ) ) :
class myCRED_Sensei_Quiz_Grade_Hook extends myCRED_Hook {

	/**
	 * Construct
	 */
	function __construct( $hook_prefs, $type ) {

		wp_enqueue_script( 'mycred-sensei-admin' );

		$defaults = array(
			array(
				'quiz_id'   => 'any',
				'min_grade' => 100,
				'creds'   => 1,
				'log'     => __('%plural% for passed quiz with grade', MYCRED_SENSEI ),
				'limit'  => '0/x'
			)
		);

		if ( isset( $hook_prefs['quiz_grade'] ) )
			$defaults = $hook_prefs['quiz_grade'];

		parent::__construct( array(
			'id'       => 'quiz_grade',
			'defaults' => $defaults
		), $hook_prefs, $type );
	}

	/**
	 * Hook into WordPress
	 */
	public function run() {
		add_action( 'sensei_user_quiz_grade', array($this, 'do_log'), 10, 5 );
	}


	public function do_log($user_id, $quiz_id, $grade, $quiz_passmark, $quiz_grade_type){

		// if pass required enabled and user grade lower than passmark
		// $pass_required = get_post_meta( $quiz_id, '_pass_required', true );
		// if($pass_required == 'on' && ($grade < $quiz_passmark) ) return;

		// Check for exclusions
		if ( $this->core->exclude_user( $user_id ) === true ) return;

		// check if match selected quiz
		$quiz_ids = wp_list_pluck( $this->prefs, 'quiz_id' );
		if(!in_array('any', $quiz_ids) && !in_array($quiz_id, $quiz_ids) ) return;

		$creds = 0;
		// process any
		if(in_array('any', $quiz_ids)){
			$need_process = true;

			$used_any = filter_by_value($this->prefs, 'quiz_id', 'any');
			$instance_any = key($used_any);

			// Make sure we award points other then zero
			if ( ! isset( $this->prefs[ $instance_any ]['creds'] ) ) $need_process = false;
			if ( empty( $this->prefs[ $instance_any ]['creds'] ) || $this->prefs[ $instance_any ]['creds'] == 0 ) $need_process = false;	

			// check limit
			if ($this->over_hook_limit( $instance_any, 'quiz_grade', $user_id )) $need_process = false;

			// check min grade
			if ( ! isset( $this->prefs[ $instance_any ]['min_grade'] ) ) $need_process = false;
			if ( empty( $this->prefs[ $instance_any ]['min_grade'] ) || $this->prefs[ $instance_any ]['min_grade'] == 0 ) $need_process = false;	
			if ( absint($grade) < absint( $this->prefs[ $instance_any ]['min_grade'] ) ) $need_process = false;

			if($need_process){
				$entry = $this->get_replaced_log($instance_any, $quiz_id);
				$creds  = $creds + $this->core->number($this->prefs[ $instance_any ]['creds']);
			}
		}

		// need to process others?
		if(in_array($quiz_id, $quiz_ids)){
			$need_process = true;

			$used = filter_by_value($this->prefs, 'quiz_id', $quiz_id);
			$instance = key($used);

			// Make sure we award points other then zero
			if ( ! isset( $this->prefs[ $instance ]['creds'] ) ) $need_process = false;
			if ( empty( $this->prefs[ $instance ]['creds'] ) || $this->prefs[ $instance ]['creds'] == 0 ) $need_process = false;

			// check limit
			if ($this->over_hook_limit( $instance, 'quiz_grade', $user_id )) $need_process = false;

			// check min grade
			if ( ! isset( $this->prefs[ $instance ]['min_grade'] ) ) $need_process = false;
			if ( empty( $this->prefs[ $instance ]['min_grade'] ) || $this->prefs[ $instance ]['min_grade'] == 0 ) $need_process = false;
			if ( absint($grade) < absint( $this->prefs[ $instance ]['min_grade'] ) ) $need_process = false;

			if ($need_process){ 
				$entry = $this->get_replaced_log($instance, $quiz_id);
				$creds = $creds + $this->core->number($this->prefs[ $instance ]['creds']);
			}
		}

		$data = array( 'ref_type' => 'quiz' );

		// Make sure this is unique
		if ( $this->core->has_entry( 'quiz_grade', $quiz_id, $user_id, $data, $this->mycred_type ) ) return;

		// Check creds
		if ( $creds > 0 ){
			$this->core->add_creds(
				'quiz_grade',
				$user_id,
				$this->core->format_number($creds),
				$entry,
				$quiz_id,
				$data,
				$this->mycred_type
			);
		}
	}



	public function get_replaced_log($instance, $quiz_id){
		$replace = get_the_title( $quiz_id );
		$replaced = str_replace('%min_grade%', $this->prefs[ $instance ]['min_grade'] . '%', $this->prefs[ $instance ]['log']);
		$replaced = str_replace('%quiz_title%', $replace, $replaced);
		return $replaced;
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
		
		$args = array(
                'post_type' => 'quiz',
                'posts_per_page' 		=> -1,
                'orderby'         	=> 'title',
                'order'           	=> 'ASC',
                'post_status'      	=> 'any',
                'suppress_filters' 	=> false,
        );

        $wp_query_obj =  new WP_Query( $args );
		$quizes = $wp_query_obj->posts;

		// echo "<pre>";
		// print_r($quizes);
		// echo "</pre>";
		?>
		<div class="container-setting" style="padding: 10px 0;">
			<?php
			echo $this->get_repeatable_template($quizes);
			echo $this->display_saved_data($prefs, $quizes); 
			?>

			<div class="repeatable-container" data-template="#template_quiz_grade"></div>
		    <input type="button" value="<?php _e('Add New', MYCRED_SENSEI); ?>" class="mycred_sensei_add_new button button-primary" />
		</div>
		<?php
	}

	public function display_saved_data($prefs, $quizes){

		$html = '';
		if(!empty($prefs)){
			foreach ($prefs as $key => $pref) {
			
			ob_start();
			?>
			<div class="field-group" style="position: relative; padding-left: 50px; ">
				<!-- select quiz -->
				<label class="subheader"><?php _e('Quiz', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<select name="<?php echo $this->field_name( array('exist'.$key, 'quiz_id') ); ?>" id="<?php echo $this->field_name( array('exist'.$key, 'quiz_id') ); ?>">
							<option value="any"><?php _e('Any Quizes', MYCRED_SENSEI); ?></option>
							<?php 
							if(!empty($quizes)):
							foreach ($quizes as $quiz) {
							echo '<option value="'.$quiz->ID.'" '.selected( $pref['quiz_id'], $quiz->ID, false ).'>'.$quiz->post_title.'</option>';
							}
							endif;
							?>
						</select>
					</li>
				</ol>
				<!-- min grade -->
				<label class="subheader"><?php _e('Min Grade', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<input type="number" min="0" max="100" name="<?php echo $this->field_name( array('exist'.$key, 'min_grade') ); ?>" id="<?php echo $this->field_id( array('exist'.$key, 'min_grade') ); ?>" value="<?php echo intval($pref['min_grade']); ?>" size="8" />
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

	public function get_repeatable_template($quizes){
		ob_start();
		?>

		<!-- template -->
		<script type="text/template" id="template_quiz_grade">
			<div class="field-group" style="position: relative; padding-left: 50px; ">
			    <!-- select quiz -->
				<label class="subheader"><?php _e('Quiz', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<select name="<?php echo $this->field_name( array('{?}','quiz_id') ); ?>" id="<?php echo $this->field_name( array('{?}','quiz_id') ); ?>">
							<option value="any"><?php _e('Any Quizes', MYCRED_SENSEI); ?></option>
							<?php 
							if(!empty($quizes)):
							foreach ($quizes as $quiz) {
							echo '<option value="'.$quiz->ID.'">'.$quiz->post_title.'</option>';
							}
							endif;
							?>
						</select>
					</li>
				</ol>

				<label class="subheader"><?php _e('Min Grade', MYCRED_SENSEI); ?></label>
				<ol>
					<li>
						<input type="number" min="0" max="100" name="<?php echo $this->field_name( array('{?}', 'min_grade') ); ?>" id="<?php echo $this->field_id( array('{?}', 'min_grade') ); ?>" value="<?php echo $this->prefs[0]['min_grade']; ?>" size="8" />
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

			if(isset($each_data['min_grade'])){
				$percent = intval($each_data['min_grade']);
				if($percent > 100)
					$percent = 100;

				$each_data['min_grade'] = $percent;
			}

			$new_data[$i] = $each_data;

		$i++;
		}
		endif;

		return $new_data;

	}
}
endif;