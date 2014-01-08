<?php

namespace Bangpound\Bundle\DrupalBundle;

/**
 * Class SwiftMailSystem
 * @package Bangpound\Bundle\DrupalBundle
 */
class SwiftMailSystem implements \MailSystemInterface
{
    // Define message formats.
    const SWIFTMAILER_FORMAT_PLAIN = 'text/plain';
    const SWIFTMAILER_FORMAT_HTML = 'text/html';

    // Define header types.
    const SWIFTMAILER_HEADER_TEXT = 'text';
    const SWIFTMAILER_HEADER_PARAMETERIZED = 'parameterized';
    const SWIFTMAILER_HEADER_MAILBOX = 'mailbox';
    const SWIFTMAILER_HEADER_DATE = 'date';
    const SWIFTMAILER_HEADER_ID = 'ID';
    const SWIFTMAILER_HEADER_PATH = 'path';

    // Define system variables defaults.
    const SWIFTMAILER_VARIABLE_RESPECT_FORMAT_DEFAULT = false;
    const SWIFTMAILER_VARIABLE_CONVERT_MODE_DEFAULT = false;
    const SWIFTMAILER_VARIABLE_PATH_DEFAULT = '';
    const SWIFTMAILER_VARIABLE_FORMAT_DEFAULT = 'text/plain';
    const SWIFTMAILER_VARIABLE_CHARACTER_SET_DEFAULT = 'UTF-8';

    const SWIFTMAILER_DATE_PATTERN = '(Mon|Tue|Wed|Thu|Fri|Sat|Sun), [0-9]{2} (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) [0-9]{4} (2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9]) (\+|\-)([01][0-2])([0-5][0-9])';
    const SWIFTMAILER_MAILBOX_PATTERN = '(^.*\<.*@.*\>$|^.*@.*$)';

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    public function __construct()
    {
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $GLOBALS['service_container'];
        $this->mailer = $container->get('mailer');
    }

