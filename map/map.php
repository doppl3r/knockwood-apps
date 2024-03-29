<!doctype html>
<html lang="en" amp="">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, minimum-scale=1">
        <title>Map by Doppler Creative</title>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js" integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==" crossorigin=""></script>
        <style>
            html, body, #mapid { height: 100%; width: 100%; margin: 0; overflow: hidden; }
            .leaflet-popup-content-wrapper { padding: 0; border-radius: 0; box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15); width: 180px; }
            .leaflet-popup-content { margin: 0; padding: 0 0 12px; text-align: center; }
            .leaflet-popup-content h2 { margin: 0; padding: 0 12px 6px; }
            .leaflet-popup-content p { margin: 0; padding: 0 12px 6px; }
            .leaflet-popup-content a { background-color: #eee; color: #333; text-decoration: none; display: inline-block; padding: 4px 16px; }
            .leaflet-popup-content a:hover { background-color: #333; color: #fff; }
            .leaflet-popup-content img { display: block; max-width: 100%; margin: 0 0 12px; background-color: #ccc; }
            .leaflet-container a.leaflet-popup-close-button { color: #000; }
        </style>
    </head>
    <body>
        <div id="mapid"></div>
        <script>
            <?php
                // Initialize Wordpress database
                require_once(dirname(__FILE__) . '/wp-config.php');
                
                // Define search query
                $args = array(
                    'numberposts' => -1,
                    'orderby' => 'post_date',
                    'order' => 'DESC',
                    'post_type' => 'avada_portfolio',
                    'post_status' => 'draft, publish, future, pending, private',
                    'suppress_filters' => true
                );

                // Use parameter to filter map results by category
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

                // Use argument to search for recent posts
                $recent_posts = wp_get_recent_posts( $args, ARRAY_A );
                
                // List all search results
                $portfolio = [];
                foreach( $recent_posts as $index => $recent ) {
                    $portfolio[$index]['name'] = $recent["post_title"];
                    $portfolio[$index]['exerpt'] = get_the_excerpt($recent["ID"]);
                    $portfolio[$index]['link'] = get_permalink($recent["ID"]);
                    $portfolio[$index]['categories'] = wp_get_post_terms($recent['ID'], 'portfolio_category', ['orderby' => 'name', 'order' => 'ASC', 'fields' => 'slugs']);
                    $portfolio[$index]['thumbnail'] = wp_get_attachment_url( get_post_thumbnail_id($recent["ID"]), 'thumbnail' );
                    
                    // Loop through custom fields for geo coordinates
                    $custom_fields = get_post_custom($recent['ID']);
                    foreach($custom_fields as $key => $value) {
                        if (isset($custom_fields['geo'])) {
                            $portfolio[$index]['geo'] = explode(',', $custom_fields['geo'][0]);
                            break;
                        }
                    }
                }

                // Print JS object for leaflet.js
                echo 'var portfolio = '.json_encode($portfolio).';';
                wp_reset_query();
            ?>
            
            // Initialize map
            // (faster) - https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png
            // (slower) - https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png
            var map = L.map('mapid', { dragging: !L.Browser.mobile, tap: false, zoomControl: false }).setView([33.5641086, -112.1946049], 10);
            map.scrollWheelZoom.disable();
            L.tileLayer('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
            
            // Icon options
            var iconOne = L.icon({ iconUrl: 'iconOne.png', shadowUrl: 'iconShadow.png', iconSize: [25, 41], shadowSize: [41, 41], iconAnchor: [13, 41], shadowAnchor: [13, 41], popupAnchor: [0, -41] });
            var iconTwo = L.icon({ iconUrl: 'iconTwo.png', shadowUrl: 'iconShadow.png', iconSize: [25, 41], shadowSize: [41, 41], iconAnchor: [13, 41], shadowAnchor: [13, 41], popupAnchor: [0, -41] });
            
            // Loop through each portfolio and create a pin and popup
            var group = new L.featureGroup([]);
            portfolio.forEach(function(value){
                var name = value['name'];
                var exerpt = value['exerpt'];
                var link = value['link'];
                var geo = value['geo'];
                var categories = value['categories'];
                var thumbnail = value['thumbnail'];
                var button = '';

                // Add icon if geo coordinates exist
                if (geo != null) {
                    var marker = L.marker(geo, { icon: iconOne }).addTo(group);
                    categories.forEach(function(category){
                        if (category.includes('highlight')) {
                            marker.setIcon(iconTwo);
                            button = '<a href="' + link + '" target="_top">View Page</a>';
                        }
                    });
                    marker.bindPopup(
                        '<img src="' + thumbnail + '" />' +
                        '<h2>'+ name + '</h2>' +
                        '<p>'+ exerpt + '</p>' +
                        button
                    );
                }
                else console.log('Portfolio "' + name + '" is missing custom field "geo": ' + link);
            });

            // zoom settings
            group.addTo(map);
            map.fitBounds(group.getBounds());
            L.control.zoom({ position:'bottomright' }).addTo(map);
        </script>
    </body>
</html>