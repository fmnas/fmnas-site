<?php
require_once 'api.php';

endpoint(...[
	// TODO [#57]: API to get non-adoptable pets
		'get' => function() use ($db): Result {
			if (isset($_GET['species'])) {
				foreach (_G_species() as $species) {
					if (strtolower($species->plural) === $_GET['species']) {
						return new Result(200, $db->getAdoptablePetsBySpecies($species));
					}
				}
				return new Result(404, 'Species ' . $_GET['species'] . ' not found');
			}
			return new Result(200, $db->getAdoptablePets());
		},
		'get_value' => function($value) use ($db): Result {
			$pet = $db->getPetByPath($value); // Try path first
			$pet ??= $db->getPetById($value);
			if ($pet === null) {
				return new Result(404, "Pet $value not found");
			}
			return new Result(200, $pet);
		},
		'put' => $reject,
		'put_value' => function($key, $pet) use ($db): Result {
			$error = $db->insertPet($pet);
			if ($key !== $pet['id']) {
				$error ??= $db->deletePet($key); // Delete the original pet
			}
			@unlink(cached_assets() . '/' . $pet['description']['key'] . '.html'); // Invalidate cached description
			if ($error !== null) {
				return new Result(500, $error);
			}
			return new Result(204);
		},
		'post' => function($pet) use ($db): Result {
			if (isset($pet['id']) && $db->getPetById($pet['id']) !== null) {
				return new Result(409, "Pet {$pet['id']} already exists");
			}
			$error = $db->insertPet($pet);
			if ($error !== null) {
				return new Result(500, $error);
			}
			return new Result(204);
		},
		'post_value' => $reject,
		'delete'=> $reject,
		'delete_value' => function($key) use ($db): Result {
			$error = $db->deletePet($key);
			if ($error !== null) {
				return new Result(500, $error);
			}
			return new Result(204);
		},
]);
