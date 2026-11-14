<?php

if (isset($_SESSION['id'])) {
	// Get input and validate strictly as positive integer
	$raw_id = $_SESSION['id'];
	$id = filter_var($raw_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

	if ($id === false) {
		unset($_SESSION['id']);
		$html .= '<pre>Invalid user ID.</pre>';
	} else {
		// Check database using prepared statement
		$stmt = mysqli_prepare(
			$GLOBALS["___mysqli_ston"],
			"SELECT first_name, last_name FROM users WHERE user_id = ? LIMIT 1;"
		);

		if ($stmt) {
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $first, $last);

			// Get results
			while (mysqli_stmt_fetch($stmt)) {
				// Sanitize output to prevent XSS
				$safe_id = htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8');
				$safe_first = htmlspecialchars((string) $first, ENT_QUOTES, 'UTF-8');
				$safe_last = htmlspecialchars((string) $last, ENT_QUOTES, 'UTF-8');

				// Feedback for end user
				$html .= "<pre>ID: {$safe_id}<br />First name: {$safe_first}<br />Surname: {$safe_last}</pre>";
			}

			mysqli_stmt_close($stmt);
		} else {
			$html .= '<pre>Something went wrong.</pre>';
		}
	}

	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
}

?>