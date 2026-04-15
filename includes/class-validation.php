<?php

if (!defined('ABSPATH')) {
    exit;
}

class SimpleCF7Conditional_Validation
{
    private $field_to_groups_cache = [];

    public function __construct()
    {
        add_filter('wpcf7_validate', [$this, 'filter_validation'], 20, 2);
        add_filter('wpcf7_posted_data', [$this, 'filter_posted_data'], 20);
    }

    public function filter_validation($result, $tags)
    {
        $hidden_groups = $this->get_hidden_groups_from_post();
        if (empty($hidden_groups)) {
            return $result;
        }

        $contact_form = $this->get_current_contact_form();
        if (!$contact_form) {
            return $result;
        }

        $field_to_groups = $this->get_field_to_group_map($contact_form);

        $fields_to_clear = [];
        foreach ($tags as $tag) {
            if (empty($tag->name) || !isset($field_to_groups[$tag->name])) {
                continue;
            }

            $visible_parents = array_diff($field_to_groups[$tag->name], $hidden_groups);
            if (empty($visible_parents)) {
                $fields_to_clear[] = $tag->name;
            }
        }

        if (empty($fields_to_clear)) {
            return $result;
        }

        return $this->remove_invalid_fields($result, $fields_to_clear);
    }

    public function filter_posted_data($posted_data)
    {
        $hidden_groups = $this->get_hidden_groups_from_post();
        if (empty($hidden_groups)) {
            unset($posted_data['_scf7c_hidden_groups']);
            return $posted_data;
        }

        $contact_form = $this->get_current_contact_form();
        if (!$contact_form) {
            unset($posted_data['_scf7c_hidden_groups']);
            return $posted_data;
        }

        $field_to_groups = $this->get_field_to_group_map($contact_form);

        foreach ($field_to_groups as $field_name => $groups) {
            $visible_parents = array_diff($groups, $hidden_groups);
            if (empty($visible_parents)) {
                unset($posted_data[$field_name]);
            }
        }

        unset($posted_data['_scf7c_hidden_groups']);

        return $posted_data;
    }

    private function get_hidden_groups_from_post()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- CF7 handles nonce upstream
        if (empty($_POST['_scf7c_hidden_groups'])) {
            return [];
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below
        $raw = wp_unslash($_POST['_scf7c_hidden_groups']);
        if (!is_string($raw)) {
            return [];
        }

        $groups = array_map('sanitize_text_field', explode(',', $raw));
        $groups = array_filter(array_map('trim', $groups));

        return array_values(array_unique($groups));
    }

    private function get_current_contact_form()
    {
        if (!class_exists('WPCF7_Submission')) {
            return null;
        }

        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return null;
        }

        return $submission->get_contact_form();
    }

    private function get_field_to_group_map($contact_form)
    {
        $form_id = $contact_form->id();

        if (isset($this->field_to_groups_cache[$form_id])) {
            return $this->field_to_groups_cache[$form_id];
        }

        $template = $contact_form->prop('form');
        $map = [];

        if (empty($template) || !is_string($template)) {
            $this->field_to_groups_cache[$form_id] = $map;
            return $map;
        }

        if (preg_match_all('/\[group\s+([^\s\]]+)[^\]]*\](.*?)\[\/group\]/s', $template, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $group_name = $match[1];
                $group_content = $match[2];

                if (preg_match_all('/\[[a-zA-Z0-9_-]+\*?\s+([a-zA-Z0-9_-]+)/', $group_content, $field_matches)) {
                    foreach ($field_matches[1] as $field_name) {
                        if ($field_name === 'group') {
                            continue;
                        }

                        if (!isset($map[$field_name])) {
                            $map[$field_name] = [];
                        }

                        if (!in_array($group_name, $map[$field_name], true)) {
                            $map[$field_name][] = $group_name;
                        }
                    }
                }
            }
        }

        $this->field_to_groups_cache[$form_id] = $map;

        return $map;
    }

    private function remove_invalid_fields($result, $field_names)
    {
        try {
            $ref = new ReflectionClass($result);
            if (!$ref->hasProperty('invalid_fields')) {
                return $result;
            }

            $prop = $ref->getProperty('invalid_fields');
            $prop->setAccessible(true);

            $invalid = $prop->getValue($result);
            if (!is_array($invalid)) {
                return $result;
            }

            foreach ($field_names as $name) {
                unset($invalid[$name]);
            }

            $prop->setValue($result, $invalid);
        } catch (ReflectionException $e) {
            return $result;
        }

        return $result;
    }
}
