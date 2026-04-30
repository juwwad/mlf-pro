<?php
if (!defined('ABSPATH')) exit;

class MLF_Emails {

    public function __construct(){

        add_action('mylisting/submission/save-listing-data', [$this,'admin_email'],10,2);
        add_action('transition_post_status', [$this,'status_change'],10,3);
    }

    // ADMIN NOTIFICATION - Enhanced with full form data
    public function admin_email($id){

        $post = get_post($id);
        $meta = get_post_meta($id);
        
        // Build HTML email with form data
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $html .= '<h2 style="color: #95160c;">🎉 New Listing Submitted</h2>';
        $html .= '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px;">';
        $html .= '<h3 style="margin-top: 0;">' . get_the_title($id) . '</h3>';
        $html .= '<p><strong>Status:</strong> ' . ucfirst($post->post_status) . '</p>';
        $html .= '<p><strong>Submitted:</strong> ' . get_the_date('F j, Y g:i A', $id) . '</p>';
        $html .= '</div>';
        
        // Add key form fields
        $key_fields = [
            'email' => 'Email',
            'phone' => 'Phone',
            'complete-address' => 'Address',
            'credentials' => 'Credentials',
            'certifying-body' => 'Certifying Body',
            'my-style-of-practice' => 'Style of Practice',
            'basic-information' => 'Basic Information',
            'the-why' => 'The Why',
            'your-focus' => 'Focus',
            'formal-bio' => 'Bio'
        ];
        
        $has_fields = false;
        foreach($key_fields as $key => $label) {
            if(!empty($meta[$key][0])) {
                if(!$has_fields) {
                    $html .= '<h4 style="margin-top: 20px;">Key Details:</h4>';
                    $html .= '<table style="width: 100%; border-collapse: collapse;">';
                    $has_fields = true;
                }
                $html .= '<tr><td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>' . $label . ':</strong></td>';
                $html .= '<td style="padding: 8px 0; border-bottom: 1px solid #eee;">' . esc_html($meta[$key][0]) . '</td></tr>';
            }
        }
        
        if($has_fields) {
            $html .= '</table>';
        }
        
        $html .= '<p style="margin-top: 20px;"><a href="' . admin_url('post.php?post=' . $id . '&action=edit') . '" style="background: #95160c; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Full Listing</a></p>';
        $html .= '</div>';
        
        $to = get_option('mlf_email_admin', 'esther@myndmyself.com');
        $subject = 'New Listing: ' . get_the_title($id);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        ];
        
        wp_mail($to, $subject, $html, $headers);
    }

    // STATUS CHANGE EMAILS
    public function status_change($new,$old,$post){

        if($post->post_type!='job_listing') return;

        $email = '';
        $template = '';

        if($new=='publish'){
            $template = get_option('mlf_email_approved');
        }

        if($new=='draft' && $old=='publish'){
            $template = get_option('mlf_email_rejected');
        }

        if(!$template) return;

        $body = str_replace(
            ['{{listing_title}}'],
            [$post->post_title],
            $template
        );

        wp_mail(
            get_post_meta($post->ID,'email',true),
            'Listing Update',
            $body,
            ['Content-Type: text/html; charset=UTF-8']
        );
    }
}

new MLF_Emails();
