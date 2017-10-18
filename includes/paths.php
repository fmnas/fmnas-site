<?php

	require_once($_SERVER['DOCUMENT_ROOT'].'/config/shelter.php');

	$BASE = $_SERVER['DOCUMENT_ROOT'].'/'.$document_root.'/';

	//Runtime dependencies

		//Client-side scripts

			//jQuery
			$jquery_path = 'https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js';

			//TinyMCE
			$tinymce_path = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.7.1/tinymce.min.js';
			$tinymce_jquery_path = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.7.1/jquery.tinymce.min.js';
			$tinymce_plugins = array();
			$tinymce_plugins[] = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.7.1/plugins/lists/plugin.min.js';
			$tinymce_plugins[] = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.7.1/plugins/link/plugin.min.js';
			$tinymce_plugins[] = "/".$document_root."tinymce_plugins/pets/plugin.js";
			$tinymce_plugins[] = "/".$document_root."tinymce_plugins/image_gallery/plugin.js";
			$tinymce_plugins[] = "/".$document_root."tinymce_plugins/video/plugin.js";
			$tinymce_plugins[] = "/".$document_root."tinymce_plugins/templates/plugin.js";
