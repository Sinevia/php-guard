function login_action() {
    /* START: Data */
    $password = (isset($_POST['password']) == false) ? '' : trim($_POST['password']);
    $email = (isset($_POST['email']) == false) ? '' : trim($_POST['email']);
    $sid = (isset($_POST['sid']) == false) ? '' : trim($_POST['sid']);
    $error = '';
    /* END: Data */

    // START: Submit
    if ($sid == session_id()) {
        if ($email == "") {
            $error = 'Email is required...';
        } else if ($password == "") {
            $error = 'Password is required';
        } else if ($email != Config::adminEmail()) {
            $error = 'E-mail is not correct';
        } else if ($password != Config::adminPassword()) {
            $error = 'Password is not correct';
        }

        if ($error == '') {
            $_SESSION['is_logged'] = true;
            redirect($_SERVER['PHP_SELF']);
        }
    }
    // END: Submit

    if ($error != '') {
        $error = '<h1 style="color:red;font-size:12px;">' . $error . '</h1>';
    }

    $form = '';
    $form.='<form method="post">';
    $form.='  <div class="form-group">';
    $form.='    <div style="font-size:36px;color:orange;font-family:georgia,arial;">login</div>';
    $form.= $error;
    $form.='  </div>';
    $form.='  <div class="form-group">';
    $form.='    <label>E-mail</label>';
    $form.='    <input class="form-control" type="text" name="email" value="' . $email . '" />';
    $form.='  </div>';
    $form.='  <div class="form-group">';
    $form.='    <label>Password</label>';
    $form.='    <input class="form-control" type="password" name="password" value="' . $password . '" />';
    $form.='  </div>';
    $form.='  <div class="form-group">';
    $form.='    <button class="btn btn-success" type="submit">Login</button>';
    $form.='  </div>';
    $form.='  <div class="form-group">';
    $form.='    <a href="' . $_SERVER['PHP_SELF'] . '?a=password_restore">Forgot Password</a>';
    $form.='  </div>';
    $form.='  <input type="hidden" name="sid" value="' . session_id() . '">';
    $form.='  <input type="hidden" name="tid" value="' . base64_encode(time()) . '">';
    $form.='</form>';

    $html = '';
    $html .= '<div class="container">';
    $html .= $form;
    $html .= '</div>';

    return Ui::webpage('Login', $html);
}

function logout_action() {
    session_unset();
    return redirect(url('login'));
}
