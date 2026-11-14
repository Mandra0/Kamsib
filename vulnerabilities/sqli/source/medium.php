<?php

$number_of_rows = 0;
try {
	switch ($_DVWA['SQLI_DB']) {
		case MYSQL:
			$countResult = mysqli_query($GLOBALS['___mysqli_ston'], 'SELECT COUNT(*) FROM users');
			if ($countResult === false) {
				throw new RuntimeException('MySQL user count failed.');
			}
			$number_of_rows = (int) mysqli_fetch_row($countResult)[0];
			$countResult->free();
			break;

		case SQLITE:
			global $sqlite_db_connection;
			$count = $sqlite_db_connection->querySingle('SELECT COUNT(*) FROM users');
			if ($count === false) {
				throw new RuntimeException('SQLite user count failed.');
			}
			$number_of_rows = (int) $count;
			break;

		default:
			throw new RuntimeException('Unsupported database driver.');
	}
} catch (Exception $exception) {
	error_log('DVWA User ID list failed: ' . $exception->getMessage());
	$html .= '<pre>Unable to load User IDs.</pre>';
	return;
}

if (!isset($_POST['Submit'])) {
	return;
}

$rawId = $_POST['id'] ?? null;
if (!is_string($rawId)) {
	$html .= '<pre>User ID must be a positive integer.</pre>';
	return;
}

try {
	if ($_DVWA['SQLI_DB'] === MYSQL) {
		// Supplementary escaping; validation and parameter binding enforce safety.
		$rawId = mysqli_real_escape_string($GLOBALS['___mysqli_ston'], $rawId);
	}
} catch (Exception $exception) {
	error_log('DVWA User ID processing failed: ' . $exception->getMessage());
	$html .= '<pre>Database lookup failed.</pre>';
	return;
}

$id = filter_var($rawId, FILTER_VALIDATE_INT, [
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