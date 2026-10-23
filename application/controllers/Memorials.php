<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Memorials extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'clan']);
        $this->load->model('Member_model');
    }

    public function index()
    {
        $members = $this->Member_model->get_all();
        $memorials = [];
        foreach ($members as $member)
        {
            if ((int) $member->is_deceased === 1)
            {
                $memorials[] = $member;
            }
        }
        usort($memorials, function ($a, $b) {
            if (empty($a->death_date) && empty($b->death_date))
            {
                return strcasecmp(member_full_name($a), member_full_name($b));
            }
            if (empty($a->death_date))
            {
                return 1;
            }
            if (empty($b->death_date))
            {
                return -1;
            }
            return strcmp($b->death_date, $a->death_date);
        });

        $this->load->view('memorials', [
            'title'      => 'In Memoriam',
            'memorials'  => $memorials,
        ]);
    }
}
