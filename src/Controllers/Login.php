<?php
namespace Bolt\Controllers;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;

class Login implements Silex\ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Silex\Application $app)
    {
        /** @var $ctl \Silex\ControllerCollection */
        $ctl = $app['controllers_factory'];

        $ctl->get('/login', array($this, 'getLogin'))
            ->before(array('\Bolt\Controllers\Backend', 'before'))
            ->bind('login');

        $ctl->post('/login', array($this, 'postLogin'))
            ->before(array('\Bolt\Controllers\Backend', 'before'))
            ->bind('postLogin');

        $ctl->match('/logout', array($this, 'logout'))
            ->bind('logout');

        $ctl->get('/resetpassword', array($this, 'resetPassword'))
            ->bind('resetpassword');

        return $ctl;
    }

    /**
     * Handle a login attempt.
     *
     * @param  Silex\Application $app     The application/container
     * @param  Request           $request The Symfony Request
     * @return string
     */
    public function postLogin(Silex\Application $app, Request $request)
    {
        switch ($request->get('action')) {
            case 'login':
                // Log in, if credentials are correct.
                $result = $app['users']->login($request->get('username'), $request->get('password'));

                if ($result) {
                    $app['logger.system']->addInfo('Logged in: ' . $request->get('username'), array('event' => 'authentication'));
                    $retreat = $app['session']->get('retreat');
                    $redirect = !empty($retreat) && is_array($retreat) ? $retreat : array('route' => 'dashboard', 'params' => array());

                    return Lib::redirect($redirect['route'], $redirect['params']);
                }

                return $this->getLogin($app, $request);

            case 'reset':
                // Send a password request mail, if username exists.
                $username = trim($request->get('username'));
                if (empty($username)) {
                    $app['users']->session->getFlashBag()->add('error', Trans::__('Please provide a username', array()));
                } else {
                    $app['users']->resetPasswordRequest($request->get('username'));

                    return Lib::redirect('login');
                }

                return $this->getLogin($app, $request);

            default:
                // Let's not disclose any internal information.
                return $app->abort(400, 'Invalid request');
        }
    }

    /**
     * Login page and "Forgotten password" page.
     *
     * @param  Silex\Application $app The application/container
     * @return string
     */
    public function getLogin(Silex\Application $app)
    {
        if (!empty($app['users']->currentuser) && $app['users']->currentuser['enabled'] == 1) {
            return Lib::redirect('dashboard', array());
        }

        $context = array(
            'randomquote' => true,
        );

        return $app['render']->render('login/login.twig', array('context' => $context));
    }

    /**
     * Logout page.
     *
     * @param  Silex\Application $app The application/container
     * @return string
     */
    public function logout(Silex\Application $app)
    {
        $user = $app['session']->get('user');
        $app['logger.system']->addInfo('Logged out: ' . $user['displayname'], array('event' => 'authentication'));

        $app['users']->logout();

        return Lib::redirect('login');
    }

    /**
     * Reset the password. This controller is normally only reached when the user
     * clicks a "password reset" link in the email.
     *
     * @param  Silex\Application $app     The application/container
     * @param  Request           $request The Symfony Request
     * @return string
     */
    public function resetPassword(Silex\Application $app, Request $request)
    {
        $app['users']->resetPasswordConfirm($request->get('token'));

        return Lib::redirect('login');
    }
}
