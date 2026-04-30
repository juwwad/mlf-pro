<?php
if (!defined('ABSPATH')) exit;

class MLF_Dashboard {

    public function __construct(){
        add_shortcode('mlf_dashboard', [$this,'render']);
        add_shortcode('mlf_listings', [$this,'render']);
        add_action('wp_ajax_mlf_action', [$this,'ajax']);
        add_action('wp_ajax_nopriv_mlf_action', [$this,'ajax']);
    }

    // Main shortcode for displaying listings
    public function render($atts){
        $atts = shortcode_atts([
            'title' => 'Listing Manager',
            'show_stats' => 'yes',
            'status' => '',
            'posts_per_page' => 12,
            'columns' => 3,
        ], $atts);
        
        ob_start();
        
        $primary_color = get_option('mlf_primary_color', '#95160c');
        
        $posts = get_posts([
            'post_type'=>'job_listing',
            'numberposts'=>-1,
            'post_status'=>['publish', 'pending', 'draft']
        ]);
        
        $pending = 0;
        $publish = 0;
        $draft = 0;
        
        foreach($posts as $p){
            if($p->post_status == 'pending') $pending++;
            elseif($p->post_status == 'publish') $publish++;
            elseif($p->post_status == 'draft') $draft++;
        }
        
        ?>
        <div class="mlf-container">
            <div class="mlf-header">
                <h1><?php echo esc_html($atts['title']); ?></h1>
                <?php if($atts['show_stats'] === 'yes'): ?>
                <div class="mlf-stats">
                    <div class="mlf-stat-box">
                        <div class="count"><?php echo count($posts); ?></div>
                        <div class="label">Total</div>
                    </div>
                    <div class="mlf-stat-box">
                        <div class="count"><?php echo $pending; ?></div>
                        <div class="label">Pending</div>
                    </div>
                    <div class="mlf-stat-box">
                        <div class="count"><?php echo $publish; ?></div>
                        <div class="label">Published</div>
                    </div>
                    <div class="mlf-stat-box">
                        <div class="count"><?php echo $draft; ?></div>
                        <div class="label">Draft</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="mlf-grid" id="mlf-cards-grid">
                <?php if(empty($posts)): ?>
                    <div class="mlf-empty">
                        <div class="mlf-empty-icon">📋</div>
                        <p>No listings found</p>
                    </div>
                <?php else: ?>
                    <?php foreach($posts as $p): 
                        $meta = get_post_meta($p->ID);
                        $email = isset($meta['email'][0]) ? $meta['email'][0] : '';
                        $phone = isset($meta['phone'][0]) ? $meta['phone'][0] : '';
                        $company = isset($meta['company'][0]) ? $meta['company'][0] : '';
                        $location = isset($meta['complete-address'][0]) ? $meta['complete-address'][0] : '';
                        $name = $p->post_title;
                        $initial = strtoupper(substr($name, 0, 1));
                        $status_class = $p->post_status == 'publish' ? 'publish' : ($p->post_status == 'pending' ? 'pending' : 'draft');
                        $status_label = $p->post_status == 'publish' ? 'Published' : ($p->post_status == 'pending' ? 'Pending' : 'Draft');
                    ?>
                    <div class="mlf-user-card" data-id="<?php echo $p->ID; ?>">
                        <div class="card-content" onclick="mlfOpenDetail(<?php echo $p->ID; ?>)">
                            <div class="avatar"><?php echo $initial; ?></div>
                            <h3><?php echo esc_html($name); ?></h3>
                            <?php if($company): ?>
                            <p class="meta-info company"><?php echo esc_html($company); ?></p>
                            <?php endif; ?>
                            <p class="meta-info"><?php echo $email ? esc_html($email) : ''; ?></p>
                            <?php if($phone): ?>
                            <p class="meta-info phone"><?php echo esc_html($phone); ?></p>
                            <?php endif; ?>
                            <?php if($location): ?>
                            <p class="meta-info location"><?php echo esc_html($location); ?></p>
                            <?php endif; ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                        </div>
                        <div class="card-actions" onclick="event.stopPropagation();">
                            <?php if($p->post_status != 'publish'): ?>
                            <button class="mlf-card-btn mlf-btn-approve" onclick="mlfCardAction(<?php echo $p->ID; ?>, 'approve')">✓ Approve</button>
                            <?php endif; ?>
                            <?php if($p->post_status != 'draft'): ?>
                            <button class="mlf-card-btn mlf-btn-reject" onclick="mlfCardAction(<?php echo $p->ID; ?>, 'reject')">✗ Reject</button>
                            <?php endif; ?>
                            <button class="mlf-card-btn mlf-btn-delete" onclick="mlfCardAction(<?php echo $p->ID; ?>, 'trash')">🗑 Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Detail Modal -->
        <div class="mlf-modal" id="mlf-detail-modal">
            <div class="mlf-modal-content">
                <div class="mlf-modal-header">
                    <h2 id="mlf-modal-title">Listing Details</h2>
                    <button class="mlf-modal-close" onclick="mlfCloseModal()">&times;</button>
                </div>
                <div class="mlf-modal-body" id="mlf-modal-body">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
        
        <script>
        function mlfRemoveCardFromDom(id) {
            ['.mlf-user-card', '.mlf-listing-card', '.mlf-elementor-card'].forEach(function(selector) {
                var card = document.querySelector(selector + '[data-id="' + id + '"]');
                if(card) {
                    card.remove();
                }
            });
        }

        function mlfOpenDetail(id) {
            var modal = document.getElementById('mlf-detail-modal');
            var body = document.getElementById('mlf-modal-body');
            var title = document.getElementById('mlf-modal-title');
            
            body.innerHTML = '<div class="mlf-loading"><div class="mlf-spinner"></div></div>';
            modal.classList.add('active');
            
            // Fetch listing data
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mlf_get_detail',
                    id: id
                },
                success: function(response) {
                    if(response.success) {
                        var data = response.data;
                        title.textContent = data.title;
                        body.innerHTML = data.detail_html || '<div class="mlf-error">No detail content available.</div>';
                    }
                }
            });
        }
        
        function mlfCloseModal() {
            document.getElementById('mlf-detail-modal').classList.remove('active');
        }
        
        function mlfAction(id, type) {
            if(!confirm('Are you sure you want to ' + type + ' this listing?')) return;
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mlf_action',
                    id: id,
                    type: type
                },
                success: function(response) {
                    if(response.success) {
                        if(type === 'trash') {
                            mlfRemoveCardFromDom(id);
                            mlfCloseModal();
                            return;
                        }

                        location.reload();
                    }
                }
            });
        }
        
        // Close modal on outside click
        document.getElementById('mlf-detail-modal').addEventListener('click', function(e) {
            if(e.target === this) {
                mlfCloseModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                mlfCloseModal();
            }
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    public function ajax(){
        $id = intval($_POST['id']);
        $type = sanitize_text_field($_POST['type']);
        
        $post = get_post($id);
        if(!$post) {
            wp_send_json_error('Post not found');
        }
        
        $customer_email = get_post_meta($id, 'email', true);
        
        if($type=='approve'){
            wp_update_post(['ID'=>$id,'post_status'=>'publish']);
            
            // Send approval email to customer
            if($customer_email) {
                $email_template = get_option('mlf_email_approved');
                if($email_template) {
                    $body = str_replace(
                        ['{{listing_title}}', '{{listing_url}}', '{{admin_email}}'],
                        [$post->post_title, get_permalink($id), get_option('admin_email')],
                        $email_template
                    );
                    wp_mail(
                        $customer_email,
                        'Your Listing Has Been Approved',
                        $body,
                        ['Content-Type: text/html; charset=UTF-8']
                    );
                }
            }
        }
        
        if($type=='reject'){
            wp_update_post(['ID'=>$id,'post_status'=>'draft']);
            
            // Send rejection email to customer
            if($customer_email) {
                $email_template = get_option('mlf_email_rejected');
                if($email_template) {
                    $body = str_replace(
                        ['{{listing_title}}', '{{listing_url}}', '{{admin_email}}'],
                        [$post->post_title, get_permalink($id), get_option('admin_email')],
                        $email_template
                    );
                    wp_mail(
                        $customer_email,
                        'Your Listing Status Has Been Updated',
                        $body,
                        ['Content-Type: text/html; charset=UTF-8']
                    );
                }
            }
        }
        
        if($type=='trash'){
            wp_trash_post($id);
        }
        
        wp_send_json_success();
    }
    
    public function get_detail(){
        $id = intval($_POST['id']);
        $post = get_post($id);
        
        if(!$post) {
            wp_send_json_error('Post not found');
        }
        
        $meta = get_post_meta($id);
        $meta_array = [];
        
        // Helper function to decode serialized PHP data
        function mlf_decode_value($value) {
            if (empty($value) && $value !== '0' && $value !== 0) {
                return $value;
            }
            
            // Convert 0/1 to Yes/No
            if ($value === '1' || $value === 1 || $value === 'true') {
                return 'Yes';
            }
            if ($value === '0' || $value === 0 || $value === 'false') {
                return 'No';
            }
            
            // Check if it looks like serialized PHP array (a:...)
            if (is_string($value) && preg_match('/^a:\d+:/', $value)) {
                $decoded = @unserialize($value);
                if ($decoded !== false) {
                    // Format the decoded array into readable text
                    $output = [];
                    foreach ($decoded as $day => $data) {
                        if (is_array($data) && isset($data['status'])) {
                            $status = $data['status'];
                            $hours = isset($data['hours']) ? $data['hours'] : '';
                            
                            // Make status human readable
                            $status_text = '';
                            switch ($status) {
                                case 'by-appointment-only':
                                    $status_text = 'By Appointment Only';
                                    break;
                                case 'enter-hours':
                                    $status_text = $hours ? $hours : 'Enter Hours';
                                    break;
                                case 'closed':
                                    $status_text = 'Closed';
                                    break;
                                default:
                                    $status_text = ucfirst(str_replace('-', ' ', $status));
                            }
                            
                            $output[] = "$day: $status_text";
                        } else {
                            $output[] = "$day: " . (is_array($data) ? json_encode($data) : $data);
                        }
                    }
                    return implode("\n", $output);
                }
            }
            
            return $value;
        }
        
        foreach($meta as $k => $v) {
            if(!in_array($k, ['_edit_lock', '_edit_last'])) {
                $value = is_array($v) ? $v[0] : $v;
                // Decode serialized PHP data
                $meta_array[$k] = mlf_decode_value($value);
            }
        }
        
        $status_class = $post->post_status == 'publish' ? 'publish' : ($post->post_status == 'pending' ? 'pending' : 'draft');
        $status_label = $post->post_status == 'publish' ? 'Published' : ($post->post_status == 'pending' ? 'Pending' : 'Draft');
        
        wp_send_json_success([
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'status_class' => $status_class,
            'status_label' => $status_label,
            'date' => get_the_date('F j, Y', $post),
            'meta' => $meta_array,
            'post_status' => $post->post_status
        ]);
    }
}

if (!function_exists('mlf_detail_label')) {
    function mlf_detail_label($key) {
        return ucwords(str_replace(['-', '_'], ' ', $key));
    }
}

if (!function_exists('mlf_detail_is_empty_value')) {
    function mlf_detail_is_empty_value($value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!mlf_detail_is_empty_value($item)) {
                    return false;
                }
            }

            return true;
        }

        if (is_object($value)) {
            return mlf_detail_is_empty_value((array) $value);
        }

        return trim((string) $value) === '';
    }
}

