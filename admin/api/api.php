<?php
require_once 'db.php';
$db = new DatabaseWriter();

class Result implements JsonSerializable {
	public function __construct(
			public int $status,
			public mixed $value = null,
			public ?string $error = null,
			public ?string $location = null,
	) {
	}

	public function jsonSerialize(): mixed {
		if ($this->error) {
			return ['error' => $this->error];
		}
		return $this->value;
	}
}

function call(?callable $fn, ?string $val = null, mixed $data = null): Result {
	if ($fn === null) {
		return new Result(501, error: "Action not implemented");
	}
	if ($val === null && $data === null) {
		return $fn();
	}
	if ($val === null) {
		return $fn($data);
	}
	if ($data === null) {
		return $fn($val);
	}
	return $fn($val, $data);
}

/**
 * @param callable|null $get () => Result
 * @param callable|null $post (array) => Result
 * @param callable|null $put (array) => Result
 * @param callable|null $delete () => Result
 * @param callable|null $get_value (string) => Result
 * @param callable|null $post_value (string, mixed) => Result
 * @param callable|null $put_value (string, mixed) => Result
 * @param callable|null $delete_value (string) => Result
 */
function endpoint(?callable $get = null, ?callable $post = null, ?callable $put = null, ?callable $delete = null,
		?callable $get_value = null, ?callable $post_value = null,
		?callable $put_value = null, ?callable $delete_value = null) {
	header('Content-Type:application/json;charset=utf-8');
	$result = new Result(500, error: "Endpoint function didn't return a result");

	$method = $_SERVER['REQUEST_METHOD'];
	$data = json_decode(file_get_contents('php://input'), true);
	$v = isset($_GET['v']);
	$value = $v ? $_GET['v'] : null;
	switch ($method) {
	case 'GET':
		$result = call($v ? $get_value : $get, $value);
		break;
	case 'PUT':
	case 'POST':
		if (empty($data)) {
			$result = new Result(400, error: "No data provided");
			break;
		}
		if ($method === 'PUT') {
			$result = call($v ? $put_value : $put, $value, $data);
		} else {
			$result = call($v ? $post_value : $post, $value, $data);
		}
		break;
	case 'DELETE': // Delete
		$result = call($v ? $delete_value : $delete, $value);
		break;
	}
	http_response_code($result->status);
	echo json_encode($result);
}

$reject = function($key = null, $value = null): Result {
	return new Result(405, "Action not allowed");
};
