<?php
/**
 * Helper for getting stored values in database
 * @since 4.0.0
 */
class Helpful_Helper_Stats {

  public static $green = '#88c057';
  public static $red = '#ed7161';

  /**
   * Get pro count by post id
   * @param int $post_id if null current post id
   * @param bool $percentages return percentage values on true
   * @return int count
   */
   public static function getPro($post_id = null, $percentages = false) {
    if( is_null($post_id ) ) {
      global $post;
      $post_id = $post->ID;
    }

    global $wpdb;

    $post_id = absint($post_id);
    $helpful = $wpdb->prefix . 'helpful';
    $sql = $wpdb->prepare("SELECT COUNT(*) FROM $helpful WHERE pro = 1 AND post_id = %d", $post_id);
    $var = $wpdb->get_var($sql);

    if( false == $percentages ) {
      return $var;
    }

    $pro = ( $var ? (int) $var : 0 );
    $contra = self::getContra($post_id);

    $pro_percent = 0;

    if( 0 !== $pro ) {
      $pro_percent = ( ( $pro / ( $pro + $contra ) ) * 100 );
    }

    $pro_percent = number_format($pro_percent, 2);

    return (float) str_replace('.00', '', $pro_percent);
  }
  
  /**
   * Get contra count by post id
   * @param int $post_id if null current post id
   * @param bool $percentages return percentage values on true
   * @return int count
   */
  public static function getContra($post_id = null, $percentages = false) {
    if( is_null($post_id ) ) {
      global $post, $wpdb;
      $post_id = $post->ID;
    }

    global $wpdb;
    $post_id = absint($post_id);
    $helpful = $wpdb->prefix . 'helpful';    
    $sql = $wpdb->prepare("SELECT COUNT(*) FROM $helpful WHERE contra = 1 AND post_id = %d", $post_id);
    $var = $wpdb->get_var($sql);

    if( false == $percentages ) {
      return $var;
    }

    $contra = ( $var ? (int) $var : 0 );
    $pro = self::getPro($post_id);

    $contra_percent = 0;

    if( 0 !== $contra ) {
      $contra_percent = ( ( $contra / ( $pro + $contra ) ) * 100 );
    }

    $contra_percent = number_format($contra_percent, 2);
    return (float) str_replace('.00', '', $contra_percent);
  }
  
  /**
   * Get pro count of all posts
   * @param bool $percentages return percentage values on true
   * @return int count
   */
  public static function getProAll($percentages = false) {
    global $wpdb;
    $helpful = $wpdb->prefix . 'helpful';    
    $sql = "SELECT COUNT(*) FROM $helpful WHERE pro = 1";
    $var = $wpdb->get_var($sql);

    if( false == $percentages ) {
      return $var;
    }

    $pro = ( $var ? (int) $var : 0 );
    $contra = self::getContraAll();

    $pro_percent = 0;

    if( 0 !== $pro ) {
      $pro_percent = ( ( $pro / ( $pro + $contra ) ) * 100 );
    }

    $pro_percent = number_format($pro_percent, 2);

    return (float) str_replace('.00', '', $pro_percent);
  }
  
  /**
   * Get contra count of all posts
   * @param bool $percentages return percentage values on true
   * @return int count
   */
  public static function getContraAll($percentages = false) {
    global $wpdb;
    $helpful = $wpdb->prefix . 'helpful';    
    $sql = "SELECT COUNT(*) FROM $helpful WHERE contra = 1";
    $var = $wpdb->get_var($sql);

    if( false == $percentages ) {
      return $var;
    }

    $contra = ( $var ? (int) $var : 0 );
    $pro = self::getProAll();

    $contra_percent = 0;

    if( 0 !== $contra ) {
      $contra_percent = ( ( $contra / ( $pro + $contra ) ) * 100 );
    }

    $contra_percent = number_format($contra_percent, 2);
    return (float) str_replace('.00', '', $contra_percent);
  }

  /**
   * Get years
   * @return array
   */
  public static function getYears() {
    global $wpdb;
    $helpful = $wpdb->prefix . 'helpful';
    $sql = "SELECT time FROM $helpful ORDER BY time DESC";
    $results = $wpdb->get_results($sql);

    if( !$results ) {
      return [];
    }

    $years = [];

    foreach( $results as $result ) {
      $years[] = date('Y', strtotime($result->time));
    }

    $years = array_unique($years);

    return $years;
  }
  
