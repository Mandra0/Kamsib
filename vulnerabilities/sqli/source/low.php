<?php

if (!isset($_GET['Submit'])) {
	return;
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
	'options' => ['min_range' => 1],
]);
if ($id === false) {
	$html .= '<pre>User ID must be a positive integer.</pre>';
	return;
}

$query = 'SELECT first_name, last_name FROM users WHERE user_id = ? LIMIT 1';
$stmt = null;
$result = null;

try {
	switch ($_DVWA['SQLI_DB']) {
		case MYSQL:
			$stmt = mysqli_prepare($GLOBALS['___mysqli_ston'], $query);
			if (
				!$stmt || !mysqli_stmt_bind_param($stmt, 'i', $id)
				|| !mysqli_stmt_execute($stmt)
			) {
				throw new RuntimeException('MySQL statement failed.');
			}
			$result = mysqli_stmt_get_result($stmt);
			if ($result === false) {
				throw new RuntimeException('MySQL result unavailable.');
			}
			$row = mysqli_fetch_assoc($result);
			break;

		case SQLITE:
			global $sqlite_db_connection;
			$stmt = $sqlite_db_connection->prepare($query);
			if (!$stmt || !$stmt->bindValue(1, $id, SQLITE3_INTEGER)) {
				throw new RuntimeException('SQLite statement preparation failed.');
			}
			$result = $stmt->execute();
			if ($result === false) {
				throw new RuntimeException('SQLite statement execution failed.');
			}
			$row = $result->fetchArray(SQLITE3_ASSOC);
			break;

		default:
			throw new RuntimeException('Unsupported database driver.');
	}

	if (!$row) {
		$html .= '<pre>User not found.</pre>';
		return;
	}

	$first = htmlspecialchars($row['first_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	$last = htmlspecialchars($row['last_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	$html .= "<pre>ID: {$id}<br />First name: {$first}<br />Surname: {$last}</pre>";
} catch (Exception $exception) {
	error_log('DVWA User ID lookup failed: ' . $exception->getMessage());
	$html .= '<pre>Database lookup failed.</pre>';
} finally {
	if ($result instanceof mysqli_result) {
		$result->free();
	} elseif ($result instanceof SQLite3Result) {
		$result->finalize();
	}

	if ($stmt instanceof mysqli_stmt || $stmt instanceof SQLite3Stmt) {
		$stmt->close();
	}
}