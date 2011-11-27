<?php

class Auth extends MY_Controller
{
    public function login()
    {
        if($_POST)
        {
            $user = User::validate_login($_POST['email'], $_POST['password']);

            if($user)
                redirect('');
            else
                redirect('login');
        }
        $this->load->view("auth/login");
    }

    public function logout()
    {
        User::logout();

        redirect('');
    }
}

?>