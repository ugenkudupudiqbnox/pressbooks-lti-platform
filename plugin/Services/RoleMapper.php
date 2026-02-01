<?php
namespace PB_LTI\Services;

class RoleMapper {
    public static function login_user($claims) {
        $roles = $claims->{'https://purl.imsglobal.org/spec/lti/claim/roles'} ?? [];
        $wp_role = 'subscriber';

        foreach ($roles as $role) {
            if (str_contains($role, 'Instructor')) {
                $wp_role = 'editor';
                break;
            }
        }

        $email = $claims->email ?? uniqid().'@lti.local';
        $user = get_user_by('email', $email);

        if (!$user) {
            $user_id = wp_create_user($email, wp_generate_password(), $email);
            $user = get_user_by('id', $user_id);
        }

        $user->set_role($wp_role);
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        return $user->ID;
    }
}
