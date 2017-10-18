
	//Sort table
	$(function(){ //set data-originalorder on each tr
		$('table.listings tbody tr').attr('data-originalorder',function(index){return index;});
	});
	$(function(){sortOn('original')}); //Set how to sort: dob, id, name, fee, original - add a 'desc' to make previous descending

	var sortArgs;
	function sortOn() {
		table = $('table.listings tbody').first();
		sortArgs = arguments;
		rows = table.find('tr').toArray().sort(function(a, b){
			for(i=0; i<sortArgs.length; i++){
				comp = getcomparator(sortArgs[i])(a, b);
				if(sortArgs[i+1] == 'desc') {
					comp *= -1;
					i++;
				}
				if(comp) return comp;
			}
			return 0;
		});
		for(i=0;i<rows.length;i++) { table.append(rows[i]); }
	}
	function getcomparator(property) {
		switch (property) {
			case 'dob':
				return function(a, b){
					da = new Date($(a).find('time').first().attr('datetime'));
					db = new Date($(b).find('time').first().attr('datetime'));
					return da - db;
				};
			case 'id':
				return function(a, b){
					da = $(a).find('th>a[id]').first().attr('id');
					db = $(b).find('th>a[id]').first().attr('id');
					return da.localeCompare(db);
				};
			case 'name':
				return function(a, b){
					da = $(a).find('th>a[id]').first().text();
					db = $(b).find('th>a[id]').first().text();
					return da.localeCompare(db);
				};
			case 'fee':
				return function(a, b){
					da = parseInt($(a).find('td.fee>span').first().text().match(/\d+/)[0],10)||0;
					db = parseInt($(b).find('td.fee>span').first().text().match(/\d+/)[0],10)||0;
					return da - db;
				};
			case 'original':
				return function(a, b){
					da = parseInt($(a).attr('data-originalorder'));
					db = parseInt($(b).attr('data-originalorder'));
					return da - db;
				};
		}
		return function(a,b){return 0;}
	}

	function disablePendingShove() { //Stop pending/closed listings from going to the end
		$('table.listings tbody tr').attr('style','order: 0 !important;');
	}
	function enablePendingShove() { //Cause pending/closed listings to go to the end
		$('table.listings tbody tr').attr('style','');
	}

	$(function(){
		enablePendingShove();
	})
