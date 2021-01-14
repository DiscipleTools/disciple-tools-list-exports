<?php

add_action( 'dt_post_list_filters_sidebar', 'dt_list_exports_filters', 10, 1 );
function dt_list_exports_filters( $post_type ) {
    if ( 'contacts' === $post_type ) :
        ?>
    <div class="bordered-box collapsed">
        <div class="section-header"><?php esc_html_e( 'List Exports', 'disciple_tools' )?>&nbsp;
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
            <a id="bcc-email-list">bcc email list</a><br>
            <a id="phone-list">phone number list</a><br>
            <a id="csv-list">csv list</a><br>
            <?php if ( class_exists( 'DT_Mapbox_API' ) && DT_Mapbox_API::get_key() ) : ?>
            <a id="map-list">map list</a><br>
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
        <span class="section-header"> | Mapped Locations: <span id="mapped" class="loading-spinner active"></span> | Contacts Without Locations: <span id="unmapped" class="loading-spinner active"></span> </span>
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
        <span class="section-header">List Export Help</span>
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
                    if (typeof v.contact_email !== 'undefined' && v.contact_email !== '') {
                        if (typeof email_totals[group] === "undefined") {
                            email_totals[group] = ''
                        }
                        $.each(v.contact_email, function (ii, vv) {
                            email_totals[group] += vv.value + ', '
                            count++
                            list_count['full']++
                        })
                        if (typeof v.contact_email[1] !== "undefined") {
                            contacts_with.append(`<a href="/contacts/${v.ID}">${_.escape(v.post_title)}</a><br>`)
                            list_count['with']++
                        }
                        if (count > 50) {
                            group++
                            count = 0
                        }
                    } else {
                        contacts_without.append(`<a href="/contacts/${v.ID}">${_.escape(v.post_title)}</a><br>`)
                        list_count['without']++
                    }
                })

                let list_print = jQuery('#email-list-print')
                $.each(email_totals, function (index, string) {
                    list_print.append(_.escape(string))
                    index++
                })

                // console.log(list_count)
                jQuery('#list-count-with').html(list_count['with'])
                jQuery('#list-count-without').html(list_count['without'])
                jQuery('#list-count-full').html(list_count['full'])

                hide_spinner()
            }
            function generate_email_links() {
                let email_links = []
                let count = 0
                let group = 0
                $.each(window.export_list, function (i, v) {
                    if (typeof v.contact_email !== 'undefined' && v.contact_email !== '') {
                        if (typeof email_links[group] === "undefined") {
                            email_links[group] = ''
                        }
                        $.each(v.contact_email, function (ii, vv) {
                            email_links[group] += vv.value + ','
                            count++
                        })
                        if (count > 50) {
                            group++
                            count = 0
                        }
                    }
                })

                // loop 50 each
                let grouping_table = $('#grouping-table')
                let email_strings = []
                $.each(email_links, function (index, string) {
                    index++

                    email_strings = []
                    email_strings = string
                    email_strings.replace(/,/g, ', ')

                    grouping_table.append(`
                    <tr><td style="vertical-align:top; width:50%;"><a href="mailto:?subject=group${index}&bcc=${string}" id="group-link-${index}" class="button expanded export-link-button">Open Email for Group ${index}</a></td><td><a onclick="jQuery('#group-addresses-${index}').toggle()">show group addresses</a> <span style="display:none;overflow-wrap: break-word;" id="group-addresses-${index}">${string.replace(/,/g, ', ')}</span></td></tr>
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
                    let count = 0
                    let group = 0
                    let contacts_with = jQuery('#contacts-with')
                    let contacts_without = jQuery('#contacts-without')

                    $.each(window.export_list, function (i, v) {
                        if (typeof v.contact_phone !== 'undefined' && v.contact_phone !== '') {
                            if (typeof phone_list[group] === "undefined") {
                                phone_list[group] = ''
                            }
                            $.each(v.contact_phone, function (ii, vv) {
                                phone_list[group] += vv.value + ', '
                                count++
                                list_count['full']++
                            })
                            if (typeof v.contact_phone[1] !== "undefined") {
                                contacts_with.append(`<a href="/contacts/${v.ID}">${_.escape(v.post_title)}</a><br>`)
                                list_count['with']++
                            }
                            if (count > 50) {
                                group++
                                count = 0
                            }
                        } else {
                            contacts_without.append(`<a href="/contacts/${v.ID}">${_.escape(v.post_title)}</a><br>`)
                            list_count['without']++
                        }
                    })

                    let list_print = jQuery('#email-list-print')
                    $.each(phone_list, function (index, string) {
                        list_print.append(_.escape(string))
                        index++
                    })

                    // console.log(list_count)
                    jQuery('#list-count-with').html(list_count['with'])
                    jQuery('#list-count-without').html(list_count['without'])
                    jQuery('#list-count-full').html(list_count['full'])

                    hide_spinner()
                }

            })

            /* CSV LIST EXPORT **************************************/
            let csv_list = $('#csv-list')
            csv_list.on('click', function(){
                clear_vars()
                show_spinner()
                $('#export-title').html('CSV List')
                $('#export-reveal').foundation('open')

                // console.log('pre_export_contact')
                let required = Math.ceil(window.records_list.total / 100)
                let complete = 0
                export_contacts( 0, 'name' )
                $( document ).ajaxComplete(function( event, xhr, settings ) {
                    complete++
                    if ( required === complete ){
                        // console.log('post_export_contact')
                        csv_export()
                    }
                });

                function csv_export() {
                    window.csv_export = []

                    $.each(window.export_list, function (i, v) {

                        window.csv_export[i] = {}
                        window.csv_export[i]['title'] = v.post_title

                        if (typeof v.contact_phone !== 'undefined' && v.contact_phone !== '') {
                            window.csv_export[i]['phone'] = v.contact_phone[0].value
                        } else {
                            window.csv_export[i]['phone'] = ''
                        }
                        if (typeof v.contact_email !== 'undefined' && v.contact_email !== '') {
                            window.csv_export[i]['email'] = v.contact_email[0].value
                        } else {
                            window.csv_export[i]['email'] = ''
                        }
                    })

                    let head_row = {title:"Title", phone:"Phone", email:"Email" }
                    window.csv_export.unshift(head_row)

                    $('#export-content').append(`
                        <div class="grid-x">
                                <div class="cell"><button class="button" type="button" id="download_csv_file">Download CSV File</button></div>
                                <div class="cell">
                                   <a onclick="jQuery('#csv-output').toggle()">show list</a><br><br>
                                   <code id="csv-output" style="display:none"></code>
                                </div>
                                <div class="cell"><br></div>
                            </div>
                        `)

                    let csv_output = $('#csv-output')
                    $.each(window.csv_export, function(i,v){
                        csv_output.append( document.createTextNode($.map(v, function(e){
                            return e;
                        }).join(',')))
                        csv_output.append(`<br>`)
                    })

                    $('#download_csv_file').on('click', function(){
                        DownloadJSON2CSV(window.csv_export);
                    })

                    hide_spinner()
                }

                function DownloadJSON2CSV(objArray)
                {
                    var array = typeof objArray != 'object' ? JSON.parse(objArray) : objArray;

                    var str = '';

                    for (var i = 0; i < array.length; i++) {
                        var line = '';

                        for (var index in array[i]) {
                            line += '"' + array[i][index] + '",';
                        }

                        line.slice(0,line.Length-1);

                        str += line + '\r\n';
                    }
                    window.open( "data:text/csv;charset=utf-8," + escape(str))
                }
            })

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
                                if ( typeof v.location_grid_meta !== 'undefined') {
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

                            if ( window.records_list.total < 100 ) {
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
                let cachedFilter = window.SHAREDFUNCTIONS.get_json_cookie("last_view")
                let closedSwitch = $(".show-closed-switch");
                let showClosedCheckbox = $('#show_closed')
                let currentFilter = window.current_filter
                let customFilters = []
                let checked = $(".js-list-view:checked")
                let currentView = checked.val()
                let filterId = checked.data("id") || currentView
                let query = {}
                let filter = {
                    type:"default",
                    ID:currentView,
                    query:{},
                    labels:[{ id:"all", name:window.list_settings.translations.filter_all, field: "assigned"}]
                }
                customFilters.push(JSON.parse(JSON.stringify(currentFilter)))
                if ( currentView === "custom_filter"){
                    let filterId = checked.data("id")
                    if ( _.find(customFilters, {ID:filterId}) ){
                        filter = _.find(customFilters, {ID:filterId})
                    }
                    filter.type = currentView
                    query = filter.query
                } else if ( currentView ) {
                    filter = _.find(window.list_settings.filters.filters, {ID:filterId}) || _.find(window.list_settings.filters.filters, {ID:filterId.toString()}) || filter
                    if ( filter ){
                        filter.type = 'default'
                        filter.labels =  filter.labels || [{ id:filterId, name:filter.name}]
                        query = filter.query
                    }
                }

                if (currentView === "custom_filter" || currentView === "saved-filters" ){
                    closedSwitch.show()
                } else {
                    closedSwitch.hide()
                }

                filter.query = query
                let sortField = _.get(currentFilter, "query.sort", "overall_status")
                filter.query.sort = _.get(currentFilter, "query.sort", "overall_status");
                if ( _.get( cachedFilter, "query.sort") ){
                    filter.query.sort = cachedFilter.query.sort;
                    sortField = _.get(cachedFilter, "query.sort", "overall_status")
                }
                currentFilter = JSON.parse(JSON.stringify(filter))

                let data = currentFilter.query

                if ( offset ){
                    data.offset = offset
                }
                if ( sort ){
                    data.sort = sort
                    data.offset = 0
                } else if (!data.sort) {
                    data.sort = 'name';
                    if ( window.wpApiShare.post_type === "contacts" ){
                        data.sort = 'overall_status'
                    } else if ( window.wpApiShare.post_type === "groups" ){
                        data.sort = "group_type";
                    }
                }

                currentFilter.query = data
                document.cookie = `last_view=${JSON.stringify(currentFilter)}`

                let showClosed = showClosedCheckbox.prop("checked")
                if ( !showClosed && ( currentView === 'custom_filter' || currentView === 'saved-filters' ) ){
                    if ( window.wpApiShare.post_type === "contacts" ){
                        if ( !data.overall_status ){
                            data.overall_status = [];
                        }
                        if ( !data.overall_status.includes("-closed") ){
                            data.overall_status.push( "-closed" )
                        }
                    }
                }
                //abort previous promise if it is not finished.
                if (getContactsPromise && _.get(getContactsPromise, "readyState") !== 4){
                    getContactsPromise.abort()
                }
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
                            items = _.unionBy(items, data.posts || [], "ID")
                        } else  {
                            items = data.posts || []
                        }
                        if (typeof window.export_list === 'undefined' ) {
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
                        if ( _.get( err, "statusText" ) !== "abort" ) {
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
    endif;
}
