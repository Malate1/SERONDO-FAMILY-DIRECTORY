<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Clan_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /** All clans with member totals, deepest generation and newest-member timestamp. */
    public function get_all_with_stats()
    {
        return $this->db
            ->select('c.*, COUNT(m.id) AS member_count, COALESCE(MAX(m.generation), 0) AS max_generation, MAX(m.created_at) AS last_added_at', FALSE)
            ->from('clans c')
            ->join('family_members m', 'm.clan_id = c.id', 'left')
            ->group_by('c.id')
            ->order_by('c.id', 'ASC')
            ->get()->result();
    }

    public function get($id)
    {
        return $this->db->get_where('clans', ['id' => (int) $id])->row();
    }

    public function update_details($id, $data)
    {
        return $this->db->where('id', (int) $id)->update('clans', [
            'clan_name'   => $data['clan_name'],
            'description' => $data['description'],
        ]);
    }

    /** Stats for a single clan (member count + deepest generation + per-generation counts). */
    public function get_stats($clan_id)
    {
        $row = $this->db
            ->select('COUNT(id) AS member_count, COALESCE(MAX(generation), 0) AS max_generation', FALSE)
            ->where('clan_id', (int) $clan_id)->get('family_members')->row();

        $per_gen = [];
        $rows = $this->db->select('generation, COUNT(id) AS total', FALSE)
            ->where('clan_id', (int) $clan_id)->group_by('generation')->order_by('generation')
            ->get('family_members')->result();
        foreach ($rows as $r)
        {
            $per_gen[(int) $r->generation] = (int) $r->total;
        }

        return (object) [
            'member_count'   => (int) $row->member_count,
            'max_generation' => (int) $row->max_generation,
            'per_generation' => $per_gen,
        ];
    }

    /* ---------------- Representatives ---------------- */

    public function get_representative_by_username($username)
    {
        return $this->db
            ->select('r.*, c.clan_name')
            ->from('representatives r')
            ->join('clans c', 'c.id = r.clan_id')
            ->where('r.username', $username)
            ->get()->row();
    }

    public function get_representative($id)
    {
        return $this->db
            ->select('r.*, c.clan_name')
            ->from('representatives r')
            ->join('clans c', 'c.id = r.clan_id')
            ->where('r.id', (int) $id)
            ->get()->row();
    }

    public function update_representative_password($id, $hash)
    {
        return $this->db->where('id', (int) $id)->update('representatives', ['password' => $hash]);
    }
}
