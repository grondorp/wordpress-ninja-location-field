<?php
/*
    Plugin Name: Wordpress Ninja Location Field
    Plugin URI: https://www.sjoukebakker.nl/
    Description: This plugin publishes uses a hidden field and html field to display a location field in Ninja Forms.
    Author: Sjouke Bakker
    Author URI: http://www.sjoukebakker.nl/
    Version: 1.0.0
*/


function ninja_location_field_settings_page() {
    add_options_page(
        'Ninja Location Field Settings',
        'Ninja Location Field',
        'manage_options',
        'ninja-location-field',
        'ninja_location_field_settings_page_html'
    );
}
add_action('admin_menu', 'ninja_location_field_settings_page');

function ninja_location_field_settings_page_html() {
    ?>
    <div class="wrap">
        <h1>Ninja Location Field Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('ninja_location_field_settings');
            do_settings_sections('ninja-location-field');
            submit_button('Opslaan');
            ?>
        </form>
    </div>
    <?php
}

function ninja_location_field_settings_init() {
    register_setting('ninja_location_field_settings', 'ninja_location_field_number');

    add_settings_section(
        'ninja_location_field_section',
        'Locatieveld Instellingen',
        'ninja_location_field_section_callback',
        'ninja-location-field'
    );

    add_settings_field(
        'ninja_location_field_number',
        'Veldnummer',
        'ninja_location_field_number_callback',
        'ninja-location-field',
        'ninja_location_field_section'
    );

    add_settings_section(
        'ninja_location_field_section_2',
        'Hoe werkt deze plugin?',
        'ninja_location_desc_section_callback',
        'ninja-location-field'
    );
}
add_action('admin_init', 'ninja_location_field_settings_init');

function ninja_location_field_section_callback() {
    echo '<p>Vul hier het veldnummer in van het verborgen veld dat je op het formulier wil gebruiken om de locatiewaarden op te slaan.</p>';
}

function ninja_location_field_number_callback() {
    $field_number = get_option('ninja_location_field_number', '');
    echo '<input type="text" id="ninja_location_field_number" name="ninja_location_field_number" value="' . esc_attr($field_number) . '" />';
}

function ninja_location_desc_section_callback() {
    echo '<p>Deze plugin werkt door gebruik te maken van een verborgen veld. Je moet twee dingen toevoegen aan een Ninja formulier:</p>
    <ul style="list-style: inside;">
        <li>Een verborgen veld</li>
        <li>Een HTML veld met de volgende inhoud: 
            <pre style="display: inline-block;">' . htmlspecialchars('<div id="form-map" style="height: 400px;"></div>') . '</pre>. Uiteraard kun je hier zelf nog HTML aan toevoegen.
        </li>
    </ul>
    <p>Vervolgens zet je het formulier d.m.v. de shortcode op een pagina en zoek je het verborgen veld op in de bron van je pagina zodat je het veldnummer vindt. Dit nummer vul je in op deze pagina met instellingen. De plugin werkt dus maar voor één formulier tegelijk.</p>';
    }

function ninja_form_leaflet_script() {
    $field_number = get_option('ninja_location_field_number', '311'); // Default to 311 if not set
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            // Check if Leaflet is available
            if (typeof L === 'undefined') {
                console.error('Leaflet library not found.');
                return;
            }

            let retryCount = 0;
            const maxRetries = 3;
            const fieldNumber = <?php echo json_encode($field_number); ?>;

            // Retry mechanism to ensure the map container exists
            function initializeMap() {
                var mapContainer = document.getElementById('form-map');
                if (mapContainer) {
                    console.log('Map container with ID "form-map" is found!');
                    
                    var map = L.map('form-map').setView([53.17, 6.7], 9);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);

                    var marker;
                    map.on('click', function(e) {
                        var latlng = e.latlng;
                        if (marker) {
                            marker.setLatLng(latlng);
                        } else {
                            marker = L.marker(latlng).addTo(map);
                        }

                        var hiddenField = document.querySelector('input[id="nf-field-' + fieldNumber + '"]');
                        if (hiddenField) {
                            var wkt = 'POINT(' + latlng.lng.toFixed(6) + ' ' + latlng.lat.toFixed(6) + ')'; // Location as Well Known Text
                            hiddenField.value = wkt;
                            console.log('Hidden field set to', hiddenField.value);
                            var newValue = hiddenField.value;
                            console.log(hiddenField.value);
                            jQuery('#nf-field-' + fieldNumber).val(newValue).trigger('change');
                        } else {
                            console.error('Hidden field not found.');
                        }
                    });
                } else if (retryCount < maxRetries) {
                    retryCount++;
                    console.log('Map container with ID "form-map" not yet available. Retrying...');
                    setTimeout(initializeMap, 100); // Retry after 100ms
                } else {
                    console.error('Failed to initialize map after 3 attempts.');
                }
            }

            initializeMap();
        
        });
    </script>
    <?php
}
add_action('wp_footer', 'ninja_form_leaflet_script');