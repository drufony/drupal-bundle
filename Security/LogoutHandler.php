<?php
namespace Bangpound\Bundle\DrupalBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutHandler implements LogoutHandlerInterface
{

    /**
     * {@inheritdoc}
     * @see user_logout()
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $user = $token->getUser();
        if (is_a($user, 'Bangpound\\Bundle\\DrupalBundle\\Security\\User\\User')) {

            /** @var \stdClass $user */
            $user = $token->getUser()->getDrupalUser();

            watchdog('user', 'Session closed for %name.', array('%name' => $user->name));

            module_invoke_all('user_logout', $user);

            $GLOBALS['user'] = drupal_anonymous_user();
        }
    }
}
