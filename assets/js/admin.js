(function ($) {

	/*
	 * myCRED_repeat
	 */
	var myCRED_repeat = function(el){
	 
	    /*
	     * Variables accessible
	     * in the class
	     */
	    var s = {};
	 
	    /*
	     * Can access this.method
	     * inside other methods using
	     * root.method()
	     */
	    var root = this;
	 
	    /*
	     * Constructor
	     */
	    this.construct = function(el){
	    	s.this = el;
	        s.wrapper = el.closest('.container-setting');
	        s.repeatebleContainer = s.wrapper.find('.repeatable-container');
	        s.template = s.repeatebleContainer.attr('data-template');
	        s.iterator = parseInt( s.repeatebleContainer.attr('data-iterator') ) + 1 || 0;
	        s.itemContainer = ".field-group";
	        s.prefix = "";
	        s.total = parseInt(s.iterator) + $(s.repeatebleContainer).find(s.itemContainer).length;
	    };

		/**
		 * Alter the given template to make
		 * each form field name unique
		 * @return {jQuery object}
		 */
		var getUniqueTemplate = function () {
			// alert(s.total);
			var template = $(s.template).html();
			template = template.replace(/{\?}/g, s.prefix + s.total); 	// {?} => iterated placeholder
			template = template.replace(/\{[^\?\}]*\}/g, ""); 	// {valuePlaceholder} => ""
			return $(template);
		};

	    /**
		 * Add an element to the target
		 * @return null
		 */
		var createOne = function() {
			getUniqueTemplate().appendTo(s.repeatebleContainer);
			s.total++;
		};

	    this.addField = function(){
	    	// alert(s.total);
			createOne();
	    };

	    this.removeField = function(){
	    	// alert('remove '+ s.wrapper.attr('class') +' youk');
	    	s.this.parents(s.itemContainer).first().remove();
			s.total--;
	    };
	 
	 
	    /*
	     * Pass el when class instantiated
	     */
	    this.construct(el);
	 
	};
	 

	$(document).on("click", '.mycred_sensei_add_new', function(){
		var myCRED_Repeater = new myCRED_repeat( $(this) );
		myCRED_Repeater.addField();
	});

	$(document).on("click", '.mycred_sensei_delete', function(){
		var myCRED_Repeater = new myCRED_repeat( $(this) );
		myCRED_Repeater.removeField();
	});


	/**
	 * SENSEI BADGE SCRIPTS
	 * @param  {String} ){		if (             $(this).find( ':selected' ).val() ! [description]
	 * @return {[type]}         [description]
	 */
    $( document ).on('change', 'select.limit-toggle', function(){

		if ( $(this).find( ':selected' ).val() != 'x' )
			$(this).prev().attr( 'type', 'text' ).val( 0 );
		else
			$(this).prev().attr( 'type', 'hidden' ).val( 0 );

	});

	function update_sensei_badge_object(selected_el){
		var select_wrapper = $(selected_el).closest('.level-requirements');
		var reference = $(selected_el).val();
		var wrapper_obj = select_wrapper.find('.wrapper_object');
		var level = wrapper_obj.attr('data-level');

		if(reference == '') return false;

		var data = {
			action: 'mycred_sensei_change_reference',
			reference: reference,
			id: mycred_sensei.id,
			level: level,
		};

		// Do AJAX request
	   	$.post(mycred_sensei.ajax_url, data, function(response) {
			wrapper_obj.html(response);
	   	});
	}

	function toggle_metabox(){
		var is_enabled = $('#enable_sensei_badge').is(':checked');
		// alert(is_enabled);

		if(is_enabled){
			$('#mycred-badge-setup').hide();
			$('#mycred_sensei_badge_requirements').show();
		} else {
			$('#mycred_sensei_badge_requirements').hide();
			$('#mycred-badge-setup').show();
		}
	}

	$( document ).ready(toggle_metabox);
	$( document ).on('change', '#enable_sensei_badge', toggle_metabox);

	$( "#mycred_sensei_badge_requirements .form-control.reference" ).each(function() {
		update_sensei_badge_object(this);
	});

	$( '#mycred_sensei_badge_requirements' ).on( 'change', '.form-control.reference', function(e){
		update_sensei_badge_object(this);
	});

	$( '#mycred_sensei_badge_requirements' ).on( 'click', 'button.change-level-image', function(e){

		// console.log( 'Change level image button' );

		var button       = $(this);
		var currentlevel = button.data( 'level' );
		// alert(currentlevel);

		LevelImageSelector = wp.media.frames.file_frame = wp.media({
			title    : myCREDBadge.uploadtitle,
			button   : {
				text     : myCREDBadge.uploadbutton
			},
			multiple : false
		});

		// When a file is selected, grab the URL and set it as the text field's value
		LevelImageSelector.on( 'select', function(){

			attachment = LevelImageSelector.state().get('selection').first().toJSON();
			if ( attachment.url != '' ) {

				$( '#mycred-sensei-badge-level' + currentlevel + ' .level-image-wrapper' ).fadeOut(function(){

					$( '#mycred-sensei-badge-level' + currentlevel + ' .level-image-wrapper' ).empty().removeClass( 'empty dashicons' ).html( '<img src="' + attachment.url + '" alt="Badge level image" \/><input type="hidden" name="mycred_sensei_badge[levels][' + currentlevel + '][attachment_id]" value="' + attachment.id + '" \/><input type="hidden" name="mycred_sensei_badge[levels][' + currentlevel + '][image]" value="" \/>' ).fadeIn();
					button.text( myCREDBadge.changeimage );

				});

			}

		});

		// Open the uploader dialog
		LevelImageSelector.open();

	});

})(jQuery);