if (!function_exists('mlf_detail_is_image_source')) {
    function mlf_detail_is_image_source($value) {
        if (is_numeric($value) && wp_attachment_is_image((int) $value)) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)(\?.*)?$/i', trim($value));
    }
}

if (!function_exists('mlf_detail_image_sources')) {
    function mlf_detail_image_sources($value, $size = 'thumbnail') {
        $sources = ['thumbnail' => '', 'full' => ''];

        if (is_numeric($value) && wp_attachment_is_image((int) $value)) {
            $thumb = wp_get_attachment_image_src((int) $value, $size);
            $full = wp_get_attachment_image_src((int) $value, 'full');
            $sources['thumbnail'] = $thumb ? $thumb[0] : '';
            $sources['full'] = $full ? $full[0] : $sources['thumbnail'];
            return $sources;
        }

        if (!is_string($value)) {
            return $sources;
        }

        $value = trim($value);
        if ($value === '') {
            return $sources;
        }

        $attachment_id = attachment_url_to_postid($value);
        if ($attachment_id) {
            $thumb = wp_get_attachment_image_src($attachment_id, $size);
            $full = wp_get_attachment_image_src($attachment_id, 'full');
            $sources['thumbnail'] = $thumb ? $thumb[0] : $value;
            $sources['full'] = $full ? $full[0] : $value;
            return $sources;
        }

        $sources['thumbnail'] = $value;
        $sources['full'] = $value;
        return $sources;
    }
}

