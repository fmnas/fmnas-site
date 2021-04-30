<?php
require_once 'api.php';

function findSpecies(): ?Species {
    foreach(_G_species() as $species) {
        if (strtolower($species->plural) === $_GET['species']) {
            return $species;
        }
    }
    return null;
}

endpoint(...[
    'get'       => function() use ($db): Result {
        if (isset($_GET['species'])) {
            foreach(_G_species() as $species) {
                if (strtolower($species->plural) === $_GET['species']) {
                    return new Result(200, $db->getAdoptablePetsBySpecies($species));
                }
            }
            return new Result(404, 'Species ' . $_GET['species'] . ' not found');
        }
        return new Result(200, $db->getAdoptablePets());
    },
    'get_value' => function($value) use ($db): Result {
        $pet = $db->getPetById($value);
        if ($pet === null) {
            return new Result(404, "Pet $value not found");
        }
        return new Result(200, $pet);
    },
]);