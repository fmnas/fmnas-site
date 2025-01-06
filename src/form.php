<?php
/*
 * Copyright 2025 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Form {
	// Properties corresponding to database fields
	public string $id;
	public string $title;
	public string $fillout_id;

	public function embed(): void { ?>
		<div style="width:100%;height:500px;" data-fillout-id="<?=$this->fillout_id?>" data-fillout-embed-type="standard"
				data-fillout-inherit-parameters data-fillout-dynamic-resize></div>
		<script src="https://server.fillout.com/embed/v1/"></script>
		<?php
	}
}
