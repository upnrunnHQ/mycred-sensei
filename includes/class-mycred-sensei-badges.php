<?php
/**
 * myCRED_Sensei_Badges Class.
 *
 * @class       myCRED_Sensei_Badges
 * @version		1.0
 * @author 		Kishore Sahoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * myCRED_Sensei_Badges class.
 */
class myCRED_Sensei_Badges {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->includes();

		// echo "string";

		// add_action( 'mycred_edit_badge_before_req', array($this, 'before_wrap_metabox'), 10, 1 );
		// add_action( 'mycred_edit_badge_after_req', array($this, 'after_wrap_metabox'), 10, 1 );
		// add_action( 'mycred_edit_badge_after_req', array($this, 'add_custom_metabox'), 11, 1 );
		
		add_filter( 'mycred_badge_display_requirements', array($this, 'change_badges_column'), 10, 2 );
	
		// add_action( 'do_meta_boxes', array($this, 'remove_metabox') );
		add_action( 'add_meta_boxes_mycred_badge', array($this, 'add_sensei_badge_metabox') );
		add_action( 'mycred_edit_badge_before_actions', array($this, 'add_change_sensei_badge_button') );
		add_action( 'mycred_save_badge', array($this, 'save_sensei_badge'), 10, 1 );
		add_filter( 'postbox_classes_mycred_badge_mycred_sensei_badge_requirements', array( $this, 'metabox_classes' ) );


		add_filter( 'mycred_get_badge', array($this, 'get_badge_sensei'), 10, 3 );

		add_action( 'wp_ajax_mycred_sensei_change_reference', array($this, 'change_reference_callback') );

		add_action( 'sensei_user_course_start', array($this, 'sensei_badge_check_enrolled_course'), 10, 2 );
		add_action( 'sensei_user_course_start', array($this, 'sensei_badge_check_enrolled_course_categories'), 10, 2 );

		add_action( 'sensei_user_quiz_grade', array($this, 'sensei_badge_check_passed_quiz'), 10, 5 );
		add_action( 'sensei_user_quiz_grade', array($this, 'sensei_badge_check_quiz_grade'), 10, 5 );