  /**
   * Stats for today
   * @param integer $year
   * @return array
   */
  public static function getStatsToday($year) {

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    $query   = "
    SELECT pro, contra, time 
    FROM $helpful 
    WHERE DAYOFYEAR(time) = DAYOFYEAR(NOW()) 
    AND YEAR(time) = %d
    ";

    $query   = $wpdb->prepare($query, $year);
    $results = $wpdb->get_results($query);
    
    if( !$results ) {
      return [
        'status' => 'error',
        'message' => __('No entries found', 'helpful'),
      ];
    }

    $pro = wp_list_pluck($results, 'pro');
    $pro = array_sum($pro);

    $contra = wp_list_pluck($results, 'contra');
    $contra = array_sum($contra);

    /* Response for ChartJS */    
    $response = [
      'type' => 'doughnut',
      'data' => [
        'datasets' => [
          [
            'data' => [ absint($pro), absint($contra), ],
            'backgroundColor' => [ self::$green, self::$red, ],
          ],
        ],
        'labels' => ['Pro', 'Contra'],
      ],
      'options' => [
        'legend' => [
          'position' => 'bottom',
        ],
      ],
    ];

    return $response;
  }

  /**
   * Stats for yesterday
   * @param integer $year
   * @return array
   */
  public static function getStatsYesterday($year) {

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    $query   = "
    SELECT pro, contra, time 
    FROM $helpful 
    WHERE DAYOFYEAR(time) = DAYOFYEAR(SUBDATE(CURDATE(),1)) 
    AND YEAR(time) = %d
    ";

    $query   = $wpdb->prepare($query, $year);
    $results = $wpdb->get_results($query);
    
    if( !$results ) {
      return [
        'status' => 'error',
        'message' => __('No entries found', 'helpful'),
      ];
    }

    $pro = wp_list_pluck($results, 'pro');
    $pro = array_sum($pro);

    $contra = wp_list_pluck($results, 'contra');
    $contra = array_sum($contra);

    /* Response for ChartJS */    
    $response = [
      'type' => 'doughnut',
      'data' => [
        'datasets' => [
          [
            'data' => [ absint($pro), absint($contra), ],
            'backgroundColor' => [ self::$green, self::$red, ],
          ],
        ],
        'labels' => ['Pro', 'Contra'],
      ],
      'options' => [
        'legend' => [
          'position' => 'bottom',
        ],
      ],
    ];

    return $response;
  }

  /**
   * Stats for week
   * @param integer $year
   * @return array
   */
  public static function getStatsWeek($year) {

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    $query   = "
    SELECT pro, contra, time 
    FROM $helpful 
    WHERE WEEK(time, 1) = WEEK(CURDATE(), 1) 
    AND YEAR(time) = %d
    ";

    $query   = $wpdb->prepare($query, $year);
    $results = $wpdb->get_results($query);

    if( !$results ) {
      return [
        'status' => 'error',
        'message' => __('No entries found', 'helpful'),
      ];
    }
    
    $pro = [];    
    $contra = [];
    $labels = [];
    $timestamp = strtotime('monday this week');
    $days = 7;

    for( $i = 0; $i < $days; $i++ ) {
      $date = date_i18n( 'Ymd', strtotime('+'.$i.' days', $timestamp) );
      $day = date_i18n( 'D', strtotime('+'.$i.' days', $timestamp) );
      $pro[$date] = 0;
      $contra[$date] = 0;
      $labels[] = $day;
    }

    foreach( $results as $result ) {
      for( $i = 0; $i < $days; $i++ ) {
        $day = date_i18n( 'Ymd', strtotime('+'.$i.' days', $timestamp) );
        $date = date_i18n( 'Ymd', strtotime($result->time) );
        
        if( $day == $date ) {
          $pro[$date] += $result->pro;
          $contra[$date] += $result->contra;
        }
      }
    }

    /* Response for ChartJS */    
    $response = [
      'type' => 'bar',
      'data' => [
        'datasets' => [
          [
            'label' => 'Pro',
            'data' => array_values($pro),
            'backgroundColor' => self::$green,
          ],
          [
            'label' => 'Contra',
            'data' => array_values($contra),
            'backgroundColor' => self::$red,
          ],
        ],
        'labels' => $labels,
      ],
      'options' => [
        'scales' => [
          'xAxes' => [
            [ 'stacked' => true ],
          ],
          'yAxes' => [
            [ 'stacked' => true ],
          ],
        ],
        'legend' => [
          'position' => 'bottom',
        ],
      ],
    ];

    return $response;
  }

