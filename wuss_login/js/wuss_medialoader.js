	var file_frame;
	var targetfield;
	jQuery('#_unique_wuss_button').live('click', function( event ){
 
	event.preventDefault();
 
	targetfield = jQuery( this ).attr("target");
		
	file_frame = wp.media.frames.file_frame = wp.media
	({
		title: "Select " + jQuery( this ).attr( 'title' ) + " image...",
		button: {
			text: "Select this image",
		},
		multiple: false  
	});
 
	file_frame.on( 'select', function() {
		attachment = file_frame.state().get('selection').first().toJSON(); 
		jQuery( "#"+targetfield ).val(attachment.id);
        jQuery('#img'+targetfield).attr("src",attachment.url);
        jQuery('#img'+targetfield).attr("srcset", '');
	});
 
	file_frame.open();
	});
	