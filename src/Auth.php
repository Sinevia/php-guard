<?php

namespace Sinevia\Guard;

class Auth {

    private $endpoint = '';
    private $commandName = 'grd';
    private $loginSuccessfulRedirectUrl = null;

    public function getLoginSuccessfulRedirectUrl() {
        return $this->loginSuccessfulRedirectUrl;
    }

    public function setLoginSuccessfulRedirectUrl($loginSuccessfulRedirectUrl) {
        $this->loginSuccessfulRedirectUrl = $loginSuccessfulRedirectUrl;
        return $this;
    }

    function __construct() {
        $this->endpoint = $_SERVER['PHP_SELF'];
    }

    public function getEndpoint() {
        return $this->endpoint;
    }

    public function setEndpoint($endpoint) {
        $this->endpoint = $endpoint;
        return $this;
    }

    function serve() {
        $isPost = (($_SERVER['REQUEST_METHOD'] ?? "")) == "POST" ? true : false;
        $command = $_REQUEST['grd'] ?? 'login';
        if ($command == 'confirm') {
            return $this->emailConfirmationProcess();
        }
        if ($command == 'register') {
            $error = $isPost ? $this->formRegisterProcess() : '';
            return $this->formRegister($error);
        }
        if ($command == 'login') {
            $error = $isPost ? $this->formLoginProcess() : '';
            if ($isPost AND trim($error) == "") {
                return redirect($this->loginSuccessfulRedirectUrl);
            }
            return $this->formLogin($error);
        }
    }

    function linkLogin() {
        return $this->endpoint . '?' . $this->commandName . '=login';
    }

    function linkPasswordRestore() {
        return $this->endpoint . '?' . $this->commandName . '=password_restore';
    }

    function linkRegister() {
        return $this->endpoint . '?' . $this->commandName . '=register';
    }

    function linkConfirmEmail($confirmationCode) {
        return $this->endpoint . '?' . $this->commandName . '=confirm&code=' . urlencode($confirmationCode);
    }

    function emailConfirmationProcess() {
        /* START: Data */
        $code = (isset($_REQUEST['code']) == false) ? '' : trim($_REQUEST['code']);
        $error = '';
        /* END: Data */

        var_dump($code);
        // 2. Retrieve entities by search
        $userTemps = \Sinevia\Schemaless::getEntities([
                    'Type' => 'SnvGuardUserTemp',
                    'limitFrom' => 0,
                    'limitTo' => 1,
                    // Advanced querying
                    'wheres' => [
                        ['Attribute_Token', '=', $code],
                    ],
                    // Should returned entities contain also the attributes
                    'withAttributes' => true,
        ]);

        $userTemp = count($userTemps) > 0 ? $userTemps[0] : null;

        if (is_null($userTemp)) {
            die('This link has expired. Please try again');
        }

        $email = $userTemp['Attributes']['Email'];
    }

    function formLoginProcess() {
        /* START: Data */
        $email = (isset($_POST['email']) == false) ? '' : trim($_POST['email']);
        $password = (isset($_POST['password']) == false) ? '' : trim($_POST['password']);
        $sid = (isset($_POST['sid']) == false) ? '' : trim($_POST['sid']);
        /* END: Data */

        // START: Validate
        if ($sid != session_id()) {
            return 'Security token mismatch or expired';
        }
        if ($email == "") {
            $error = 'Email is required...';
        } else if ($password == "") {
            $error = 'Password is required';
        }
        // END: Validate

        return $error;
    }

    function formRegisterProcess() {
        /* START: Data */
        $email = (isset($_POST['email']) == false) ? '' : trim($_POST['email']);
        $emailConfirm = (isset($_POST['email_confirm']) == false) ? '' : trim($_POST['email_confirm']);
        $password = (isset($_POST['password']) == false) ? '' : trim($_POST['password']);
        $passwordConfirm = (isset($_POST['password_confirm']) == false) ? '' : trim($_POST['password_confirm']);
        $sid = (isset($_POST['sid']) == false) ? '' : trim($_POST['sid']);
        $error = '';
        /* END: Data */

        // START: Validate
        if ($sid != session_id()) {
            return 'Security token mismatch or expired';
        }
        if ($email == "") {
            $error = 'Email is required...';
        } else if ($password == "") {
            $error = 'Password is required';
        } else if ($email != $emailConfirm) {
            $error = 'Confirmation email DOES NOT match email';
        } else if ($password != $passwordConfirm) {
            $error = 'Confirmation password DOES NOT match password';
        }
        // END: Validate
        $token = \Sinevia\AuthenticationUtils::randomPassword(12, 'BCDF');
        $newEntry = \Sinevia\Schemaless::createEntity([
                    'Type' => 'SnvGuardUserTemp',
                    'Title' => $email
                        ], [
                    'Email' => $email,
                    'Password' => \Sinevia\AuthenticationUtils::hash($password),
                    'Token' => $token
        ]);

        $msg = [];
        $msg[] = 'Please, use the link below to confirm your e-mail:';
        $msg[] = '<a href="' . $this->linkConfirmEmail($token) . '">' . $this->linkConfirmEmail($token) . '</a>';

        $htmlMessage = implode("<br />\n", $msg);
        $textMessage = \Sinevia\StringUtils::htmlEmailToText($htmlMessage);

        $isSent = \App\Helpers\App::sendMail([
                    'From' => 'info@sinevia.com',
                    'To' => 'info@sinevia.com',
                    //'Cc' => '',
                    //'Bcc' => '',
                    'Subject' => 'Confirm Your E-mail',
                    'Text' => $textMessage,
                    'Html' => $htmlMessage,
                        ], true);

        var_dump($isSent);
        dd($newEntry);


        return $error;
    }

