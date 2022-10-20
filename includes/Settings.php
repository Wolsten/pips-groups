<?php

class Settings {

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
    );

    public static function init(){
        add_action('admin_menu', 'Settings::admin_menu');
        add_action('admin_init', 'Settings::register_settings');
    }

    public static function admin_menu(){
        add_menu_page('Subscriber Plugin','Subscriber', 'manage_options', 'subscriber_menu', 'settings::page' ); 
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

        if ( isset($_POST['subscriber_message_delay']) ){
            $subscriber_message_delay = intval($_POST['subscriber_message_delay']);
            if ( $subscriber_message_delay < 1 ) $subscriber_message_delay = 1;
            if ( $subscriber_message_delay > 10 ) $subscriber_message_delay = 10;
            update_option('subscriber_message_delay', $subscriber_message_delay);
        }

        if ( isset($_POST['subscriber_message_emails']) ){
            $subscriber_message_emails = intval($_POST['subscriber_message_emails']);
            if ( $subscriber_message_emails < 1 ) $subscriber_message_emails = 1;
            if ( $subscriber_message_emails > 10 ) $subscriber_message_emails = 10;
            update_option('subscriber_message_emails', $subscriber_message_emails);
        }

        if ( isset($_POST['subscriber_email_image']) ){
            $subscriber_email_image = sanitize_text_field($_POST['subscriber_email_image']);
            update_option('subscriber_email_image', $subscriber_email_image);
        }
        
        ?>

        <style>
            .subscriber-settings p label{
                display:inline-block;
                width: 200px;
            }
            .subscriber-settings input[name=subscriber_email_image] {
                width:600px;
            }
            .subscriber-settings img {
                width:100%;
                height:auto;
            }
        </style>

        <div class="wrap subscriber-settings">
            <h1>Subscriber Admin Page</h1>
            <form method="post">
            <?php settings_fields('subscriber-settings-group'); // Output nonce field?>

                <h2>Email frequency settings</h2>
                <p>When starting have a larger delay and smaller number of emails per block. This is more important if you have many subscribers, to avoid emails being marked as spam.</p>
                <p>
                    <label for="subscriber_message_delay">Message delay (1-10secs)</label>
                    <input type="number" name="subscriber_message_delay" value="<?=$subscriber_message_delay?>" min="1" max="10"/>
                </p>

                <p>
                    <label for="subscriber_message_emails">Emails per block (1-10)</label>
                    <input type="number" name="subscriber_message_emails" value="<?=$subscriber_message_emails?>" min="1" max="10"/>
                </p>

                <h2>Email content settings</h2>

                <?php // @todo See https://jeroensormani.com/how-to-include-the-wordpress-media-selector-in-your-plugin/ for hpow to add the media selector?>
                <p>Choose an image. Needs to be a full valid url. You can copy a url from the Media library.</p>
                <p>
                    <label for="subscriber_email_image">Emails image <img src="<?=$subscriber_email_image?>" alt="email image"></label>
                    <input type="text" name="subscriber_email_image" value="<?=$subscriber_email_image?>"/>
                </p>

                <p>
                    <input type="submit" value="Save Changes"/>
                </p>
            </form>
        </div>
    <?php }
}