<?php

function controller_user($act, $d) {
    if ($act == 'delete') return User::user_delete($d);
    return '';
}
