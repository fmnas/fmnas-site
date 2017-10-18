<?php

	function build_selector($prepend,$classes,$append) {
		$classes2 = array();
		foreach($classes as $class) {
			$classes2[] = $prepend.$class.$append;
		}
		return implode(',',$classes2);
	}
