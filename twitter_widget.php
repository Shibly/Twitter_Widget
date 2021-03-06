<?php

/**
 * Plugin Name: Twitter Widget
 * Plugin URI: https://github.com/Shibly
 * Description: A simple wordpress widget plugin that will allow you to fetch tweet from your twitter account in your wordpress blog. You will need php version 5.3 or later to run this plugin.
 *  Author: Shibly
 * Author URI: https://github.com/Shibly 
 * Version: 1.0
 */
class Twitter extends WP_Widget {

    public function __construct() {
        $params = array(
            'name' => 'Display Tweets',
            'description' => 'Display and cache tweets from your twitter account'
        );

        parent::__construct('Twitter', '', $params);
    }

    /**
     * 
     * @param type $instance
     */
    public function form($instance) {

        extract($instance);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title') ?>">Title: </label>
            <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>"
                   value="<?php if (isset($title)) echo esc_attr($title); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('username') ?>">User Name: </label>
            <input type="text" class="widefat" id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>"
                   value="<?php if (isset($username)) echo esc_attr($username); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('tweet_count') ?>">Number of Tweets to Retrieve: </label>
            <input type="number" 
                   class="widefat" 
                   style="width: 40px;" 
                   id="<?php echo $this->get_field_id('tweet_count') ?>"
                   name="<?php echo $this->get_field_name('tweet_count') ?>" 
                   min="1" 
                   max="10"
                   value="<?php echo!empty($tweet_count) ? $tweet_count : 5; ?>"/> 
        </p>
        <?php
    }

    /**
     * 
     * @param type $args
     * @param type $instance
     */
    public function widget($args, $instance) {
        extract($args);
        extract($instance);

        if (empty($title)) {
            $title = 'Recent Tweets';
        }



        $data = $this->twitter($tweet_count, $username);
        if (FALSE !== $data && isset($data->tweets))
            echo $before_widget;
        echo $before_title;
        echo $title;
        echo $after_title;
        echo '<ul><li>' . implode('</li><li>', $data->tweets) . '</li></ul>';
        echo $after_widget;
    }

    /**
     * 
     * @param type $tweet_count
     * @param type $username
     * @return boolean
     */
    private function twitter($tweet_count, $username) {
        if (empty($username)) {
            return false;
        }

        $tweets = get_transient('recent_tweets_widget');
        if (!$tweets || $tweets->username !== $username || $tweets->tweet_count !== $tweet_count) {
            return $this->fetch_tweets($tweet_count, $username);
        }
        return $tweets;
    }

    /**
     * 
     * @param type $tweet_count
     * @param type $username
     * @return boolean|\stdClass
     */
    private function fetch_tweets($tweet_count, $username) {
        $tweets = wp_remote_get("https://api.twitter.com/1/statuses/user_timeline/$username.json");

        $tweets = json_decode($tweets['body']);
        if (isset($tweets->error)) {
            return false;
        }
        $data = new stdClass();
        $data->username = $username;
        $data->tweet_count = $tweet_count;
        $data->tweets = array();

        foreach ($tweets as $tweet) {
            if ($tweet_count-- === 0)
                break;
            $data->tweets[] = $this->filter_tweets($tweet->text);
        }
        set_transient('recent_tweets_widget', $data, 60 * 5);
        return $data;
    }

    /**
     * 
     * @param type $tweet
     * @return type
     */
    private function filter_tweets($tweet) {
        $tweet = preg_replace('/(http[^s]+)/im', '<a href="$1">$1</a>', $tweet);
        $tweet = preg_replace('/@([^s]+)/i', '<a href="http://twitter.com/$1">@$1></a>', $tweet);
        return $tweet;
    }

}

add_action('widgets_init', function () {
            register_widget('Twitter');
        })
?>
