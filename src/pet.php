<?php

require_once "common.php";
require_once "assets.php";

/**
 * @property int|null species_count
 * @property int id
 * @property string|null name
 * @property string|null plural
 * @property string|null young
 * @property string|null young_plural
 * @property string|null old
 * @property string|null old_plural
 * @property int|null age_unit_cutoff // Number of months after which age shall be reported in years
 * @property int|null young_cutoff // Below this number of months, exclusive, use "young name"
 * @property int|null old_cutoff // Above this number of months, inclusive, use "old name"
 */
class Species implements JsonSerializable {
    private array $values = array();

    /**
     * Get an appropriate name for this species depending on age and plurality
     * such as "cat" or "kittens"
     * @param string|null $dob Date of birth of the animal in months
     * @param bool $plural Whether the word should be plural
     * @return string
     */
    public function nameGivenDob(string $dob = null, bool $plural = false): string {
        $age = null;
        if ($dob != null) {
            try {
                $diff = (new DateTime())->diff(new DateTime($dob));
                $age  = $diff->y * 12 + $diff->m;
            } catch (Exception $e) {
                log_err("Exception when converting $dob to DateTime: " . $e->getMessage());
            }
        }
        $age ??= $this->young_cutoff ?? 36;
        if ($age < ($this->young_cutoff ?? 0)) {
            return $plural ? ($this->young_plural ?? $this->plural) : ($this->young ?? $this->name);
        }
        if ($age >= ($this->old_cutoff ?? PHP_INT_MAX)) {
            return $plural ? ($this->old_plural ?? $this->plural) : ($this->old ?? $this->name);
        }
        return $plural ? $this->plural : $this->name;
    }

    /**
     * Get a printable version of the age such as "11 years" or "3 months"
     * @param string $dob Date of birth of the animal
     * @return string Printable version of the age
     */
    public function age(?string $dob): string {
        if (!$dob) {
            return "&nbsp;";
        }
        try {
            $interval = (new DateTime())->diff(new DateTime($dob));
            $months   = $interval->y * 12 + $interval->m;
            if ($months < 4) {
                return "DOB " . (new DateTime($dob))->format("n/j/y");
            }
            if ($months == 0) {
                return $interval->d . " day" . ($interval->d === 1 ? "" : "s") . " old";
            }
            if ($months > ($this->age_unit_cutoff ?: 12)) {
                return $interval->y . " year" . ($interval->y === 1 ? "" : "s") . " old";
            }
            return $months . " month" . ($months === 1 ? "" : "s") . " old";
        } catch (Exception $e) {
            log_err("Exception when converting $dob to DateTime: " . $e->getMessage());
            return '';
        }
    }

    public function __get($key) {
        return isset($this->values[$key]) ? $this->values[$key] : null;
    }

    public function __set($key, $val) {
        if ($val === null) {
            unset($this->values[$key]);
        } else {
            $this->values[$key] = $val;
        }
    }

    public function setAll(array $arr) {
        $this->values = $arr;
    }

    public function __toString(): string {
        return strval($this->values["name"]);
    }

    public function plural(): string {
        return $this->plural ?: $this->name . 's';
    }

    public function pluralWithYoung(): string {
        return ucfirst($this->plural()) . (
            (($this->young ?: $this->name) === $this->name) ?
                "" :
                " &amp; " . ucfirst($this->young_plural ?: $this->young . 's')
            );
    }

    public function jsonSerialize(): array {
        return $this->values;
    }
}

class Sex {
    public int $key;
    public string $name;

    public function __toString() {
        return $this->name;
    }
}

class Status {
    public int $key;
    public string $name;
    public ?bool $displayStatus; // Display the status in lieu of the adoption fee?
    public bool $listed; // Display this animal in the adoptable animal listings?
    public ?string $description; // Optional explanatory description, where $listed is true and $displayStatus is true

    public function __toString() {
        return $this->name;
    }
}

class Pet implements JsonSerializable {
    // Properties corresponding to database fields
    public string $id;
    public string $name;
    public string $path;
    public Species $species;
    public ?string $breed; // breed or other description
    public ?string $dob; // date of birth
    public ?Sex $sex;
    public ?string $fee; // adoption fee
    public ?Asset $photo; // profile picture asset
    public ?array $photos; // photo assets (array of Assets)
    public ?Asset $description; // description asset
    public Status $status;
    public ?bool $plural; // @todo Two animals in one listing?

    public function age(): string {
        return $this->species->age($this->dob);
    }

    public function listed(): bool {
        return !($this->description !== null && startsWith($this->description->fetch(), "{{>coming_soon}}")) &&
               (($this->description !== null && strlen(trim($this->description->fetch())) > 0) ||
                ($this->photos !== null && count($this->photos) > 0));
    }

    public function species(): string {
        if (!isset($this->species) || $this->species === null) {
            return '';
        }
        return $this->species->nameGivenDob($this->dob, $this->plural);
    }

    public function __toString(): string {
        return htmlspecialchars("$this->name $this->id");
    }

    // Strings provided to listing description parser.
    public function toArray(): array {
        return [
            "id"      => $this->id,
            "name"    => $this->name,
            "species" => $this->species(),
            "breed"   => $this->breed,
            "dob"     => $this->dob,
            "age"     => $this->age(),
            "sex"     => $this->sex->name,
            "fee"     => $this->fee,
            "status"  => $this->status->name,
            "path"    => $this->path,
            "plural"  => $this->plural,
        ];
    }

    // Data provided to listing editor.
    public function jsonSerialize(): array {
        return [
            "id"          => $this->id,
            "name"        => $this->name,
            "species"     => $this->species->id,
            "breed"       => $this->breed,
            "dob"         => $this->dob,
            "sex"         => $this->sex->key,
            "fee"         => $this->fee,
            "status"      => $this->status->key,
            "path"        => $this->path,
            "plural"      => $this->plural,
            "photo"       => $this->photo,
            "photos"      => $this->photos,
            "description" => $this->description,
        ];
    }
}