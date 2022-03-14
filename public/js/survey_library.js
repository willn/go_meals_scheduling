/**
 * Apply a preference to all of a certain day of week.
 * @param[in] dow int number of day of week.
 * @param[in] choice int the preference to apply (0, 1, 2)
 */
function select_day(dow, choice) {
	var days = document.getElementsByTagName('td');
	var selects;

	var num = days.length;
	for(i=0; i<num; i++) {
		if (days[i].className === 'dow_' + dow) {
			selects = days[i].getElementsByTagName('select');
			for(s=0; s<selects.length; s++) {
				options = selects[s].getElementsByTagName('option');
				options[choice].selected='selected';
			}
		}
	}
}

