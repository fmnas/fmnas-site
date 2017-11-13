$(function(){
	$("#dob").datepicker({
		maxDate: 0, /* do not allow pets born in the future */
		minDate: "-99Y", /* otherwise it can break with years between 00 and current */
		defaultDate: $(this).attr("data-default"), /* default to yesterday */
		dateFormat: "m/d/y", /* 1/3/17 */
		shortYearCutoff: "+0", /* 1/1/99 is 1999 not 2099 */
		showButtonPanel: true, /* add Today and Done buttons */
		changeYear: true, /* add drop down menu for year */
		altField: "#dob-iso", /* store ISO-formatted date */
		altFormat: "yy-mm-dd"
	});

	$("#petid").on("input",function(){ /* When Pet ID is changed */
		$("section.preview table.listings th.name>a").attr("id",$(this).val()); /* Update pet ID in attribute */
		updateEmailLinks();
	});

	$("#name").on("input",function(){
		titlePart2 = $(this).val(); /* Get new pet name */
		updateTitle(); /* Update page title */
		$("section.preview table.listings th.name>a").text(titlePart2); /* Update name in preview */
		updateEmailLinks();
	});

	$("#species").on("change",function(){
		if($(this).val()==="-1") {
			//TODO: update table
			alert("Not yet implemented");
		}
		else {
			$(".speciespagetitle").text($("option:selected",this).attr("data-pagetitle")); /* Update species page title in description of preview section */
		}
	});

	$("#sex").on("change",function(){
		$("section.preview table.listings td.sex").text($("option:selected",this).attr("data-displaytext")); /* Update sex in preview */
	});

	$("#dob, #approx").on("change",function(){ /* When date of birth OR approximate flag is changed */
		$("section.preview table.listings td.age time").attr('datetime',$("#dob-iso").val()); /* set datetime attribute of time element in preview to ISO date */
		if($("#approx").is(":checked")){ /* Age is approximate */
			$("section.preview table.listings td.age time").html("not yet implemented"); /* change displayed date in preview */
			//TODO
		}
		else { /* Age is not approximate */
			$("section.preview table.listings td.age time").html("<abbr title=\"Date of birth\">DOB</abbr> "+$("#dob").val()); /* change displayed date in preview */
		}
	});




	/* Update preview on load in case the browser has loaded in cached values */
	$("section.pet_data *").each(function(){$(this).trigger("input");}); /* Trigger an input event on everything in .pet_data section */
	$("section.pet_data *").each(function(){$(this).trigger("change");}); /* Trigger a change event on everything in .pet_data section */

});
