<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Event_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get($id)
    {
        return $this->db
            ->select('e.*, c.clan_name')
            ->from('clan_events e')
            ->join('clans c', 'c.id = e.clan_id', 'left')
            ->where('e.id', (int) $id)
            ->get()->row();
    }

    public function get_all_with_rsvps($upcoming_only = FALSE, $limit = NULL)
    {
        $query = $this->db
            ->select("e.*, c.clan_name AS scope_clan_name, creator.clan_name AS creator_clan_name", FALSE)
            ->from('clan_events e')
            ->join('clans c', 'c.id = e.clan_id', 'left')
            ->join('clans creator', 'creator.id = e.created_by_clan_id');

        if ($upcoming_only)
        {
            $query->where('e.starts_at >=', date('Y-m-d H:i:s'));
        }
        $query->order_by('e.starts_at', 'ASC');
        if ($limit !== NULL)
        {
            $query->limit((int) $limit);
        }
        $events = $query->get()->result();
        if ( ! $events)
        {
            return [];
        }

        $event_ids = [];
        foreach ($events as $event)
        {
            $event->rsvps = [];
            $event->total_headcount = 0;
            $event_ids[] = (int) $event->id;
        }

        $rsvps = $this->db
            ->select('r.event_id, r.clan_id, r.headcount, c.clan_name')
            ->from('clan_event_rsvps r')
            ->join('clans c', 'c.id = r.clan_id')
            ->where_in('r.event_id', $event_ids)
            ->order_by('c.id', 'ASC')
            ->get()->result();

        $by_id = [];
        foreach ($events as $event)
        {
            $by_id[(int) $event->id] = $event;
        }
        foreach ($rsvps as $rsvp)
        {
            $by_id[(int) $rsvp->event_id]->rsvps[] = $rsvp;
            $by_id[(int) $rsvp->event_id]->total_headcount += (int) $rsvp->headcount;
        }
        return $events;
    }

    public function insert(array $data)
    {
        return $this->db->insert('clan_events', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update($id, array $data)
    {
        return $this->db->where('id', (int) $id)->update('clan_events', $data);
    }

    public function delete($id)
    {
        $this->db->trans_start();
        $this->db->where('event_id', (int) $id)->delete('clan_event_rsvps');
        $this->db->where('id', (int) $id)->delete('clan_events');
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function save_rsvp($event_id, $clan_id, $headcount)
    {
        return $this->db->query(
            'INSERT INTO clan_event_rsvps (event_id, clan_id, headcount) VALUES (?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE headcount = VALUES(headcount)',
            [(int) $event_id, (int) $clan_id, (int) $headcount]
        );
    }
}
