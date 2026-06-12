<?php
/**
 * Not-found exception for frame requests.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */

namespace AchttienVijftien\Bundle\WpTurboBundle\Http;

/**
 * Thrown when a frame request references state that does not exist or is not
 * publicly viewable; the dispatcher translates it into a 404 response.
 *
 * @package AchttienVijftien\Bundle\WpTurboBundle
 */
class NotFoundException extends \RuntimeException {
}
