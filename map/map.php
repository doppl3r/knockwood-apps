<h2>Portfolios</h2>
<ul>
<?php
    // initialize wordpress database
    require_once(dirname(__FILE__) . '/wp-config.php');
    
    // define search query
    $args = array(
        'numberposts' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'post_type' => 'avada_portfolio',
        'post_status' => 'draft, publish, future, pending, private',
        'suppress_filters' => true
    );

    // use parameter to filter map results by category
    $category = $_GET["category"];
    if (isset($category)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'portfolio_category',
                'field'    => 'slug',
                'terms'    => $category
            ],
        ];
    }

    // use argument to search for recent posts
    $recent_posts = wp_get_recent_posts( $args, ARRAY_A );
    
    // list all search results
	foreach( $recent_posts as $recent ) {
        // loop through custom fields for geo coordinates
        $custom_fields = get_post_custom($recent['ID']);
        $geo = 'no geo coordinates';
        foreach($custom_fields as $key => $value) {
            if(isset($custom_fields['geo'])) {
                $geo = $custom_fields['geo'][0];
                break;
            }
        }
		echo '<li><a href="'.get_permalink($recent["ID"]).'">'.$recent["post_title"].' - '.$geo.'</a></li> ';
	}
	wp_reset_query();
?>
</ul>