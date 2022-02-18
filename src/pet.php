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
	private array $values = [];

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
				$age = $diff->y * 12 + $diff->m;
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
	 * @param string|null $dob Date of birth of the animal
	 * @return string Printable version of the age
	 */
	public function age(?string $dob): string {
		if (!$dob) {
			return '';
		}
		try {
			$interval = (new DateTime())->diff(new DateTime($dob));
			$months = $interval->y * 12 + $interval->m;
			if ($months < 4) {
				return "DOB " . (new DateTime($dob))->format("n/j/y");
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
		return $this->values[$key] ?? null;
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

	public function pluralWithYoung(): string {
		return ucfirst($this->plural()) . (
				(($this->young ?: $this->name) === $this->name) ?
						"" :
						" &amp; " . ucfirst($this->young_plural ?: $this->young . 's')
				);
	}

	public function plural(): string {
		return $this->plural ?: $this->name . 's';
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
	public ?Species $species;
	public ?string $breed; // breed or other description
	public ?string $dob; // date of birth
	public ?Sex $sex;
	public ?string $fee; // adoption fee
	public ?Asset $photo; // profile picture asset
	public ?array $photos; // photo assets (array of Assets)
	public ?Asset $description; // description asset
	public Status $status;
	public int $bonded;
	public ?Pet $friend;
	public ?string $adoption_date;
	public ?int $order;
	public ?string $modified;

	public function listed(): bool {
		$description = $this->description?->fetch();
		return $description && !startsWith(trim($description), "{{>coming_soon}}");
	}

	public function __toString(): string {
		if ($this->bonded === 1) {
			return htmlspecialchars("$this->name $this->id & $this->friend");
		}
		return htmlspecialchars("$this->name $this->id");
	}

	public function toArray(): array {
		return [
				"id" => $this->id(),
				"name" => $this->name(),
				"species" => $this->species(),
				"breed" => $this->breed(),
				"dob" => $this->dob(),
				"age" => $this->age(),
				"sex" => $this->sex(),
				"fee" => $this->fee,
				"status" => $this->status->name,
				"path" => $this->path,
				"bonded" => $this->bonded,
				"friend" => $this->friend?->id,
				"adoption_date" => $this->adoption_date,
				"order" => $this->order,
				"modified" => $this->modified,
		];
	}

	public function species(): string {
		return $this->species?->nameGivenDob($this->dob, $this->bonded === 1) ?? '';
	}

	public function name(): string {
		if ($this->bonded === 1) {
			return $this->name . ' & ' . $this->friend->name;
		}
		return $this->name;
	}

	public function id(): string {
		if ($this->bonded === 1) {
			return $this->id . $this->friend->id;
		}
		return $this->id;
	}

	public function breed(): string {
		if ($this->bonded !== 1 || $this->breed === $this->friend->breed) {
			return $this->breed ?? '';
		}
		return ($this->breed ?? '') . ' & ' . ($this->friend->breed ?? '');
	}

	public function dob(): string {
		if ($this->bonded !== 1 || $this->dob === $this->friend->dob) {
			return $this->dob ?? '';
		}
		return ($this->dob ?? '') . ' & ' . ($this->friend->dob ?? '');
	}

	public function age(): string {
		$leftAge = $this->species->age($this->dob);
		$rightAge = $this->species->age($this->friend?->dob) ?: false;
		if (!$rightAge || $leftAge === $rightAge) {
			return $leftAge;
		}
		return $leftAge . ' & ' . $rightAge;
	}

	public function collapsedAge(): string {
		return preg_replace(
				['/^([^&]+) & \1$/', '/^([0-9]+) (months?|years?) old & ([0-9]+) \2 old$/'],
				['\1', '\1 & \ \2\3 old'],
				$this->age());
	}

	public function sex(): string {
		if ($this->bonded !== 1 || $this->sex?->key === $this->friend->sex?->key) {
			return ($this->sex?->name ?? '');
		}
		return ($this->sex?->name ?? '') . ' & ' . ($this->friend->sex?->name ?? '');
	}

	public function jsonSerialize(): array {
		return [
				"id" => $this->id,
				"name" => $this->name,
				"species" => $this->species->id,
				"breed" => $this->breed,
				"dob" => $this->dob,
				"sex" => $this->sex?->key,
				"fee" => $this->fee,
				"status" => $this->status?->key,
				"path" => $this->path,
				"photo" => $this->photo,
				"photos" => $this->photos,
				"description" => $this->description,
				"bonded" => $this->bonded,
				"friend" => $this->bonded === 1 ? $this->friend->jsonSerialize() : null,
				"adoption_date" => $this->adoption_date,
				"order" => $this->order,
				"modified" => $this->modified,
		];
	}
}
