$(function(){

	/* DATA AND PREVIEW */

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

		$("#species").on("input",function(){
			if($(this).val()==="-1") {
				//TODO: update table
				alert("Not yet implemented");
			}
			else {
				$(".speciespagetitle").text($("option:selected",this).attr("data-pagetitle")); /* Update species page title in description of preview section */
			}
		});

		$("#sex").on("input",function(){
			$("section.preview table.listings td.sex").text($("option:selected",this).attr("data-displaytext")); /* Update sex in preview */
		});

		$("#dob, #approx").on("input",function(){ /* When date of birth OR approximate flag is changed */
			var timeElement = $("section.preview table.listings td.age time"); /* Displayed date in preview */
			timeElement.attr('datetime',$("#dob-iso").val()); /* set datetime attribute of time element in preview to ISO date */
			if($("#approx").is(":checked")){ /* Age is approximate */
				//This algorithm is duplicated in listing_table.php
				var dob = $("#dob").datepicker("getDate"); //Date object
				var now = new Date();
				var _MS_PER_MONTH = 30.4375 * 24 * 60 * 60 * 1000;
				var age = (now.getTime() - dob.getTime())/_MS_PER_MONTH; //in months
				if(age <= 24) { //measure in months
					timeElement.html(Math.floor(age)+" months old");
				}
				else { //measure in years
					timeElement.html(Math.floor(age/12)+" years old");
				}
			}
			else { /* Age is not approximate */
				$("section.preview table.listings td.age time").html("<abbr title=\"Date of birth\">DOB</abbr> "+$("#dob").val()); /* change displayed date in preview */
			}
		});

		/* Update preview on load in case the browser has loaded in cached values */
		$("section.pet_data *").each(function(){$(this).trigger("input");}); /* Trigger an input event on everything in .pet_data section */


	/* PHOTO UPLOADER */

	    // Initialize the jQuery File Upload widget:
	    $('#fileupload').fileupload({
		   url: '/' + documentRoot + 'admin/upload_photo.php',
		   autoUpload: true,
           done: function(e, data){console.log(data._response.result);}
	    });



});
