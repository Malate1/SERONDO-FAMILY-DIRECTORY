<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Events extends CI_Controller
{
    private $rep;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'form', 'clan']);
        $this->load->model(['Clan_model', 'Event_model']);
    }

    public function index()
    {
        $session_rep = current_rep();
        $rep = $session_rep ? $this->Clan_model->get_representative($session_rep->id) : NULL;
        if ($rep && ! (int) $rep->is_active)
        {
            $rep = NULL;
        }
        $this->load->view('events/index', [
            'title'  => 'Clan Calendar',
            'events' => $this->Event_model->get_all_with_rsvps(),
            'rep'    => $rep,
        ]);
    }

    public function create()
    {
        $this->_require_rep();
        $this->_save();
    }

    public function edit($id = NULL)
    {
        $this->_require_rep();
        $event = $this->_find_manageable_event($id);
        $this->_save($event);
    }

    public function delete($id = NULL)
    {
        if ($this->input->method() !== 'post')
        {
            show_error('Method not allowed.', 405);
        }
        $this->_require_rep();
        $event = $this->_find_manageable_event($id);

        if ($this->Event_model->delete($event->id))
        {
            $this->session->set_flashdata('success', 'Event removed from the calendar.');
        }
        else
        {
            $this->session->set_flashdata('error', 'Could not remove the event. Please try again.');
        }
        redirect('events');
    }

    public function rsvp($id = NULL)
    {
        if ($this->input->method() !== 'post')
        {
            show_error('Method not allowed.', 405);
        }
        $rep = $this->_require_rep();
        $event = $this->_find_event($id);
        if ($event->clan_id !== NULL && (int) $event->clan_id !== (int) $rep->clan_id)
        {
            show_error('You can only submit a headcount for your own clan or an all-clan event.', 403, 'Access denied');
        }

        $this->form_validation->set_rules('headcount', 'Headcount', 'trim|required|integer|greater_than_equal_to[0]|less_than_equal_to[10000]');
        if ( ! $this->form_validation->run())
        {
            $this->session->set_flashdata('error', 'Enter a whole-number headcount from 0 to 10,000.');
            redirect('events#event-' . (int) $event->id);
        }

        if ($this->Event_model->save_rsvp($event->id, $rep->clan_id, $this->input->post('headcount', TRUE)))
        {
            $this->session->set_flashdata('success', 'Your clan headcount was saved.');
        }
        else
        {
            $this->session->set_flashdata('error', 'Could not save your clan headcount. Please try again.');
        }
        redirect('events#event-' . (int) $event->id);
    }

    private function _save($event = NULL)
    {
        if ($this->input->method() === 'post')
        {
            $this->form_validation->set_rules('title', 'Event name', 'trim|required|min_length[2]|max_length[160]');
            $this->form_validation->set_rules('category', 'Category', 'required|callback__valid_category');
            $this->form_validation->set_rules('starts_at', 'Start date and time', 'trim|required|callback__valid_datetime');
            $this->form_validation->set_rules('ends_at', 'End date and time', 'trim|callback__valid_optional_datetime|callback__end_after_start');
            $this->form_validation->set_rules('scope', 'Event scope', 'required|callback__valid_scope');
            $this->form_validation->set_rules('location', 'Location', 'trim|max_length[255]');
            $this->form_validation->set_rules('description', 'Description', 'trim|max_length[3000]');

            if ($this->form_validation->run())
            {
                $start = DateTime::createFromFormat('Y-m-d\TH:i', $this->input->post('starts_at', TRUE));
                $end_value = $this->input->post('ends_at', TRUE);
                $end = $end_value ? DateTime::createFromFormat('Y-m-d\TH:i', $end_value) : NULL;
                $scope = $this->input->post('scope', TRUE);
                $data = [
                    'title'       => $this->input->post('title', TRUE),
                    'category'    => $this->input->post('category', TRUE),
                    'description' => $this->input->post('description', TRUE),
                    'location'    => $this->input->post('location', TRUE),
                    'starts_at'   => $start->format('Y-m-d H:i:s'),
                    'ends_at'     => $end ? $end->format('Y-m-d H:i:s') : NULL,
                    'clan_id'     => $scope === 'all' ? NULL : (int) $this->rep->clan_id,
                ];

                if ($event)
                {
                    $saved = $this->Event_model->update($event->id, $data);
                    $message = 'Event updated.';
                }
                else
                {
                    $data['created_by_rep_id'] = (int) $this->rep->id;
                    $data['created_by_clan_id'] = (int) $this->rep->clan_id;
                    $saved = $this->Event_model->insert($data);
                    $event = $saved ? (object) ['id' => $saved] : NULL;
                    $message = 'Event added to the calendar.';
                }

                if ($saved)
                {
                    $this->session->set_flashdata('success', $message);
                    redirect('events#event-' . (int) $event->id);
                }
                $this->session->set_flashdata('error', 'Could not save the event. Please try again.');
            }
        }

        $clan = $this->Clan_model->get($this->rep->clan_id);
        if ($event && $event->clan_id !== NULL)
        {
            $scope = (int) $event->clan_id === (int) $this->rep->clan_id ? 'own' : 'all';
        }
        else
        {
            $scope = 'all';
        }

        $this->load->view('events/form', [
            'title' => $event ? 'Edit event' : 'Create event',
            'event' => $event,
            'clan'  => $clan,
            'scope' => set_value('scope', $scope),
        ]);
    }

    private function _require_rep()
    {
        if ($this->rep)
        {
            return $this->rep;
        }
        $sess = current_rep();
        if ( ! $sess)
        {
            $this->session->set_flashdata('error', 'Please sign in as a clan representative to manage events.');
            redirect('login');
        }
        $rep = $this->Clan_model->get_representative($sess->id);
        if ( ! $rep || ! (int) $rep->is_active)
        {
            $this->session->sess_destroy();
            redirect('login');
        }
        $this->rep = $rep;
        return $rep;
    }

    private function _find_event($id)
    {
        $event = ctype_digit((string) $id) ? $this->Event_model->get($id) : NULL;
        if ( ! $event)
        {
            show_404();
        }
        return $event;
    }

    private function _find_manageable_event($id)
    {
        $event = $this->_find_event($id);
        if ((int) $event->created_by_clan_id !== (int) $this->rep->clan_id)
        {
            show_error('Only the representative who created this event can manage it.', 403, 'Access denied');
        }
        return $event;
    }

    public function _valid_category($value)
    {
        if (in_array($value, ['reunion', 'celebration', 'memorial'], TRUE))
        {
            return TRUE;
        }
        $this->form_validation->set_message('_valid_category', 'Choose a valid event category.');
        return FALSE;
    }

    public function _valid_scope($value)
    {
        if (in_array($value, ['all', 'own'], TRUE))
        {
            return TRUE;
        }
        $this->form_validation->set_message('_valid_scope', 'Choose a valid event scope.');
        return FALSE;
    }

    public function _valid_datetime($value)
    {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if ($date && $date->format('Y-m-d\TH:i') === $value)
        {
            return TRUE;
        }
        $this->form_validation->set_message('_valid_datetime', 'Enter a valid date and time.');
        return FALSE;
    }

    public function _valid_optional_datetime($value)
    {
        if ($value === '')
        {
            return TRUE;
        }
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if ($date && $date->format('Y-m-d\TH:i') === $value)
        {
            return TRUE;
        }
        $this->form_validation->set_message('_valid_optional_datetime', 'Enter a valid end date and time.');
        return FALSE;
    }

    public function _end_after_start($value)
    {
        if ($value === '')
        {
            return TRUE;
        }
        $start_value = $this->input->post('starts_at', TRUE);
        $start = DateTime::createFromFormat('Y-m-d\TH:i', $start_value);
        $end = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if ( ! $start || ! $end || $end >= $start)
        {
            return TRUE;
        }
        $this->form_validation->set_message('_end_after_start', 'The end date and time must be after the start.');
        return FALSE;
    }
}
