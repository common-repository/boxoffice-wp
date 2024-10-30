(() => {
  const el = window.wp.element.createElement;
  const { registerBlockType } = window.wp.blocks;

  registerBlockType('box-office-wp/box-office-wp-event-details-iframe', {
    title: 'BoxOffice WP - Event Details iFrame',
    icon: 'universal-access-alt',
    category: 'box-office-wp-plugin',
    attributes: {
      content: {
        type: 'array',
        source: 'string',
        selector: 'div',
      },
    },
    edit: myEdit,
    save: mySave
  });
  
  function myEdit(props)
  {
	return el(
		'div',
		props,
		'Event Details iFrame Will Appear Here. Upgrade to Pro to enjoy full configuration of the Event List layout, custom filters, and more!'
	);
  }
  
  function mySave(props)
  {
	return "[box_office_wp_event_details_iframe]";
  }
})();