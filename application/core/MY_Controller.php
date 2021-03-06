<?php

class MY_User_Controller extends MY_Basic_Controller{
    // I think these can go away 11-28-16
    // protected $userid;
    // protected $leagueid;
    // protected $current_week;
    // protected $current_year;
    // protected $teamid;
    function __construct()
    {
        // Parent loads common_noauth_model and noauth session variables.
        parent::__construct();
        // 1. Initialize flexi auth (lite) and see if we're logged in.
        $this->auth = new stdClass;
        $this->load->library('flexi_auth_lite', FALSE, 'flexi_auth');
        $this->load->model('common/common_model');

        // If not logged in redirect to login page
        if (!$this->flexi_auth->is_logged_in() && !$this->input->is_ajax_request())
        {
             redirect('');
        }

        // 2. Make sure session variables exist and current.
        // User is logged in, but session variables don't exist.  When the flexi_auth_lite library is initialized above, it checks for
        // remember me tokens and "logs in".  If that happens, the session data will have disappeared, this reloads it.
        if ($this->session->userdata('user_id') == "")
        {
            $this->load->model('security_model');
            $this->security_model->set_session_variables();
        }

        // This is to make sure the user session gets these vars if so much time has passed
        // since they were first set.
        if ($this->session->userdata('expire_league_vars') < time())
        {
            $this->load->model('security_model');
            $this->security_model->set_dynamic_session_variables();
        }

        // 3. Turn debugging on, if enabled.
        if ($this->session->userdata('debug') && !$this->input->is_ajax_request())
        {
                $sections = array(
                        'benchmarks' => TRUE, 'memory_usage' => TRUE,
                        'config' => FALSE, 'controller_info' => FALSE, 'get' => FALSE, 'post' => TRUE, 'queries' => TRUE,
                        'uri_string' => FALSE, 'http_headers' => FALSE, 'session_data' => TRUE
                );
                $this->output->set_profiler_sections($sections);
                $this->output->enable_profiler(TRUE);
        }

        // 4. Set some local variables for easier access, sort of regret doing this since I depend on them now.
        // Load session variables
        $this->userid = $this->session->userdata('user_id');
        $this->is_site_admin = $this->session->userdata('is_site_admin');
        $this->is_league_admin = $this->session->userdata('is_league_admin');
        $this->debug = $this->session->userdata('debug');

        // Owner specific session variables
        if ($this->session->userdata('is_owner'))
        {
            $this->leagueid = $this->session->userdata('league_id');
            $this->teamid = $this->session->userdata('team_id');
            $this->ownerid = $this->session->userdata('owner_id');
            $this->team_name = $this->session->userdata('team_name');
            $this->current_year = $this->session->userdata('current_year');
            $this->current_week = $this->session->userdata('current_week');
            $this->week_type = $this->session->userdata('week_type');
            $this->league_name = $this->session->userdata('league_name');
            $this->offseason = $this->session->userdata('offseason');
        }
        elseif ($this->is_site_admin)
        {
            redirect('admin');
        }

        // 5. Initialize array for use with breadcrumbs
        $this->bc = array();
    }

    // View for logged in user who has an ownerid, leagueid, teamid, etc.
    function user_view($viewname, $d=null)
    {
        $this->load->model('menu_model');

        $d['menu_items'] = $this->menu_model->get_menu_items_data();

        $d['v'] = $viewname;
        $d['bc'] = $this->bc;

        $d['_messages'] = $this->common_model->get_user_messages();
        $this->load->view('template/user_init', $d);
    }

}

class MY_Admin_Controller extends MY_Basic_Controller{

    function __construct()
    {
        parent::__construct();

        if ($this->session->userdata('is_owner'))
        {
            $this->current_year = $this->session->userdata('current_year');
            $this->current_week = $this->session->userdata('current_week');
            $this->league_name = $this->session->userdata('league_name');
        }
        $this->is_league_admin = $this->session->userdata('is_league_admin');
        // Initialize flexi auth (lite)
        $this->auth = new stdClass;
        $this->load->library('flexi_auth_lite', FALSE, 'flexi_auth');
        $this->is_admin = $this->flexi_auth->is_admin();

        $this->bc = array();

        // Turn debugging on, if enabled.
        if ($this->session->userdata('debug') && !$this->input->is_ajax_request())
        {
                $sections = array(
                        'benchmarks' => TRUE, 'memory_usage' => TRUE,
                        'config' => FALSE, 'controller_info' => FALSE, 'get' => FALSE, 'post' => TRUE, 'queries' => TRUE,
                        'uri_string' => FALSE, 'http_headers' => FALSE, 'session_data' => TRUE
                );
                $this->output->set_profiler_sections($sections);
                $this->output->enable_profiler(TRUE);
        }

        // If not logged in redirect to login page
        if (!$this->flexi_auth->is_logged_in() || !($this->is_admin || $this->is_league_admin))
        {
             redirect('');
        }
    }

    function admin_view($viewname, $d=null)
    {
        $this->load->model('menu_model');
        $this->load->model('admin/admin_security_model');
        $d['_messages'] = $this->admin_security_model->get_admin_messages();
        $d['menu_items'] = $this->menu_model->get_menu_items_data(true);
        $d['v'] = $viewname;
        $d['bc'] = $this->bc;
        $this->load->view('template/admin_init', $d);
    }
}

class MY_Basic_Controller extends CI_Controller{

    function __construct()
    {
        parent::__construct();
        $this->load->model('common/common_noauth_model');
        $this->common_noauth_model->set_session_variables();

        // Turn debugging on, if enabled.
        if ($this->session->userdata('basic_debug') && !$this->input->is_ajax_request())
        {
                $sections = array(
                        'benchmarks' => TRUE, 'memory_usage' => TRUE,
                        'config' => FALSE, 'controller_info' => FALSE, 'get' => FALSE, 'post' => TRUE, 'queries' => TRUE,
                        'uri_string' => FALSE, 'http_headers' => FALSE, 'session_data' => TRUE
                );
                $this->output->set_profiler_sections($sections);
                $this->output->enable_profiler(TRUE);
        }
    }

    // Basic view not requiring a user to be logged in.  Includes CSS and JS files, but little else.
    function basic_view($viewname, $d=null)
    {
        $d['v'] = $viewname;
        $this->load->view('template/simple', $d);
    }

}
