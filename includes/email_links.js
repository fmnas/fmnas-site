
$(function() {
	$("a[data-email]").each(function() { //for every email link (with data-email attribute)
		user = 'info'; //default value
		domain = '@forgetmenotshelter.org';
		data = $(this).attr('data-email');
		subject = '';
		if(!!$.trim(data)) user=data; //if data-email attribute non-empty, use as user part
		else if ($(this).parent().is('td.inquiry')) { //empty in inquiry link on listing
			pet = $(this).closest('tr').find('th.name>*').first(); //find name on listing
			user = 'adopt+'+pet.attr('id'); //add id to adopt+ for user
			subject = pet.text();
			$(this).text('Email to adopt '+subject+'!'); //set link text
		}
		if(user.charAt(0)=='+') user='adopt'+user; //for adoption links
		if(!$.trim($(this).text())) $(this).html(user+domain); //make text email address if still blank
		$(this).attr('href','mailto:'+user+domain+'?subject='+subject); //set href
	});
});
