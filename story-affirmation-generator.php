<?php
/**
 * Plugin Name: Story & Affirmation Generator Suite
 * Plugin URI: https://yoursite.com
 * Description: Unified multisite story and affirmation generator with centralized content management
 * Version: 2.0.0
 * Author: Your Name
 * Network: true
 * Text Domain: story-affirmation-suite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SAG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SAG_VERSION', '2.0.0');

// === MAIN SITE ADMIN FUNCTIONALITY ===
if (is_main_site() && is_admin()) {

    // Register unified admin menu
    add_action('admin_menu', 'sag_register_admin_menu');
    function sag_register_admin_menu() {
        add_menu_page(
            'Content Generators',
            'Content Generators',
            'manage_options',
            'content-generators',
            'sag_render_main_dashboard',
            'dashicons-edit-page',
            30
        );
        
        add_submenu_page(
            'content-generators',
            'Story Generator',
            'Story Generator',
            'manage_options',
            'story-generator-admin',
            'sag_render_story_admin'
        );
        
        add_submenu_page(
            'content-generators',
            'Affirmation Generator',
            'Affirmation Generator',
            'manage_options',
            'affirmation-generator-admin',
            'sag_render_affirmation_admin'
        );
    }

    // Main dashboard page
    function sag_render_main_dashboard() {
        if (isset($_POST['push_all_content'])) {
            $story_message = sag_push_story_content_to_subsites();
            $affirmation_message = sag_push_affirmation_content_to_subsites();
            echo '<div class="notice notice-success"><p>Story Generator: ' . esc_html($story_message) . '</p></div>';
            echo '<div class="notice notice-success"><p>Affirmation Generator: ' . esc_html($affirmation_message) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Content Generators Dashboard</h1>
            
            <div class="dashboard-widgets-wrap">
                <div class="postbox-container" style="width: 49%; float: left; margin-right: 2%;">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle">Story Generator</h2>
                            </div>
                            <div class="inside">
                                <p>Manage story templates, purposes, obstacles, and more.</p>
                                <p><a href=" echo admin_url('admin.php?page=story-generator-admin'); ?>" class="button button-primary">Manage Story Content</a></p>
                                <p><strong>Active Subsites:</strong>  echo count(get_sites()) - 1; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="postbox-container" style="width: 49%; float: left;">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle">Affirmation Generator</h2>
                            </div>
                            <div class="inside">
                                <p>Manage affirmation categories and positive statements.</p>
                                <p><a href=" echo admin_url('admin.php?page=affirmation-generator-admin'); ?>" class="button button-primary">Manage Affirmation Content</a></p>
                                <p><strong>Latest Summaries:</strong> <a href=" echo admin_url('admin.php?page=affirmation-generator-admin#summaries'); ?>">View All</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="clear: both;"></div>
            
            <hr>
            
            <h2>Bulk Operations</h2>
            <form method="post" action="">
                 wp_nonce_field('sag_push_all', 'sag_push_all_nonce'); ?>
                <p>Push all content (Story + Affirmation) to all subsites:</p>
                 submit_button('Push All Content to Subsites', 'secondary', 'push_all_content'); ?>
            </form>
            
            <hr>
            
            <h2>Recent Activity</h2>
             sag_display_recent_activity(); ?>
        </div>
        
    }

    // Display recent activity summary
    function sag_display_recent_activity() {
        $sites = get_sites();
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Site</th><th>Story Status</th><th>Latest Affirmation</th><th>Last Updated</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($sites as $site) {
            if ((int)$site->blog_id === 1) continue;
            
            switch_to_blog($site->blog_id);
            $story_data = get_option('site_story', []);
            $affirmation_summary = get_option('affirmation_summary', '');
            $affirmation_date = get_option('affirmation_summary_date', '');
            $site_details = get_blog_details($site->blog_id);
            restore_current_blog();
            
            echo '<tr>';
            echo '<td>' . esc_html($site_details->blogname) . '</td>';
            echo '<td>' . (isset($story_data['story_text']) && $story_data['story_text'] ? 'Generated' : 'Not Generated') . '</td>';
            echo '<td>' . esc_html($affirmation_summary ?: 'No summary yet') . '</td>';
            echo '<td>' . esc_html($affirmation_date ? date('Y-m-d H:i', strtotime($affirmation_date)) : 'N/A') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }

    // === STORY GENERATOR ADMIN ===
    function sag_render_story_admin() {
        if (isset($_POST['update_story_content'])) {
            sag_update_story_content();
        }
        if (isset($_POST['push_story_to_subsites'])) {
            $message = sag_push_story_content_to_subsites();
            echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
        }
        
        $content = sag_get_story_content();
        ?>
        <div class="wrap">
            <h1>Story Generator Content Management</h1>
            <p><a href=" echo admin_url('admin.php?page=content-generators'); ?>">&larr; Back to Dashboard</a></p>
            
            <form method="post" action="">
                 wp_nonce_field('sag_story_update', 'sag_story_nonce'); ?>
                
                <h2>Purposes</h2>
                <textarea name="purposes" rows="10" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content['purposes'])); ?></textarea>
                <p class="description">One purpose per line</p>
                
                <h2>Life Goals</h2>
                <textarea name="life_goals" rows="10" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content['life_goals'])); ?></textarea>
                <p class="description">One life goal per line</p>
                
                <h2>Obstacles</h2>
                <textarea name="obstacles" rows="10" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content['obstacles'])); ?></textarea>
                <p class="description">One obstacle per line</p>
                
                <h2>Positives (must match obstacles order)</h2>
                <textarea name="positives" rows="10" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content['positives'])); ?></textarea>
                <p class="description">One positive per line, corresponding to obstacles above</p>
                
                <h2>Adjectives</h2>
                <textarea name="adjectives" rows="5" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode(", ", $content['adjectives'])); ?></textarea>
                <p class="description">Comma-separated list</p>
                
                <h2>Archetypes</h2>
                <textarea name="archetypes" rows="5" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode(", ", $content['archetypes'])); ?></textarea>
                <p class="description">Comma-separated list</p>
                
                <h2>Genre Templates</h2>
                 foreach ($content['templates'] as $genre => $templates): ?>
                    <h3> echo esc_html(ucwords(str_replace('_', '/', $genre))); ?></h3>
                    <textarea name="templates[ echo esc_attr($genre); ?>]" rows="15" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n---TEMPLATE---\n", $templates)); ?></textarea>
                    <p class="description">Separate templates with ---TEMPLATE---</p>
                 endforeach; ?>
                
                 submit_button('Update Story Content', 'primary', 'update_story_content'); ?>
            </form>
            
            <hr>
            <form method="post" action="">
                 wp_nonce_field('sag_story_push', 'sag_story_push_nonce'); ?>
                 submit_button('Push Story Content to All Subsites', 'secondary', 'push_story_to_subsites'); ?>
            </form>
        </div>
        
    }

    // === AFFIRMATION GENERATOR ADMIN ===
    function sag_render_affirmation_admin() {
        if (isset($_POST['update_affirmation_content'])) {
            sag_update_affirmation_content();
        }
        if (isset($_POST['push_affirmation_to_subsites'])) {
            $message = sag_push_affirmation_content_to_subsites();
            echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
        }

        $content = sag_get_affirmation_content();
        ?>
        <div class="wrap">
            <h1>Affirmation Generator Content Management</h1>
            <p><a href=" echo admin_url('admin.php?page=content-generators'); ?>">&larr; Back to Dashboard</a></p>

            <form method="post" action="">
                 wp_nonce_field('sag_affirmation_update', 'sag_affirmation_nonce'); ?>

                 sag_render_affirmation_textarea_group('Health', $content); ?>
                 sag_render_affirmation_textarea_group('Wealth', $content); ?>
                 sag_render_affirmation_textarea_group('Relationship', $content); ?>

                <h2>Affirmation Template</h2>
                <textarea name="template" rows="5" style="width: 100%; font-family: monospace;"> echo esc_textarea($content['template']); ?></textarea>
                <p class="description">Use <code>$positive</code> as placeholder for the positive statement</p>

                 submit_button('Update Affirmation Content', 'primary', 'update_affirmation_content'); ?>
            </form>

            <hr>
            
            <form method="post" action="">
                 wp_nonce_field('sag_affirmation_push', 'sag_affirmation_push_nonce'); ?>
                 submit_button('Push Affirmation Content to All Subsites', 'secondary', 'push_affirmation_to_subsites'); ?>
            </form>

            <hr>

            <h2 id="summaries">Subsite Affirmation Summaries</h2>
             sag_display_affirmation_summaries(); ?>
        </div>
        
    }

    // Render grouped textareas for affirmations
    function sag_render_affirmation_textarea_group($type, $content) {
        $type_lc = strtolower($type);
        ?>
        <h2> echo esc_html($type); ?> Obstacles</h2>
        <textarea name=" echo esc_attr($type_lc . '_obstacles'); ?>" rows="10" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_obstacles']));
        ?></textarea>
        <p class="description">One  echo esc_html($type_lc); ?> obstacle per line</p>

        <h2> echo esc_html($type); ?> Positives - Set 1</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_1'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_1']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 2</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_2'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_2']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 3</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_3'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_3']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 4</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_4'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_4']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 5</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_5'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_5']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 6</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_6'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_6']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 7</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_7'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_7']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 8</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_8'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_8']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 9</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_9'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_9']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <h2> echo esc_html($type); ?> Positives - Set 10</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives_10'); ?>" rows="5" style="width: 100%; font-family: monospace;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives_10']));
        ?></textarea>
        <p class="description">10 positive statements (one per line)</p>

        <hr>
        
    }

    // Display affirmation summaries
    function sag_display_affirmation_summaries() {
        $sites = get_sites();
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Site</th><th>Latest Summary</th><th>Date</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($sites as $site) {
            if ((int)$site->blog_id === 1) continue;
            
            switch_to_blog($site->blog_id);
            $summary = get_option('affirmation_summary', '');
            $summary_date = get_option('affirmation_summary_date', '');
            $site_details = get_blog_details($site->blog_id);
            restore_current_blog();
            
            echo '<tr>';
            echo '<td>' . esc_html($site_details->blogname) . '</td>';
            echo '<td>' . esc_html($summary ?: 'No summary yet') . '</td>';
            echo '<td>' . esc_html($summary_date ? date('Y-m-d H:i:s', strtotime($summary_date)) : 'N/A') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }

    // === CONTENT MANAGEMENT FUNCTIONS ===

    // Get story content with defaults
    function sag_get_story_content() {
        $defaults = [
            'purposes' => [
                "inspire curiosity and growth",
                "build trust and respect",
                "develop original ideas",
                "give a voice to the unheard",
                "create meaningful connections"
            ],
            'life_goals' => [
                "ended poverty for families and communities in need",
                "alleviated hunger for children and vulnerable groups",
                "secured quality healthcare for underserved populations"
            ],
            'obstacles' => [
                "fears of failure and rejection",
                "entrenched cultural biases",
                "scarcities of time and energy"
            ],
            'positives' => [
                "confidence in your path and people",
                "deep cultural transformation",
                "abundance of resources"
            ],
            'adjectives' => ["bold", "brave", "charismatic", "cheerful", "compassionate"],
            'archetypes' => ["Alchemist", "Caregiver", "Celestial being", "Champion", "Creator"],
            'templates' => [
                'action_adventure' => [
                    "Fueled by fierce determination, \$heroName, the \$adjective \$archetype, set out to \$purpose. When \$obstacle threatened to break their spirit, they battled relentlessly, forging their path with courage. Ultimately, they \$lifeGoal. This thrilling adventure grips you from start to finish, reminding us all of the power of bravery and resolve."
                ],
                'biography_drama' => [
                    "In the quiet strength of \$heroName, the \$adjective \$archetype, lies a story of resolve. They set out to \$purpose, confronting \$obstacle that tested their very soul. Through heartache and hope, they ultimately \$lifeGoal. This deeply moving tale captures the raw essence of human spirit and resilience."
                ],
                'comedy_musical' => [
                    "\$heroName, the \$adjective \$archetype, set out to \$purpose, armed with laughter and a song in their heart. Faced with \$obstacle that tried to dim their spark, they danced through challenges with wit and charm. Ultimately, they \$lifeGoal, turning life's chaos into a joyous celebration."
                ],
                'fantasy_scifi' => [
                    "In a realm where reality bends, \$heroName, the \$adjective \$archetype, set out to \$purpose. Facing \$obstacle that defied logic and time, they wielded courage and wisdom to prevail. Ultimately, they \$lifeGoal. This spellbinding tale will transport you beyond the stars."
                ],
                'spirituality' => [
                    "On a path of inner awakening, \$heroName, the \$adjective \$archetype, set out to \$purpose. Faced with \$obstacle that shook their faith and resolve, they journeyed through darkness into light. Ultimately, they \$lifeGoal. This film offers a transformative experience that resonates deeply with the soul."
                ]
            ]
        ];
        
        return get_option('sag_story_content', $defaults);
    }

    // Get affirmation content with defaults (now with 10 sets per category)
    function sag_get_affirmation_content() {
        $default_content = [
            'health_obstacles' => [
                "chronic fatigue and low energy",
                "persistent pain and discomfort",
                "poor sleep quality",
                "lack of motivation to exercise",
                "unhealthy eating habits"
            ],
            'wealth_obstacles' => [
                "constant financial stress",
                "limited income opportunities",
                "overwhelming debt burden",
                "fear of financial insecurity",
                "lack of savings and investments"
            ],
            'relationship_obstacles' => [
                "feeling lonely and isolated",
                "difficulty trusting others",
                "constant relationship conflicts",
                "fear of intimacy and vulnerability",
                "lack of meaningful connections"
            ],
            'template' => "I am embracing \$positive in my life. Every day, I move closer to this reality with confidence and grace."
        ];

        // Add 10 sets of positives for each category
        $categories = ['health', 'wealth', 'relationship'];
        foreach ($categories as $category) {
            for ($i = 1; $i <= 10; $i++) {
                $default_content["{$category}_positives_{$i}"] = [
                    "abundant {$category} and vitality",
                    "complete {$category} transformation",
                    "unlimited {$category} potential",
                    "perfect {$category} balance",
                    "radiant {$category} energy",
                    "divine {$category} guidance",
                    "exceptional {$category} outcomes",
                    "harmonious {$category} flow",
                    "magnificent {$category} abundance",
                    "extraordinary {$category} wellness"
                ];
            }
        }

        return get_option('sag_affirmation_content', $default_content);
    }

    // Update story content
    function sag_update_story_content() {
        if (!wp_verify_nonce($_POST['sag_story_nonce'], 'sag_story_update')) {
            wp_die('Security check failed');
        }
        
        $content = [];
        
        $content['purposes'] = array_filter(array_map('trim', explode("\n", $_POST['purposes'])));
        $content['life_goals'] = array_filter(array_map('trim', explode("\n", $_POST['life_goals'])));
        $content['obstacles'] = array_filter(array_map('trim', explode("\n", $_POST['obstacles'])));
        $content['positives'] = array_filter(array_map('trim', explode("\n", $_POST['positives'])));
        
        $content['adjectives'] = array_filter(array_map('trim', explode(",", $_POST['adjectives'])));
        $content['archetypes'] = array_filter(array_map('trim', explode(",", $_POST['archetypes'])));
        
        $content['templates'] = [];
        foreach ($_POST['templates'] as $genre => $template_text) {
            $content['templates'][$genre] = array_filter(array_map('trim', explode("---TEMPLATE---", $template_text)));
        }
        
        update_option('sag_story_content', $content);
        
        echo '<div class="notice notice-success"><p>Story content updated successfully!</p></div>';
    }

    // Update affirmation content
    function sag_update_affirmation_content() {
        if (!wp_verify_nonce($_POST['sag_affirmation_nonce'], 'sag_affirmation_update')) {
            wp_die('Security check failed');
        }

        $fields = ['health', 'wealth', 'relationship'];
        $content = [];

        foreach ($fields as $field) {
            $content["{$field}_obstacles"] = array_filter(array_map('trim', explode("\n", $_POST["{$field}_obstacles"] ?? '')));
            
            // Handle 10 sets of positives per category
            for ($i = 1; $i <= 10; $i++) {
                $content["{$field}_positives_{$i}"] = array_filter(array_map('trim', explode("\n", $_POST["{$field}_positives_{$i}"] ?? '')));
            }
        }

        $content['template'] = trim($_POST['template'] ?? '');

        update_option('sag_affirmation_content', $content);

        echo '<div class="notice notice-success"><p>Affirmation content updated successfully!</p></div>';
    }

    // Push story content to subsites
    function sag_push_story_content_to_subsites() {
        if (isset($_POST['sag_story_push_nonce']) && !wp_verify_nonce($_POST['sag_story_push_nonce'], 'sag_story_push')) {
            wp_die('Security check failed');
        }
        
        $content = sag_get_story_content();
        $sites = get_sites();
        $updated_count = 0;
        
        foreach ($sites as $site) {
            if ($site->blog_id == 1) continue;
            
            switch_to_blog($site->blog_id);
            update_option('sag_story_content', $content);
            restore_current_blog();
            $updated_count++;
        }
        
        return "Story content pushed to {$updated_count} subsites successfully!";
    }

    // Push affirmation content to subsites
    function sag_push_affirmation_content_to_subsites() {
        if (isset($_POST['sag_affirmation_push_nonce']) && !wp_verify_nonce($_POST['sag_affirmation_push_nonce'], 'sag_affirmation_push')) {
            wp_die('Security check failed');
        }

        $content = sag_get_affirmation_content();
        $sites = get_sites();
        $updated = 0;

        foreach ($sites as $site) {
            if ((int)$site->blog_id === 1) continue;

            switch_to_blog($site->blog_id);
            update_option('sag_affirmation_content', $content);
            restore_current_blog();

            $updated++;
        }

        return "Affirmation content pushed to {$updated} subsites successfully!";
    }
}

// === REST API ENDPOINTS (Main Site Only) ===
add_action('rest_api_init', function () {
    if (is_main_site()) {
        // Story content endpoint
        register_rest_route('content-generators/v1', '/story-content', [
            'methods' => 'GET',
            'callback' => function () {
                return ['content' => sag_get_story_content()];
            },
            'permission_callback' => '__return_true',
        ]);
        
        // Affirmation content endpoint
        register_rest_route('content-generators/v1', '/affirmation-content', [
            'methods' => 'GET',
            'callback' => function () {
                return ['content' => sag_get_affirmation_content()];
            },
            'permission_callback' => '__return_true',
        ]);
    }
});

// === SUBSITE FUNCTIONALITY ===

// Get story content for subsites
function sag_get_story_content_subsite() {
    $content = get_option('sag_story_content');
    
    if (!$content || empty($content)) {
        $main_site_url = network_site_url();
        $response = wp_remote_get(trailingslashit($main_site_url) . 'wp-json/content-generators/v1/story-content');
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if ($data && isset($data['content'])) {
                $content = $data['content'];
                update_option('sag_story_content', $content);
            }
        }
        
        if (!$content) {
            $content = sag_get_story_content(); // fallback to defaults
        }
    }
    
    return $content;
}

// Get affirmation content for subsites
function sag_get_affirmation_content_subsite() {


/*
Plugin Name: Story & Affirmation Generator
Description: Unified multisite story and affirmation generator with centralized content management
Version: 2.0
Network: true
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// === PLUGIN INITIALIZATION ===
class StoryAffirmationGenerator {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }
    
    public function init() {
        if (is_main_site() && is_admin()) {
            $this->init_main_site_admin();
        }
        $this->init_subsite_functionality();
        $this->init_rest_endpoints();
    }
    
    public function activate() {
        if (is_main_site()) {
            $this->initialize_default_content();
        }
    }
    
    private function init_main_site_admin() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_bar_menu', [$this, 'add_admin_bar_link'], 100);
    }
    
    private function init_subsite_functionality() {
        add_shortcode('story_generator', [$this, 'story_generator_shortcode']);
        add_shortcode('affirmation_generator', [$this, 'affirmation_generator_shortcode']);
        add_action('wp_ajax_save_affirmation_summary', [$this, 'save_affirmation_summary']);
        add_action('wp_ajax_nopriv_save_affirmation_summary', [$this, 'save_affirmation_summary']);
        
        if (is_admin()) {
            add_action('admin_menu', [$this, 'register_subsite_menu']);
        }
    }
    
    private function init_rest_endpoints() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    // === ADMIN MENU REGISTRATION ===
    public function register_admin_menu() {
        add_menu_page(
            'Content Generators',
            'Content Generators',
            'manage_options',
            'content-generators',
            [$this, 'render_admin_dashboard'],
            'dashicons-admin-tools',
            30
        );
    }
    
    public function register_subsite_menu() {
        if (!is_main_site()) {
            add_menu_page(
                'My Generators',
                'My Generators',
                'manage_options',
                'my-generators',
                [$this, 'render_subsite_dashboard'],
                'dashicons-admin-tools',
                30
            );
        }
    }
    
    public function add_admin_bar_link($wp_admin_bar) {
        if (!is_admin_bar_showing() || !current_user_can('manage_options')) return;
        
        if (is_main_site()) {
            $wp_admin_bar->add_node([
                'id' => 'content-generators',
                'title' => 'Content Generators',
                'href' => admin_url('admin.php?page=content-generators'),
            ]);
        } else {
            $wp_admin_bar->add_node([
                'id' => 'my-generators',
                'title' => 'My Generators',
                'href' => admin_url('admin.php?page=my-generators'),
            ]);
        }
    }
    
    // === MAIN SITE ADMIN DASHBOARD ===
    public function render_admin_dashboard() {
        $active_tab = $_GET['tab'] ?? 'story';
        
        // Handle form submissions
        if (isset($_POST['update_story_content'])) {
            $this->handle_story_content_update();
        }
        if (isset($_POST['update_affirmation_content'])) {
            $this->handle_affirmation_content_update();
        }
        if (isset($_POST['push_to_subsites'])) {
            $message = $this->push_content_to_subsites();
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Content Generators Dashboard</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=content-generators&tab=story" class="nav-tab  echo $active_tab === 'story' ? 'nav-tab-active' : ''; ?>">Story Generator</a>
                <a href="?page=content-generators&tab=affirmation" class="nav-tab  echo $active_tab === 'affirmation' ? 'nav-tab-active' : ''; ?>">Affirmation Generator</a>
                <a href="?page=content-generators&tab=subsites" class="nav-tab  echo $active_tab === 'subsites' ? 'nav-tab-active' : ''; ?>">Subsite Summaries</a>
            </nav>
            
             if ($active_tab === 'story'): ?>
                 $this->render_story_admin(); ?>
             elseif ($active_tab === 'affirmation'): ?>
                 $this->render_affirmation_admin(); ?>
             elseif ($active_tab === 'subsites'): ?>
                 $this->render_subsite_summaries(); ?>
             endif; ?>
            
            <hr>
            <h2>Push to All Subsites</h2>
            <p>Click below to update all subsites with the current content.</p>
            <form method="post" action="">
                 wp_nonce_field('sag_push_content', 'sag_push_nonce'); ?>
                 submit_button('Push to All Subsites', 'secondary', 'push_to_subsites'); ?>
            </form>
        </div>
        
        <style>
        .wrap textarea { font-family: monospace; }
        .nav-tab-wrapper { margin-bottom: 20px; }
        </style>
        
    }
    
    private function render_story_admin() {
        $content = $this->get_story_content();
        ?>
        <form method="post" action="">
             wp_nonce_field('sag_story_update', 'sag_story_nonce'); ?>
            
            <h2>Purposes</h2>
            <textarea name="purposes" rows="10" style="width: 100%;"> echo esc_textarea(implode("\n", $content['purposes'])); ?></textarea>
            <p class="description">One purpose per line</p>
            
            <h2>Life Goals</h2>
            <textarea name="life_goals" rows="10" style="width: 100%;"> echo esc_textarea(implode("\n", $content['life_goals'])); ?></textarea>
            <p class="description">One life goal per line</p>
            
            <h2>Obstacles</h2>
            <textarea name="obstacles" rows="10" style="width: 100%;"> echo esc_textarea(implode("\n", $content['obstacles'])); ?></textarea>
            <p class="description">One obstacle per line</p>
            
            <h2>Positives (must match obstacles order)</h2>
            <textarea name="positives" rows="10" style="width: 100%;"> echo esc_textarea(implode("\n", $content['positives'])); ?></textarea>
            <p class="description">One positive per line, corresponding to obstacles above</p>
            
            <h2>Adjectives</h2>
            <textarea name="adjectives" rows="5" style="width: 100%;"> echo esc_textarea(implode(", ", $content['adjectives'])); ?></textarea>
            <p class="description">Comma-separated list</p>
            
            <h2>Archetypes</h2>
            <textarea name="archetypes" rows="5" style="width: 100%;"> echo esc_textarea(implode(", ", $content['archetypes'])); ?></textarea>
            <p class="description">Comma-separated list</p>
            
            <h2>Genre Templates</h2>
             foreach ($content['templates'] as $genre => $templates): ?>
                <h3> echo esc_html(ucwords(str_replace('_', '/', $genre))); ?></h3>
                <textarea name="templates[ echo esc_attr($genre); ?>]" rows="15" style="width: 100%;"> echo esc_textarea(implode("\n---TEMPLATE---\n", $templates)); ?></textarea>
                <p class="description">Separate templates with ---TEMPLATE---</p>
             endforeach; ?>
            
             submit_button('Update Story Content', 'primary', 'update_story_content'); ?>
        </form>
        
    }
    
    private function render_affirmation_admin() {
        $content = $this->get_affirmation_content();
        ?>
        <form method="post" action="">
             wp_nonce_field('sag_affirmation_update', 'sag_affirmation_nonce'); ?>
            
             $this->render_affirmation_textarea_group('Health', $content); ?>
             $this->render_affirmation_textarea_group('Wealth', $content); ?>
             $this->render_affirmation_textarea_group('Relationship', $content); ?>
            
            <h2>Affirmation Template</h2>
            <textarea name="template" rows="5" style="width: 100%;"> echo esc_textarea($content['template']); ?></textarea>
            <p class="description">Use <code>$positive</code> as placeholder for the positive statement</p>
            
             submit_button('Update Affirmation Content', 'primary', 'update_affirmation_content'); ?>
        </form>
        
    }
    
    private function render_affirmation_textarea_group($type, $content) {
        $type_lc = strtolower($type);
        ?>
        <h2> echo esc_html($type); ?> Obstacles</h2>
        <textarea name=" echo esc_attr($type_lc . '_obstacles'); ?>" rows="10" style="width: 100%;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_obstacles']));
        ?></textarea>
        <p class="description">One  echo esc_html($type_lc); ?> obstacle per line</p>

        <h2> echo esc_html($type); ?> Positives (must match obstacles order)</h2>
        <textarea name=" echo esc_attr($type_lc . '_positives'); ?>" rows="10" style="width: 100%;">
            echo esc_textarea(implode("\n", $content[$type_lc . '_positives']));
        ?></textarea>
        <p class="description">One positive per line, corresponding to obstacles above</p>
        
    }
    
    private function render_subsite_summaries() {
        $sites = get_sites();
        ?>
        <h2>Subsite Activity</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Latest Story</th>
                    <th>Latest Affirmation Summary</th>
                    <th>Summary Date</th>
                </tr>
            </thead>
            <tbody>
             foreach ($sites as $site): ?>
                 if ((int)$site->blog_id === 1) continue; ?>
                
                switch_to_blog($site->blog_id);
                $story_data = get_option('sag_site_story', []);
                $affirmation_summary = get_option('sag_affirmation_summary', '');
                $summary_date = get_option('sag_affirmation_summary_date', '');
                $site_details = get_blog_details($site->blog_id);
                restore_current_blog();
                ?>
                <tr>
                    <td> echo esc_html($site_details->blogname); ?></td>
                    <td> echo esc_html($story_data['story_text'] ? substr($story_data['story_text'], 0, 100) . '...' : 'No story yet'); ?></td>
                    <td> echo esc_html($affirmation_summary ?: 'No summary yet'); ?></td>
                    <td> echo esc_html($summary_date ? date('Y-m-d H:i:s', strtotime($summary_date)) : 'N/A'); ?></td>
                </tr>
             endforeach; ?>
            </tbody>
        </table>
        
    }
    
    // === SUBSITE DASHBOARD ===
    public function render_subsite_dashboard() {
        $story_data = get_option('sag_site_story', []);
        $affirmation_summary = get_option('sag_affirmation_summary', '');
        
        ?>
        <div class="wrap">
            <h1>My Content Generators</h1>
            
            <div class="card" style="max-width: none;">
                <h2>Story Generator</h2>
                 if (!empty($story_data['story_text'])): ?>
                    <p><strong>Current Story:</strong>  echo esc_html(substr($story_data['story_text'], 0, 150)) . '...'; ?></p>
                    <p>
                        <a href=" echo admin_url('admin.php?page=my-generators&action=clear_story'); ?>" class="button">Clear Story</a>
                        <a href=" echo admin_url('admin.php?page=my-generators&action=refresh_content'); ?>" class="button">Refresh Content</a>
                    </p>
                 else: ?>
                    <p>No story generated yet.</p>
                 endif; ?>
                
                <p><strong>Shortcode:</strong> <code>[story_generator]</code></p>
            </div>
            
            <div class="card" style="max-width: none;">
                <h2>Affirmation Generator</h2>
                 if (!empty($affirmation_summary)): ?>
                    <p><strong>Latest Summary:</strong>  echo esc_html($affirmation_summary); ?></p>
                 else: ?>
                    <p>No affirmation summary yet.</p>
                 endif; ?>
                
                <p><strong>Shortcode:</strong> <code>[affirmation_generator]</code></p>
            </div>
            
            
            // Handle actions
            if (isset($_GET['action'])) {
                if ($_GET['action'] === 'clear_story') {
                    delete_option('sag_site_story');
                    echo '<div class="notice notice-success"><p>Story cleared successfully!</p></div>';
                } elseif ($_GET['action'] === 'refresh_content') {
                    $this->refresh_subsite_content();
                    echo '<div class="notice notice-success"><p>Content refreshed from main site!</p></div>';
                }
            }
            ?>
        </div>
        
    }
    
    // === CONTENT MANAGEMENT ===
    public function get_story_content() {
        $defaults = [
            'purposes' => [
                "inspire curiosity and growth",
                "build trust and respect",
                "develop original ideas",
                "give a voice to the unheard",
                "create meaningful connections"
            ],
            'life_goals' => [
                "ended poverty for families and communities in need",
                "alleviated hunger for children and vulnerable groups",
                "secured quality healthcare for underserved populations"
            ],
            'obstacles' => [
                "fears of failure and rejection",
                "entrenched cultural biases",
                "scarcities of time and energy"
            ],
            'positives' => [
                "confidence in your path and people",
                "deep cultural transformation",
                "abundance of resources"
            ],
            'adjectives' => ["bold", "brave", "charismatic", "cheerful", "compassionate"],
            'archetypes' => ["Alchemist", "Caregiver", "Celestial being", "Champion", "Creator"],
            'templates' => [
                'action_adventure' => [
                    "Fueled by fierce determination, \$heroName, the \$adjective \$archetype, set out to \$purpose. When \$obstacle threatened to break their spirit, they battled relentlessly, forging their path with courage. Ultimately, they \$lifeGoal. This thrilling adventure grips you from start to finish, reminding us all of the power of bravery and resolve."
                ],
                'biography_drama' => [
                    "In the quiet strength of \$heroName, the \$adjective \$archetype, lies a story of resolve. They set out to \$purpose, confronting \$obstacle that tested their very soul. Through heartache and hope, they ultimately \$lifeGoal. This deeply moving tale captures the raw essence of human spirit and resilience."
                ],
                'comedy_musical' => [
                    "\$heroName, the \$adjective \$archetype, set out to \$purpose, armed with laughter and a song in their heart. Faced with \$obstacle that tried to dim their spark, they danced through challenges with wit and charm. Ultimately, they \$lifeGoal, turning life's chaos into a joyous celebration."
                ],
                'fantasy_scifi' => [
                    "In a realm where reality bends, \$heroName, the \$adjective \$archetype, set out to \$purpose. Facing \$obstacle that defied logic and time, they wielded courage and wisdom to prevail. Ultimately, they \$lifeGoal. This spellbinding tale will transport you beyond the stars."
                ],
                'spirituality' => [
                    "On a path of inner awakening, \$heroName, the \$adjective \$archetype, set out to \$purpose. Faced with \$obstacle that shook their faith and resolve, they journeyed through darkness into light. Ultimately, they \$lifeGoal. This film offers a transformative experience that resonates deeply with the soul."
                ]
            ]
        ];
        
        return get_option('sag_story_content', $defaults);
    }
    
    public function get_affirmation_content() {
        $defaults = [
            'health_obstacles' => [
                "chronic fatigue and low energy",
                "persistent pain and discomfort", 
                "poor sleep quality",
                "lack of motivation to exercise",
                "unhealthy eating habits"
            ],
            'health_positives' => [
                "vibrant energy and vitality",
                "complete comfort and well-being",
                "deep, restorative sleep", 
                "enthusiasm for physical activity",
                "nourishing food choices"
            ],
            'wealth_obstacles' => [
                "constant financial stress",
                "limited income opportunities",
                "overwhelming debt burden",
                "fear of financial insecurity", 
                "lack of savings and investments"
            ],
            'wealth_positives' => [
                "abundant financial peace",
                "unlimited income potential",
                "complete debt freedom",
                "total financial security",
                "growing wealth and prosperity"
            ],
            'relationship_obstacles' => [
                "feeling lonely and isolated",
                "difficulty trusting others",
                "constant relationship conflicts",
                "fear of intimacy and vulnerability",
                "lack of meaningful connections"
            ],
            'relationship_positives' => [
                "deep connection and belonging",
                "complete trust and openness", 
                "harmonious and loving relationships",
                "comfort with intimacy and authenticity",
                "rich, meaningful connections"
            ],
            'template' => "I am embracing \$positive in my life. Every day, I move closer to this reality with confidence and grace."
        ];
        
        return get_option('sag_affirmation_content', $defaults);
    }
    
    // Get story content for subsites
    public function get_story_content_subsite() {
        $content = get_option('sag_story_content');
        
        if (!$content || empty($content)) {
            $main_site_url = network_site_url();
            $response = wp_remote_get(trailingslashit($main_site_url) . 'wp-json/content-generators/v1/story-content');
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if ($data && isset($data['content'])) {
                    $content = $data['content'];
                    update_option('sag_story_content', $content);
                }
            }
            
            if (!$content) {
                $content = $this->get_story_content(); // fallback to defaults
            }
        }
        
        return $content;
    }
    
    // Get affirmation content for subsites
    public function get_affirmation_content_subsite() {
        $content = get_option('sag_affirmation_content');
        
        if (!$content || empty($content)) {
            $main_site_url = network_site_url();
            $response = wp_remote_get(trailingslashit($main_site_url) . 'wp-json/content-generators/v1/affirmation-content');
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if ($data && isset($data['content'])) {
                    $content = $data['content'];
                    update_option('sag_affirmation_content', $content);
                }
            }
            
            if (!$content) {
                $content = $this->get_affirmation_content(); // fallback to defaults
            }
        }
        
        return $content;
    }
    
    private function initialize_default_content() {
        $story_content = $this->get_story_content();
        $affirmation_content = $this->get_affirmation_content();
        
        update_option('sag_story_content', $story_content);
        update_option('sag_affirmation_content', $affirmation_content);
    }
    
    // === FORM HANDLERS ===
    public function handle_story_content_update() {
        if (!wp_verify_nonce($_POST['sag_story_nonce'], 'sag_story_update')) {
            wp_die('Security check failed');
        }
        
        $content = [];
        $content['purposes'] = array_filter(array_map('trim', explode("\n", $_POST['purposes'])));
        $content['life_goals'] = array_filter(array_map('trim', explode("\n", $_POST['life_goals'])));
        $content['obstacles'] = array_filter(array_map('trim', explode("\n", $_POST['obstacles'])));
        $content['positives'] = array_filter(array_map('trim', explode("\n", $_POST['positives'])));
        $content['adjectives'] = array_filter(array_map('trim', explode(",", $_POST['adjectives'])));
        $content['archetypes'] = array_filter(array_map('trim', explode(",", $_POST['archetypes'])));
        
        $content['templates'] = [];
        foreach ($_POST['templates'] as $genre => $template_text) {
            $content['templates'][$genre] = array_filter(array_map('trim', explode("---TEMPLATE---", $template_text)));
        }
        
        update_option('sag_story_content', $content);
        echo '<div class="notice notice-success"><p>Story content updated successfully!</p></div>';
    }
    
    public function handle_affirmation_content_update() {
        if (!wp_verify_nonce($_POST['sag_affirmation_nonce'], 'sag_affirmation_update')) {
            wp_die('Security check failed');
        }
        
        $fields = ['health', 'wealth', 'relationship'];
        $content = [];
        
        foreach ($fields as $field) {
            $content["{$field}_obstacles"] = array_filter(array_map('trim', explode("\n", $_POST["{$field}_obstacles"] ?? '')));
            $content["{$field}_positives"] = array_filter(array_map('trim', explode("\n", $_POST["{$field}_positives"] ?? '')));
        }
        
        $content['template'] = trim($_POST['template'] ?? '');
        
        update_option('sag_affirmation_content', $content);
        echo '<div class="notice notice-success"><p>Affirmation content updated successfully!</p></div>';
    }
    
    public function push_content_to_subsites() {
        if (!wp_verify_nonce($_POST['sag_push_nonce'], 'sag_push_content')) {
            wp_die('Security check failed');
        }
        
        $story_content = $this->get_story_content();
        $affirmation_content = $this->get_affirmation_content();  
        $sites = get_sites();
        $updated_count = 0;
        
        foreach ($sites as $site) {
            if ($site->blog_id == 1) continue;
            
            switch_to_blog($site->blog_id);
            update_option('sag_story_content', $story_content);
            update_option('sag_affirmation_content', $affirmation_content);
            restore_current_blog();
            $updated_count++;
        }
        
        return "Content pushed to {$updated_count} subsites successfully!";
    }
    
    private function refresh_subsite_content() {
        delete_option('sag_story_content');
        delete_option('sag_affirmation_content');
        
        // Force re-fetch from main site
        $this->get_story_content_subsite();
        $this->get_affirmation_content_subsite();
    }
    
    // === REST API ENDPOINTS ===
    public function register_rest_routes() {
        if (is_main_site()) {
            register_rest_route('content-generators/v1', '/story-content', [
                'methods' => 'GET',
                'callback' => [$this, 'rest_get_story_content'],
                'permission_callback' => '__return_true',
            ]);
            
            register_rest_route('content-generators/v1', '/affirmation-content', [
                'methods' => 'GET', 
                'callback' => [$this, 'rest_get_affirmation_content'],
                'permission_callback' => '__return_true',
            ]);
        }
    }
    
    public function rest_get_story_content() {
        return ['content' => $this->get_story_content()];
    }
    
    public function rest_get_affirmation_content() {
        return ['content' => $this->get_affirmation_content()];
    }
    
    // === SHORTCODES ===
    public function story_generator_shortcode() {
        return $this->render_story_generator();
    }
    
    public function affirmation_generator_shortcode() {
        return $this->render_affirmation_generator();
    }
    
    // === STORY GENERATOR FUNCTIONALITY ===
    private function render_story_generator() {
        $result = $this->process_story_form();
        $errors = $result['errors'];
        $story_data = $result['story_data'];
        $content = $result['content'] ?? $this->get_story_content_subsite();
        
        if (!$content) {
            return '<div class="error">Story generator content not available. Please contact the administrator.</div>';
        }
        
        $genres = [
            "action_adventure" => "Action/Adventure",
            "biography_drama" => "Biography/Drama", 
            "comedy_musical" => "Comedy/Musical",
            "fantasy_scifi" => "Fantasy/Science Fiction",
            "spirituality" => "Spirituality"
        ];
        
        ob_start();
        ?>
        <div class="sag-story-generator">
            <style>
            .sag-story-generator {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 600px;
                margin: 0 auto;
            }
            .sag-story-generator form {
                background: white;
                padding: 25px 30px;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .sag-story-generator label {
                display: block;
                margin-top: 20px;
                font-weight: 600;
            }
            .sag-story-generator input[type="text"],
            .sag-story-generator select {
                width: 100%;
                padding: 10px;
                margin-top: 5px;
                border: 1px solid #ccc;
                border-radius: 6px;
                font-size: 1rem;
                box-sizing: border-box;
            }
            .sag-story-generator button {
                margin-top: 25px;
                padding: 12px 20px;
                background-color: #6a1b9a;
                color: white;
                font-size: 1rem;
                font-weight: bold;
                border: none;
                border-radius: 6px;
                cursor: pointer;
            }
            .sag-story-generator .error {
                background: #ffecec;
                border: 1px solid #f5c2c2;
                color: #d33;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .sag-story-generator .story {
                background: #fff;
                border-left: 6px solid #7d6bb5;
                padding: 20px 25px;
                border-radius: 8px;
                margin-top: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            }
            </style>
            
             if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                     foreach ($errors as $err): ?>
                        <li> echo esc_html($err); ?></li>
                     endforeach; ?>
                    </ul>
                </div>
             endif; ?>

            <form method="POST" action="">
                <label for="hero_name">What is the hero's name?</label>
                <input type="text" id="hero_name" name="hero_name" value=" echo esc_attr($story_data['heroName']); ?>" required />

                <label for="genre">What genre is your movie?</label>
                <select id="

/*
Plugin Name: Story & Affirmation Generator
Description: Complete multisite story and affirmation generator with centralized dashboard
Version: 1.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class StoryAffirmationGenerator {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activation']);
    }
    
    public function init() {
        // Main site admin functionality
        if (is_main_site() && is_admin()) {
            add_action('admin_menu', [$this, 'register_admin_menu']);
        }
        
        // Shortcodes for all sites
        add_shortcode('story_generator', [$this, 'story_generator_shortcode']);
        add_shortcode('affirmation_generator', [$this, 'affirmation_generator_shortcode']);
        
        // AJAX handlers
        add_action('wp_ajax_save_affirmation_summary', [$this, 'save_affirmation_summary']);
        add_action('wp_ajax_nopriv_save_affirmation_summary', [$this, 'save_affirmation_summary']);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        
        // Admin bar
        add_action('admin_bar_menu', [$this, 'admin_bar_menu'], 100);
    }
    
    // === ADMIN MENU ===
    public function register_admin_menu() {
        add_menu_page(
            'Story & Affirmation Generator',
            'Story & Affirmation',
            'manage_options',
            'story-affirmation-admin',
            [$this, 'render_admin_page'],
            'dashicons-book-alt',
            30
        );
    }
    
    // === ADMIN PAGE ===
    public function render_admin_page() {
        $active_tab = $_GET['tab'] ?? 'story';
        
        if (isset($_POST['update_story_content'])) {
            $this->update_story_content();
        }
        if (isset($_POST['update_affirmation_content'])) {
            $this->update_affirmation_content();
        }
        if (isset($_POST['push_to_subsites'])) {
            $message = $this->push_to_subsites();
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Story & Affirmation Generator</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=story-affirmation-admin&tab=story" class="nav-tab  echo $active_tab === 'story' ? 'nav-tab-active' : ''; ?>">Story Generator</a>
                <a href="?page=story-affirmation-admin&tab=affirmation" class="nav-tab  echo $active_tab === 'affirmation' ? 'nav-tab-active' : ''; ?>">Affirmation Generator</a>
                <a href="?page=story-affirmation-admin&tab=summaries" class="nav-tab  echo $active_tab === 'summaries' ? 'nav-tab-active' : ''; ?>">Subsite Summaries</a>
            </nav>
            
             if ($active_tab === 'story'): ?>
                 $this->render_story_admin(); ?>
             elseif ($active_tab === 'affirmation'): ?>
                 $this->render_affirmation_admin(); ?>
             elseif ($active_tab === 'summaries'): ?>
                 $this->render_summaries_admin(); ?>
             endif; ?>
            
            <hr>
            <h2>Push to All Subsites</h2>
            <p>Click below to update all subsites with the current content.</p>
            <form method="post" action="">
                 wp_nonce_field('sag_push', 'sag_push_nonce'); ?>
                 submit_button('Push to All Subsites', 'secondary', 'push_to_subsites'); ?>
            </form>
        </div>
        
    }
    
    // === STORY ADMIN ===
    private function render_story_admin() {
        $content = $this->get_story_content();
        ?>
        <form method="post" action="">
             wp_nonce_field('sag_story_update', 'sag_story_nonce'); ?>
            
            <h2>Purposes</h2>
            <textarea name="purposes" rows="10" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content['purposes'])); ?></textarea>
            <p class="description">One purpose per line</p>
            
            <h2>Life Goals</h2>
            <textarea name="life_goals" rows="10" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content['life_goals'])); ?></textarea>
            <p class="description">One life goal per line</p>
            
            <h2>Obstacles</h2>
            <textarea name="obstacles" rows="10" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content['obstacles'])); ?></textarea>
            <p class="description">One obstacle per line</p>
            
            <h2>Positives (must match obstacles order)</h2>
            <textarea name="positives" rows="10" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content['positives'])); ?></textarea>
            <p class="description">One positive per line, corresponding to obstacles above</p>
            
            <h2>Adjectives</h2>
            <textarea name="adjectives" rows="5" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode(", ", $content['adjectives'])); ?></textarea>
            <p class="description">Comma-separated list</p>
            
            <h2>Archetypes</h2>
            <textarea name="archetypes" rows="5" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode(", ", $content['archetypes'])); ?></textarea>
            <p class="description">Comma-separated list</p>
            
            <h2>Genre Templates</h2>
             foreach ($content['templates'] as $genre => $templates): ?>
                <h3> echo esc_html(ucwords(str_replace('_', '/', $genre))); ?></h3>
                <textarea name="templates[ echo esc_attr($genre); ?>]" rows="15" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n---TEMPLATE---\n", $templates)); ?></textarea>
                <p class="description">Separate templates with ---TEMPLATE---</p>
             endforeach; ?>
            
             submit_button('Update Story Content', 'primary', 'update_story_content'); ?>
        </form>
        
    }
    
    // === AFFIRMATION ADMIN ===
    private function render_affirmation_admin() {
        $content = $this->get_affirmation_content();
        ?>
        <form method="post" action="">
             wp_nonce_field('sag_affirmation_update', 'sag_affirmation_nonce'); ?>
            
             for ($i = 1; $i <= 10; $i++): ?>
                <h2>Positive Option  echo $i; ?></h2>
                <textarea name="positive_ echo $i; ?>" rows="5" style="width: 100%; font-family: monospace;"> echo esc_textarea(implode("\n", $content["positive_$i"])); ?></textarea>
                <p class="description">One positive statement per line for dropdown  echo $i; ?></p>
             endfor; ?>
            
            <h2>Affirmation Template</h2>
            <textarea name="template" rows="5" style="width: 100%; font-family: monospace;"> echo esc_textarea($content['template']); ?></textarea>
            <p class="description">Use <code>$positives</code> as placeholder for the selected positive statements</p>
            
             submit_button('Update Affirmation Content', 'primary', 'update_affirmation_content'); ?>
        </form>
        
    }
    
    // === SUMMARIES ADMIN ===
    private function render_summaries_admin() {
        $sites = get_sites();
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Site</th><th>Story Summary</th><th>Affirmation Summary</th><th>Date</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($sites as $site) {
            if ((int)$site->blog_id === 1) continue;
            
            switch_to_blog($site->blog_id);
            $story_summary = get_option('story_summary', '');
            $affirmation_summary = get_option('affirmation_summary', '');
            $summary_date = get_option('summary_date', '');
            $site_details = get_blog_details($site->blog_id);
            restore_current_blog();
            
            echo '<tr>';
            echo '<td>' . esc_html($site_details->blogname) . '</td>';
            echo '<td>' . esc_html($story_summary ?: 'No story summary') . '</td>';
            echo '<td>' . esc_html($affirmation_summary ?: 'No affirmation summary') . '</td>';
            echo '<td>' . esc_html($summary_date ? date('Y-m-d H:i:s', strtotime($summary_date)) : 'N/A') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    // === CONTENT MANAGEMENT ===
    private function get_story_content() {
        $defaults = [
            'purposes' => [
                "inspire curiosity and growth",
                "build trust and respect",
                "develop original ideas",
                "give a voice to the unheard",
                "create meaningful connections"
            ],
            'life_goals' => [
                "ended poverty for families and communities in need",
                "alleviated hunger for children and vulnerable groups",
                "secured quality healthcare for underserved populations"
            ],
            'obstacles' => [
                "fears of failure and rejection",
                "entrenched cultural biases",
                "scarcities of time and energy"
            ],
            'positives' => [
                "confidence in your path and people",
                "deep cultural transformation",
                "abundance of resources"
            ],
            'adjectives' => ["bold", "brave", "charismatic", "cheerful", "compassionate"],
            'archetypes' => ["Alchemist", "Caregiver", "Celestial being", "Champion", "Creator"],
            'templates' => [
                'action_adventure' => [
                    "Fueled by fierce determination, \$heroName, the \$adjective \$archetype, set out to \$purpose. When \$obstacle threatened to break their spirit, they battled relentlessly, forging their path with courage. Ultimately, they \$lifeGoal. This thrilling adventure grips you from start to finish, reminding us all of the power of bravery and resolve."
                ],
                'biography_drama' => [
                    "In the quiet strength of \$heroName, the \$adjective \$archetype, lies a story of resolve. They set out to \$purpose, confronting \$obstacle that tested their very soul. Through heartache and hope, they ultimately \$lifeGoal. This deeply moving tale captures the raw essence of human spirit and resilience."
                ],
                'comedy_musical' => [
                    "\$heroName, the \$adjective \$archetype, set out to \$purpose, armed with laughter and a song in their heart. Faced with \$obstacle that tried to dim their spark, they danced through challenges with wit and charm. Ultimately, they \$lifeGoal, turning life's chaos into a joyous celebration."
                ],
                'fantasy_scifi' => [
                    "In a realm where reality bends, \$heroName, the \$adjective \$archetype, set out to \$purpose. Facing \$obstacle that defied logic and time, they wielded courage and wisdom to prevail. Ultimately, they \$lifeGoal. This spellbinding tale will transport you beyond the stars."
                ],
                'spirituality' => [
                    "On a path of inner awakening, \$heroName, the \$adjective \$archetype, set out to \$purpose. Faced with \$obstacle that shook their faith and resolve, they journeyed through darkness into light. Ultimately, they \$lifeGoal. This film offers a transformative experience that resonates deeply with the soul."
                ]
            ]
        ];
        
        return get_option('sag_story_content', $defaults);
    }
    
    private function get_affirmation_content() {
        $defaults = [
            'template' => "I am embracing \$positives in my life. Every day, I move closer to this reality with confidence and grace."
        ];
        
        // Initialize 10 positive option arrays
        for ($i = 1; $i <= 10; $i++) {
            $defaults["positive_$i"] = [
                "vibrant health and energy",
                "abundant wealth and prosperity",
                "loving relationships and connection",
                "inner peace and happiness",
                "creative expression and fulfillment"
            ];
        }
        
        return get_option('sag_affirmation_content', $defaults);
    }
    
    private function update_story_content() {
        if (!wp_verify_nonce($_POST['sag_story_nonce'], 'sag_story_update')) {
            wp_die('Security check failed');
        }
        
        $content = [];
        $content['purposes'] = array_filter(array_map('trim', explode("\n", $_POST['purposes'])));
        $content['life_goals'] = array_filter(array_map('trim', explode("\n", $_POST['life_goals'])));
        $content['obstacles'] = array_filter(array_map('trim', explode("\n", $_POST['obstacles'])));
        $content['positives'] = array_filter(array_map('trim', explode("\n", $_POST['positives'])));
        $content['adjectives'] = array_filter(array_map('trim', explode(",", $_POST['adjectives'])));
        $content['archetypes'] = array_filter(array_map('trim', explode(",", $_POST['archetypes'])));
        
        $content['templates'] = [];
        foreach ($_POST['templates'] as $genre => $template_text) {
            $content['templates'][$genre] = array_filter(array_map('trim', explode("---TEMPLATE---", $template_text)));
        }
        
        update_option('sag_story_content', $content);
        echo '<div class="notice notice-success"><p>Story content updated successfully!</p></div>';
    }
    
    private function update_affirmation_content() {
        if (!wp_verify_nonce($_POST['sag_affirmation_nonce'], 'sag_affirmation_update')) {
            wp_die('Security check failed');
        }
        
        $content = [];
        $content['template'] = trim($_POST['template'] ?? '');
        
        for ($i = 1; $i <= 10; $i++) {
            $content["positive_$i"] = array_filter(array_map('trim', explode("\n", $_POST["positive_$i"] ?? '')));
        }
        
        update_option('sag_affirmation_content', $content);
        echo '<div class="notice notice-success"><p>Affirmation content updated successfully!</p></div>';
    }
    
    private function push_to_subsites() {
        if (!wp_verify_nonce($_POST['sag_push_nonce'], 'sag_push')) {
            wp_die('Security check failed');
        }
        
        $story_content = $this->get_story_content();
        $affirmation_content = $this->get_affirmation_content();
        $sites = get_sites();
        $updated = 0;
        
        foreach ($sites as $site) {
            if ($site->blog_id == 1) continue;
            
            switch_to_blog($site->blog_id);
            update_option('sag_story_content', $story_content);
            update_option('sag_affirmation_content', $affirmation_content);
            restore_current_blog();
            $updated++;
        }
        
        return "Content pushed to {$updated} subsites successfully!";
    }
    
    // === REST API ===
    public function register_rest_endpoints() {
        if (is_main_site()) {
            register_rest_route('sag/v1', '/story-content', [
                'methods' => 'GET',
                'callback' => [$this, 'get_story_content_api'],
                'permission_callback' => '__return_true',
            ]);
            
            register_rest_route('sag/v1', '/affirmation-content', [
                'methods' => 'GET',
                'callback' => [$this, 'get_affirmation_content_api'],
                'permission_callback' => '__return_true',
            ]);
        }
    }
    
    public function get_story_content_api() {
        return ['content' => $this->get_story_content()];
    }
    
    public function get_affirmation_content_api() {
        return ['content' => $this->get_affirmation_content()];
    }
    
    // === SUBSITE CONTENT RETRIEVAL ===
    private function get_story_content_subsite() {
        $content = get_option('sag_story_content');
        
        if (!$content) {
            $main_site_url = network_site_url();
            $response = wp_remote_get(trailingslashit($main_site_url) . 'wp-json/sag/v1/story-content');
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if ($data && isset($data['content'])) {
                    $content = $data['content'];
                    update_option('sag_story_content', $content);
                }
            }
        }
        
        return $content;
    }
    
    private function get_affirmation_content_subsite() {
        $content = get_option('sag_affirmation_content');
        
        if (!$content) {
            $main_site_url = network_site_url();
            $response = wp_remote_get(trailingslashit($main_site_url) . 'wp-json/sag/v1/affirmation-content');
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if ($data && isset($data['content'])) {
                    $content = $data['content'];
                    update_option('sag_affirmation_content', $content);
                }
            }
        }
        
        return $content;
    }
    
    // === STORY GENERATOR FUNCTIONALITY ===
    private function render_story_generator() {
        $result = $this->process_story_form();
        $errors = $result['errors'];
        $story_data = $result['story_data'];
        $content = $result['content'] ?? $this->get_story_content_subsite();
        
        if (!$content) {
            return '<div class="error">Story generator content not available. Please contact the administrator.</div>';
        }
        
        $genres = [
            "action_adventure" => "Action/Adventure",
            "biography_drama" => "Biography/Drama",
            "comedy_musical" => "Comedy/Musical",
            "fantasy_scifi" => "Fantasy/Science Fiction",
            "spirituality" => "Spirituality"
        ];
        
        ob_start();
        ?>
        <div class="sag-story-generator">
            <style>
            .sag-story-generator {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 600px;
                margin: 0 auto;
            }
            .sag-story-generator form {
                background: white;
                padding: 25px 30px;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .sag-story-generator label {
                display: block;
                margin-top: 20px;
                font-weight: 600;
            }
            .sag-story-generator input[type="text"],
            .sag-story-generator select {
                width: 100%;
                padding: 10px;
                margin-top: 5px;
                border: 1px solid #ccc;
                border-radius: 6px;
                font-size: 1rem;
                box-sizing: border-box;
            }
            .sag-story-generator button {
                margin-top: 25px;
                padding: 12px 20px;
                background-color: #6a1b9a;
                color: white;
                font-size: 1rem;
                font-weight: bold;
                border: none;
                border-radius: 6px;
                cursor: pointer;
            }
            .sag-story-generator .error {
                background: #ffecec;
                border: 1px solid #f5c2c2;
                color: #d33;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .sag-story-generator .story {
                background: #fff;
                border-left: 6px solid #7d6bb5;
                padding: 20px 25px;
                border-radius: 8px;
                margin-top: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            }
            </style>
            
             if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                     foreach ($errors as $err): ?>
                        <li> echo esc_html($err); ?></li>
                     endforeach; ?>
                    </ul>
                </div>
             endif; ?>
            
            <form method="POST" action="">
                <label for="hero_name">What is the hero's name?</label>
                <input type="text" id="hero_name" name="hero_name" value=" echo esc_attr($story_data['heroName']); ?>" required />
                
                <label for="genre">What genre is your movie?</label>
                <select id="genre" name="genre" required>
                    <option value="">Select genre</option>
                     foreach ($genres as $key => $label): ?>
                        <option value=" echo esc_attr($key); ?>"  selected($story_data['genre'], $key); ?>>
                             echo esc_html($label); ?>
                        </option>
                     endforeach; ?>
                </select>
                
                <label for="purpose">Which statement best reflects your purpose?</label>
                <select id="purpose" name="purpose" required>
                    <option value="">Select purpose</option>
                     foreach ($content['purposes'] as $item): ?>
                        <option value=" echo esc_attr($item); ?>"  selected($story_data['purpose'], $item); ?>>
                             echo esc_html($item); ?>
                        </option>
                     endforeach; ?>
                </select>
                
                <label for="life_goal">What would you like to be remembered for?</label>
                <select id="life_goal" name="life_goal" required>
                    <option value="">Select legacy</option>
                     foreach ($content['life_goals'] as $item): ?>
                        <option value=" echo esc_attr($item); ?>"  selected($story_data['lifeGoal'], $item); ?>>
                             echo esc_html($item); ?>
                        </option>
                     endforeach; ?>
                </select>
                
                <label for="obstacle">What are the obstacles you're currently facing?</label>
                <select id="obstacle" name="obstacle" required>
                    <option value="">Select obstacle</option>
                     foreach ($content['obstacles'] as $item): ?>
                        <option value=" echo esc_attr($item); ?>"  selected($story_data['obstacle'], $item); ?>>
                             echo esc_html($item); ?>
                        </option>
                     endforeach; ?>
                </select>
                
                <label for="adjective">Which word best describes your personality?</label>
                <select id="adjective" name="adjective" required>
                    <option value="">Select personality</option>
                     foreach ($content['adjectives'] as $item): ?>
                        <option value=" echo esc_attr($item); ?>"  selected($story_data['adjective'], $item); ?>>
                             echo esc_html($item); ?>
                        </option>
                     endforeach; ?>
                </select>
                
                <label for="archetype">Which word best describes your persona?</label>
                <select id="archetype" name="archetype" required>
                    <option value="">Select persona</option>
                     foreach (array_unique($content['archetypes']) as $item): ?>
                        <option value=" echo esc_attr($item); ?>"  selected($story_data['archetype'], $item); ?>>
                             echo esc_html(ucfirst($item)); ?>
                        </option>
                     endforeach; ?>
                </select>
                
                <button type="submit">Generate Story</button>
            </form>
            
             if ($story_data['story_text']): ?>
                <div class="story">
                    <h2>A peek into your quantum reality:</h2>
                    <p> echo nl2br(esc_html($story_data['story_text'])); ?></p>
                </div>
                
                <div class="story">
                    <h2>Reflections for embodying your quantum reality</h2>
                    <h3>Embracing  echo esc_html($story_data['positive']); ?>:</h3>
                    <ul>
                        <li>How does it feel when  echo esc_html($story_data['obstacle']); ?> melt away?</li>
                        <li>How are you experiencing abundance in your quantum reality where you successfully  echo esc_html($story_data['lifeGoal']); ?>?</li>
                        <li>How are others experiencing abundance just because you endeavoured to  echo esc_html($story_data['purpose']); ?>?</li>
                        <li>What practices will you,  echo esc_html($story_data['heroName']); ?>, take daily to embody this new reality?</li>
                        <li>What advice does the future  echo esc_html($story_data['heroName']); ?> have for you as you navigate your now?</li>
                    </ul>
                    
                    <h3>Daily recalibration</h3>
                    <ul>
                        <li>What actions are you inspired to take daily for  echo esc_html($story_data['positive']); ?>?</li>
                        <li>How is life surprising you each day with people, situations, or opportunities to  echo esc_html($story_data['purpose']); ?>?</li>
                        <li>How does it feel knowing that a quantum reality already exists where you've  echo esc_html($story_data['lifeGoal']); ?>?</li>
                    </ul>
                </div>
             endif; ?>
        </div>
        
        
        return ob_get_clean();
    }
    
    private function process_story_form() {
        $content = $this->get_story_content_subsite();
        $errors = [];
        $story_data = get_option('sag_site_story', [
            'genre' => '',
            'heroName' => '',
            'purpose' => '',
            'lifeGoal' => '',
            'obstacle' => '',
            'adjective' => '',
            'archetype' => '',
            'story_text' => '',
            'positive' => ''
        ]);
        
        if (!$content) {
            $errors[] = "Content not available. Please contact administrator.";
            return ['errors' => $errors, 'story_data' => $story_data];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $story_data['heroName'] = trim($_POST['hero_name'] ?? '');
            $story_data['genre'] = $_POST['genre'] ?? '';
            $story_data['purpose'] = $_POST['purpose'] ?? '';
            $story_data['lifeGoal'] = $_POST['life_goal'] ?? '';
            $story_data['obstacle'] = $_POST['obstacle'] ?? '';
            $story_data['adjective'] = $_POST['adjective'] ?? '';
            $story_data['archetype'] = $_POST['archetype'] ?? '';
            
            // Validation
            if ($story_data['heroName'] === '') {
                $errors[] = "Please enter the hero's name.";
            }
            
            $genres = [
                "action_adventure" => "Action/Adventure",
                "biography_drama" => "Biography/Drama",
                "comedy_musical" => "Comedy/Musical",
                "fantasy_scifi" => "Fantasy/Science Fiction",
                "spirituality" => "Spirituality"
            ];
            
            if (!array_key_exists($story_data['genre'], $genres)) {
                $errors[] = "Please select a valid genre.";
            }
            if (!in_array($story_data['purpose'], $content['purposes'])) {
                $errors[] = "Please select a valid purpose.";
            }
            if (!in_array($story_data['lifeGoal'], $content['life_goals'])) {
                $errors[] = "Please select a valid legacy.";
            }
            if (!in_array($story_data['obstacle'], $content['obstacles'])) {
                $errors[] = "Please select a valid obstacle.";
            }
            if (!in_array($story_data['adjective'], $content['adjectives'])) {
                $errors[] = "Please select a valid personality.";
            }
            if (!in

// Continue validation from where it was cut off
        if (!in_array($story_data['archetype'], $content['archetypes'])) {
            $errors[] = "Please select a valid persona.";
        }

        if (empty($errors)) {
            // Find matching positive for the obstacle
            $obstacle_index = array_search($story_data['obstacle'], $content['obstacles']);
            $story_data['positive'] = ($obstacle_index !== false && isset($content['positives'][$obstacle_index])) 
                ? $content['positives'][$obstacle_index] 
                : '';
            
            $story_data['story_text'] = $this->generate_story_text(
                $story_data['genre'], 
                $story_data['heroName'], 
                $story_data['purpose'], 
                $story_data['lifeGoal'], 
                $story_data['obstacle'], 
                $story_data['adjective'], 
                $story_data['archetype'], 
                $content['templates']
            );
            
            update_option('sag_site_story', $story_data);
        }
    }
    
    return ['errors' => $errors, 'story_data' => $story_data, 'content' => $content];
}

private function generate_story_text($genre, $heroName, $purpose, $lifeGoal, $obstacle, $adjective, $archetype, $templates) {
    if (!isset($templates[$genre])) {
        return "Sorry, no templates available for the selected genre.";
    }
    
    $genre_templates = $templates[$genre];
    $template = $genre_templates[array_rand($genre_templates)];

    $story = str_replace(
        ['$heroName', '$purpose', '$lifeGoal', '$obstacle', '$adjective', '$archetype'],
        [
            esc_html($heroName),
            esc_html($purpose),
            esc_html($lifeGoal),
            esc_html($obstacle),
            esc_html($adjective),
            esc_html($archetype)
        ],
        $template
    );
    
    return $story;
}

private function display_story_generator() {
    $result = $this->process_story_form();
    $errors = $result['errors'];
    $story_data = $result['story_data'];
    $content = $result['content'] ?? $this->get_story_content_subsite();
    
    if (!$content) {
        return '<div class="error">Story generator content not available. Please contact the administrator.</div>';
    }
    
    $genres = [
        "action_adventure" => "Action/Adventure",
        "biography_drama" => "Biography/Drama",
        "comedy_musical" => "Comedy/Musical",
        "fantasy_scifi" => "Fantasy/Science Fiction",
        "spirituality" => "Spirituality"
    ];
    
    ob_start();
    ?>
    <style>
    .sag-form {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .sag-form label {
        display: block;
        margin-top: 20px;
        font-weight: 600;
    }
    .sag-form input[type="text"],
    .sag-form select {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 1rem;
        box-sizing: border-box;
    }
    .sag-form button {
        margin-top: 25px;
        padding: 12px 20px;
        background-color: #6a1b9a;
        color: white;
        font-size: 1rem;
        font-weight: bold;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }
    .sag-error {
        background: #ffecec;
        border: 1px solid #f5c2c2;
        color: #d33;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .sag-story {
        background: #fff;
        border-left: 6px solid #7d6bb5;
        padding: 20px 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-top: 30px;
    }
    </style>

     if (!empty($errors)): ?>
        <div class="sag-error">
            <ul>
             foreach ($errors as $err): ?>
                <li> echo esc_html($err); ?></li>
             endforeach; ?>
            </ul>
        </div>
     endif; ?>

    <form method="POST" class="sag-form">
        <label for="hero_name">What is the hero's name?</label>
        <input type="text" id="hero_name" name="hero_name" value=" echo esc_attr($story_data['heroName']); ?>" required />

        <label for="genre">What genre is your movie?</label>
        <select id="genre" name="genre" required>
            <option value="">Select genre</option>
             foreach ($genres as $key => $label): ?>
                <option value=" echo esc_attr($key); ?>"  selected($story_data['genre'], $key); ?>>
                     echo esc_html($label); ?>
                </option>
             endforeach; ?>
        </select>

        <label for="purpose">Which statement best reflects your purpose?</label>
        <select id="purpose" name="purpose" required>
            <option value="">Select purpose</option>
             foreach ($content['purposes'] as $item): ?>
                <option value=" echo esc_attr($item); ?>"  selected($story_data['purpose'], $item); ?>>
                     echo esc_html($item); ?>
                </option>
             endforeach; ?>
        </select>

        <label for="life_goal">What would you like to be remembered for?</label>
        <select id="life_goal" name="life_goal" required>
            <option value="">Select legacy</option>
             foreach ($content['life_goals'] as $item): ?>
                <option value=" echo esc_attr($item); ?>"  selected($story_data['lifeGoal'], $item); ?>>
                     echo esc_html($item); ?>
                </option>
             endforeach; ?>
        </select>

        <label for="obstacle">What are the obstacles you're currently facing?</label>
        <select id="obstacle" name="obstacle" required>
            <option value="">Select obstacle</option>
             foreach ($content['obstacles'] as $item): ?>
                <option value=" echo esc_attr($item); ?>"  selected($story_data['obstacle'], $item); ?>>
                     echo esc_html($item); ?>
                </option>
             endforeach; ?>
        </select>

        <label for="adjective">Which word best describes your personality?</label>
        <select id="adjective" name="adjective" required>
            <option value="">Select personality</option>
             foreach ($content['adjectives'] as $item): ?>
                <option value=" echo esc_attr($item); ?>"  selected($story_data['adjective'], $item); ?>>
                     echo esc_html($item); ?>
                </option>
             endforeach; ?>
        </select>

        <label for="archetype">Which word best describes your persona?</label>
        <select id="archetype" name="archetype" required>
            <option value="">Select persona</option>
             foreach (array_unique($content['archetypes']) as $item): ?>
                <option value=" echo esc_attr($item); ?>"  selected($story_data['archetype'], $item); ?>>
                     echo esc_html(ucfirst($item)); ?>
                </option>
             endforeach; ?>
        </select>

        <button type="submit">Generate Story</button>
    </form>

     if ($story_data['story_text']): ?>
        <div class="sag-story">
            <h2>A peek into your quantum reality:</h2>
            <p> echo nl2br(esc_html($story_data['story_text'])); ?></p>
        </div>
        <div class="sag-story">
            <h2>Reflections for embodying your quantum reality</h2>
            <h3>Embracing  echo esc_html($story_data['positive']); ?>:</h3>
            <ul>
                <li>How does it feel when  echo esc_html($story_data['obstacle']); ?> melt away?</li>
                <li>How are you experiencing abundance in your quantum reality where you successfully  echo esc_html($story_data['lifeGoal']); ?>?</li>
                <li>How are others experiencing abundance just because you endeavoured to  echo esc_html($story_data['purpose']); ?>?</li>
                <li>What practices will you,  echo esc_html($story_data['heroName']); ?>, take daily to embody this new reality?</li>
                <li>What advice does the future  echo esc_html($story_data['heroName']); ?> have for you as you navigate your now?</li>
            </ul>
            
            <h3>Daily recalibration</h3>
            <ul>
                <li>What actions are you inspired to take daily for  echo esc_html($story_data['positive']); ?>?</li>
                <li>How is life surprising you each day with people, situations, or opportunities to  echo esc_html($story_data['purpose']); ?>?</li>
                <li>How does it feel knowing that a quantum reality already exists where you've  echo esc_html($story_data['lifeGoal']); ?>?</li>
            </ul>
        </div>
     endif; ?>

    
    return ob_get_clean();
}

// AFFIRMATION GENERATOR METHODS
private function get_affirmation_content_subsite() {
    $content = get_option('sag_affirmation_content');
    
    if (!$content || empty($content)) {
        // Try fetching from main site REST endpoint
        $main_site_url = network_site_url();
        $response = wp_remote_get(trailingslashit($main_site_url) . 'wp-json/story-affirmation-generator/v1/affirmation-content');
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if ($data && isset($data['content'])) {
                $content = $data['content'];
                update_option('sag_affirmation_content', $content);
            }
        }
        
        if (!$content) {
            $content = [
                'health_obstacles' => [],
                'health_positives' => [],
                'wealth_obstacles' => [],
                'wealth_positives' => [],
                'relationship_obstacles' => [],
                'relationship_positives' => [],
                'template' => 'I am embracing $positive.'
            ];
        }
    }
    
    return $content;
}

private function display_affirmation_generator() {
    $content = $this->get_affirmation_content_subsite();

    ob_start();
    ?>
    <style>
    .sag-affirmation-form {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .sag-desire-select {
        width: 100%;
        height: 100px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    .sag-affirmation-btn {
        background: #0073aa;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        margin-right: 10px;
    }
    .sag-save-btn {
        background: #00a32a;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }
    .sag-affirmation-output {
        margin: 15px 0;
        padding: 15px;
        background: #f9f9f9;
        border-left: 4px solid #0073aa;
        font-style: italic;
        min-height: 20px;
    }
    .sag-summary-textarea {
        width: 100%;
        height: 80px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    </style>

    <div class="sag-affirmation-form">
        <form id="sag-affirmation-form">
            <h3>Select up to 10 total desires:</h3>

            <label>Health:</label><br>
            <select multiple name="health[]" class="sag-desire-select">
                 foreach ($content['health_positives'] as $positive): ?>
                    <option value=" echo esc_attr($positive); ?>"> echo esc_html($positive); ?></option>
                 endforeach; ?>
            </select>

            <label>Wealth:</label><br>
            <select multiple name="wealth[]" class="sag-desire-select">
                 foreach ($content['wealth_positives'] as $positive): ?>
                    <option value=" echo esc_attr($positive); ?>"> echo esc_html($positive); ?></option>
                 endforeach; ?>
            </select>

            <label>Relationships:</label><br>
            <select multiple name="relationships[]" class="sag-desire-select">
                 foreach ($content['relationship_positives'] as $positive): ?>
                    <option value=" echo esc_attr($positive); ?>"> echo esc_html($positive); ?></option>
                 endforeach; ?>
            </select>

            <button type="button" onclick="sagGenerateAffirmation()" class="sag-affirmation-btn">Generate Affirmation</button>

            <h4>Your Affirmation:</h4>
            <div id="sag-generated-affirmation" class="sag-affirmation-output"></div>

            <label>Summarise your affirmation (max 255 characters):</label><br>
            <textarea id="sag-user-summary" maxlength="255" class="sag-summary-textarea" placeholder="Enter your personal summary of this affirmation..."></textarea>

            <button type="submit" class="sag-save-btn">Save Summary</button>
        </form>

        <div id="sag-save-status" style="margin-top: 10px;"></div>
    </div>

    <script>
    function sagGenerateAffirmation() {
        const selects = document.querySelectorAll(".sag-desire-select");
        let selectedValues = [];
        
        selects.forEach(select => {
            [...select.selectedOptions].forEach(option => selectedValues.push(option.value));
        });
        
        if (selectedValues.length === 0) {
            alert("Please select at least one desire.");
            return;
        }
        
        if (selectedValues.length > 10) {
            alert("Please select no more than 10 desires in total.");
            return;
        }
        
        const template = " echo esc_js($content['template']); ?>";
        let affirmation;
        
        if (selectedValues.length === 1) {
            affirmation = template.replace("$positive", selectedValues[0]);
        } else {
            const positiveText = selectedValues.slice(0, -1).join(", ") + " and " + selectedValues[selectedValues.length - 1];
            affirmation = template.replace("$positive", positiveText);
        }
        
        document.getElementById("sag-generated-affirmation").innerHTML = "<strong>" + affirmation + "</strong>";
    }

    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("sag-affirmation-form").addEventListener("submit", function(e) {
            e.preventDefault();
            
            const summary = document.getElementById("sag-user-summary").value.trim();
            if (summary.length === 0) {
                alert("Please enter a summary.");
                return;
            }
            
            const statusDiv = document.getElementById("sag-save-status");
            statusDiv.innerHTML = "Saving...";
            statusDiv.style.color = "#0073aa";
            
            const data = new URLSearchParams();
            data.append("action", "sag_save_affirmation_summary");
            data.append("summary", summary);
            data.append("nonce", " echo wp_create_nonce('sag_save_affirmation_summary'); ?>");
            
            fetch(" echo admin_url('admin-ajax.php'); ?>", {
                method: "POST",
                credentials: "same-origin",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    statusDiv.style.color = "#00a32a";
                    statusDiv.innerHTML = "✓ Summary saved successfully!";
                    document.getElementById("sag-user-summary").value = "";
                } else {
                    statusDiv.style.color = "#d63638";
                    statusDiv.innerHTML = "Failed to save: " + (result.data || "Unknown error");
                }
            })
            .catch(error => {
                statusDiv.style.color = "#d63638";
                statusDiv.innerHTML = "Failed to save summary. Please try again.";
            });
        });
    });
    </script>
    
    return ob_get_clean();
}

// AJAX HANDLERS
public function ajax_save_affirmation_summary() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sag_save_affirmation_summary')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!isset($_POST['summary'])) {
        wp_send_json_error('Missing summary');
        return;
    }
    
    $summary = sanitize_text_field($_POST['summary']);
    
    if (empty($summary)) {
        wp_send_json_error('Summary cannot be empty');
        return;
    }
    
    update_option('sag_affirmation_summary', $summary);
    update_option('sag_affirmation_summary_date', current_time('mysql'));
    
    wp_send_json_success('Summary saved successfully');
}

// SHORTCODE HANDLERS
public function story_generator_shortcode($atts) {
    return $this->display_story_generator();
}

public function affirmation_generator_shortcode($atts) {
    return $this->display_affirmation_generator();
}

// ACTIVATION/DEACTIVATION
public function activate() {
    if (is_main_site()) {
        $story_content = $this->get_story_content();
        update_option('sag_story_content', $story_content);
        
        $affirmation_content = $this->get_affirmation_content();
        update_option('sag_affirmation_content', $affirmation_content);
    }
}

public function deactivate() {
    // Clean up if needed
}

}

// Initialize the plugin
new Story_Affirmation_Generator();

?>