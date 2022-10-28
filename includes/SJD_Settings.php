<?php

class SJD_Settings {

    private const MAX_EMAILS_PER_BLOCK = 20;
    private const MAX_SECONDS_BETWEEN_BLOCKS = 10; //secs

    private const SETTINGS = array(
        array(
            'name'=>'subscriber_message_delay',
            'default' => 10
        ),
        array(
            'name'=>'subscriber_messages_emails',
            'default' => 10
        ),
        array(
            'name'=>'subscriber_email_image',
            'default' => SJD_SUBSCRIBE_IMAGE
        ),
        array(
            'name'=>'subscriber_url',
            'default' => ''
        ),
        array(
            'name'=>'notify_on_subscribe_email',
            'default' => ''
        )
    );

    public static function init(){
        add_action('admin_menu', 'SJD_Settings::admin_menu');
        add_action('admin_init', 'SJD_Settings::register_settings');
    }

    public static function admin_menu(){
        add_menu_page('Subscriber Plugin','Subscriber', 'manage_options', 'subscriber_menu', 'SJD_Settings::page' ); 
    }


    public static function register_settings(){
        foreach ( self::SETTINGS as $setting ){
            register_setting('subscriber-settings-group', $setting['name'], array(
                'default' => $setting['default'],
                'show_in_rest' => false
            ));
        }
    }

    public static function page(){ 
        $subscriber_message_delay = get_option('subscriber_message_delay');
        $subscriber_message_emails = get_option('subscriber_message_emails');
        $subscriber_email_image = get_option('subscriber_email_image');
        $notify_on_subscribe_email = get_option('notify_on_subscribe_email');

        if ( isset($_POST['subscriber_message_delay']) ){
            $subscriber_message_delay = intval($_POST['subscriber_message_delay']);
            if ( $subscriber_message_delay < 1 ) $subscriber_message_delay = 1;
            if ( $subscriber_message_delay > self::MAX_SECONDS_BETWEEN_BLOCKS ) $subscriber_message_delay = self::MAX_SECONDS_BETWEEN_BLOCKS;
            update_option('subscriber_message_delay', $subscriber_message_delay);
        }

        if ( isset($_POST['subscriber_message_emails']) ){
            $subscriber_message_emails = intval($_POST['subscriber_message_emails']);
            if ( $subscriber_message_emails < 1 ) $subscriber_message_emails = 1;
            if ( $subscriber_message_emails > self::MAX_EMAILS_PER_BLOCK ) $subscriber_message_emails = self::MAX_EMAILS_PER_BLOCK;
            update_option('subscriber_message_emails', $subscriber_message_emails);
        }

        if ( isset($_POST['subscriber_email_image']) ){
            $subscriber_email_image = sanitize_text_field($_POST['subscriber_email_image']);
            update_option('subscriber_email_image', $subscriber_email_image);
        }

        if ( isset($_POST['notify_on_subscribe_email']) ){
            $notify_on_subscribe_email = sanitize_text_field($_POST['notify_on_subscribe_email']);
            update_option('notify_on_subscribe_email', $notify_on_subscribe_email);
        }
        
        ?>

        <style>
            .subscriber-settings p label{
                display:inline-block;
                width: 200px;
            }
            .subscriber-settings input[name=subscriber_email_image],
            .subscriber-settings input[name=notify_on_subscribe_email] {
                width:600px;
            }
            .subscriber-settings img {
                width:100%;
                height:auto;
            }
            .subscriber-settings table th {
                text-align:right;
            }
            .subscriber-settings .error {
                color:red;
            }
        </style>

        <div class="wrap subscriber-settings">
            <h1>Subscriber Administration</h1>
            <form method="post">
            <?php settings_fields('subscriber-settings-group'); ?>

                <!-- FREQUENCY SETTINGS -->
                <h2>Email frequency settings</h2>
                <p>When starting have a larger delay and smaller number of emails per block. This is more important if you have many subscribers, to avoid emails being marked as spam.</p>
                <p>
                    <label for="subscriber_message_delay">Message delay (1-<?=self::MAX_SECONDS_BETWEEN_BLOCKS?>secs)</label>
                    <input type="number" name="subscriber_message_delay" value="<?=$subscriber_message_delay?>" min="1" max="<?=self::MAX_SECONDS_BETWEEN_BLOCKS?>"/>
                </p>

                <p>
                    <label for="subscriber_message_emails">Emails per block (1-<?=self::MAX_EMAILS_PER_BLOCK?>)</label>
                    <input type="number" name="subscriber_message_emails" value="<?=$subscriber_message_emails?>" min="1" max="<?=self::MAX_EMAILS_PER_BLOCK?>"/>
                </p>

                <!-- CONTENT SETTINGS -->
                <h2>Email content settings</h2>
                <?php // @todo See https://jeroensormani.com/how-to-include-the-wordpress-media-selector-in-your-plugin/ for hpow to add the media selector?>
                <p>Choose an image. Needs to be a full valid url. You can copy a url from the Media library.</p>
                <p>
                    <label for="subscriber_email_image">Emails image <img src="<?=$subscriber_email_image?>" alt="email image"></label>
                    <input type="text" name="subscriber_email_image" value="<?=$subscriber_email_image?>"/>
                </p>

                <h2>Notify Admin Settings</h2>
                <p>Choose which email to notify when a new contact subscribes. By default this will be the admin email if one is not set here.</p>
                <p>
                    <label for="notify_on_subscribe_email">Notification for new subscriber email</label>
                    <input type="text" name="notify_on_subscribe_email" value="<?=$notify_on_subscribe_email?>"/>
                </p>

                <p>
                    <input type="submit" value="Save Changes"/>
                </p>
            </form>

            <br><br>
            <h2>Other operations</h2>

            <!-- IMPORT -->
            <h3>Import Subscribers</h3>
            <p>
                Install the <a href="https://wordpress.org/plugins/really-simple-csv-importer/">Really Simple CSV Importer</a> 
                and then choose <a href="/wp-admin/admin.php?import=csv">CSV Import</a> in Tools->Import. The CSV file must have the following headings on the first row:</p>
            <table>
                <tbody>
                    <tr>
                        <th>Heading</th><td>Row Content</td>
                    </tr>
                    <tr>
                        <th>post_title</th><td>Subscribers email address</td>
                    </tr>
                    <tr>
                        <th>subscriber_email</th><td>Should be the same as post title</td>
                    </tr>
                    <tr>
                        <th>subscriber_first_name</th><td>First name</td>
                    </tr>
                    <tr>
                        <th>subscriber_last_name</th><td>Last name</td>
                    </tr>
                    <tr>
                        <th>post_type</th><td>Must be set to "subscribers"</td>
                    </tr>
                    <tr>
                        <th>post_status</th><td>"draft" or "publish"</td>
                    </tr>
                </tbody>
            </table>

            <!-- EXPORT -->
            <h3>Export Subscribers</h3>
            <p>Exporting subscribers can be done using the standard Wordpress XMP exporter, found under Tools->Export.</p>
        </div>
    <?php }
}