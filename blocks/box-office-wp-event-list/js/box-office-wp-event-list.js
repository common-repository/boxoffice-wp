(() => {
    const el = window.wp.element.createElement;
    const { registerBlockType } = window.wp.blocks;
    const { InspectorControls } = window.wp.blockEditor;
    const { PanelBody, TextControl, SelectControl, ToggleControl } = window.wp.components;
    const { useBlockProps } = window.wp.blockEditor;
    const apiFetch = window.wp.apiFetch;
    
    registerBlockType('box-office-wp/box-office-wp-event-list', {
        title: 'BoxOffice WP - Event List',
        icon: 'universal-access-alt',
        category: 'box-office-wp-plugin',
        attributes: {
            content: {
                type: 'array',
                source: 'string',
                selector: 'div',
            },
            listLimit: {
                type: 'number',
                default: 999,
            },
            shortcode: {
                type: 'string',
                default: '[box_office_wp_event_list limit=\"999\"]',
            },
            eventlist: {
                type: 'array',
                default: [0,0,0,0,0,0,0,0,0],
            },
            useEventList: {
                type: 'boolean',
                default: false,
            },
            maxDescriptionLength: {
                type: 'number',
                default: 99,
            }
        },
        edit: myEdit,
        save: mySave
    });

    //REST API fetch for event list
    async function eventListApiFetch() {
        var fetch = await apiFetch( { path: '/box-office-wp/v1/event-list', parse: true } );
        fetch = JSON.parse(fetch);
        return fetch;
    }

    var eventListArray = [];
    var events = eventListApiFetch();
    events.then(function(result) {
        result.forEach(event => {
            eventListArray.push({ label: event['name'], value: event['eventID'] });
        });
    });
    eventListArray.push({ label: 'Select event', value: '0' });
    
    function myEdit( props )
    {
        function onChangeListLimit( value ) {
            props.setAttributes( { listLimit: Number(value) } );
            onChangeShortcode(value, props.attributes.eventlist, props.attributes.useEventList, props.attributes.maxDescriptionLength);
        }

        function onChangeMaxDescriptionLength( value ) {
            props.setAttributes( { maxDescriptionLength: Number(value) } );
            onChangeShortcode(props.attributes.listLimit, props.attributes.eventlist, props.attributes.useEventList, value);
        }
    
        function onChangeShortcode( limit, eventList, useEventList, maxDescriptionLength ) {
            eventList = eventList.join(',');
            shortcodeText = '[box_office_wp_event_list limit=\"' + limit + '\" max_description_words_to_display=\"' + maxDescriptionLength + '\"';
            if (useEventList) {
                shortcodeText = shortcodeText + ' event_id_list=\"' + eventList + '\"';
            }
            shortcodeText = shortcodeText + ']';
            props.setAttributes( { shortcode: shortcodeText } );
            console.log(shortcodeText);
        }

        function onChangeEventList( value, eventNumber ) {
            const eventList = props.attributes.eventlist;
            const updateEventList = eventList.map((item, i) => {
                if (i === eventNumber) {
                    return value;
                } else {
                    return item;
                }
            });
            props.setAttributes( { eventlist: updateEventList } );
            onChangeShortcode(props.attributes.listLimit, updateEventList, props.attributes.useEventList, props.attributes.maxDescriptionLength);
        } 
    
        return el(
            'div',
            props,
            el(
                InspectorControls,
                { key: 'box-office-wp-list-settings' },
                el( PanelBody, {
                    title: 'Event List Settings',
                    className: 'box-office-wp-event-list-settings',
                    initialOpen: false,
                },
                el( TextControl, {
                    label: 'Number of Events to Display',
                    type: 'number',
                    value: props.attributes.listLimit,
                    onChange: onChangeListLimit,
                }),
                el( TextControl, {
                    label: 'Max description length (words)',
                    type: 'number',
                    value: props.attributes.maxDescriptionLength,
                    onChange: onChangeMaxDescriptionLength,
                }),
            )),
            el(
                InspectorControls,
                { key: 'box-office-wp-event-list-selection' },
                el( PanelBody, {
                    title: 'Event selection',
                    className: 'box-office-wp-event-list-selection',
                    initialOpen: false,
                },
                    el( ToggleControl, {
                        label: 'Use Event List',
                        checked: props.attributes.useEventList,
                        onChange: ( value ) => {
                            props.setAttributes( { useEventList: value } );
                            onChangeShortcode(props.attributes.listLimit, props.attributes.eventlist, value);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 1 + ' selection',
                        value: props.attributes.eventlist[0],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 0);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 2 + ' selection',
                        value: props.attributes.eventlist[1],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 1);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 3 + ' selection',
                        value: props.attributes.eventlist[2],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 2);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 4 + ' selection',
                        value: props.attributes.eventlist[3],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 3);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 5 + ' selection',
                        value: props.attributes.eventlist[4],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 4);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 6 + ' selection',
                        value: props.attributes.eventlist[5],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 5);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 7 + ' selection',
                        value: props.attributes.eventlist[6],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 6);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 8 + ' selection',
                        value: props.attributes.eventlist[7],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 7);
                        }
                    }),
                    props.attributes.useEventList &&
                    el( SelectControl, {
                        label: 'Event ' + 9 + ' selection',
                        value: props.attributes.eventlist[8],
                        options: eventListArray,
                        onChange: ( value ) => {
                            onChangeEventList(value, 8);
                        }
                    }),
                ),
            ),
            'Event List Will Appear Here'
        );
    }
    
    function mySave(props)
    {
        var blockProps = useBlockProps.save();

        return el(
            'div',
            blockProps,
            props.attributes.shortcode
        );
    }
    })();