		add_action( 'sensei_user_course_end', array($this, 'sensei_badge_check_completed_course'), 10, 2 );
		add_action( 'sensei_user_course_end', array($this, 'sensei_badge_check_completed_course_categories'), 10, 2 );
		add_action( 'sensei_user_lesson_end', array($this, 'sensei_badge_check_completed_lesson'), 10, 2 );
	}

	/**
	 * TODO
	 * @param  [type] $badge    [description]
	 * @param  [type] $badge_id [description]
	 * @param  [type] $level    [description]
	 * @return [type]           [description]
	 */
	public function get_badge_sensei($badge, $badge_id, $level){

		return $badge;
	}

	public function remove_metabox(){
		if( !is_admin() ) // todo dynamic from option
			return;

		remove_meta_box('mycred_badge_requirements', 'mycred_badge', 'normal');
	}

	public function add_sensei_badge_metabox() {

		add_meta_box(
			'mycred_sensei_badge_requirements',
			__( 'Sensei Badge Setup', 'mycred' ),
			array( $this, 'metabox_sensei_badge_requirements' ),
			'mycred_badge',
			'normal',
			'high'
		);

	}

	public function change_badges_column($return, $badge_id){
		$is_enabled = get_post_meta( $badge_id, 'enable_sensei_badge', true );
		$sensei_badge_prefs = get_post_meta( $badge_id, 'sensei_badge_prefs', true );

		if( ($is_enabled == 'on') && !empty($sensei_badge_prefs) ){
			$return = '';
			foreach ($sensei_badge_prefs as $level => $badge_data) {
				$return .= '<strong>Level '.$level.':</strong>';
				$return .= '<ul class="mycred-badge-requirement-list">';
				$return .= '<li>'.$badge_data['reference'].'</li>';
				// $return .= '<li>Points for "Website Visit" x 10</li>';
				$return .= '</ul>';
			}
		}

		return $return;
	}

	public function metabox_classes( $classes ) {
		$classes[] = 'mycred-metabox';
		return $classes;
	}

	public function add_change_sensei_badge_button($post){
		$is_enabled = get_post_meta( $post->ID, 'enable_sensei_badge', true );
		echo '<p><input type="checkbox" name="enable_sensei_badge" value="on" id="enable_sensei_badge" '.checked( $is_enabled, 'on', false ).'><label for="enable_sensei_badge">'. __('Enable Sensei Badge', MYCRED_SENSEI) .'</label></p>';
	}

	public function metabox_sensei_badge_requirements($post){

		$metadata = $this->get_sensei_badge_data($post->ID);
		$iterator = (count($metadata) > 0) ? 2 : count($metadata) + 1;

		// echo "<pre>";
		// print_r($metadata);
		// echo "</pre>";

		?>
		<div id="sensei-badge-levels" class="container-setting">
			<div style="display: block; margin-bottom: 10px; text-align: right; "><button type="button" class="button button-seconary button-small mycred_sensei_add_new" id="sensei-badges-add-new-level"><?php _e('Add Level', MYCRED_SENSEI); ?></button></div>
			<?php 
			echo $this->get_repeatable_template();
			echo $this->display_saved_data($metadata); 
			?>
			<div class="repeatable-container" data-template="#template_sensei_badges" data-iterator="<?php echo $iterator; ?>"></div>
		</div>
		<?php

		wp_enqueue_script( 'mycred-sensei-admin' );
		wp_localize_script( 'mycred-sensei-admin', 'mycred_sensei', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'id' => $post->ID,
		) );
	}

	public function display_saved_data($metadata){

		$references  = $this->get_mycred_sensei_references();

		$html = '';
		if(!empty($metadata)){
			foreach ($metadata as $level => $data) {
				$image_html = $this->get_level_image($data, $level);
				if($image_html){
					$img_button_label = __('Change Image', MYCRED_SENSEI);
				} else {
					$img_button_label = __('Set Image', MYCRED_SENSEI);
					$image_html = '';
					$img_container_class = 'empty';
				}

				ob_start();
				?>
				<div class="field-group" style="position:relative;">
					<div class="row badge-level" id="mycred-sensei-badge-level<?php echo $level; ?>" data-level="<?php echo $level; ?>">
						<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">
				         	<div class="level-image">
				            	<div class="level-image-wrapper image-wrapper <?php echo $img_container_class; ?> dashicons"><?php echo $image_html; ?></div>
				            	<div class="level-image-actions"><button type="button" class="button button-secondary change-level-image" data-level="<?php echo $level; ?>"><?php echo $img_button_label; ?></button></div>
				         	</div>
				         	<div class="label-field"><input type="text" placeholder="Level <?php echo $level; ?>" name="mycred_sensei_badge[levels][<?php echo $level; ?>][label]" value=""></div>
				      	</div>
				      	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				         	<div class="req-title">
				            	<?php _e('Requirement', MYCRED_SENSEI); ?>
				         	</div>
				         	<div class="level-requirements">
				            	<div class="row row-narrow" id="level0requirement0" data-row="0">
				               		<div class="col-md-6 form">
				                  		<div class="form-group">
				                     		<select name="mycred_sensei_badge[levels][<?php echo $level; ?>][reference]" class="form-control reference">
					                        	<option value=""><?php _e('Select Reference', MYCRED_SENSEI); ?></option>
						                        <?php 
						                        if(!empty($references)):
						                        foreach ($references as $ref_id => $ref_label) {
						                        	echo '<option value="'.$ref_id.'" '.selected( $data['reference'], $ref_id, false ).'>'.$ref_label.'</option>';
						                        }
						                        endif;
						                        ?>
	 			                     		</select>
				                  		</div>
				               		</div>
				               		<div class="col-md-6 form">
				                  		<div class="form-group">
				                  			<div class="wrapper_object" data-level="<?php echo $level; ?>">
				                     		<select name="mycred_sensei_badge[levels][<?php echo $level; ?>][object]" class="form-control object">
					                        	<option value=""><?php _e('Please select reference', MYCRED_SENSEI); ?></option>
				                     		</select>
				                     		</div>
				                  		</div>
				               		</div>
				            	</div>
				         	</div>
				      	</div>
				   	</div>
				   	<span class="delete_exist_row" style="position: absolute; left: 0; top: 0; ">
						<a href="javascript:;" class="mycred_sensei_delete button button-primary" title="<?php _e('Delete', MYCRED_SENSEI) ?>">X</a>
					</span>
			   	</div>
				<?php
				$html .= ob_get_clean();
			}// end foreach
		} else {
			$img_button_label = __('Set Image', MYCRED_SENSEI);
			$image_html = '';
			$img_container_class = 'empty';
			$level = 1;
			ob_start();
			?>
			<div class="field-group" style="position:relative;">
				<div class="row badge-level" id="mycred-sensei-badge-level<?php echo $level; ?>" data-level="<?php echo $level; ?>">
					<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">
			         	<div class="level-image">
			            	<div class="level-image-wrapper image-wrapper <?php echo $img_container_class; ?> dashicons"><?php echo $image_html; ?></div>
			            	<div class="level-image-actions"><button type="button" class="button button-secondary change-level-image" data-level="<?php echo $level; ?>"><?php echo $img_button_label; ?></button></div>
			         	</div>
			         	<div class="label-field"><input type="text" placeholder="Level <?php echo $level; ?>" name="mycred_sensei_badge[levels][<?php echo $level; ?>][label]" value=""></div>
			      	</div>
			      	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			         	<div class="req-title">
			            	<?php _e('Requirement', MYCRED_SENSEI); ?>
			         	</div>
			         	<div class="level-requirements">
			            	<div class="row row-narrow" id="level0requirement0" data-row="0">
			               		<div class="col-md-6 form">
			                  		<div class="form-group">
			                     		<select name="mycred_sensei_badge[levels][<?php echo $level; ?>][reference]" class="form-control reference">
				                        	<option value=""><?php _e('Select Reference', MYCRED_SENSEI); ?></option>
					                        <?php 
					                        if(!empty($references)):
					                        foreach ($references as $ref_id => $ref_label) {
					                        	echo '<option value="'.$ref_id.'" '.selected( $data['reference'], $ref_id, false ).'>'.$ref_label.'</option>';
					                        }
					                        endif;
					                        ?>
 			                     		</select>
			                  		</div>
			               		</div>
			               		<div class="col-md-6 form">
			                  		<div class="form-group">
			                  			<div class="wrapper_object" data-level="<?php echo $level; ?>">
			                     		<select name="mycred_sensei_badge[levels][<?php echo $level; ?>][object]" class="form-control object">
				                        	<option value=""><?php _e('Please select reference', MYCRED_SENSEI); ?></option>
			                     		</select>
			                     		</div>
			                  		</div>
			               		</div>
			            	</div>
			         	</div>
			      	</div>
			   	</div>
			   	<span class="delete_exist_row" style="position: absolute; left: 0; top: 0; ">
					<a href="javascript:;" class="mycred_sensei_delete button button-primary" title="<?php _e('Delete', MYCRED_SENSEI) ?>">X</a>
				</span>
		   	</div>
			<?php
			$html .= ob_get_clean();
		}

		return $html;
	}

	public function get_repeatable_template(){

		$references  = $this->get_mycred_sensei_references();

		ob_start();
		?>
		<script type="text/template" id="template_sensei_badges">
			<div class="field-group" style="position:relative;">
				<div class="row badge-level" id="mycred-sensei-badge-level{?}" data-level="{?}">
					<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">
			         	<div class="level-image">
			            	<div class="level-image-wrapper image-wrapper empty dashicons"></div>
			            	<div class="level-image-actions"><button type="button" class="button button-secondary change-level-image" data-level="{?}">Set Image</button></div>
			         	</div>
			         	<div class="label-field"><input type="text" placeholder="Level {?}" name="mycred_sensei_badge[levels][{?}][label]" value=""></div>
			      	</div>
			      	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			         	<div class="req-title">
			            	<?php _e('Requirement', MYCRED_SENSEI); ?>
			         	</div>
			         	<div class="level-requirements">
			            	<div class="row row-narrow" id="level0requirement0" data-row="0">
			               		<div class="col-md-6 form">
			                  		<div class="form-group">
			                     		<select name="mycred_sensei_badge[levels][{?}][reference]" class="form-control reference">
				                        	<option value=""><?php _e('Select Reference', MYCRED_SENSEI); ?></option>
					                        <?php 
					                        if(!empty($references)):
					                        foreach ($references as $ref_id => $ref_label) {
					                        	echo '<option value="'.$ref_id.'" '.selected( $data['reference'], $ref_id, false ).'>'.$ref_label.'</option>';
					                        }
					                        endif;
					                        ?>
			                     		</select>
			                  		</div>
			               		</div>
			               		<div class="col-md-6 form">
			                  		<div class="form-group">
			                  			<div class="wrapper_object" data-level="{?}">
			                     		<select name="mycred_sensei_badge[levels][{?}][object]" class="form-control object">
				                        	<option value=""><?php _e('Please select reference', MYCRED_SENSEI); ?></option>
			                     		</select>
			                     		</div>
			                  		</div>
			               		</div>
			            	</div>
			         	</div>
			      	</div>
			   	</div>
			   	<span class="delete_exist_row" style="position: absolute; left: 0; top: 0; ">
					<a href="javascript:;" class="mycred_sensei_delete button button-primary" title="<?php _e('Delete', MYCRED_SENSEI) ?>">X</a>
				</span>
		   	</div>
	   	</script>
		<?php

		return ob_get_clean();
	}

	public function get_level_image( $setup, $level = 0 ) {

			$image = false;

			if ( isset($setup['attachment_id']) && ($setup['attachment_id'] > 0) ) {

				$_image = wp_get_attachment_url( $setup['attachment_id'] );
				if ( strlen( $_image ) > 5 )
					$image = '<img src="' . $_image . '" alt="Badge level image" /><input type="hidden" name="mycred_sensei_badge[levels][' . $level . '][attachment_id]" value="' . $setup['attachment_id'] . '" /><input type="hidden" name="mycred_sensei_badge[levels][' . $level . '][image_url]" value="" />';

			}
			else {

				if ( isset($setup['image_url']) && (strlen( $setup['image_url'] ) > 5) )
					$image = '<img src="' . $setup['image_url'] . '" alt="Badge level image" /><input type="hidden" name="mycred_sensei_badge[levels][' . $level . '][attachment_id]" value="0" /><input type="hidden" name="mycred_sensei_badge[levels][' . $level . '][image_url]" value="' . $setup['image_url'] . '" />';

			}

			return $image;

		}

	public function save_sensei_badge($post_id){

		if(!isset($_POST['mycred_sensei_badge']['levels']) || empty($_POST['mycred_sensei_badge']['levels']))
			return;

		if(is_array($_POST['mycred_sensei_badge']['levels'])):
		$sensei_levels = array();
		$i=1;
		foreach ($_POST['mycred_sensei_badge']['levels'] as $key => $level) {
			if(empty($level['reference']) || empty($level['object']) )
				continue;

			$sensei_levels[$i] = $level;

		$i++;
		}
		endif;

		if(isset($_POST['enable_sensei_badge']) && $_POST['enable_sensei_badge'] ){
			update_post_meta( $post_id, 'enable_sensei_badge', 'on' );
			// Save Sensei Badge Setup
			update_post_meta( $post_id, 'sensei_badge_prefs', $sensei_levels );
		} else {
			delete_post_meta( $post_id, 'enable_sensei_badge' );
		}
	}

	public function before_wrap_metabox(){
		?>
		<tr class="bodered-row" id="mycred-badge-image<?php echo $row; ?>">
			<td>start</td>
		</tr>
		<?php

	}

	public function after_wrap_metabox(){
		?>
		<tr class="bodered-row" id="mycred-badge-image">
			<td>after</td>
		</tr>
		<?php
	}

	public function add_custom_metabox($post){
		?>
		<tr class="bodered-row" id="mycred-badge-image">
			<td>ok</td>
		</tr>
		<?php
	}

	public function get_sensei_badge_data($post_id){
		$meta = get_post_meta( $post_id, 'sensei_badge_prefs', true );

		return apply_filters( 'get_sensei_badge_data', $meta );
	}

	public function get_mycred_sensei_references(){
		global $mycred_sensei;

		$hooks = $mycred_sensei->mycred_sensei_hooks();
		if(!empty($hooks) && is_array($hooks)){
			$references = array();
			foreach ($hooks as $key => $hook) {
				if(!$hook['references'] || !$hook['reference_title'])
					continue;

				$references[$key] = $hook['reference_title'];
			}
		}

		return $references;
	}

	public function change_reference_callback(){
		if(!isset($_POST['reference']) || empty($_POST['reference']))
			wp_die( 'Reference Not Valid' );

		$post_id = absint( $_POST['id'] );
		$reference = esc_html( $_POST['reference'] );
		$level = esc_html( $_POST['level'] );
		$data = $this->get_sensei_badge_data($post_id);
		$objval = (isset($data[$level]['object'])) ? $data[$level]['object'] : false;

		$return = '';
		switch ($reference) {

			case 'enrolled_course':
			case 'completed_course':

				if(!class_exists('Sensei_Course'))
					wp_die( __('Please activate WooThemes Sensei plugin.', MYCRED_SENSEI ) );

				$courses = Sensei_Course::get_all_courses();
				ob_start();
				?>
				<select class="form-control object" name="mycred_sensei_badge[levels][<?php echo $level; ?>][object]">
					<option value="any"><?php _e('Any Courses', MYCRED_SENSEI); ?></option>
					<?php 
					if(!empty($courses)):
					foreach ($courses as $course) {
					echo '<option value="'.$course->ID.'" '.selected( $objval, $course->ID, false ).'>'.$course->post_title.'</option>';
					}
					endif;
					?>
				</select>
				<?php
				$return = ob_get_clean();

				break;
				
			case 'enrolled_course_category':
			case 'completed_course_category':

				$course_cats = get_terms('course-category', array('hide_empty' => false) );
				ob_start();
				?>
				<select class="form-control object" name="mycred_sensei_badge[levels][<?php echo $level; ?>][object]">
					<option value="any"><?php _e('Any Course Categories', MYCRED_SENSEI); ?></option>
					<?php 
					if(!empty($course_cats)):
					foreach ($course_cats as $course_cat) {
					echo '<option value="'.$course_cat->term_id.'" '.selected( $objval, $course_cat->term_id, false ).'>'.$course_cat->name.'</option>';
					}
					endif;
					?>
				</select>
				<?php
				$return = ob_get_clean();

				break;
				
			case 'passed_quiz':

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
				ob_start();
				?>
				<select class="form-control object" name="mycred_sensei_badge[levels][<?php echo $level; ?>][object]">
					<option value="any"><?php _e('Any Quizes', MYCRED_SENSEI); ?></option>
					<?php 
					if(!empty($quizes)):
					foreach ($quizes as $quiz) {
					echo '<option value="'.$quiz->ID.'" '.selected( $objval, $quiz->ID, false ).'>'.$quiz->post_title.'</option>';
					}
					endif;
					?>
				</select>
				<?php
				$return = ob_get_clean();

				break;

			case 'quiz_grade':

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
				$grade_val = (isset($data[$level]['object'])) ? $data[$level]['grade'] : false;
				ob_start();
				?>
				<div class="row form">
				   	<div class="col-md-3"><input type="text" class="form-control" name="mycred_sensei_badge[levels][<?php echo $level; ?>][grade]" placeholder="0" value="<?php echo $grade_val; ?>"></div>
				   	<div class="col-md-2">% for : </div>
				   	<div class="col-md-7">
				      	<select class="form-control object" name="mycred_sensei_badge[levels][<?php echo $level; ?>][object]">
							<option value="any"><?php _e('Any Quizes', MYCRED_SENSEI); ?></option>
							<?php 
							if(!empty($quizes)):
							foreach ($quizes as $quiz) {
							echo '<option value="'.$quiz->ID.'" '.selected( $objval, $quiz->ID, false ).'>'.$quiz->post_title.'</option>';
							}
							endif;
							?>
						</select>
				   </div>
				</div>
				<?php
				$return = ob_get_clean();

				break;
				
			case 'completed_lesson':
				$args = array(
		            'post_type'=>'lesson',
		            'post_status' => 'publish',
		            'suppress_filters' 	=> false,
		            'numberposts' => -1, // legacy support
		            'post_per_page' => -1
		        );

				$lessons = get_posts( $args );
				ob_start();
				?>
				<select class="form-control object" name="mycred_sensei_badge[levels][<?php echo $level; ?>][object]">
					<option value="any"><?php _e('Any Lessons', MYCRED_SENSEI); ?></option>
					<?php 
					if(!empty($lessons)):
					foreach ($lessons as $lesson) {
					echo '<option value="'.$lesson->ID.'" '.selected( $objval, $lesson->ID, false ).'>'.$lesson->post_title.'</option>';
					}
					endif;
					?>
				</select>
				<?php
				$return = ob_get_clean();

				break;
		}

		echo apply_filters( 'mycred_sensei_change_reference_callback', $return, $reference );
		wp_die();
	}

	public function get_sensei_badge_prefs($reference_keys){
		global $wpdb;

		// var_dump(is_array($reference_keys));
		// echo "<pre>";
		// print_r($reference_keys);
		// echo "</pre>";
		// var_dump(in_array('quiz_grade', $reference_keys));

		$references = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'sensei_badge_prefs';" );
		$lists = array();
		if ( ! empty( $references ) ) {
			foreach ( $references as $entry ) {

				// Manual badges should be ignored
				if ( absint( get_post_meta( $entry->post_id, 'manual_badge', true ) ) === 1 ) continue;

				$levels = maybe_unserialize( $entry->meta_value );
				if ( ! is_array( $levels ) || empty( $levels ) ) continue;

				foreach ( $levels as $level => $setup ) {

					if ( is_array($reference_keys) && !in_array($setup['reference'], $reference_keys) ) continue;

					if ( !is_array($reference_keys) && ($reference_keys != $setup['reference']) ) continue;

					$grade = ( ($setup['reference'] == 'quiz_grade') && (isset($setup['grade'])) ) ? $setup['grade'] : null;

					$lists[] = array(
						'badge_id' => $entry->post_id,
						'level' => $level,
						'reference' => $setup['reference'],
						'object' => $setup['object'],
						'grade' => $grade
					);
				}

			}
		}

		return $lists;
	}

	public function sensei_badge_check_enrolled_course($user_id, $course_id){
		$badges = $this->get_sensei_badge_prefs( 'enrolled_course' );
		if(empty($badges))
			return;

		if(!function_exists('mycred_assign_badge_to_user'))
			return;

		foreach ($badges as $key => $badge) {

			if($badge['object'] == 'any'){
				mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
			}

			if($badge['object'] != $course_id)
				continue;

			if( get_post_meta( $badge['badge_id'], 'enable_sensei_badge', true ) != 'on' )
				continue;

			mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
		}
	}

	public function sensei_badge_check_enrolled_course_categories($user_id, $course_id){
		$badges = $this->get_sensei_badge_prefs( 'enrolled_course_category' );
		if(empty($badges))
			return;

		if(!function_exists('mycred_assign_badge_to_user'))
			return;

		$post_cats = wp_get_post_terms( $course_id, 'course-category', array('fields' => 'ids') );
		foreach ($badges as $key => $badge) {

			if($badge['object'] == 'any'){
				mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
			}

			if( !in_array( $badge['object'], $post_cats))
				continue;

			if( get_post_meta( $badge['badge_id'], 'enable_sensei_badge', true ) != 'on' )
				continue;

			mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
		}
	}


	public function sensei_badge_check_passed_quiz($user_id, $quiz_id, $grade, $quiz_passmark, $quiz_grade_type){
		$badges = $this->get_sensei_badge_prefs( 'passed_quiz' );
		if(empty($badges))
			return;

		if(!function_exists('mycred_assign_badge_to_user'))
			return;

		foreach ($badges as $key => $badge) {

			if($badge['object'] == 'any'){
				mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
			}

			if($badge['object'] != $quiz_id)
				continue;

			if( get_post_meta( $badge['badge_id'], 'enable_sensei_badge', true ) != 'on' )
				continue;

			mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
		}
	}


	public function sensei_badge_check_quiz_grade($user_id, $quiz_id, $grade, $quiz_passmark, $quiz_grade_type){
		$badges = $this->get_sensei_badge_prefs( 'quiz_grade' );
		if(empty($badges))
			return;

		if(!function_exists('mycred_assign_badge_to_user'))
			return;

		foreach ($badges as $key => $badge) {

			if($badge['object'] == 'any'){
				if ( absint($grade) > absint( $badge['grade'] ) ){
					mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
				}
			}

			if($badge['object'] != $quiz_id)
				continue;

			if( get_post_meta( $badge['badge_id'], 'enable_sensei_badge', true ) != 'on' )
				continue;

			if ( absint($grade) < absint( $badge['grade'] ) )
				continue;

			mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
		}
	}

	public function sensei_badge_check_completed_course($user_id, $course_id){
		$badges = $this->get_sensei_badge_prefs( 'completed_course' );
		if(empty($badges))
			return;

		if(!function_exists('mycred_assign_badge_to_user'))
			return;

		foreach ($badges as $key => $badge) {

			if($badge['object'] == 'any'){
				mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); return;
			}

			if($badge['object'] != $course_id)
				continue;

			if( get_post_meta( $badge['badge_id'], 'enable_sensei_badge', true ) != 'on' )
				continue;

			mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
		}
	}

	public function sensei_badge_check_completed_course_categories($user_id, $course_id){
		$badges = $this->get_sensei_badge_prefs( 'completed_course_category' );
		if(empty($badges))
			return;

		if(!function_exists('mycred_assign_badge_to_user'))
			return;

		$post_cats = wp_get_post_terms( $course_id, 'course-category', array('fields' => 'ids') );
		foreach ($badges as $key => $badge) {

			if($badge['object'] == 'any'){
				mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
			}

			if( !in_array( $badge['object'], $post_cats))
				continue;

			if( get_post_meta( $badge['badge_id'], 'enable_sensei_badge', true ) != 'on' )
				continue;

			mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
		}
	}

	public function sensei_badge_check_completed_lesson($user_id, $lesson_id){
		$badges = $this->get_sensei_badge_prefs( 'completed_lesson' );
		if(empty($badges))
			return;

		if(!function_exists('mycred_assign_badge_to_user'))
			return;

		foreach ($badges as $key => $badge) {

			if($badge['object'] == 'any'){
				mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
			}

			if($badge['object'] != $lesson_id)
				continue;

			if( get_post_meta( $badge['badge_id'], 'enable_sensei_badge', true ) != 'on' )
				continue;

			mycred_assign_badge_to_user($user_id, $badge['badge_id'], $badge['level']); break;
		}
	}

	public function includes(){
		
	}

}

$GLOBALS[ 'mycred_sensei_badge' ] = new myCRED_Sensei_Badges();