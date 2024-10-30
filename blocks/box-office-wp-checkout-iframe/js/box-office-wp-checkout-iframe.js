(() => {
  const el = window.wp.element.createElement;
  const { registerBlockType } = window.wp.blocks;

  registerBlockType('box-office-wp/box-office-wp-checkout-iframe', {
    title: 'BoxOffice WP - Checkout iFrame',
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
		'Checkout iFrame Will Appear Here'
	);
  }
  
  function mySave(props)
  {
	return "[box_office_wp_checkout_iframe]";
  }
})();