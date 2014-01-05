<?php

/**
 * @file
 * User session handling functions.
 *
 * The user-level session storage handlers:
 * - _drupal_session_open()
 * - _drupal_session_close()
 * - _drupal_session_read()
 * - _drupal_session_write()
 * - _drupal_session_destroy()
 * - _drupal_session_garbage_collection()
 * are assigned by session_set_save_handler() in bootstrap.inc and are called
 * automatically by PHP. These functions should not be called directly. Session
 * data should instead be accessed via the $_SESSION superglobal.
 */

namespace Bangpound\Bundle\DrupalBundle;

class DrupalSessionHandler implements \SessionHandlerInterface
{
    /**
     * Session handler assigned by session_set_save_handler().
     *
     * This function is used to handle any initialization, such as file paths or
     * database connections, that is needed before accessing session data. Drupal
     * does not need to initialize anything in this function.
     *
     * This function should not be called directly.
     *
     * @param  string $save_path
     * @param  string $session_id
     * @return bool   This function will always return TRUE.
     */
    public function open($save_path, $session_id)
    {
        return TRUE;
    }

    /**
     * Session handler assigned by session_set_save_handler().
     *
     * This function is used to close the current session. Because Drupal stores
     * session data in the database immediately on write, this function does
     * not need to do anything.
     *
     * This function should not be called directly.
     *
     * @return bool This function will always return TRUE.
     */
    public function close()
    {
        return TRUE;
    }

    /**
     * Reads an entire session from the database (internal use only).
     *
     * Also initializes the $user object for the user associated with the session.
     * This function is registered with session_set_save_handler() to support
     * database-backed sessions. It is called on every page load when PHP sets
     * up the $_SESSION superglobal.
     *
     * This function is an internal function and must not be called directly.
     * Doing so may result in logging out the current user, corrupting session data
     * or other unexpected behavior. Session data must always be accessed via the
     * $_SESSION superglobal.
     *
     * @param string $sid
     *                    The session ID of the session to retrieve.
     *
     * @return string The user's session, or an empty string if no session exists.
     */
    public function read($sid)
    {
        $session = db_query("SELECT session FROM {sessions} WHERE sid = :sid", array(':sid' => $sid))->fetchField();

        return $session;
    }

    /**
     * Writes an entire session to the database (internal use only).
     *
     * This function is registered with session_set_save_handler() to support
     * database-backed sessions.
     *
     * This function is an internal function and must not be called directly.
     * Doing so may result in corrupted session data or other unexpected behavior.
     * Session data must always be accessed via the $_SESSION superglobal.
     *
     * @param string $sid
     *                      The session ID of the session to write to.
     * @param string $value
     *                      Session data to write as a serialized string.
     *
     * @return bool Always returns TRUE.
     */
    public function write($sid, $value)
    {
        global $user;

        // The exception handler is not active at this point, so we need to do it
        // manually.
        try {
            if (!drupal_save_session()) {
                // We don't have anything to do if we are not allowed to save the session.
                return;
            }

            // Either ssid or sid or both will be added from $key below.
            $fields = array(
                'uid' => $user->uid,
                'cache' => isset($user->cache) ? $user->cache : 0,
                'hostname' => ip_address(),
                'session' => $value,
                'timestamp' => REQUEST_TIME,
            );

            // Use the session ID as 'sid' and an empty string as 'ssid' by default.
            // _drupal_session_read() does not allow empty strings so that's a safe
            // default.
            $key = array('sid' => $sid, 'ssid' => '');
            // On HTTPS connections, use the session ID as both 'sid' and 'ssid'.
            unset($key['ssid']);

            db_merge('sessions')
                ->key($key)
                ->fields($fields)
                ->execute();

            // Likewise, do not update access time more than once per 180 seconds.
            if ($user->uid) {
                db_update('users')
                    ->fields(array(
                        'access' => REQUEST_TIME
                    ))
                    ->condition('uid', $user->uid)
                    ->execute();
            }

            return TRUE;
        } catch (\Exception $exception) {
            require_once DRUPAL_ROOT . '/includes/errors.inc';
            // If we are displaying errors, then do so with no possibility of a further
            // uncaught exception being thrown.
            if (error_displayable()) {
                print '<h1>Uncaught exception thrown in session handler.</h1>';
                print '<p>' . _drupal_render_exception_safe($exception) . '</p><hr />';
            }

            return FALSE;
        }
    }

    /**
     * Session handler assigned by session_set_save_handler().
     *
     * Cleans up a specific session.
     *
     * @param $sid
     *   Session ID.
     * @return bool|void
     */
    public function destroy($sid)
    {
        global $user;

        // Nothing to do if we are not allowed to change the session.
        if (!drupal_save_session()) {
            return;
        }

        // Delete session data.
        db_delete('sessions')
            ->condition('sid', $sid)
            ->execute();

        // Reset $_SESSION and $user to prevent a new session from being started
        // in drupal_session_commit().
        $_SESSION = array();
        $user = drupal_anonymous_user();
    }

    /**
     * Session handler assigned by session_set_save_handler().
     *
     * Cleans up stalled sessions.
     *
     * @param $lifetime
     *   The value of session.gc_maxlifetime, passed by PHP.
     *   Sessions not updated for more than $lifetime seconds will be removed.
     * @return bool
     */
    public function gc($lifetime)
    {
        // Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
        // value. For example, if you want user sessions to stay in your database
        // for three weeks before deleting them, you need to set gc_maxlifetime
        // to '1814400'. At that value, only after a user doesn't log in after
        // three weeks (1814400 seconds) will his/her session be removed.
        db_delete('sessions')
            ->condition('timestamp', REQUEST_TIME - $lifetime, '<')
            ->execute();

        return TRUE;
    }
}