    /**
     * {@inheritDoc}
     */
    public function format(array $message)
    {
        // Join the body array into one string.
        $message['body'] = implode("\n\n", $message['body']);
        // Convert any HTML to plain-text.
        $message['body'] = drupal_html_to_text($message['body']);
        // Wrap the mail body for sending.
        $message['body'] = drupal_wrap_mail($message['body']);

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function mail(array $message)
    {
        // Create a new message.
        $m = \Swift_Message::newInstance();

        // Not all Drupal headers should be added to the e-mail message.
        // Some headers must be supressed in order for Swift Mailer to
        // do its work properly.
        $suppressable_headers = self::getSupressableHeaders();

        // Keep track of whether we need to respect the provided e-mail
        // format or not
        $respect_format = variable_get('swiftmailer_respect_format', self::SWIFTMAILER_VARIABLE_RESPECT_FORMAT_DEFAULT);

        // Process headers provided by Drupal. We want to add all headers which
        // are provided by Drupal to be added to the message. For each header we
        // first have to find out what type of header it is, and then add it to
        // the message as the particular header type.
        if (!empty($message['headers']) && is_array($message['headers'])) {
            foreach ($message['headers'] as $header_key => $header_value) {

                // Check wether the current header key is empty or represents
                // a header that should be supressed. If yes, then skip header.
                if (empty($header_key) || in_array($header_key, $suppressable_headers)) {
                    continue;
                }

                // Skip 'Content-Type' header if the message to be sent will be a
                // multipart message or the provided format is not to be respected.
                if ($header_key == 'Content-Type' && (!$respect_format || self::isMultipart($message))) {
                    continue;
                }

                // Get header type.
                $header_type = self::getHeaderType($header_key, $header_value);

                // Add the current header to the e-mail message.
                switch ($header_type) {
                    case self::SWIFTMAILER_HEADER_ID:
                        self::addIdHeader($m, $header_key, $header_value);
                        break;

                    case self::SWIFTMAILER_HEADER_PATH:
                        self::addPathHeader($m, $header_key, $header_value);
                        break;

                    case self::SWIFTMAILER_HEADER_MAILBOX:
                        self::addMailboxHeader($m, $header_key, $header_value);
                        break;

                    case self::SWIFTMAILER_HEADER_DATE:
                        self::addDateHeader($m, $header_key, $header_value);
                        break;

                    case self::SWIFTMAILER_HEADER_PARAMETERIZED:
                        self::addParameterizedHeader($m, $header_key, $header_value);
                        break;

                    default:
                        self::addTextHeader($m, $header_key, $header_value);
                        break;

                }
            }
        }

        // Set basic message details.
        self::removeHeader($m, 'From');
        self::removeHeader($m, 'To');
        self::removeHeader($m, 'Subject');

        // Parse 'from' and 'to' mailboxes.
        $from = self::parseMailboxes($message['from']);
        $to = self::parseMailboxes($message['to']);

        // Set 'from', 'to' and 'subject' headers.
        $m->setFrom($from);
        $m->setTo($to);
        $m->setSubject($message['subject']);

        // Get applicable format.
        $applicable_format = $this->getApplicableFormat($message);

        // Get applicable character set.
        $applicable_charset = $this->getApplicableCharset($message);

        // Set body.
        $m->setBody($message['body'], $applicable_format, $applicable_charset);

        // Add alternative plain text version if format is HTML and plain text
        // version is available.
        if ($applicable_format == self::SWIFTMAILER_FORMAT_HTML && !empty($message['plain'])) {
            $m->addPart($message['plain'], self::SWIFTMAILER_FORMAT_PLAIN, $applicable_charset);
        }

        // Validate that $message['params']['files'] is an array.
        if (empty($message['params']['files']) || !is_array($message['params']['files'])) {
            $message['params']['files'] = array();
        }

        // Let other modules get the chance to add attachable files.
        $files = module_invoke_all('swiftmailer_attach', $message['key']);
        if (!empty($files) && is_array($files)) {
            $message['params']['files'] = array_merge(array_values($message['params']['files']), array_values($files));
        }

        // Attach files.
        if (!empty($message['params']['files']) && is_array($message['params']['files'])) {
            $this->attach($m, $message['params']['files']);
        }

        // Embed images.
        if (!empty($message['params']['images']) && is_array($message['params']['images'])) {
            $this->embed($m, $message['params']['images']);
        }

        // Send the message.
        return $this->mailer->send($m);
    }

    /**
     * Process attachments.
     *
     * @param \Swift_Message $m
     *                              The message which attachments are to be added to.
     * @param array          $files
     *                              The files which are to be added as attachments to the provided message.
     */
    protected function attach(\Swift_Message $m, array $files)
    {
        // Iterate through each array element.
        foreach ($files as $file) {

            if ($file instanceof \stdClass) {

                // Validate required fields.
                if (empty($file->uri) || empty($file->filename) || empty($file->filemime)) {
                    continue;
                }

                // Get file data.
                if (valid_url($file->uri, TRUE)) {
                    $content = file_get_contents($file->uri);
                } else {
                    $content = file_get_contents(drupal_realpath($file->uri));
                }

                $filename = $file->filename;
                $filemime = $file->filemime;

                // Attach file.
                $m->attach(\Swift_Attachment::newInstance($content, $filename, $filemime));
            }
        }
    }

    /**
     * Process inline images..
     *
     * @param \Swift_Message $m
     *                               The message which inline images are to be added to.
     * @param array          $images
     *                               The images which are to be added as inline images to the provided
     *                               message.
     */
    protected function embed(\Swift_Message $m, array $images)
    {
        // Iterate through each array element.
        foreach ($images as $image) {

            if ($image instanceof \stdClass) {

                // Validate required fields.
                if (empty($image->uri) || empty($image->filename) || empty($image->filemime) || empty($image->cid)) {
                    continue;
                }

                // Keep track of the 'cid' assigned to the embedded image.
                $cid = NULL;

                // Get image data.
                if (valid_url($image->uri, TRUE)) {
                    $content = file_get_contents($image->uri);
                } else {
                    $content = file_get_contents(drupal_realpath($image->uri));
                }

                $filename = $image->filename;
                $filemime = $image->filemime;

                // Embed image.
                $cid = $m->embed(\Swift_Image::newInstance($content, $filename, $filemime));

                // The provided 'cid' needs to be replaced with the 'cid' returned
                // by the Swift Mailer library.
                $body = $m->getBody();
                $body = preg_replace('/cid.*' . $image->cid . '/', $cid, $body);
                $m->setBody($body);
            }
        }
    }

    /**
     * Returns the applicable format.
     *
     * @param array $message
     *                       The message for which the applicable format is to be determined.
     *
     * @return string
     *                A string being the applicable format.
     *
     */
    protected function getApplicableFormat($message)
    {
        // Get the configured default format.
        $default_format = variable_get('swiftmailer_format', self::SWIFTMAILER_VARIABLE_FORMAT_DEFAULT);

        // Get whether the provided format is to be respected.
        $respect_format = variable_get('swiftmailer_respect_format', self::SWIFTMAILER_VARIABLE_RESPECT_FORMAT_DEFAULT);

        // Check if a format has been provided particularly for this message. If
        // that is the case, then apply that format instead of the default format.
        $applicable_format = !empty($message['params']['format']) ? $message['params']['format'] : $default_format;

        // Check if the provided format is to be respected, and if a format has been
        // set through the header "Content-Type". If that is the case, the apply the
        // format provided. This will override any format which may have been set
        // through $message['params']['format'].
        if ($respect_format && !empty($message['headers']['Content-Type'])) {
            $format = $message['headers']['Content-Type'];
            $format = preg_match('/.*\;/U', $format, $matches);

            if ($format > 0) {
                $applicable_format = trim(substr($matches[0], 0, -1));
            } else {
                $applicable_format = $default_format;
            }
        }

        return $applicable_format;
    }

    /**
     * Returns the applicable charset.
     *
     * @param array $message
     *                       The message for which the applicable charset is to be determined.
     *
     * @return string
     *                A string being the applicable charset.
     *
     */
    protected function getApplicableCharset($message)
    {
        // Get the configured default format.
        $default_charset = variable_get('swiftmailer_character_set', self::SWIFTMAILER_VARIABLE_CHARACTER_SET_DEFAULT);

        // Get whether the provided format is to be respected.
        $respect_charset = variable_get('swiftmailer_respect_format', self::SWIFTMAILER_VARIABLE_RESPECT_FORMAT_DEFAULT);

        // Check if a format has been provided particularly for this message. If
        // that is the case, then apply that format instead of the default format.
        $applicable_charset = !empty($message['params']['charset']) ? $message['params']['charset'] : $default_charset;

        // Check if the provided format is to be respected, and if a format has been
        // set through the header "Content-Type". If that is the case, the apply the
        // format provided. This will override any format which may have been set
        // through $message['params']['format'].
        if ($respect_charset && !empty($message['headers']['Content-Type'])) {
            $format = $message['headers']['Content-Type'];
            $format = preg_match('/charset.*=.*\;/U', $format, $matches);

            if ($format > 0) {
                $applicable_charset = trim(substr($matches[0], 0, -1));
                $applicable_charset = preg_replace('/charset=/', '', $applicable_charset);
            } else {
                $applicable_charset = $default_charset;
            }
        }

        return $applicable_charset;
    }

    /**
     * Determines the header type based on the a header key and value.
     *
     * @param string $key
     *                      The header key.
     * @param string $value
     *                      The header value.
     *
     * @return string
     *                The header type as determined based on the provided header key
     *                and value.
     */
    public function getHeaderType($key, $value)
    {
        if (self::isIdHeader($key, $value)) {
            return self::SWIFTMAILER_HEADER_ID;
        }

        if (self::isPathHeader($key, $value)) {
            return self::SWIFTMAILER_HEADER_PATH;
        }

        if (self::isMailboxHeader($key, $value)) {
            return self::SWIFTMAILER_HEADER_MAILBOX;
        }

        if (self::isDateHeader($key, $value)) {
            return self::SWIFTMAILER_HEADER_DATE;
        }

        if (self::isParameterizedHeader($key, $value)) {
            return self::SWIFTMAILER_HEADER_PARAMETERIZED;
        }

        return self::SWIFTMAILER_HEADER_TEXT;
    }

    /**
     * Adds a text header to a message.
     *
     * @param \Swift_Message $message
     *                                The message which the text header is to be added to.
     * @param string         $key
     *                                The header key.
     * @param string         $value
     *                                The header value.
     */
    protected static function addTextHeader(\Swift_Message $message, $key, $value)
    {
        // Remove any already existing header identified by the provided key.
        self::removeHeader($message, $key);

        // Add the header.
        $message->getHeaders()->addTextHeader($key, $value);
    }

    /**
     * Checks whether a header is a parameterized header.
     *
     * @see http://swift_mailer.org/docs/header-parameterized
     *
     * @param string $key
     *                      The header key.
     * @param string $value
     *                      The header value.
     *
     * @return boolean
     *                 TRUE if the provided header is a parameterized header,
     *                 and otherwise FALSE.
     */
    protected static function isParameterizedHeader($key, $value)
    {
        if (preg_match('/;/', $value)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Adds a parameterized header to a message.
     *
     * @param \Swift_Message $message
     *                                The message which the parameterized header is to be added to.
     * @param string         $key
     *                                The header key.
     * @param string         $value
     *                                The header value.
     */
    protected static function addParameterizedHeader(\Swift_Message $message, $key, $value)
    {
        // Remove any already existing header identified by the provided key.
        self::removeHeader($message, $key);

        // Define variables to hold the header's value and parameters.
        $header_value = NULL;
        $header_parameters = array();

        // Split the provided value by ';' (semicolon), which we assume is the
        // character is used to separate the parameters.
        $parameter_pairs = explode(';', $value);

        // Iterate through the extracted parameters, and prepare each of them to be
        // added to a parameterized message header. There should be a single text
        // parameter and one or more key/value parameters in the provided header
        // value. We assume that a '=' (equals) character is used to separate the
        // key and value for each of the parameters.
        foreach ($parameter_pairs as $parameter_pair) {

            // Find out whether the current parameter pair really is a parameter
            // pair or just a single value.
            if (preg_match('/=/', $parameter_pair) > 0) {

                // Split the parameter so that we can access the parameter's key and
                // value separately.
                $parameter_pair = explode('=', $parameter_pair);

                // Validate that the parameter has been split in two, and that both
                // the parameter's key and value is accessible. If that is the case,
                // then add the current parameter's key and value to the array which
                // holds all parameters to be added to the current header.
                if (!empty($parameter_pair[0]) && !empty($parameter_pair[1])) {
                    $header_parameters[trim($parameter_pair[0])] = trim($parameter_pair[1]);
                }

            } else {
                $header_value = trim($parameter_pair);
            }
        }

        // Add the parameterized header.
        $message->getHeaders()->addParameterizedHeader($key, $header_value, $header_parameters);
    }

    /**
     * Checks whether a header is a date header.
     *
     * @see http://swift_mailer.org/docs/header-date
     *
     * @param string $key
     *                      The header key.
     * @param string $value
     *                      The header value.
     *
     * @return boolean
     *                 TRUE if the provided header is a date header, and otherwise FALSE.
     */
    protected static function isDateHeader($key, $value)
    {
        if (preg_match('/' . self::SWIFTMAILER_DATE_PATTERN . '/', $value)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Adds a date header to a message.
     *
     * @param \Swift_Message $message
     *                                The message which the date header is to be added to.
     * @param string         $key
     *                                The header key.
     * @param string         $value
     *                                The header value.
     */
    protected static function addDateHeader(\Swift_Message $message, $key, $value)
    {
        // Remove any already existing header identified by the provided key.
        self::removeHeader($message, $key);

        // Add the header.
        $message->getHeaders()->addDateHeader($key, $value);
    }

    /**
     * Checks whether a header is a mailbox header.
     *
     * It is difficult to distinguish id, mailbox and path headers from each other
     * as they all may very well contain the exact same value. This function simply
     * checks whether the header key equals to 'Message-ID' to determine if the
     * header is a path header.
     *
     * @see http://swift_mailer.org/docs/header-mailbox
     *
     * @param string $key
     *                      The header key.
     * @param string $value
     *                      The header value.
     *
     * @return boolean
     *                 TRUE if the provided header is a mailbox header, and otherwise FALSE.
     */
    protected static function isMailboxHeader($key, $value)
    {
        if (preg_match('/' . self::SWIFTMAILER_MAILBOX_PATTERN . '/', $value)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Adds a mailbox header to a message.
     *
     * @param \Swift_Message $message
     *                                The message which the mailbox header is to be added to.
     * @param string         $key
     *                                The header key.
     * @param string         $value
     *                                The header value.
     */
    protected static function addMailboxHeader(\Swift_Message $message, $key, $value)
    {
        // Remove any already existing header identified by the provided key.
        self::removeHeader($message, $key);

        // Parse mailboxes.
        $mailboxes = self::parseMailboxes($value);

        // Add the header.
        $message->getHeaders()->addMailboxHeader($key, $mailboxes);
    }

    /**
     * Checks whether a header is an id header.
     *
     * It is difficult to distinguish id, mailbox and path headers from each other
     * as they all may very well contain the exact same value. This function simply
     * checks whether the header key equals to 'Message-ID' to determine if the
     * header is a path header.
     *
     * @see http://swift_mailer.org/docs/header-id
     *
     * @param string $key
     *                      The header key.
     * @param string $value
     *                      The header value.
     *
     * @return boolean
     *                 TRUE if the provided header is an id header, and otherwise FALSE.
     */
    protected static function isIdHeader($key, $value)
    {
        if (valid_email_address($value) && $key == 'Message-ID') {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Adds an id header to a message.
     *
     * @param \Swift_Message $message
     *                                The message which the id header is to be added to.
     * @param string         $key
     *                                The header key.
     * @param string         $value
     *                                The header value.
     */
    protected static function addIdHeader(\Swift_Message $message, $key, $value)
    {
        // Remove any already existing header identified by the provided key.
        self::removeHeader($message, $key);

        // Add the header.
        $message->getHeaders()->addIdHeader($key, $value);
    }

    /**
     * Checks whether a header is a path header.
     *
     * It is difficult to distinguish id, mailbox and path headers from each other
     * as they all may very well contain the exact same value. This function simply
     * checks whether the header key equals to 'Message-ID' to determine if the
     * header is a path header.
     *
     * @see http://swift_mailer.org/docs/header-path
     *
     * @param string $key
     *                      The header key.
     * @param string $value
     *                      The header value.
     *
     * @return boolean
     *                 TRUE if the provided header is a path header, and otherwise FALSE.
     */
    protected static function isPathHeader($key, $value)
    {
        if (valid_email_address($value) && $key == 'Return-Path') {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Adds a path header to a message.
     *
     * @param \Swift_Message $message
     *                                The message which the path header is to be added to.
     *
     * @param string $key
     *                      The header key.
     * @param string $value
     *                      The header value.
     */
    protected static function addPathHeader(\Swift_Message $message, $key, $value)
    {
        // Remove any already existing header identified by the provided key.
        self::removeHeader($message, $key);

        // Add the header.
        $message->getHeaders()->addPathHeader($key, $value);
    }

    /**
     * Removes a header from a message.
     *
     * @param \Swift_Message $message
     *                                The message which the header is to be removed from.
     * @param string         $key
     *                                The header key.
     */
    protected static function removeHeader(\Swift_Message $message, $key)
    {
        // Get message headers.
        $headers = $message->getHeaders();

        // Remove the header if it already exists.
        $headers->removeAll($key);
    }

    /**
     * Converts a string holding one or more mailboxes to an array.
     *
     * @param $value
     *    A string holding one or more mailboxes.
     * @return array
     */
    protected static function parseMailboxes($value)
    {
        // Split mailboxes by ',' (comma) and ';' (semicolon).
        $mailboxes_raw = preg_split('/(,|;)/', $value);

        // Define an array which will keep track of mailboxes.
        $mailboxes = array();

        // Iterate through each of the raw mailboxes and process them.
        foreach ($mailboxes_raw as $mailbox_raw) {

            // Remove leading and trailing whitespace.
            $mailbox_raw = trim($mailbox_raw);

            if (preg_match('/^.*<.*>$/', $mailbox_raw)) {
                $mailbox_components = explode('<', $mailbox_raw);
                $mailbox_name = trim($mailbox_components[0]);
                $mailbox_address = preg_replace('/>.*/', '', $mailbox_components[1]);
                $mailboxes[$mailbox_address] = $mailbox_name;
            } else {
                $mailboxes[] = $mailbox_raw;
            }
        }

        return $mailboxes;
    }

    /**
     * Returns a list of supressable e-mail headers.
     *
     * The returned e-mail headers could be provided by Drupal, but should be
     * ignored in order to make Swift Mailer work as smooth as possible.
     *
     * @return array
     *               A list of e-mail headers which could be provided by Drupal, but which
     *               should be ignored.
     */
    protected static function getSupressableHeaders()
    {
        return array(
            'Content-Transfer-Encoding',
        );
    }

    /**
     * Validates whether a message is multipart or not.
     *
     * @param array $message
     *                       The message which is to be validatet.
     *
     * @return boolean
     *                 A boolean indicating whether the message is multipart or not.
     */
    protected static function isMultipart(&$message)
    {
        $parts = 0;

        if (!empty($message['body'])) {
            $parts++;
        }

        if (!empty($message['plain'])) {
            $parts++;
        }

        if (!empty($message['params']['files'])) {
            $parts++;
        }

        if (!empty($message['params']['images'])) {
            $parts++;
        }

        return ($parts > 1) ? TRUE : FALSE;
    }
}
