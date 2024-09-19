<?php

    function list_unused_images() {
        global $wpdb;

        // Get all image attachments
        $all_images = $wpdb->get_col("
            SELECT ID FROM $wpdb->posts
            WHERE post_type = 'attachment'
            AND post_mime_type LIKE 'image/%'
        ");

        // Get images used as featured images
        $featured_images = $wpdb->get_col("
            SELECT meta_value FROM $wpdb->postmeta
            WHERE meta_key = '_thumbnail_id'
        ");

        // Get content of all published posts and pages
        $post_contents = $wpdb->get_results("
            SELECT post_content FROM $wpdb->posts
            WHERE post_status = 'publish'
            AND (post_type = 'post' OR post_type = 'page')
        ");

        $used_images_in_content = array();

        // Regex pattern to match image IDs in post content
        $pattern = '/wp-image-([0-9]+)/';

        foreach ($post_contents as $post) {
            if (preg_match_all($pattern, $post->post_content, $matches)) {
                $used_images_in_content = array_merge($used_images_in_content, $matches[1]);
            }
        }

        // Merge all used image IDs
        $used_images = array_unique(array_merge($featured_images, $used_images_in_content));

        // Find unused images
        $unused_images = array_diff($all_images, $used_images);

        // Prepare log file path
        $log_file = WP_CONTENT_DIR . '/unused_images_log.txt';

        if (!empty($unused_images)) {
            // Create or open the log file for writing
            $file_handle = fopen($log_file, 'w');
            if ($file_handle) {
                // Write unused image IDs to the file
                fwrite($file_handle, "Unused Images Log:\n\n");
                foreach ($unused_images as $image_id) {
                    $image = get_post($image_id);
                    $title = $image->post_title;
                    $line = "Image Title: " . esc_html($title) . " | ID: " . $image_id . "\n";
                    fwrite($file_handle, $line);
                }
                fclose($file_handle);

                // Admin notice for successful logging
                echo '<div class="notice notice-warning"><p><strong>Unused images have been logged to wp-content/unused_images_log.txt</strong></p></div>';
            } else {
                // Error notice if log file cannot be written
                echo '<div class="notice notice-error"><p>Unable to write log file.</p></div>';
            }
        } else {
            echo '<div class="notice notice-success"><p>No unused images found.</p></div>';
        }
    }
    add_action('admin_notices', 'list_unused_images');



?>