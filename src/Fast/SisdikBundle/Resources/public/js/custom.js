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


/**
 * simulate strstr in php
 */
function strstr (haystack, needle, bool) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: strstr('Kevin van Zonneveld', 'van');
    // *     returns 1: 'van Zonneveld'
    // *     example 2: strstr('Kevin van Zonneveld', 'van', true);
    // *     returns 2: 'Kevin '
    // *     example 3: strstr('name@example.com', '@');
    // *     returns 3: '@example.com'
    // *     example 4: strstr('name@example.com', '@', true);
    // *     returns 4: 'name'
    var pos = 0;
    
    haystack += '';
    pos = haystack.indexOf(needle);
    if (pos == -1) {
        return false;
    } else {
        if (bool) {
            return haystack.substr(0, pos);
        } else {
            return haystack.slice(pos);
        }
    }
}
