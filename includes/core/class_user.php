<?php

class User
{

    // GENERAL

    public static function user_info($d)
    {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='" . $user_id . "'";
        else if ($phone) $where = "phone='" . $phone . "'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, phone, access FROM users WHERE " . $where . " LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int)$row['user_id'],
                'access' => (int)$row['access']
            ];
        } else {
            return [
                'id' => 0,
                'access' => 0
            ];
        }
    }

    public static function users_list_plots($number)
    {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%" . $number . "%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach ($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int)$row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

    public static function users_fetch($data = [])
    {
        $info = User::users_list($data);
        HTML::assign('users', $info['items']);
        HTML::assign('search', $info['search']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    public static function users_list($d = [])
    {
        // vars
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $searchData = isset($d['search']) ? json_decode(urldecode($d['search']), true) : null;
        $limit = 20;
        $items = [];
        // where
        $where = [];
        $search = ['email' => '', 'phone' => '', 'first_name' => ''];
        if ($searchData and 'array' == gettype($searchData)) {
            foreach ($searchData as $item) {
                $searchDataVal = isset($item['search']) && trim($item['search']) ? $item['search'] : '';
                if (isset($search[$item['column']])) $search[$item['column']] = $searchDataVal;
                if ($searchDataVal) $where[] = $item['column'] . " LIKE '%" . $searchDataVal . "%'";
            }
        }
        // Так как в шаблонизаторе нельзя использовать ключи типа string
        $search = array_values($search);
        $where = $where ? "WHERE " . implode(" AND ", $where) : "";
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, email, phone, last_login
            FROM users " . $where . " ORDER BY user_id LIMIT " . $offset . ", " . $limit . ";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'user_id' => (int)$row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'plots' => $row['plot_id'] ? Plot::plots_list_users($row['plot_id']) : [],
                'last_login' => $row['last_login'],
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users " . $where . ";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?';
        if (isset($d['search'])) $url .= '&search=' . urlencode($d['search']) . "&";
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator, 'search' => $search];
    }

    public static function user_delete($d)
    {
        DB::query("DELETE FROM users WHERE user_id = " . $d['user_id']) or die (DB::error());
        return User::users_fetch($d);
    }

}
