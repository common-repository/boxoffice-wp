<?php

/**
 * REST API class.
 * 
 * Concerned with all logic relating to creation of REST routes and endpoints.
 */

 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Rest_Api{


    /**
     * Namespace for REST routes
     */
    public static string $namespace = 'box-office-wp/v1';

    /**
     * REST routes
     */
    public static string $event_list = '/event-list'; 


    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Register REST route for event list
     */
    public function register_rest_routes()
    {
        register_rest_route(self::$namespace, self::$event_list, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'load_event_list'],
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Event list - load event list from the API
     */
    public function load_event_list()
    {
        if(class_exists('Box_Office_WP_Spektrix_Api'))
        {
            $api = new Box_Office_WP_Spektrix_Api();
    
            $event_list = $api->get_event_list(Box_Office_WP_Constants::$default_event_list_limit);
    
            foreach($event_list as $event){
                $eventList[$event->eventID] = $event->name;
            }
        }
        $event_list = wp_json_encode($event_list);
        $response = new WP_REST_Response($event_list);

        return $response;
    }
}