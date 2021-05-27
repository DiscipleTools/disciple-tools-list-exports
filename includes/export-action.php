<?php

add_action( 'dt_post_list_filters_sidebar', 'dt_list_exports_filters', 10, 1 );
function dt_list_exports_filters( $post_type ) {
    ?>
    <br>
    <div class="bordered-box collapsed">
        <div class="section-header"><?php esc_html_e( 'List Exports', 'disciple-tools-list-exports' )?>&nbsp;
             <button class="float-right" data-open="export-help-text">
                <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>" alt="help"/>
            </button>
            <button class="section-chevron chevron_down">
                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>" alt="expand"/>
            </button>
            <button class="section-chevron chevron_up">
                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>" alt="collapse"/>
            </button>
        </div>
        <div class="section-body" style="padding-top:1em;">
            <a id="csv-list"><?php esc_html_e( "csv list", 'disciple-tools-list-exports' ) ?></a><br>
            <?php
            if ( $post_type === 'contacts' ):
                ?>
            <a id="bcc-email-list"><?php esc_html_e( "bcc email list", 'disciple-tools-list-exports' ) ?></a><br>
            <a id="phone-list"><?php esc_html_e( "phone number list", 'disciple-tools-list-exports' ) ?></a><br>
            <?php endif; ?>
            <?php if ( class_exists( 'DT_Mapbox_API' ) && DT_Mapbox_API::get_key() ) : ?>
                <a id="map-list"><?php esc_html_e( "map list", 'disciple-tools-list-exports' ) ?></a><br>
            <?php endif; ?>
        </div>

    </div>
    <div id="export-reveal" class="large reveal" data-reveal data-v-offset="10px">
        <span class="section-header" id="export-title"></span> <span id="reveal-loading-spinner" style="display: inline-block" class="loading-spinner active"></span>
        <hr>
        <div id="export-content"></div>
        <button class="close-button" data-close aria-label="Close modal" type="button">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div id="export-reveal-map" class="full reveal" data-v-offset="0" data-reveal>
        <span class="section-header" id="export-title-map"></span> <span id="full-reveal-loading-spinner" style="display: inline-block" class="loading-spinner active"></span>
        <span class="section-header"> | <?php esc_html_e( "Mapped Locations", 'disciple-tools-list-exports' ) ?>: <span id="mapped" class="loading-spinner active"></span> | <?php esc_html_e( "Contacts Without Locations", 'disciple-tools-list-exports' ) ?>: <span id="unmapped" class="loading-spinner active"></span> </span>
        <div id="export-content-full">
            <div id="dynamic-styles"></div>
            <div id="map-wrapper">
                <div id='map'></div>
            </div>
        </div>
        <button class="close-button" data-close aria-label="Close modal" type="button">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div id="export-help-text" class="large reveal" data-reveal data-v-offset="10px">
        <span class="section-header"><?php esc_html_e( "List Export Help", 'disciple-tools-list-exports' ) ?></span>
        <hr>
        <div class="grid-x">
            <div class="cell">
                <p><strong>BCC Email List</strong></p>
                <p>Using the current filter, the available emails are grouped by 50 and can be launched into your default email client by group. Many email providers put a limit of 50 on BCC emails. You can open all groups at once using the "Open All" button. If the list is too large, this might alert your email provider. </p>
                <p>The BCC email tool is meant to assist small group emails. Bulk email should be handled through bulk email providers.</p>
            </div>
            <div class="cell">
                <p><strong>Phone Number List</strong></p>
                <p>Using the current filter, this is intended for copy pasting a list of numbers into a messaging app, WhatsApp, Signal, etc. This is a quick way of starting a group conversation.</p>
            </div>
            <div class="cell">
                <p><strong>CSV List</strong></p>
                <p>Using the current filter, this is a simple way to export basic information and use it in other applications.</p>
            </div>
            <div class="cell">
                <p><strong>Map List</strong></p>
                <p>Using the current filter, this creates a basic points map of known locations of listed individuals.</p>
            </div>
        </div>
        <button class="close-button" data-close aria-label="Close modal" type="button">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <script>
        jQuery(document).ready(function($){
            window.mapbox_key = '<?php echo ( class_exists( 'DT_Mapbox_API' ) && DT_Mapbox_API::get_key() ) ? esc_attr( DT_Mapbox_API::get_key() ) : ''; ?>'

            $('.js-list-view').on('click', function(){
                clear_vars()
            })

            /* BCC EXPORT **************************************/
            let email_list_button = $('#bcc-email-list')
            email_list_button.on('click', function(){
                clear_vars()
                show_spinner()
                $('#export-title').html('BCC Email List')
                $('#export-reveal').foundation('open')

                // console.log('pre_export_contact')
                let required = Math.ceil(window.records_list.total / 100)
                let complete = 0
                export_contacts( 0, 'name' )
                $( document ).ajaxComplete(function( event, xhr, settings ) {
                    complete++
                    if ( required === complete ){
                        // console.log('post_export_contact')
                        generate_email_totals()
                        generate_email_links()
                    }
                });
            })
            function generate_email_totals(){

                let bcc_email_content = jQuery('#export-content')
                bcc_email_content.empty()

                bcc_email_content.append(`
                    <div class="grid-x">
                        <div class="cell">
                           <table><tbody id="grouping-table"></tbody></table>
                        </div>

                        <div class="cell">
                            <a onclick="jQuery('#email-list-print').toggle();"><strong>Full List (<span id="list-count-full"></span>)</strong></a>
                            <div class="cell" id="email-list-print" style="display:none;"></div>
                        </div>
                        <div class="cell">
                            <a onclick="jQuery('#contacts-without').toggle();"><strong>No Addresses (<span id="list-count-without"></span>)</strong></a>
                            <div id="contacts-without" style="display:none;"></div>
                        </div>
                        <div class="cell">
                            <a onclick="jQuery('#contacts-with').toggle();"><strong>With Additional Addresses (<span id="list-count-with"></span>)</strong></a>
                            <div id="contacts-with" style="display:none;"></div>
                        </div>
                    </div>
                `)

                let email_totals = []
                let list_count = {
                    with: 0,
                    without: 0,
                    full: 0
                }
                let count = 0
                let group = 0
                let contacts_with = jQuery('#contacts-with')
                let contacts_without = jQuery('#contacts-without')

                $.each(window.export_list, function (i, v) {
                    let has_email = false
                    if (typeof v.contact_email !== "undefined" && v.contact_email !== '') {
                        if (typeof email_totals[group] === "undefined") {
                            email_totals[group] = []
                        }
                        let non_empty_values = v.contact_email.filter(val=>val.value)
                        non_empty_values.forEach(vv=>{
                            email_totals[group].push(window.lodash.escape(vv.value))
                            count++
                            list_count['full']++
                            has_email = true;
                        })
                        if (count > 50) {
                            group++
                            count = 0
                        }
                        if ( non_empty_values.length > 1 )
                        contacts_with.append(`<a href="${window.lodash.escape(window.wpApiShare.site_url)}/contacts/${window.lodash.escape(v.ID)}">${window.lodash.escape(v.post_title)}</a><br>`)
                        list_count['with']++
                    }
                    if ( !has_email ){
                        contacts_without.append(`<a href="${window.lodash.escape(window.wpApiShare.site_url)}/contacts/${window.lodash.escape(v.ID)}">${window.lodash.escape(v.post_title)}</a><br>`)
                        list_count['without']++
                    }
                })

                let list_print = jQuery('#email-list-print')
                $.each(email_totals, function (index, values) {
                    list_print.append(window.lodash.escape(values.join(', ')))
                })

                // console.log(list_count)
                jQuery('#list-count-with').html(list_count['with'])
                jQuery('#list-count-without').html(list_count['without'])
                jQuery('#list-count-full').html(list_count['full'])

                hide_spinner()
            }
            function generate_email_links() {
                let email_links = []
                let group = 0
                $.each(window.export_list, function (i, v) {
                    if (typeof v.contact_email !== "undefined" && v.contact_email !== '') {
                        if (typeof email_links[group] === "undefined") {
                            email_links[group] = []
                        }
                        $.each(v.contact_email, function (ii, vv) {
                            if ( vv.value ){
                                email_links[group].push( vv.value )
                            }
                        })
                        if (email_links[group].length > 50) {
                            group++
                        }
                    }
                })

                // loop 50 each
                let grouping_table = $('#grouping-table')
                let email_strings = []
                $.each(email_links, function (index, values) {
                    index++
                    email_strings = []
                    email_strings = window.lodash.escape(values.join(', '))
                    email_strings.replace(/,/g, ', ')

                    grouping_table.append(`
                    <tr><td style="vertical-align:top; width:50%;"><a href="mailto:?subject=group${index}&bcc=${email_strings}" id="group-link-${index}" class="button expanded export-link-button">Open Email for Group ${index}</a></td>
                    <td><a onclick="jQuery('#group-addresses-${index}').toggle()">show group addresses</a> <p style="display:none;overflow-wrap: break-word;" id="group-addresses-${index}">${email_strings.replace(/,/g, ', ')}</p></td></tr>
                    `)

                })
                grouping_table.append(`
                    <tr><td style="vertical-align:top; text-align:center; width:50%;"><a class="button expanded export-link-button" id="open_all">Open All</a></td><td></td></tr>
                `)

                $('.export-link-button').on('click',function(){
                    $(this).addClass('warning');
                })
                $('#open_all').on('click', function(){
                    $('.export-link-button').each(function(i,v){
                        document.getElementById(v.id).click()
                    })
                })
                hide_spinner()
            }

            /* PHONE EXPORT **************************************/
            let phone_list = $('#phone-list')
            phone_list.on('click', function(){

                clear_vars()
                show_spinner()
                jQuery('#export-title').html('Phone List')
                $('#export-reveal').foundation('open')

                // console.log('pre_export_contact')
                let required = Math.ceil(window.records_list.total / 100)
                let complete = 0
                export_contacts( 0, 'name' )
                $( document ).ajaxComplete(function( event, xhr, settings ) {
                    complete++
                    if ( required === complete ){
                        // console.log('post_export_contact')
                        phone_content()
                    }
                });

                function phone_content() {
                    let phone_content_container = jQuery('#export-content')
                    phone_content_container.empty()

                    phone_content_container.append(`
                        <div class="grid-x">
                            <a onclick="jQuery('#email-list-print').toggle();"><strong>Full List (<span id="list-count-full"></span>)</strong></a>
                            <div class="cell" id="email-list-print"></div>
                        </div>
                        <hr>
                        <div class="grid-x">
                            <div class="cell">
                                <a onclick="jQuery('#contacts-without').toggle();"><strong>Has No Phone Number (<span id="list-count-without"></span>)</strong></a>
                                <div id="contacts-without" style="display:none;"></div>
                            </div>
                            <div class="cell">
                                <a onclick="jQuery('#contacts-with').toggle();"><strong>Has Additional Phone Numbers (<span id="list-count-with"></span>)</strong></a>
                                <div id="contacts-with" style="display:none;"></div>
                            </div>
                        </div>
                    `)

                    let phone_list = []
                    let list_count = {
                        with: 0,
                        without: 0,
                        full: 0
                    }
                    let group = 0
                    let contacts_with = jQuery('#contacts-with')
                    let contacts_without = jQuery('#contacts-without')

                    $.each(window.export_list, function (i, v) {
                        let has_phone = false
                        if (typeof v.contact_phone !== 'undefined' && v.contact_phone !== '') {
                            if (typeof phone_list[group]==="undefined") {
                                phone_list[group] = []
                            }
                            let non_empty_values = v.contact_phone.filter(val=>val.value)
                            non_empty_values.forEach(vv=>{
                                phone_list[group].push(vv.value)
                                list_count['full']++
                                has_phone = true
                            })
                            if ( non_empty_values.length > 1 ){
                                contacts_with.append(`<a  href="${window.lodash.escape(window.wpApiShare.site_url)}/contacts/${window.lodash.escape(v.ID)}">${window.lodash.escape(v.post_title)}</a><br>`)
                                list_count['with']++
                            }
                            if (phone_list.length > 50) {
                                group++
                            }
                        }
                        if ( !has_phone ) {
                            contacts_without.append(`<a  href="${window.lodash.escape(window.wpApiShare.site_url)}/contacts/${window.lodash.escape(v.ID)}">${window.lodash.escape(v.post_title)}</a><br>`)
                            list_count['without']++
                        }
                    })

                    let list_print = jQuery('#email-list-print')
                    let all_numbers = []
                    $.each(phone_list, function (index, values) {
                        all_numbers = all_numbers.concat(values)
                    })
                    list_print.append(window.lodash.escape(all_numbers.join(', ')))

                    // console.log(list_count)
                    jQuery('#list-count-with').html(list_count['with'])
                    jQuery('#list-count-without').html(list_count['without'])
                    jQuery('#list-count-full').html(list_count['full'])

                    hide_spinner()
                }

            })

            /* CSV LIST CUSTOM FILTERS EXPORT ***********************/
            let csv_list = $('#csv-list');
            csv_list.on('click', function() {
                if ( $('#list-loading-spinner').hasClass('active') ) {
                    alert('Contact list is still loading, please try again in a few seconds.');
                } else {
                    clear_vars();
                    show_spinner();
                    $('#export-title').html('CSV List');
                    $('#export-reveal').foundation('open');
                    csv_export();
                }
            });

            function csv_export() {
                let columns = $('#records-table tr')[0].innerText.split('\t');
                    columns = columns.splice( 1, columns.length);

                let rows = $('#table-content > tr');

                window.csv_export = [];

                rows.each( function( i, v ) {
                    let clean_row = v.outerText.split('\t');
                    $.each( clean_row, function(i,v) {
                        clean_row[i] = clean_row[i].slice(0, clean_row[i].length - 1); //remove trailing comma
                        v = v.replace( /^\n/g, '' );
                        v = v.replace( /\n$/g, '' );
                        v = v.replace( /\n/g, '; ' );
                        clean_row[i] = v;
                    })
                    clean_row.shift(); //removes first element which is a number
                    $.each( clean_row, function(i,v) {
                        clean_row[i] = v.replace( /^\s+/g, '').replace( /\s+$/g, '' ); //trim cell contents
                    });
                    window.csv_export[i] = clean_row;
                });


                window.csv_export.unshift(columns);

                    $('#export-content').append(`
                        <div class="grid-x">
                                <div class="cell" style="margin-bottom:15px;">The following fields will be exported:</div>
                                <div class="cell">`);

                    columns.forEach( function( i ) {
                        $('#export-content').append(`<code>` + i + `</code> `);
                    });

                    $('#export-content').append(`</div>
                                <div style="margin-top:20px;">You can select which fields are exported by changing the list fields</div>
                                <hr>
                                <div class="cell"><button class="button" type="button" id="download_csv_file">Download CSV File</button></div>
                                <div class="cell">
                                   <a onclick="jQuery('#csv-output').toggle()">show list</a><br><br>
                                   <code id="csv-output" style="display:none"></code>
                                </div>
                                <div class="cell"><br></div>
                            </div>
                        `);

                    let csv_output = $('#csv-output');
                    $.each( window.csv_export, function( i, v ) {
                        csv_output.append( document.createTextNode( $.map( v, function(e) {
                            return '"' + e + '"';
                        }).join(',') ) );
                        csv_output.append(`<br>`);
                    })

                    $('#download_csv_file').on('click', function(){
                        DownloadJSON2CSV(window.csv_export);
                    })

                hide_spinner();
            }

            function DownloadJSON2CSV( objArray ) {
                var array = typeof objArray != 'object' ? JSON.parse(objArray) : objArray;

                var str = '';

                for (var i = 0; i < array.length; i++) {
                    var line = '';

                    for (var index in array[i]) {
                        line += '"' + array[i][index] + '",';
                    }

                    //remove last comma
                    line = line.slice(0,line.length-1);

                    str += line + '\r\n';
                }

                let export_link = document.createElement('a');

                let d = new Date();

                let month = d.getMonth() + 1;
                month = ( month < 10 ? '0' : '' ) + month;

                let day = d.getDate();
                day = ( day < 10 ? '0' : '' ) + day;

                let date = d.getFullYear() + '_' + month + '_' + day;

                let export_filename = date + '_list_export.csv';
                export_link.download = export_filename;
                export_link.href = "data:text/csv;charset=utf-8," + escape(str);
                export_link.click();
                export_link.remove();
            }

            /* MAP LIST EXPORT **************************************/
            if ( window.mapbox_key ) {
                let map_content = jQuery('#dynamic-styles')
                map_content.append(`
                            <style>
                                #map-wrapper {
                                    height: ${window.innerHeight - 100}px !important;
                                    position:relative;
                                }
                                #map {
                                    height: ${window.innerHeight - 100}px !important;
                                }

                            </style>
                    `)
                let map_list = $('#map-list')
                map_list.on('click', function(){
                    clear_vars()
                    show_spinner()
                    $('#export-title-map').html('Map of List')
                    $('#export-reveal-map').foundation('open')

                    // console.log('pre_export_contact')
                    let required = Math.ceil(window.records_list.total / 100)
                    let complete = 0
                    export_contacts( 0, 'name' )
                    $( document ).ajaxComplete(function( event, xhr, settings ) {
                        complete++
                        if ( required === complete ){
                            // console.log('post_export_contact')
                            map_content()
                        }
                    });

                    function map_content(){
                        mapboxgl.accessToken = window.mapbox_key;
                        var map = new mapboxgl.Map({
                            container: 'map',
                            style: 'mapbox://styles/mapbox/light-v10',
                            center: [-30, 20],
                            minZoom: 1,
                            zoom: 2
                        });

                        // disable map rotation using right click + drag
                        map.dragRotate.disable();
                        map.touchZoomRotate.disableRotation();

                        // load sources
                        map.on('load', function () {

                            let features = []
                            let mapped = 0
                            let unmapped = 0
                            $.each(window.export_list, function(i,v){
                                if ( typeof v.location_grid_meta !== "undefined") {
                                    features.push({
                                        'type': 'Feature',
                                        'geometry': {
                                            'type': 'Point',
                                            'coordinates': [v.location_grid_meta[0].lng, v.location_grid_meta[0].lat]
                                        },
                                        'properties': {
                                            'title': v.post_title,
                                            'label': v.location_grid_meta[0].label
                                        }
                                    })
                                    mapped++
                                }
                                else {
                                    unmapped++
                                }
                            })

                            $('#mapped').html('(' + mapped + ')')
                            $('#unmapped').html('(' + unmapped + ')')

                            let geojson = {
                                'type': 'FeatureCollection',
                                'features': features
                            }

                            map.addSource('pointsSource', {
                                'type': 'geojson',
                                'data': geojson
                            });
                            map.addLayer({
                                id: 'points',
                                type: 'circle',
                                source: 'pointsSource',
                                paint: {
                                    'circle-radius': {
                                        'base': 6,
                                        'stops': [
                                            [1, 6],
                                            [3, 6],
                                            [4, 6],
                                            [5, 8],
                                            [6, 10],
                                            [7, 12],
                                            [8, 14],
                                        ]
                                    },
                                    'circle-color': '#2CACE2'
                                }
                            });

                            if ( window.records_list.total < 5 ) {
                                $.each(window.export_list, function(i,v){
                                    if ( typeof v.location_grid_meta !== 'undefined') {
                                        new mapboxgl.Popup()
                                            .setLngLat([v.location_grid_meta[0].lng, v.location_grid_meta[0].lat])
                                            .setHTML(v.post_title + '<br>' + v.location_grid_meta[0].label)
                                            .addTo(map);
                                    }
                                })
                            }

                            map.on('mouseenter', 'points', function(e) {
                                map.getCanvas().style.cursor = 'pointer';
                                var coordinates = e.features[0].geometry.coordinates.slice();
                                var description = e.features[0].properties.title + '<br>' + e.features[0].properties.label;

                                while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
                                    coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
                                }

                                new mapboxgl.Popup()
                                    .setLngLat(coordinates)
                                    .setHTML(description)
                                    .addTo(map);
                            });

                            map.on('mouseleave', 'points', function() {
                                map.getCanvas().style.cursor = '';
                            });

                            var bounds = new mapboxgl.LngLatBounds();
                            geojson.features.forEach(function(feature) {
                                bounds.extend(feature.geometry.coordinates);
                            });
                            map.fitBounds(bounds);

                            hide_spinner()
                        })
                    }
                })
            }

            /* EXPORT UTILITIES */
            function clear_vars(){
                window.export_list = []
                window.current_filter = ''
                document.cookie = ''
                $('#export-content').empty()
                $('#export-title').empty()
                $('#export-title-full').empty()
                $('#map').empty()
                $('#mapped').empty()
                $('#unmapped').empty()
            }
            function show_spinner(){
                $('.loading-spinner').addClass('active')
            }
            function hide_spinner(){
                $('.loading-spinner').removeClass('active')
            }
            function export_contacts( offset, sort ) {

                let items = []
                let getContactsPromise = null
                let filter = window.SHAREDFUNCTIONS.get_json_cookie("last_view")

                let data = filter.query || {}

                let fields = [];
                fields = [ 'location_grid_meta', 'contact_phone', 'contact_email' ]
                data.fields_to_return = fields

                let required = 0
                let complete = 0
                window.export_list = []

                data.offset = 0
                let increment = 0
                while( window.records_list.total > increment ) {
                    required++

                    getContactsPromise = $.ajax({
                        url: window.wpApiShare.root + "dt-posts/v2/" + window.wpApiShare.post_type + "/",
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', window.wpApiShare.nonce);
                        },
                        data: data,
                    })
                    getContactsPromise.done((data)=>{
                        if (offset){
                            items = window.lodash.unionBy(items, data.posts || [], "ID")
                        } else  {
                            items = data.posts || []
                        }
                        if (typeof window.export_list === "undefined" ) {
                            window.export_list = items
                        } else {
                            let arr = $.merge( [], window.export_list )
                            window.export_list = $.merge( arr, items );
                        }

                        complete++
                        if ( required === complete ) {
                            // console.log('export')
                            return true;
                        }
                    }).catch(err => {
                        if ( window.lodash.get( err, "statusText" ) !== "abort" ) {
                            console.error(err)
                            complete++
                            if ( required === complete ) {
                                console.log('export_contact_complete_with_fail')
                                return true;
                            }
                        }
                    })

                    data.offset = data.offset + 100
                    increment = increment + 100
                }
            }
        })
    </script>
        <?php
}
