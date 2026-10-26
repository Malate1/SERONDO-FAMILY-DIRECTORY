<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public:   clan_members (dashboard), clan_members/clan/ID (branch grid + tree)
 * Rep only: clan_members/add, clan_members/edit/ID, clan_members/delete/ID (POST)
 *
 * Authorization rule: the clan is ALWAYS taken from the logged-in representative, never from
 * request data, and every edit/delete verifies the member belongs to that clan.
 */
class Clan_members extends CI_Controller
{
    private $upload_dir;

    /* State shared with the validation callbacks */
    private $rep;                    // authenticated representative (DB row)
    private $clan;                   // that representative's clan
    private $editing          = NULL; // member being edited, if any
    private $structure_locked = FALSE; // parent/generation can't change (root, or has children)
    private $clan_has_root    = FALSE;
    private $resolved_parent  = NULL;
    private $max_generation;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('clan');
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'form', 'clan']);
        $this->load->model(['Clan_model', 'Member_model']);
        $this->upload_dir     = FCPATH . 'uploads/members/';
        $this->max_generation = (int) $this->config->item('max_generation');
    }

    /* ================================================================== *
     *  PUBLIC PAGES
     * ================================================================== */

    /** Multi-clan dashboard: 4 summary cards, recent additions, filterable/searchable grid. */
    public function index()
    {
        $this->load->model('Event_model');
        $this->load->view('dashboard', [
            'title'   => 'Reunion Directory',
            'clans'   => $this->Clan_model->get_all_with_stats(),
            'recent'  => $this->Member_model->get_recent_per_clan(3),
            'members' => $this->Member_model->get_all(),
            'spouses' => $this->Member_model->get_spouses_by_clan(NULL),
            'generation_labels' => (array) $this->config->item('generation_labels'),
            'upcoming_events' => $this->Event_model->get_all_with_rsvps(TRUE, 4),
        ]);
    }

    /** One clan branch: grid view + lineage tree. */
    public function clan($id = NULL)
    {
        $clan = $this->_find_clan($id);
        $rep  = current_rep();

        $members = $this->Member_model->get_by_clan($clan->id);

        $this->load->view('clan_view', [
            'title'      => $clan->clan_name,
            'clan'       => $clan,
            'stats'      => $this->Clan_model->get_stats($clan->id),
            'members'    => $members,
            'tree'       => $this->Member_model->build_tree($members),
            'spouses'    => $this->Member_model->get_spouses_by_clan($clan->id),
            'can_manage' => ($rep && (int) $rep->clan_id === (int) $clan->id),
            'max_generation' => $this->max_generation,
        ]);
    }

    /* ================================================================== *
     *  REPRESENTATIVE-ONLY ACTIONS
     * ================================================================== */

    public function edit_clan()
    {
        $rep = $this->_require_rep();
        $clan = $this->Clan_model->get($rep->clan_id);
        if ( ! $clan)
        {
            show_404();
        }

        if ($this->input->method() === 'post')
        {
            $this->form_validation->set_rules('clan_name', 'Clan name', 'trim|required|min_length[2]|max_length[100]');
            $this->form_validation->set_rules('description', 'Description', 'trim|max_length[2000]');

            if ($this->form_validation->run())
            {
                $saved = $this->Clan_model->update_details($rep->clan_id, [
                    'clan_name'   => $this->input->post('clan_name', TRUE),
                    'description' => $this->input->post('description', TRUE),
                ]);

                if ($saved)
                {
                    $this->session->set_flashdata('success', 'Clan details updated.');
                    redirect('clan_members/clan/' . (int) $rep->clan_id);
                }

                $this->session->set_flashdata('error', 'Could not save clan details. Please try again.');
            }
        }

        $this->load->view('edit_clan', [
            'title' => 'Edit clan details',
            'clan'  => $clan,
        ]);
    }

    public function add()
    {
        $this->_prepare_management(NULL);

        if ($this->input->method() === 'post')
        {
            return $this->_save();
        }
        $this->_render_form();
    }

    public function edit($id = NULL)
    {
        $this->_require_rep();
        $member = $this->_find_own_member($id);
        $this->_prepare_management($member);

        if ($this->input->method() === 'post')
        {
            return $this->_save();
        }
        $this->_render_form();
    }

    /** Manage spouse names for a member in the representative's clan. */
    public function spouses($id = NULL)
    {
        $this->_require_rep();
        $member = $this->_find_own_member($id);

        if ($this->input->method() === 'post')
        {
            $action = $this->input->post('spouse_action', TRUE);
            if ($action === 'add')
            {
                $this->form_validation->set_rules('first_name', 'First name', 'trim|required|min_length[2]|max_length[100]');
                $this->form_validation->set_rules('last_name', 'Last name', 'trim|required|min_length[2]|max_length[100]');
                if ($this->form_validation->run())
                {
                    $added = $this->Member_model->add_spouse($member->id, [
                        'first_name' => $this->input->post('first_name', TRUE),
                        'last_name'  => $this->input->post('last_name', TRUE),
                    ]);
                    if ($added)
                    {
                        $this->session->set_flashdata('success', 'Spouse added to ' . member_full_name($member) . '.');
                    }
                    else
                    {
                        $this->session->set_flashdata('error', 'Could not add the spouse. Please try again.');
                    }
                    redirect('clan_members/spouses/' . $member->id);
                }
            }
            elseif ($action === 'delete')
            {
                $spouse_id = $this->input->post('spouse_id', TRUE);
                if (ctype_digit((string) $spouse_id) && $this->Member_model->delete_spouse($member->id, $spouse_id))
                {
                    $this->session->set_flashdata('success', 'Spouse removed from ' . member_full_name($member) . '.');
                }
                else
                {
                    $this->session->set_flashdata('error', 'Could not remove the spouse. Please try again.');
                }
                redirect('clan_members/spouses/' . $member->id);
            }
            else
            {
                show_error('Invalid spouse action.', 400);
            }
        }

        $this->load->view('manage_spouses', [
            'title'   => 'Spouses of ' . member_full_name($member),
            'clan'    => $this->Clan_model->get($member->clan_id),
            'member'  => $member,
            'spouses' => $this->Member_model->get_spouses($member->id),
        ]);
    }

    /** Add one or more children to a parent in the representative's clan. */
    public function children($id = NULL)
    {
        $rep = $this->_require_rep();
        $parent = $this->_find_own_member($id);
        $generation = (int) $parent->generation + 1;

        if ($generation > $this->max_generation)
        {
            $this->session->set_flashdata('error', 'This member is already in the deepest allowed generation.');
            redirect('clan_members/clan/' . $parent->clan_id);
        }

        $clan = $this->Clan_model->get($rep->clan_id);
        $rows = [[
            'first_name' => '',
            'last_name'  => '',
            'birth_date' => '',
        ]];
        $errors = [];
        $form_error = NULL;

        if ($this->input->method() === 'post')
        {
            $posted_first_names = $this->input->post('first_name', TRUE);
            $posted_last_names = $this->input->post('last_name', TRUE);
            $posted_birth_dates = $this->input->post('birth_date', TRUE);
            $posted_first_names = is_array($posted_first_names) ? $posted_first_names : [];
            $posted_last_names = is_array($posted_last_names) ? $posted_last_names : [];
            $posted_birth_dates = is_array($posted_birth_dates) ? $posted_birth_dates : [];
            $indexes = array_unique(array_merge(
                array_keys($posted_first_names),
                array_keys($posted_last_names),
                array_keys($posted_birth_dates)
            ));
            sort($indexes);
            $rows = [];
            $children = [];

            foreach ($indexes as $index)
            {
                $first_name = isset($posted_first_names[$index]) && is_string($posted_first_names[$index])
                    ? trim($posted_first_names[$index]) : '';
                $last_name = isset($posted_last_names[$index]) && is_string($posted_last_names[$index])
                    ? trim($posted_last_names[$index]) : '';
                $birth_date = isset($posted_birth_dates[$index]) && is_string($posted_birth_dates[$index])
                    ? trim($posted_birth_dates[$index]) : '';

                if ($first_name === '' && $last_name === '' && $birth_date === '')
                {
                    continue;
                }

                $row_index = count($rows);
                $rows[$row_index] = [
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'birth_date' => $birth_date,
                ];

                if (mb_strlen($first_name) < 2 || mb_strlen($first_name) > 100)
                {
                    $errors[$row_index]['first_name'] = 'Enter a first name between 2 and 100 characters.';
                }
                if (mb_strlen($last_name) < 2 || mb_strlen($last_name) > 100)
                {
                    $errors[$row_index]['last_name'] = 'Enter a last name between 2 and 100 characters.';
                }
                if ($birth_date !== '')
                {
                    $date = DateTime::createFromFormat('!Y-m-d', $birth_date);
                    $date_errors = DateTime::getLastErrors();
                    if ( ! $date || ($date_errors && ($date_errors['warning_count'] || $date_errors['error_count']))
                        || $date->format('Y-m-d') !== $birth_date || $date > new DateTime('today'))
                    {
                        $errors[$row_index]['birth_date'] = 'Enter a valid birth date that is not in the future.';
                    }
                }

                if ( ! isset($errors[$row_index]))
                {
                    $children[] = [
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                        'birth_date' => $birth_date !== '' ? $birth_date : NULL,
                    ];
                }
            }

            if ( ! $rows)
            {
                $rows = [[
                    'first_name' => '',
                    'last_name'  => '',
                    'birth_date' => '',
                ]];
                $form_error = 'Enter at least one child before saving.';
            }
            elseif ( ! $errors && $children && $this->Member_model->add_children(
                $parent->id,
                $parent->clan_id,
                $rep->id,
                $generation,
                $children
            ))
            {
                $count = count($children);
                $this->session->set_flashdata('success', $count . ' ' . ($count === 1 ? 'child' : 'children')
                    . ' added under ' . member_full_name($parent) . '.');
                redirect('clan_members/clan/' . $parent->clan_id);
            }
            elseif ( ! $errors && ! $form_error)
            {
                $form_error = 'Could not save the children. Please try again.';
            }
        }

        $this->load->view('manage_children', [
            'title'      => 'Children of ' . member_full_name($parent),
            'clan'       => $clan,
            'parent'     => $parent,
            'children'   => $this->Member_model->get_children($parent->id),
            'rows'       => $rows,
            'errors'     => $errors,
            'form_error' => $form_error,
            'generation' => $generation,
        ]);
    }

    /** POST only. Refuses to delete a member who still has children. */
    public function delete($id = NULL)
    {
        if ($this->input->method() !== 'post')
        {
            show_error('Method not allowed.', 405);
        }

        $this->_require_rep();
        $member = $this->_find_own_member($id);

        if ($this->Member_model->has_children($member->id))
        {
            $this->session->set_flashdata('error', member_full_name($member) . ' still has children in the tree. Remove or move them first.');
            redirect('clan_members/clan/' . $member->clan_id);
        }

        if ($this->Member_model->delete($member->id))
        {
            $this->_delete_photo($member->photo);
            $this->session->set_flashdata('success', member_full_name($member) . ' was removed from the tree.');
        }
        else
        {
            $this->session->set_flashdata('error', 'Could not delete the member. Please try again.');
        }
        redirect('clan_members/clan/' . $member->clan_id);
    }

    /* ================================================================== *
     *  Save (add + edit)
     * ================================================================== */

    private function _save()
    {
        $this->form_validation->set_rules('first_name', 'First name', 'trim|required|min_length[2]|max_length[100]');
        $this->form_validation->set_rules('last_name',  'Last name',  'trim|required|min_length[2]|max_length[100]');
        $this->form_validation->set_rules('birth_date', 'Birth date', 'trim|callback__valid_date');
        $this->form_validation->set_rules('is_deceased', 'Memorial status', 'callback__valid_deceased');
        $this->form_validation->set_rules('death_date', 'Passing date', 'trim|callback__valid_death_date');
        $this->form_validation->set_rules('tribute', 'Tribute', 'trim|max_length[3000]');
        $this->form_validation->set_rules('spouse_first_name', 'Spouse first name', 'trim|max_length[100]|callback__valid_spouse_first_name');
        $this->form_validation->set_rules('spouse_last_name', 'Spouse last name', 'trim|max_length[100]|callback__valid_spouse_last_name');
        $this->form_validation->set_rules('parent_id',  'Parent',     'trim|callback__valid_parent');

        $upload_error = NULL;
        $new_photo    = NULL;
        $valid        = $this->form_validation->run();

        if ($valid && ! empty($_FILES['photo']['name']))
        {
            $new_photo = $this->_upload_photo($upload_error);
            if ($new_photo === FALSE)
            {
                $valid = FALSE;
            }
        }

        if ( ! $valid)
        {
            return $this->_render_form($upload_error);
        }

        // Generation and parent are DERIVED server-side - the posted generation is only a UI hint.
        if ($this->editing && $this->structure_locked)
        {
            $parent_id  = $this->editing->parent_id;
            $generation = (int) $this->editing->generation;
        }
        elseif ($this->resolved_parent)
        {
            $parent_id  = (int) $this->resolved_parent->id;
            $generation = (int) $this->resolved_parent->generation + 1;
        }
        else
        {
            $parent_id  = NULL;   // first member of a brand-new clan = Gen 1 ancestor
            $generation = 1;
        }

        $birth_date = $this->input->post('birth_date', TRUE);
        $is_deceased = $this->input->post('is_deceased', TRUE) === '1';
        $death_date = $is_deceased ? $this->input->post('death_date', TRUE) : '';

        $data = [
            'first_name' => $this->input->post('first_name', TRUE),
            'last_name'  => $this->input->post('last_name', TRUE),
            'birth_date' => ($birth_date !== '' && $birth_date !== NULL) ? $birth_date : NULL,
            'is_deceased' => $is_deceased ? 1 : 0,
            'death_date'  => ($death_date !== '' && $death_date !== NULL) ? $death_date : NULL,
            'tribute'     => $is_deceased ? $this->input->post('tribute', TRUE) : NULL,
            'parent_id'  => $parent_id,
            'generation' => $generation,
        ];

        if ($new_photo)
        {
            $data['photo'] = $new_photo;
        }
        elseif ($this->editing && $this->input->post('remove_photo'))
        {
            $data['photo'] = NULL;
        }

        if ($this->editing)
        {
            $this->Member_model->update($this->editing->id, $data);
            $member_id = (int) $this->editing->id;

            if (array_key_exists('photo', $data) && $data['photo'] !== $this->editing->photo)
            {
                $this->_delete_photo($this->editing->photo);
            }
            $msg = 'Member updated.';
        }
        else
        {
            $data['clan_id']                    = (int) $this->clan->id;   // from the session, never from POST
            $data['added_by_representative_id'] = (int) $this->rep->id;
            $member_id = $this->Member_model->insert($data);
            if ( ! $member_id)
            {
                if ($new_photo)
                {
                    $this->_delete_photo($new_photo);
                }
                $this->session->set_flashdata('error', 'Could not add the family member. Please try again.');
                redirect('clan_members/add');
            }
            $msg = 'New member added to ' . $this->clan->clan_name . '.';
        }

        $spouse_first_name = trim((string) $this->input->post('spouse_first_name', TRUE));
        $spouse_last_name = trim((string) $this->input->post('spouse_last_name', TRUE));
        if ($spouse_first_name !== '' && $spouse_last_name !== '')
        {
            if ( ! $this->Member_model->add_spouse($member_id, [
                'first_name' => $spouse_first_name,
                'last_name'  => $spouse_last_name,
            ]))
            {
                $this->session->set_flashdata('error', $msg . ' The spouse could not be added; you can retry from the spouse manager.');
                redirect('clan_members/spouses/' . $member_id);
            }
            $msg .= ' Spouse added.';
        }

        $this->session->set_flashdata('success', $msg);
        redirect('clan_members/clan/' . $this->clan->id);
    }

    private function _render_form($upload_error = NULL)
    {
        $this->load->view('add_member', [
            'title'            => $this->editing ? 'Edit ' . member_full_name($this->editing) : 'Add Member',
            'clan'             => $this->clan,
            'member'           => $this->editing,
            'parents'          => $this->Member_model->get_parent_options($this->clan->id, $this->max_generation, $this->editing ? $this->editing->id : NULL),
            'structure_locked' => $this->structure_locked,
            'clan_has_root'    => $this->clan_has_root,
            'max_generation'   => $this->max_generation,
            'upload_error'     => $upload_error,
        ]);
    }

    /* ================================================================== *
     *  Authorization helpers
     * ================================================================== */

    /** Must be a signed-in, active representative. Re-checked against the DB on every action. */
    private function _require_rep()
    {
        if ($this->rep)
        {
            return $this->rep;
        }

        $sess = current_rep();
        if ( ! $sess)
        {
            $this->session->set_flashdata('error', 'Please sign in to manage your clan.');
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

    /** A member that exists AND belongs to the representative's clan, else 403/404. */
    private function _find_own_member($id)
    {
        $member = ctype_digit((string) $id) ? $this->Member_model->get($id) : NULL;
        if ( ! $member)
        {
            show_404();
        }
        if ((int) $member->clan_id !== (int) $this->rep->clan_id)
        {
            show_error('You can only manage members of your own clan.', 403, 'Access denied');
        }
        return $member;
    }

    private function _prepare_management($member)
    {
        $rep = $this->_require_rep();

        $this->clan          = $this->Clan_model->get($rep->clan_id);
        $this->editing       = $member;
        $this->clan_has_root = $this->Member_model->clan_has_root($this->clan->id, $member ? $member->id : NULL);

        // The clan ancestor (no parent) and anyone with children keep their place in the tree.
        $this->structure_locked = $member && ($member->parent_id === NULL || $this->Member_model->has_children($member->id));
    }

    private function _find_clan($id)
    {
        $clan = ctype_digit((string) $id) ? $this->Clan_model->get($id) : NULL;
        if ( ! $clan)
        {
            show_404();
        }
        return $clan;
    }

    /* ================================================================== *
     *  Upload helpers
     * ================================================================== */

    private function _upload_photo(&$error)
    {
        $tmp = isset($_FILES['photo']['tmp_name']) ? $_FILES['photo']['tmp_name'] : '';
        if ($tmp === '' || ! @getimagesize($tmp))
        {
            $error = 'The selected file is not a valid image.';
            return FALSE;
        }

        if ( ! is_dir($this->upload_dir) && ! @mkdir($this->upload_dir, 0755, TRUE))
        {
            $error = 'The upload folder does not exist and could not be created.';
            return FALSE;
        }
        if ( ! is_writable($this->upload_dir))
        {
            $error = 'The upload folder is not writable. Check folder permissions.';
            return FALSE;
        }

        $this->load->library('upload', [
            'upload_path'      => $this->upload_dir,
            'allowed_types'    => 'jpg|jpeg|png',
            'max_size'         => 2048,
            'max_width'        => 6000,
            'max_height'       => 6000,
            'encrypt_name'     => TRUE,      // random unique name, no overwrites
            'file_ext_tolower' => TRUE,
        ]);

        if ( ! $this->upload->do_upload('photo'))
        {
            $error = trim(strip_tags($this->upload->display_errors('', ' ')));
            return FALSE;
        }
        return $this->upload->data('file_name');
    }

    private function _delete_photo($filename)
    {
        if (empty($filename))
        {
            return;
        }
        $path = $this->upload_dir . basename($filename);   // basename() blocks path traversal
        if (is_file($path))
        {
            @unlink($path);
        }
    }

    /* ================================================================== *
     *  Validation callbacks (underscore prefix = not routable in CI3)
     * ================================================================== */

    public function _valid_date($value)
    {
        if ($value === '' || $value === NULL)
        {
            return TRUE;
        }
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if ( ! $d || $d->format('Y-m-d') !== $value)
        {
            $this->form_validation->set_message('_valid_date', 'The {field} must be a valid date.');
            return FALSE;
        }
        if ($d > new DateTime('today'))
        {
            $this->form_validation->set_message('_valid_date', 'The {field} cannot be in the future.');
            return FALSE;
        }
        return TRUE;
    }

    public function _valid_deceased($value)
    {
        if ($value === NULL || $value === '' || $value === '1')
        {
            return TRUE;
        }
        $this->form_validation->set_message('_valid_deceased', 'Invalid memorial status.');
        return FALSE;
    }

    public function _valid_death_date($value)
    {
        if ($value === '' || $value === NULL)
        {
            return TRUE;
        }
        $death = DateTime::createFromFormat('Y-m-d', $value);
        if ( ! $death || $death->format('Y-m-d') !== $value || $death > new DateTime('today'))
        {
            $this->form_validation->set_message('_valid_death_date', 'Enter a valid passing date that is not in the future.');
            return FALSE;
        }

        $birth_value = $this->input->post('birth_date', TRUE);
        if ($birth_value !== '' && $birth_value !== NULL)
        {
            $birth = DateTime::createFromFormat('Y-m-d', $birth_value);
            if ($birth && $death < $birth)
            {
                $this->form_validation->set_message('_valid_death_date', 'The passing date cannot be before the birth date.');
                return FALSE;
            }
        }
        return TRUE;
    }

    public function _valid_spouse_first_name($value)
    {
        $last_name = trim((string) $this->input->post('spouse_last_name', TRUE));
        if (trim((string) $value) === '' && $last_name === '')
        {
            return TRUE;
        }
        if (mb_strlen(trim((string) $value)) >= 2)
        {
            return TRUE;
        }
        $this->form_validation->set_message('_valid_spouse_first_name', 'Enter both spouse names, with a first name of at least 2 characters.');
        return FALSE;
    }

    public function _valid_spouse_last_name($value)
    {
        $first_name = trim((string) $this->input->post('spouse_first_name', TRUE));
        if (trim((string) $value) === '' && $first_name === '')
        {
            return TRUE;
        }
        if (mb_strlen(trim((string) $value)) >= 2)
        {
            return TRUE;
        }
        $this->form_validation->set_message('_valid_spouse_last_name', 'Enter both spouse names, with a last name of at least 2 characters.');
        return FALSE;
    }

    public function _valid_parent($value)
    {
        // Locked members (ancestor / has children) keep their parent; the field is ignored.
        if ($this->editing && $this->structure_locked)
        {
            return TRUE;
        }

        if ($value === '' || $value === NULL)
        {
            if ($this->clan_has_root)
            {
                $this->form_validation->set_message('_valid_parent', 'Please choose a parent - this clan already has its Gen 1 ancestor.');
                return FALSE;
            }
            $this->resolved_parent = NULL;   // becomes the clan's Gen 1 ancestor
            return TRUE;
        }

        $parent = ctype_digit((string) $value) ? $this->Member_model->get_parent_in_clan($value, $this->clan->id) : NULL;

        if ( ! $parent)
        {
            $this->form_validation->set_message('_valid_parent', 'The selected {field} is not a member of your clan.');
            return FALSE;
        }
        if ($this->editing && (int) $parent->id === (int) $this->editing->id)
        {
            $this->form_validation->set_message('_valid_parent', 'A member cannot be their own parent.');
            return FALSE;
        }
        if ((int) $parent->generation >= $this->max_generation)
        {
            $this->form_validation->set_message('_valid_parent', 'The tree stops at Generation ' . $this->max_generation . ', so this member cannot have children.');
            return FALSE;
        }

        $this->resolved_parent = $parent;
        return TRUE;
    }
}
