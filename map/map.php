<!doctype html>
<html lang="en" amp="">
    <head>
        <meta charset="utf-8">
        <title>Map by Doppler Creative</title>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js" integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==" crossorigin=""></script>
        <style>
            html, body, #mapid { height: 100%; width: 100%; margin: 0; overflow: hidden; }
        </style>
    </head>
    <body>
        <div id="mapid"></div>
        <script>
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
                $Portfolio = [];
                foreach( $recent_posts as $index => $recent ) {
                    $Portfolio[$index] = [];
                    $Portfolio[$index]['name'] = $recent["post_title"];
                    $Portfolio[$index]['link'] = get_permalink($recent["ID"]);
                    // loop through custom fields for geo coordinates
                    $custom_fields = get_post_custom($recent['ID']);
                    foreach($custom_fields as $key => $value) {
                        if(isset($custom_fields['geo'])) {
                            $Portfolio[$index]['geo'] = explode(',', $custom_fields['geo'][0]);
                            break;
                        }
                    }
                }
                echo 'var Portfolio = '.json_encode($Portfolio).';';
                wp_reset_query();
            ?>
            
            // Initialize map
            var mymap = L.map('mapid').setView([33.5641086, -112.1946049], 10);
            L.tileLayer('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png', { maxZoom: 15 }).addTo(mymap);
            
            // Loop through each portfolio and create a pin and popup
            Portfolio.forEach(function(value, i){
                var name = value['name'];
                var link = value['link'];
                var geo = value['geo'];
                if (geo != null){
                    var marker = L.marker(geo).addTo(mymap);
                    marker.bindPopup('<a href="'+link+'" target="_top">'+name+'</a>')
                }
                else console.log('Portfolio "' + name + '" is missing custom field "geo"');
            });
        </script>
    </body>
</html>