  /**
   * Stats for month
   * @param integer $year
   * @param integer @month
   * @return array
   */
  public static function getStatsMonth($year, $month = null) {

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    if( is_null($month) ) {
      $month = date('m');
    } else {
      $month = absint($month);
    }

    $query   = "
    SELECT pro, contra, time 
    FROM $helpful 
    WHERE MONTH(time) = %d
    AND YEAR(time) = %d
    ";

    $query   = $wpdb->prepare($query, $month, $year);
    $results = $wpdb->get_results($query);

    if( !$results ) {
      return [
        'status' => 'error',
        'message' => __('No entries found', 'helpful'),
      ];
    }
    
    $pro = [];    
    $contra = [];
    $labels = [];
    $timestamp = strtotime(date("$year-$month-1"));
    $days = date_i18n('t', $timestamp) - 1;

    for( $i = 0; $i < $days; $i++ ) {
      $date = date_i18n( 'Ymd', strtotime('+'.$i.' days', $timestamp) );
      $day = date_i18n( 'j M', strtotime('+'.$i.' days', $timestamp) );
      $pro[$date] = 0;
      $contra[$date] = 0;
      $labels[] = $day;
    }

    foreach( $results as $result ) {
      for( $i = 0; $i < $days; $i++ ) {
        $day = date_i18n( 'Ymd', strtotime('+'.$i.' days', $timestamp) );
        $date = date_i18n( 'Ymd', strtotime($result->time) );
        
        if( $day == $date ) {
          $pro[$date] += $result->pro;
          $contra[$date] += $result->contra;
        }
      }
    }

    /* Response for ChartJS */    
    $response = [
      'type' => 'bar',
      'data' => [
        'datasets' => [
          [
            'label' => 'Pro',
            'data' => array_values($pro),
            'backgroundColor' => self::$green,
          ],
          [
            'label' => 'Contra',
            'data' => array_values($contra),
            'backgroundColor' => self::$red,
          ],
        ],
        'labels' => $labels,
      ],
      'options' => [
        'scales' => [
          'xAxes' => [
            [ 'stacked' => true ],
          ],
          'yAxes' => [
            [ 'stacked' => true ],
          ],
        ],
        'legend' => [
          'position' => 'bottom',
        ],
      ],
    ];

    return $response;
  }

  /**
   * Stats for year
   * @param integer $year
   * @return array
   */
  public static function getStatsYear($year) {

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    $query   = "
    SELECT pro, contra, time 
    FROM $helpful 
    WHERE YEAR(time) = %d
    ";

    $query   = $wpdb->prepare($query, $year);
    $results = $wpdb->get_results($query);

    if( !$results ) {
      return [
        'status' => 'error',
        'message' => __('No entries found', 'helpful'),
      ];
    }
    
    $pro = [];    
    $contra = [];
    $labels = [];
    $timestamp = strtotime(sprintf(date('%d-1-1'), $year));
    $days = 12;

    for( $i = 0; $i < $days; $i++ ) {
      $month = date_i18n( 'M', strtotime('+'.$i.' months', $timestamp) );
      $pro[$month] = 0;
      $contra[$month] = 0;
      $labels[] = $month;
    }

    foreach( $results as $result ) {
      for( $i = 0; $i < $days; $i++ ) {
        $month = date_i18n( 'M', strtotime('+'.$i.' months', $timestamp) );
        $m = date_i18n( 'M', strtotime($result->time));
        
        if( $month == $m ) {
          $pro[$month] += $result->pro;
          $contra[$month] += $result->contra;
        }
      }
    }

    /* Response for ChartJS */    
    $response = [
      'type' => 'bar',
      'data' => [
        'datasets' => [
          [
            'label' => 'Pro',
            'data' => array_values($pro),
            'backgroundColor' => self::$green,
          ],
          [
            'label' => 'Contra',
            'data' => array_values($contra),
            'backgroundColor' => self::$red,
          ],
        ],
        'labels' => $labels,
      ],
      'options' => [
        'scales' => [
          'xAxes' => [
            [ 'stacked' => true ],
          ],
          'yAxes' => [
            [ 'stacked' => true ],
          ],
        ],
        'legend' => [
          'position' => 'bottom',
        ],
      ],
    ];

    return $response;
  }

