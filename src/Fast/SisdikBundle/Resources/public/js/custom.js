/**
 * custom javascript functions
 * 
 */

 
/**
 * confirm drop
 */
function confirmDrop(theLink, theQuestion) {
	var is_confirmed = confirm(theQuestion);
	if (is_confirmed) {
		if ( typeof(theLink.href) != 'undefined' ) {
			theLink.href += '/1';
		}
	}
	return is_confirmed;
}


/**
 * confirm drop button
 */
function confirmDropButton(theQuestion) {
	var is_confirmed = confirm(theQuestion);
	return is_confirmed;
}