if (!function_exists('mlf_detail_normalize_value')) {
    function mlf_detail_normalize_value($key, $value) {
        $label = mlf_detail_label($key);

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_string($value)) {
            $value = maybe_unserialize($value);
        }

        if (is_array($value)) {
            $items = [];

            foreach ($value as $item) {
                if (mlf_detail_is_empty_value($item)) {
                    continue;
                }

                if (is_object($item)) {
                    $item = (array) $item;
                }

                if (is_array($item)) {
                    $candidate = '';

                    foreach (['thumbnail', 'thumb', 'src', 'url', 'image', 'full'] as $candidate_key) {
                        if (!empty($item[$candidate_key])) {
                            $candidate = $item[$candidate_key];
                            break;
                        }
                    }

                    if ($candidate !== '') {
                        $sources = mlf_detail_image_sources($candidate);
                        $items[] = [
                            'type' => mlf_detail_is_image_source($candidate) ? 'image' : 'text',
                            'text' => $candidate,
                            'url' => $candidate,
                            'thumbnail' => $sources['thumbnail'],
                            'full' => $sources['full'],
                        ];
                        continue;
                    }

                    $flattened = array_filter(array_map('trim', array_map('strval', $item)), 'strlen');
                    if (!empty($flattened)) {
                        $items[] = [
                            'type' => 'text',
                            'text' => implode(', ', $flattened),
                        ];
                    }

                    continue;
                }

                $item = trim((string) $item);
                if ($item === '') {
                    continue;
                }

                if (filter_var($item, FILTER_VALIDATE_EMAIL)) {
                    $items[] = [
                        'type' => 'link',
                        'text' => $item,
                        'url' => 'mailto:' . $item,
                    ];
                    continue;
                }

                if (filter_var($item, FILTER_VALIDATE_URL) || preg_match('/^www\./i', $item)) {
                    $url = preg_match('/^www\./i', $item) ? 'https://' . $item : $item;

                    if (mlf_detail_is_image_source($item)) {
                        $sources = mlf_detail_image_sources($item);
                        $items[] = [
                            'type' => 'image',
                            'text' => $item,
                            'url' => $url,
                            'thumbnail' => $sources['thumbnail'],
                            'full' => $sources['full'],
                        ];
                    } else {
                        $items[] = [
                            'type' => 'link',
                            'text' => $item,
                            'url' => $url,
                        ];
                    }

                    continue;
                }

                if (mlf_detail_is_image_source($item)) {
                    $sources = mlf_detail_image_sources($item);
                    $items[] = [
                        'type' => 'image',
                        'text' => $item,
                        'url' => $item,
                        'thumbnail' => $sources['thumbnail'],
                        'full' => $sources['full'],
                    ];
                    continue;
                }

                $items[] = [
                    'type' => 'text',
                    'text' => $item,
                ];
            }

            if (empty($items)) {
                return null;
            }

            if (count($items) === 1) {
                return $items[0];
            }

            $types = array_unique(array_map(function($item) {
                return $item['type'];
            }, $items));

            if (count($types) === 1 && $types[0] === 'image') {
                return [
                    'type' => 'images',
                    'items' => $items,
                ];
            }

            if (count($types) === 1 && $types[0] === 'link') {
                return [
                    'type' => 'links',
                    'items' => $items,
                ];
            }

            return [
                'type' => 'text',
                'text' => implode(', ', array_map(function($item) {
                    return isset($item['text']) ? $item['text'] : '';
                }, $items)),
            ];
        }

        if (is_numeric($value) && mlf_detail_is_image_source($value)) {
            $sources = mlf_detail_image_sources($value);
            return [
                'type' => 'image',
                'text' => $label,
                'url' => $sources['full'],
                'thumbnail' => $sources['thumbnail'],
                'full' => $sources['full'],
            ];
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return [
                    'type' => 'link',
                    'text' => $value,
                    'url' => 'mailto:' . $value,
                ];
            }

            if (filter_var($value, FILTER_VALIDATE_URL) || preg_match('/^www\./i', $value)) {
                $url = preg_match('/^www\./i', $value) ? 'https://' . $value : $value;

                if (mlf_detail_is_image_source($value)) {
                    $sources = mlf_detail_image_sources($value);
                    return [
                        'type' => 'image',
                        'text' => $value,
                        'url' => $url,
                        'thumbnail' => $sources['thumbnail'],
                        'full' => $sources['full'],
                    ];
                }

                return [
                    'type' => 'link',
                    'text' => $value,
                    'url' => $url,
                ];
            }

            return [
                'type' => 'text',
                'text' => $value,
            ];
        }

        return [
            'type' => 'text',
            'text' => (string) $value,
        ];
    }
}

