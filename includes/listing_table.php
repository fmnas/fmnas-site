<table class="listings">
	<thead>
		<tr>
			<th>Name</th>
			<th>Sex</th>
			<th>Age</th>
			<th>Adoption fee</th>
			<th>Image</th>
			<th>Email inquiry</th>
		</tr>
	</thead>
	<tbody>
		<?php
			foreach($pets as $pet):
				$status = $statuses[$pet['status']];
				$listed = file_exists("$BASE/content/descriptions/".$pet['id'].'.html');
		?>
		<tr class="<?=$status['class'].($status['statustext']!=='Coming Soon'?'':' soon')?>">
			<th class="name"><a <?php
				if($listed):
			 ?>href="<?=urldoubleencode($pet['id'].$pet['name'])?>"
		 <?php endif; ?> id="<?=$pet['id']?>"><?=htmlspecialchars($pet['name'])?></a></th>
			<td class="sex"><?=htmlspecialchars($sexes[$pet['sex']].' '.$pet['text1'])?></td>
			<td class="age"><time datetime="<?=$pet['dob']?>"><?php
				$dob = new DateTime($pet['dob']);
				$age = '';
				if($pet['estimate']){
					//Estimated DOB?
					$now = new DateTime();
					if($dob > new DateTime('2 years ago')) { //if <= 2 yo
						$age = ($now->diff($dob)->m) + 12*($now->diff($dob)->y);
						$age .= ' month'.($age===1?'':'s').' old';
					}
					else {
						$age = $now->diff($dob)->y;
						$age .= ' year'.($age===1?'':'s').' old';
					}
				}
				else {
					//Exact DOB?
					$age = '<abbr title="Date of birth">DOB</abbr> '.$dob->format('n/j/y');
				}
				echo $age.' '.htmlspecialchars($pet['text2']);
			?></time></td>
			<td class="fee"><span><?=htmlspecialchars($status['statustext'].' '.($status['hidefee']?'':'$'.$pet['fee']).' '.$pet['text3'])?></span></td>
			<td class="img"><a <?php
				if($listed):
			 ?>href="<?=urldoubleencode($pet['id'].$pet['name'])?>"
			<?php endif; ?>>
				<img src="/<?=$document_root?>pages/get_image.php?id=<?=$pet['image']?>&amp;width=200">
			</a></td>
			<td class="inquiry"><a data-email></a></td>
		<?php endforeach; ?>
	</tbody>
</table>