  /**
   * Stats by range
   * @param string $from time string
   * @param string $to time string
   * @return array
   */
  public static function getStatsRange($from, $to) {

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    $query   = "
    SELECT pro, contra, time 
    FROM $helpful
    WHERE DATE(time) >= DATE(%s) 
    AND DATE(time) <= DATE(%s)
    ";

    $query   = $wpdb->prepare($query, $from, $to);
    $results = $wpdb->get_results($query);

    if( !$results ) {
      return [
        'status' => 'error',
        'message' => __('No entries found', 'helpful'),
      ];
    }

    $from_date = new DateTime($from);
    $to_date = new DateTime($to);

    $diff = $from_date->diff($to_date);
    
    $pro = [];    
    $contra = [];
    $labels = [];
    $timestamp = strtotime($from);
    $days = date_i18n('t', $timestamp) - 1;

    for( $i = 0; $i < ($diff->format("%a") + 1); $i++ ) {
      $date = date_i18n( 'Ymd', strtotime('+'.$i.' days', $timestamp) );
      $day = date_i18n( 'j M', strtotime('+'.$i.' days', $timestamp) );
      $pro[$date] = 0;
      $contra[$date] = 0;
      $labels[] = $day;
    }

    foreach( $results as $result ) {
      $date = date_i18n( 'Ymd', strtotime($result->time) );
      $pro[$date] += (int) $result->pro;
      $contra[$date] += (int) $result->contra;
    }

    /* Response for ChartJS */    
    $response = [
      'type' => 'bar',
      'data' => [
        'datasets' => [
          [
            'label' => 'Pro',
            'data' => array_values($pro),
            'backgroundColor' => self::$green,
          ],
          [
            'label' => 'Contra',
            'data' => array_values($contra),
            'backgroundColor' => self::$red,
          ],
        ],
        'labels' => $labels,
      ],
      'options' => [
        'legend' => [
          'position' => 'bottom',
        ],
      ],
    ];

    return $response;
  }

  /**
   * Stats for total
   * @return array
   */
  public static function getStatsTotal() {

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    $query   = "
    SELECT pro, contra, time 
    FROM $helpful
    ";

    $results = $wpdb->get_results($query);
    
    if( !$results ) {
      return [
        'status' => 'error',
        'message' => __('No entries found', 'helpful'),
      ];
    }

    $pro = wp_list_pluck($results, 'pro');
    $pro = array_sum($pro);

    $contra = wp_list_pluck($results, 'contra');
    $contra = array_sum($contra);

    /* Response for ChartJS */    
    $response = [
      'type' => 'doughnut',
      'data' => [
        'datasets' => [
          [
            'data' => [ absint($pro), absint($contra), ],
            'backgroundColor' => [ self::$green, self::$red, ],
          ],
        ],
        'labels' => ['Pro', 'Contra'],
      ],
      'options' => [
        'legend' => [
          'position' => 'bottom',
        ],
      ],
    ];

    return $response;
  }