if (!function_exists('mlf_detail_render_value')) {
    function mlf_detail_render_value($item) {
        if (empty($item)) {
            return '';
        }

        $type = isset($item['type']) ? $item['type'] : 'text';

        if ($type === 'image') {
            $thumbnail = !empty($item['thumbnail']) ? $item['thumbnail'] : (!empty($item['url']) ? $item['url'] : '');
            $full = !empty($item['full']) ? $item['full'] : (!empty($item['url']) ? $item['url'] : $thumbnail);
            if (!$thumbnail) {
                return '';
            }

            return '<a class="mlf-detail-image-link" href="' . esc_url($full) . '" target="_blank" rel="noopener"><img class="mlf-detail-image" src="' . esc_url($thumbnail) . '" alt=""></a>';
        }

        if ($type === 'images' && !empty($item['items'])) {
            $html = '<div class="mlf-detail-image-group">';
            foreach ($item['items'] as $image_item) {
                $html .= mlf_detail_render_value($image_item);
            }
            $html .= '</div>';
            return $html;
        }

        if ($type === 'link') {
            return '<a class="mlf-detail-link" href="' . esc_url($item['url']) . '" target="_blank" rel="noopener">' . esc_html($item['text']) . '</a>';
        }

        if ($type === 'links' && !empty($item['items'])) {
            $parts = [];
            foreach ($item['items'] as $link_item) {
                $parts[] = mlf_detail_render_value($link_item);
            }
            return '<div class="mlf-detail-inline">' . implode('<span class="mlf-detail-separator">, </span>', $parts) . '</div>';
        }

        $text = isset($item['text']) ? $item['text'] : '';
        return esc_html($text);
    }
}

