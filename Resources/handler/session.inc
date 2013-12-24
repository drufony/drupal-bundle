<?php

/**
 * @file
 * User session handling functions.
 *
 * Let Symfony handle sessions.
 */

/**
 * Initializes the session handler, starting a session if needed.
 */
function drupal_session_initialize() {
  global $user;
  /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
  $session = $GLOBALS['session'];

  drupal_session_start();
  if (!empty($user->uid) || !empty($_SESSION)) {
    drupal_page_is_cacheable(FALSE);
  }
  if (0 === $session->get('drupal_uid', 0)) {
    $user = drupal_anonymous_user();
  }
  else {
    $user = db_query("SELECT * FROM {users} u WHERE u.uid = :uid", array(':uid' => $session->get('drupal_uid', 0)))->fetchObject();
  }
  date_default_timezone_set(drupal_get_user_timezone());
}

/**
 * Starts a session forcefully, preserving already set session data.
 *
 * @ingroup php_wrappers
 */
function drupal_session_start() {
  /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
  $session = $GLOBALS['session'];
  // Command line clients do not support cookies nor sessions.
  if (!drupal_session_started() && !drupal_is_cli()) {
    // Save current session data before starting it, as PHP will destroy it.
    $session_data = isset($_SESSION) ? $_SESSION : NULL;

    session_start();
    $session->start();
    drupal_session_started(TRUE);

    // Restore session data.
    if (!empty($session_data)) {
      $_SESSION += $session_data;
    }
  }
}

/**
 * Commits the current session, if necessary.
 *
 * If an anonymous user already have an empty session, destroy it.
 */
function drupal_session_commit() {
  global $user;
  /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
  $session = $GLOBALS['session'];

  if (!drupal_save_session()) {
    // We don't have anything to do if we are not allowed to save the session.
    return;
  }

  if (empty($user->uid) && empty($_SESSION)) {
    // There is no session data to store, destroy the session if it was
    // previously started.
    if (drupal_session_started()) {
      $session->invalidate();
    }
  }
  else {
    // There is session data to store. Start the session if it is not already
    // started.
    if (!drupal_session_started()) {
      drupal_session_start();
    }
    $session->set('drupal_uid', $user->uid);
  }
}

/**
 * Returns whether a session has been started.
 */
function drupal_session_started($set = NULL) {
  /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
  $session = $GLOBALS['session'];
  static $session_started = FALSE;
  if (isset($set)) {
    $session_started = $set;
  }
  return $session_started && $session->getId();
}

/**
 * Called when an anonymous user becomes authenticated or vice-versa.
 *
 * @ingroup php_wrappers
 */
function drupal_session_regenerate() {
  global $user;
  /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
  $session = $GLOBALS['session'];

  // Nothing to do if we are not allowed to change the session.
  if (!drupal_save_session()) {
    return;
  }

  if (drupal_session_started()) {
    $session->migrate();
  }
  date_default_timezone_set(drupal_get_user_timezone());
}

/**
 * Ends a specific user's session(s).
 *
 * @param $uid
 *   User ID.
 */
function drupal_session_destroy_uid($uid) {
  // Nothing to do if we are not allowed to change the session.
  if (!drupal_save_session()) {
    return;
  }
}

/**
 * Determines whether to save session data of the current request.
 *
 * This function allows the caller to temporarily disable writing of
 * session data, should the request end while performing potentially
 * dangerous operations, such as manipulating the global $user object.
 * See http://drupal.org/node/218104 for usage.
 *
 * @param $status
 *   Disables writing of session data when FALSE, (re-)enables
 *   writing when TRUE.
 *
 * @return
 *   FALSE if writing session data has been disabled. Otherwise, TRUE.
 */
function drupal_save_session($status = NULL) {
  // PHP session ID, session, and cookie handling happens in the global scope.
  // This value has to persist across calls to drupal_static_reset(), since a
  // potentially wrong or disallowed session would be written otherwise.
  static $save_session = TRUE;
  if (isset($status)) {
    $save_session = $status;
  }
  return $save_session;
}