  /**
   * Get most helpful posts
   * @param integer $limit posts per page
   * @return array
   */
  public static function getMostHelpful($limit = null) {

    if( is_null($limit) ) {
      $limit = absint(get_option('helpful_widget_amount'));
    }

    $args = [
      'post_type' => get_option('helpful_post_types'),
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
    ];

    $query = new WP_Query($args);

    $posts = [];

    if( $query->found_posts ) {
      foreach( $query->posts as $post_id ) {
        $pro = self::getPro($post_id) ? self::getPro($post_id) : 0;
        $contra = self::getContra($post_id) ? self::getContra($post_id) : 0;
        $posts[$post_id] = (int) ( $pro - $contra );
      }

      if( count($posts) > 1 ) {

        arsort($posts);

        $results = [];
        $posts = array_slice($posts, 0, $limit, true);

        foreach( $posts as $post_id => $value ) {
          if( 0 == $value ) continue;
          $results[] = [
            'ID' => $post_id,
            'url' => get_the_permalink($post_id),
            'name' => get_the_title($post_id),
            'time' => sprintf( 
              __('Published %s ago', 'helpful'), 
              human_time_diff(date_i18n(get_the_date('U', $post_id)), date_i18n('U')),
            ),
          ];
        }
      }
    }

    $results = array_filter($results);

    return $results;
  }

  /**
   * Get least helpful posts
   * @param integer $limit posts per page
   * @return array
   */
  public static function getLeastHelpful($limit = null) {

    if( is_null($limit) ) {
      $limit = absint(get_option('helpful_widget_amount'));
    }

    $args = [
      'post_type' => get_option('helpful_post_types'),
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
    ];

    $query = new WP_Query($args);

    $posts = [];

    if( $query->found_posts ) {
      foreach( $query->posts as $post_id ) {
        $pro = self::getPro($post_id) ? self::getPro($post_id) : 0;
        $contra = self::getContra($post_id) ? self::getContra($post_id) : 0;
        $posts[$post_id] = (int) ( $contra - $pro );
      }

      if( count($posts) > 1 ) {

        arsort($posts);

        $results = [];
        $posts = array_slice($posts, 0, $limit, true);

        foreach( $posts as $post_id => $value ) {
          if( 0 == $value ) continue;
          $results[] = [
            'ID' => $post_id,
            'url' => get_the_permalink($post_id),
            'name' => get_the_title($post_id),
            'time' => sprintf( 
              __('Published %s ago', 'helpful'), 
              human_time_diff(date_i18n(get_the_date('U', $post_id)), date_i18n('U')),
            ),
          ];
        }
      }
    }

    $results = array_filter($results);

    return $results;
  }

  /**
   * Get recently helpful pro posts
   * @param integer $limit posts per page
   * @return array
   */
  public static function getRecentlyPro($limit = null) {

    if( is_null($limit) ) {
      $limit = absint(get_option('helpful_widget_amount'));
    }

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    $query   = "SELECT post_id, time FROM $helpful WHERE pro = %d ORDER BY id DESC LIMIT %d";
    $query   = $wpdb->prepare($query, 1, $limit);
    $results = $wpdb->get_results($query);

    if( $results ) {
      foreach( $results as $post ) {
        $timestamp = strtotime($post->time);
        $posts[] = [
          'ID' => $post->post_id,
          'url' => get_the_permalink($post->post_id),
          'name' => get_the_title($post->post_id),
          'time' => sprintf( 
            __('Submitted %s ago', 'helpful'), 
            human_time_diff(date_i18n($timestamp), date_i18n('U'))
          ),
        ];
      }
    }

    return $posts;
  }

  /**
   * Get recently unhelpful pro posts
   * @param integer $limit posts per page
   * @return array
   */
  public static function getRecentlyContra($limit = null) {

    if( is_null($limit) ) {
      $limit = absint(get_option('helpful_widget_amount'));
    }

    global $wpdb;

    $helpful = $wpdb->prefix . 'helpful';

    $query   = "SELECT post_id, time FROM $helpful WHERE contra = %d ORDER BY id DESC LIMIT %d";
    $query   = $wpdb->prepare($query, 1, $limit);
    $results = $wpdb->get_results($query);

    if( $results ) {
      foreach( $results as $post ) {
        $timestamp = strtotime($post->time);
        $posts[] = [
          'ID' => $post->post_id,
          'url' => get_the_permalink($post->post_id),
          'name' => get_the_title($post->post_id),
          'time' => sprintf( 
            __('Submitted %s ago', 'helpful'), 
            human_time_diff(date_i18n($timestamp), date_i18n('U'))
          ),
        ];
      }
    }

    return $posts;
  }
}