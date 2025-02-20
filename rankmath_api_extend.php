<?php
/**
 * Plugin Name: Rank Math API Manager Extend
 * Description: Handles Rank Math metadata updates, including the canonical URL, via API
 * Version: 1.2
 * Author: Your Name
 */
 
class Rank_Math_API_Manager_Extended {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_meta_fields']);
        add_action('rest_api_init', [$this, 'register_api_routes']);
    }
 
    public function register_meta_fields() {
        $meta_fields = [
            'rank_math_title' => 'Rank Math SEO Title',
            'rank_math_description' => 'Rank Math SEO Description',
            'rank_math_canonical_url' => 'Rank Math Canonical URL',
            'rank_math_focus_keyword' => 'Focus Keyword'
        ];
 
        foreach ($meta_fields as $key => $description) {
            register_post_meta('post', $key, [
                'show_in_rest' => true,
                'single' => true,
                'type' => 'string',
                'auth_callback' => [$this, 'check_update_permission'],
                'description' => $description,
            ]);
        }
    }
 
    public function register_api_routes() {
        register_rest_route('rank-math-api/v1', '/update-meta', [
            'methods' => 'POST',
            'callback' => [$this, 'update_rank_math_meta'],
            'permission_callback' => [$this, 'check_update_permission'],
            'args' => [
                'post_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && get_post($param);
                    }
                ],
                'rank_math_title' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'rank_math_description' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'rank_math_canonical_url' => [
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw',
                ],
                'rank_math_focus_keyword' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }
 
    public function update_rank_math_meta(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        $fields = ['rank_math_title', 'rank_math_description', 'rank_math_canonical_url', 'rank_math_focus_keyword'];
        $result = [];
 
        foreach ($fields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $update_result = update_post_meta($post_id, $field, $value);
                $result[$field] = $update_result ? 'updated' : 'failed';
            }
        }
 
        if (empty($result)) {
            return new WP_Error('no_update', 'No metadata was updated', ['status' => 400]);
        }
 
        return new WP_REST_Response($result, 200);
    }
 
    public function check_update_permission() {
        return current_user_can('edit_posts');
    }
}
 
new Rank_Math_API_Manager_Extended();