if (!function_exists('mlf_detail_render_rows')) {
    function mlf_detail_render_rows($fields) {
        $html = '<div class="mlf-detail-list">';

        foreach ($fields as $field_key => $field_value) {
            $normalized = mlf_detail_normalize_value($field_key, $field_value);
            if (empty($normalized)) {
                continue;
            }

            $html .= '<div class="mlf-detail-row">';
            $html .= '<div class="mlf-detail-label">' . esc_html(mlf_detail_label($field_key)) . '</div>';
            $html .= '<div class="mlf-detail-value">' . mlf_detail_render_value($normalized) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }
}

new MLF_Dashboard();

// Add separate handler for getting detail
add_action('wp_ajax_mlf_get_detail', function(){
    $id = intval($_POST['id']);
    $post = get_post($id);
    
    if(!$post) {
        wp_send_json_error('Post not found');
    }
    
    $meta = get_post_meta($id);
    $meta_array = [];

    foreach ($meta as $key => $values) {
        if ($key === '_edit_lock' || $key === '_edit_last' || strpos($key, 'placeholder') !== false || strpos($key, 'save-your-work') !== false) {
            continue;
        }

        $raw_value = is_array($values) ? maybe_unserialize($values[0]) : maybe_unserialize($values);
        $normalized = mlf_detail_normalize_value($key, $raw_value);

        if (!empty($normalized)) {
            $meta_array[$key] = $normalized;
        }
    }

    // Organize fields into sections
    $sections = [
        'Contact Information' => ['email', 'phone', 'complete-address'],
        'Professional Details' => ['credentials', 'certifying-body', 'my-liability-insurance-provider-is', 'health-expert-panel', 'advertising', 'my-style-of-practice'],
        'Healthcare Focus' => ['healthcare-issues-and-approaches', 'placholder-for-accessibility', 'other', 'your-focus', 'idea'],
        'About Your Practice' => ['basic-information', 'the-why', 'your-collaborations', 'your-influences', 'year', 'formal-bio', 'a-little-more-about-me'],
        'Experience & Recognition' => ['awards-and-honours', 'peer-references', 'recent-patient-testimonials'],
        'Availability' => ['form-heading', 'i-offer-the-following-types-of-sessions', 'initial-appointment', 'follow-up-appointments', '3rd-party-insurance', 'online-booking', 'confidentiality', 'waiting-list', 'offerings', 'additional-services', 'services'],
        'Connections' => ['connections', 'associations'],
        'Compliance' => ['crimimal-records-check', 'criminal-records-check-received', 'approval-dateinitials', 'date-of-interview'],
        'Media' => ['podcast-titles-done-with-this-practitioner-for-mynd-myself-admin']
    ];
    
    $organized_meta = [];
    foreach($sections as $section => $keys) {
        $section_data = [];
        foreach($keys as $key) {
            if (isset($meta_array[$key]) && !empty($meta_array[$key])) {
                $section_data[$key] = $meta_array[$key];
            }
        }
        if(!empty($section_data)) {
            $organized_meta[$section] = $section_data;
        }
    }
    
    // Add any remaining fields not in our predefined sections
    $handled_keys = [];
    foreach($sections as $keys) {
        $handled_keys = array_merge($handled_keys, $keys);
    }
    $remaining = [];
    foreach($meta_array as $key => $value) {
        if (!in_array($key, $handled_keys, true)) {
            $remaining[$key] = $value;
        }
    }
    if(!empty($remaining)) {
        $organized_meta['Other Information'] = $remaining;
    }

    $detail_html = '<div class="mlf-detail-view">';
    $detail_html .= '<section class="mlf-detail-section">';
    $detail_html .= '<h4>Basic Information</h4>';
    $detail_html .= '<div class="mlf-detail-list">';
    $detail_html .= '<div class="mlf-detail-row"><div class="mlf-detail-label">Title</div><div class="mlf-detail-value">' . esc_html($post->post_title) . '</div></div>';
    $detail_html .= '<div class="mlf-detail-row"><div class="mlf-detail-label">Status</div><div class="mlf-detail-value"><span class="status-badge ' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span></div></div>';
    $detail_html .= '<div class="mlf-detail-row"><div class="mlf-detail-label">Date</div><div class="mlf-detail-value">' . esc_html(get_the_date('F j, Y', $post)) . '</div></div>';
    $detail_html .= '<div class="mlf-detail-row"><div class="mlf-detail-label">ID</div><div class="mlf-detail-value">#' . esc_html($post->ID) . '</div></div>';
    $detail_html .= '</div></section>';

    foreach ($organized_meta as $section_title => $section_fields) {
        $detail_html .= '<section class="mlf-detail-section">';
        $detail_html .= '<h4>' . esc_html($section_title) . '</h4>';
        $detail_html .= mlf_detail_render_rows($section_fields);
        $detail_html .= '</section>';
    }

    $detail_html .= '<div class="mlf-detail-actions">';
    if ($post->post_status !== 'publish') {
        $detail_html .= '<button class="mlf-btn mlf-btn-success" onclick="mlfAction(' . $post->ID . ', \'approve\')">✓ Approve</button>';
    }
    if ($post->post_status !== 'draft') {
        $detail_html .= '<button class="mlf-btn mlf-btn-secondary" onclick="mlfAction(' . $post->ID . ', \'reject\')">✗ Reject</button>';
    }
    $detail_html .= '<button class="mlf-btn mlf-btn-danger" onclick="mlfAction(' . $post->ID . ', \'trash\')">🗑 Delete</button>';
    $detail_html .= '</div></div>';
    
    $status_class = $post->post_status == 'publish' ? 'publish' : ($post->post_status == 'pending' ? 'pending' : 'draft');
    $status_label = $post->post_status == 'publish' ? 'Published' : ($post->post_status == 'pending' ? 'Pending' : 'Draft');
    
    wp_send_json_success([
        'id' => $post->ID,
        'title' => $post->post_title,
        'status' => $post->post_status,
        'status_class' => $status_class,
        'status_label' => $status_label,
        'date' => get_the_date('F j, Y', $post),
        'meta' => $meta_array,
        'sections' => $organized_meta,
        'detail_html' => $detail_html,
        'post_status' => $post->post_status
    ]);
});