    function formLogin($error = '') {
        /* START: Data */
        $password = (isset($_POST['password']) == false) ? '' : trim($_POST['password']);
        $email = (isset($_POST['email']) == false) ? '' : trim($_POST['email']);
        $sid = (isset($_POST['sid']) == false) ? '' : trim($_POST['sid']);
        /* END: Data */

        if ($error != '') {
            $error = '<h1 style="color:red;font-size:12px;">' . $error . '</h1>';
        }

        $form = '';
        $form .= '<form method="post">';
        $form .= '  <div class="form-group">';
        $form .= '    <div style="font-size:36px;color:orange;font-family:georgia,arial;">login</div>';
        $form .= $error;
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <label>E-mail</label>';
        $form .= '    <input class="form-control" type="text" name="email" value="' . $email . '" />';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <label>Password</label>';
        $form .= '    <input class="form-control" type="password" name="password" value="' . $password . '" />';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <button class="btn btn-success" type="submit">Login</button>';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <a href="' . $this->linkPasswordRestore() . '">Forgot Password</a>';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <a href="' . $this->linkRegister() . '?grd=register">Register</a>';
        $form .= '  </div>';
        $form .= '  <input type="hidden" name="sid" value="' . session_id() . '">';
        $form .= '  <input type="hidden" name="tid" value="' . base64_encode(time()) . '">';
        $form .= '</form>';

        $html = '';
        $html .= '<div class="container">';
        $html .= $form;
        $html .= '</div>';
        return $html;
    }

    function formRegister($error = '') {
        /* START: Data */
        $email = (isset($_POST['email']) == false) ? '' : trim($_POST['email']);
        $emailConfirm = (isset($_POST['email_confirm']) == false) ? '' : trim($_POST['email_confirm']);
        $password = (isset($_POST['password']) == false) ? '' : trim($_POST['password']);
        $passwordConfirm = (isset($_POST['password_confirm']) == false) ? '' : trim($_POST['password_confirm']);
        $sid = (isset($_POST['sid']) == false) ? '' : trim($_POST['sid']);
        /* END: Data */

        if ($error != '') {
            $error = '<h1 style="color:red;font-size:12px;">' . $error . '</h1>';
        }

        $form = '';
        $form .= '<form method="post">';
        $form .= '  <div class="form-group">';
        $form .= '    <div style="font-size:36px;color:orange;font-family:georgia,arial;">register</div>';
        $form .= $error;
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <label>E-mail</label>';
        $form .= '    <input class="form-control" type="text" name="email" value="' . htmlentities($email) . '" />';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <label>Confirm E-mail</label>';
        $form .= '    <input class="form-control" type="text" name="email_confirm" value="' . htmlentities($emailConfirm) . '" />';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <label>Password</label>';
        $form .= '    <input class="form-control" type="password" name="password" value="' . htmlentities($password) . '" />';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <label>Confirm Password</label>';
        $form .= '    <input class="form-control" type="password" name="password_confirm" value="' . htmlentities($passwordConfirm) . '" />';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <button class="btn btn-success" type="submit">Register</button>';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <a href="' . $this->linkPasswordRestore() . '">Forgot Password</a>';
        $form .= '  </div>';
        $form .= '  <div class="form-group">';
        $form .= '    <a href="' . $this->linkRegister() . '">Register</a>';
        $form .= '  </div>';
        $form .= '  <input type="hidden" name="sid" value="' . session_id() . '">';
        $form .= '  <input type="hidden" name="tid" value="' . base64_encode(time()) . '">';
        $form .= '</form>';

        $html = '';
        $html .= '<div class="container">';
        $html .= $form;
        $html .= '</div>';
        return $html;
    }

}
