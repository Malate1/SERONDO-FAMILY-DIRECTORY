<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Member_model extends CI_Model
{
    protected $table = 'family_members';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /** Base query: member + clan name + parent name. */
    private function base_query()
    {
        return $this->db
            ->select("m.*, c.clan_name, CONCAT(p.first_name, ' ', p.last_name) AS parent_name", FALSE)
            ->from($this->table . ' m')
            ->join('clans c', 'c.id = m.clan_id')
            ->join($this->table . ' p', 'p.id = m.parent_id', 'left');
    }

    /** Every member of every clan (public dashboard grid + instant search). */
    public function get_all()
    {
        return $this->base_query()
            ->order_by('m.clan_id', 'ASC')->order_by('m.generation', 'ASC')
            ->order_by('m.last_name', 'ASC')->order_by('m.first_name', 'ASC')
            ->get()->result();
    }

    public function get_by_clan($clan_id)
    {
        return $this->base_query()
            ->where('m.clan_id', (int) $clan_id)
            ->order_by('m.generation', 'ASC')->order_by('m.birth_date', 'ASC')->order_by('m.first_name', 'ASC')
            ->get()->result();
    }

    /** Spouse names for all members in a clan, keyed by family member ID. */
    public function get_spouses_by_clan($clan_id)
    {
        $spouses = [];
        $query = $this->db
            ->select('s.id, s.family_member_id, s.first_name, s.last_name')
            ->from('family_member_spouses s')
            ->join($this->table . ' m', 'm.id = s.family_member_id');
        if ($clan_id !== NULL)
        {
            $query->where('m.clan_id', (int) $clan_id);
        }
        $rows = $query->order_by('s.first_name', 'ASC')->order_by('s.last_name', 'ASC')->get()->result();

        foreach ($rows as $spouse)
        {
            $spouses[(int) $spouse->family_member_id][] = $spouse;
        }
        return $spouses;
    }

    public function get_spouses($member_id)
    {
        return $this->db->where('family_member_id', (int) $member_id)
            ->order_by('first_name', 'ASC')->order_by('last_name', 'ASC')
            ->get('family_member_spouses')->result();
    }

    public function add_spouse($member_id, array $data)
    {
        $data['family_member_id'] = (int) $member_id;
        return $this->db->insert('family_member_spouses', $data);
    }

    public function delete_spouse($member_id, $spouse_id)
    {
        return $this->db->where('family_member_id', (int) $member_id)
            ->where('id', (int) $spouse_id)
            ->delete('family_member_spouses');
    }

    /** Latest additions, optionally for one clan. */
    public function get_recent($limit = 3, $clan_id = NULL)
    {
        $q = $this->base_query();
        if ($clan_id !== NULL)
        {
            $q->where('m.clan_id', (int) $clan_id);
        }
        return $q->order_by('m.created_at', 'DESC')->order_by('m.id', 'DESC')->limit((int) $limit)->get()->result();
    }

    /** Recent additions for every clan in one pass, keyed by clan_id. */
    public function get_recent_per_clan($limit = 3)
    {
        $out = [];
        foreach ($this->db->select('id')->get('clans')->result() as $c)
        {
            $out[(int) $c->id] = $this->get_recent($limit, $c->id);
        }
        return $out;
    }

    public function get($id)
    {
        return $this->base_query()->where('m.id', (int) $id)->get()->row();
    }

    public function has_children($id)
    {
        return $this->db->where('parent_id', (int) $id)->count_all_results($this->table) > 0;
    }

    public function get_children($id)
    {
        return $this->db->where('parent_id', (int) $id)->order_by('birth_date')->get($this->table)->result();
    }

    /** True when the clan already has a Gen-1 ancestor (a member without a parent). */
    public function clan_has_root($clan_id, $exclude_id = NULL)
    {
        $this->db->where('clan_id', (int) $clan_id)->where('parent_id IS NULL', NULL, FALSE);
        if ($exclude_id !== NULL)
        {
            $this->db->where('id !=', (int) $exclude_id);
        }
        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Possible parents for a clan: any member whose generation is below the maximum.
     * Never crosses clan boundaries.
     */
    public function get_parent_options($clan_id, $max_generation, $exclude_id = NULL)
    {
        $this->db->select('id, first_name, last_name, generation, clan_id')
            ->where('clan_id', (int) $clan_id)
            ->where('generation <', (int) $max_generation);
        if ($exclude_id !== NULL)
        {
            $this->db->where('id !=', (int) $exclude_id);
        }
        return $this->db->order_by('generation')->order_by('last_name')->order_by('first_name')->get($this->table)->result();
    }

    /** A parent, only if it belongs to the given clan. */
    public function get_parent_in_clan($parent_id, $clan_id)
    {
        return $this->db->get_where($this->table, ['id' => (int) $parent_id, 'clan_id' => (int) $clan_id])->row();
    }

    public function insert(array $data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function add_children($parent_id, $clan_id, $representative_id, $generation, array $children)
    {
        $this->db->trans_begin();
        foreach ($children as $child)
        {
            $saved = $this->db->insert($this->table, [
                'first_name'                   => $child['first_name'],
                'last_name'                    => $child['last_name'],
                'birth_date'                   => $child['birth_date'],
                'parent_id'                    => (int) $parent_id,
                'clan_id'                      => (int) $clan_id,
                'generation'                   => (int) $generation,
                'is_deceased'                  => 0,
                'death_date'                   => NULL,
                'tribute'                      => NULL,
                'added_by_representative_id'   => (int) $representative_id,
            ]);
            if ( ! $saved)
            {
                $this->db->trans_rollback();
                return FALSE;
            }
        }

        if ( ! $this->db->trans_status())
        {
            $this->db->trans_rollback();
            return FALSE;
        }
        return $this->db->trans_commit();
    }

    public function update($id, array $data)
    {
        return $this->db->where('id', (int) $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        $this->db->trans_start();
        $this->db->where('family_member_id', (int) $id)->delete('family_member_spouses');
        $this->db->where('id', (int) $id)->delete($this->table);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Turn a flat clan member list into nested roots: each node gets ->children (array).
     * Orphans (should not exist) are returned as extra roots so nobody disappears.
     */
    public function build_tree(array $members)
    {
        $by_id = [];
        foreach ($members as $m)
        {
            $node = clone $m;
            $node->children = [];
            $by_id[(int) $m->id] = $node;
        }

        $roots = [];
        foreach ($by_id as $id => $node)
        {
            $pid = $node->parent_id === NULL ? NULL : (int) $node->parent_id;
            if ($pid !== NULL && isset($by_id[$pid]))
            {
                $by_id[$pid]->children[] = $node;
            }
            else
            {
                $roots[] = $node;
            }
        }
        return $roots;
    }
}
