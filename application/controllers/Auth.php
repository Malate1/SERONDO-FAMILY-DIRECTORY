<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Clan Representative authentication.
 * Routes (see routes.php): /login -> auth/login, /logout -> auth/logout (POST only)
 */
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->config->load('clan');
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'form', 'clan']);
        $this->load->model('Clan_model');
    }

    public function index()
    {
        redirect('login');
    }

    public function login()
    {
        // Already signed in? Go straight to your own clan.
        if (($rep = current_rep()))
        {
            redirect('clan_members/clan/' . $rep->clan_id);
        }

        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $lock_until = (int) $this->session->userdata('login_lock_until');

            if ($lock_until > time())
            {
                $error = 'Too many failed attempts. Please try again in ' . ceil(($lock_until - time()) / 60) . ' minute(s).';
            }
            else
            {
                $this->form_validation->set_rules('username', 'Username', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('password', 'Password', 'required|max_length[200]');

                if ($this->form_validation->run())
                {
                    $rep = $this->_attempt(
                        $this->input->post('username', TRUE),
                        (string) $this->input->post('password')   // never XSS-clean a password
                    );

                    if ($rep)
                    {
                        $this->session->sess_regenerate(TRUE);      // prevent session fixation
                        $this->session->unset_userdata(['login_fails', 'login_lock_until']);
                        $this->session->set_userdata([
                            'rep_id'      => (int) $rep->id,
                            'rep_clan_id' => (int) $rep->clan_id,
                            'rep_name'    => $rep->full_name,
                        ]);
                        $this->session->set_flashdata('success', 'Welcome back, ' . $rep->full_name . '.');
                        redirect('clan_members/clan/' . $rep->clan_id);
                    }

                    $error = $this->_register_failure();
                }
            }
        }

        $this->load->view('login', ['title' => 'Representative Login', 'error' => $error]);
    }

    /** POST only (CSRF-protected form in the header). */
    public function logout()
    {
        if ($this->input->method() !== 'post')
        {
            show_error('Method not allowed.', 405);
        }
        $this->session->sess_destroy();
        redirect('login');
    }

    /* ------------------------------------------------------------------ */

    /** @return object|false  the representative row on success */
    private function _attempt($username, $password)
    {
        $rep = $this->Clan_model->get_representative_by_username($username);

        if ( ! $rep)
        {
            // Burn comparable time so response timing doesn't reveal valid usernames.
            password_verify($password, password_hash('timing-equaliser', PASSWORD_DEFAULT));
            return FALSE;
        }
        if ( ! password_verify($password, $rep->password) || ! (int) $rep->is_active)
        {
            return FALSE;
        }

        // Upgrade the stored hash transparently when PHP's default cost/algorithm changes.
        if (password_needs_rehash($rep->password, PASSWORD_DEFAULT))
        {
            $this->Clan_model->update_representative_password($rep->id, password_hash($password, PASSWORD_DEFAULT));
        }
        return $rep;
    }

    /** Count a failed login; lock the session out after too many. Returns the message to show. */
    private function _register_failure()
    {
        $max   = (int) $this->config->item('login_max_attempts');
        $fails = (int) $this->session->userdata('login_fails') + 1;

        if ($fails >= $max)
        {
            $this->session->set_userdata([
                'login_fails'      => 0,
                'login_lock_until' => time() + (int) $this->config->item('login_lock_seconds'),
            ]);
            return 'Too many failed attempts. Please wait a few minutes and try again.';
        }

        $this->session->set_userdata('login_fails', $fails);
        return 'Invalid username or password.';
    }
}
