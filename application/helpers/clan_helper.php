<?php defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('member_full_name'))
{
    function member_full_name($m)
    {
        return trim($m->first_name . ' ' . $m->last_name);
    }
}

if ( ! function_exists('member_initials'))
{
    function member_initials($m)
    {
        return mb_strtoupper(mb_substr($m->first_name, 0, 1) . mb_substr($m->last_name, 0, 1));
    }
}

if ( ! function_exists('member_photo_url'))
{
    function member_photo_url($m)
    {
        return empty($m->photo) ? NULL : base_url('uploads/members/' . rawurlencode($m->photo));
    }
}

if ( ! function_exists('member_birth_label'))
{
    function member_birth_label($date)
    {
        return empty($date) ? 'Birth date not set' : date('M j, Y', strtotime($date));
    }
}

if ( ! function_exists('member_age'))
{
    function member_age($date)
    {
        if (empty($date))
        {
            return NULL;
        }
        try
        {
            return (new DateTime($date))->diff(new DateTime('today'))->y;
        }
        catch (Exception $e)
        {
            return NULL;
        }
    }
}

if ( ! function_exists('generation_label'))
{
    /** "Gen 3 · Grandfather generation" */
    function generation_label($gen)
    {
        $CI     =& get_instance();
        $labels = (array) $CI->config->item('generation_labels');
        $extra  = isset($labels[(int) $gen]) ? $labels[(int) $gen] : '';
        return 'Gen ' . (int) $gen . ($extra !== '' ? ' · ' . $extra : '');
    }
}

if ( ! function_exists('clan_theme'))
{
    /**
     * Fixed colour palette per clan (full class names so the Tailwind CDN can see them).
     * Clan 1..4 map to indigo, emerald, amber, rose; further clans cycle.
     */
    function clan_theme($clan_id)
    {
        $themes = [
            ['name' => 'indigo',  'gradient' => 'from-indigo-600 to-violet-700',  'soft' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
             'solid' => 'bg-indigo-600', 'text' => 'text-indigo-600', 'border' => 'border-indigo-200', 'hover' => 'hover:border-indigo-300 hover:shadow-indigo-500/10',
             'button' => 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-500/30', 'line' => 'border-indigo-200', 'avatar' => 'from-indigo-500 to-violet-600'],
            ['name' => 'emerald', 'gradient' => 'from-emerald-600 to-teal-700',   'soft' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
             'solid' => 'bg-emerald-600', 'text' => 'text-emerald-600', 'border' => 'border-emerald-200', 'hover' => 'hover:border-emerald-300 hover:shadow-emerald-500/10',
             'button' => 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/30', 'line' => 'border-emerald-200', 'avatar' => 'from-emerald-500 to-teal-600'],
            ['name' => 'amber',   'gradient' => 'from-amber-500 to-orange-600',   'soft' => 'bg-amber-50 text-amber-800 ring-amber-200',
             'solid' => 'bg-amber-500', 'text' => 'text-amber-600', 'border' => 'border-amber-200', 'hover' => 'hover:border-amber-300 hover:shadow-amber-500/10',
             'button' => 'bg-amber-600 hover:bg-amber-700 shadow-amber-500/30', 'line' => 'border-amber-200', 'avatar' => 'from-amber-500 to-orange-600'],
            ['name' => 'rose',    'gradient' => 'from-rose-500 to-pink-700',      'soft' => 'bg-rose-50 text-rose-700 ring-rose-200',
             'solid' => 'bg-rose-500', 'text' => 'text-rose-600', 'border' => 'border-rose-200', 'hover' => 'hover:border-rose-300 hover:shadow-rose-500/10',
             'button' => 'bg-rose-600 hover:bg-rose-700 shadow-rose-500/30', 'line' => 'border-rose-200', 'avatar' => 'from-rose-500 to-pink-600'],
        ];
        return $themes[(max(1, (int) $clan_id) - 1) % count($themes)];
    }
}

if ( ! function_exists('member_avatar'))
{
    /** Round photo, or a clan-coloured initials badge when there is no photo. */
    function member_avatar($m, $size = 'h-16 w-16', $text = 'text-xl')
    {
        $url = member_photo_url($m);
        if ($url)
        {
            return '<img src="' . html_escape($url) . '" alt="' . html_escape(member_full_name($m)) . '" loading="lazy" '
                 . 'class="' . $size . ' shrink-0 rounded-full object-cover ring-4 ring-white shadow-md">';
        }
        $t = clan_theme($m->clan_id);
        return '<div class="' . $size . ' ' . $text . ' flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br ' . $t['avatar']
             . ' font-semibold text-white ring-4 ring-white shadow-md" aria-hidden="true">' . html_escape(member_initials($m)) . '</div>';
    }
}

if ( ! function_exists('current_rep'))
{
    /** Lightweight session info about the logged-in representative, or NULL. */
    function current_rep()
    {
        $CI =& get_instance();
        $id = $CI->session->userdata('rep_id');
        if ( ! $id)
        {
            return NULL;
        }
        return (object) [
            'id'        => (int) $id,
            'clan_id'   => (int) $CI->session->userdata('rep_clan_id'),
            'full_name' => (string) $CI->session->userdata('rep_name'),
        ];
    }
}

if ( ! function_exists('csrf_field'))
{
    function csrf_field()
    {
        $CI =& get_instance();
        return '<input type="hidden" name="' . $CI->security->get_csrf_token_name() . '" value="' . $CI->security->get_csrf_hash() . '">';
    }
}
